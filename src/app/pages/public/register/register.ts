import { Component, inject, signal, OnInit } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { ApiService, API_ENDPOINTS } from '../../../core/services/api.service';
import { Country, CountryListResponse } from '../../../models/country.model';

interface RegisterResponse {
  success: boolean;
  message: string;
  company_id?: string;
  paypal_approval_url?: string;
}

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [FormsModule, CommonModule, RouterLink],
  templateUrl: './register.html',
  styleUrl: './register.scss',
})
export class Register implements OnInit {
  private apiService = inject(ApiService);
  private router = inject(Router);

  currentStep = signal(1);
  isLoading = signal(false);
  showPassword = signal(false);
  showConfirmPassword = signal(false);
  errorMessage = signal('');
  passwordStrength = signal<{level: number, text: string, color: string}>({level: 0, text: '', color: ''});

  // Countries list
  countries = signal<Country[]>([]);
  selectedCountry = signal<Country | null>(null);
  loadingCountries = signal(true);

  // Step 1: Personal Info
  personalInfo = signal({
    firstName: '',
    lastName: '',
    email: '',
    password: '',
    confirmPassword: '',
    phone: '',
  });

  // Step 2: Company Info
  companyInfo = signal({
    companyName: '',
    vatNumber: '',
    sdiCode: '', // SDI Code for Italian electronic invoicing
    companySize: '',
    industry: '',
    website: '',
    address: '',
    city: '',
    province: '',
    postalCode: '',
    countryCode: 'IT', // ISO country code
  });

  // Step 3: Subscription Plan
  selectedPlan = signal('ultimate');

  plans = [
    {
      id: 'ultimate',
      name: 'Ultimate',
      price: 1500,
      period: 'year',
      features: [
        'Unlimited active jobs',
        'AI-powered candidate matching',
        'Advanced analytics & insights',
        '24/7 premium support',
        'Unlimited users',
        'Full API access',
        'Custom integrations',
        'White-label branding',
        'Dedicated account manager',
        'Priority feature requests',
      ],
      highlighted: true,
    },
  ];

  companySizeOptions = [
    '1-10 employees',
    '11-50 employees',
    '51-200 employees',
    '201-500 employees',
    '500+ employees',
  ];

  ngOnInit() {
    this.loadCountries();
  }

  loadCountries() {
    this.loadingCountries.set(true);
    this.apiService.get<CountryListResponse>(API_ENDPOINTS.PUBLIC_COUNTRIES, undefined, undefined, false)
      .subscribe({
        next: (response) => {
          if (response.success) {
            this.countries.set(response.countries);
            // Set Italy as default
            const italy = response.countries.find(c => c.code === 'IT');
            if (italy) {
              this.selectedCountry.set(italy);
            }
          }
          this.loadingCountries.set(false);
        },
        error: (err) => {
          console.error('Failed to load countries:', err);
          this.loadingCountries.set(false);
        }
      });
  }

  onCountryChange(countryCode: string) {
    const country = this.countries().find(c => c.code === countryCode);
    if (country) {
      this.selectedCountry.set(country);
      this.companyInfo.update(info => ({...info, countryCode}));

      // Clear SDI code if not Italy
      if (countryCode !== 'IT') {
        this.companyInfo.update(info => ({...info, sdiCode: ''}));
      }
    }
  }

  getVatLabel(): string {
    const country = this.selectedCountry();
    return country?.vat_label || 'VAT Number';
  }

  isItalySelected(): boolean {
    return this.companyInfo().countryCode === 'IT';
  }

  async nextStep() {
    if (!this.validateCurrentStep()) {
      return;
    }

    // If moving from step 1, check if email already exists
    if (this.currentStep() === 1) {
      this.isLoading.set(true);

      try {
        const response = await this.apiService.post<{success: boolean, exists: boolean, message: string}>(
          API_ENDPOINTS.CHECK_EMAIL,
          { email: this.personalInfo().email },
          undefined,
          false
        ).toPromise();

        console.log('Email check response:', response);
        this.isLoading.set(false);

        if (!response) {
          this.errorMessage.set('Unable to verify email. Please try again.');
          return;
        }

        if (response.exists) {
          this.errorMessage.set('This email is already registered. Please use a different email or sign in.');
          return;
        }
      } catch (error: any) {
        this.isLoading.set(false);
        console.error('Email check error:', error);

        // Check if it's a server error with a message
        if (error?.error?.message) {
          this.errorMessage.set(error.error.message);
        } else {
          this.errorMessage.set('Unable to verify email. Please try again.');
        }
        return;
      }
    }

    // If moving from step 2, check if VAT number already exists
    if (this.currentStep() === 2) {
      this.isLoading.set(true);

      try {
        const response = await this.apiService.post<{success: boolean, exists: boolean, message: string, company_name?: string}>(
          API_ENDPOINTS.CHECK_VAT,
          { vat_number: this.companyInfo().vatNumber },
          undefined,
          false
        ).toPromise();

        console.log('VAT check response:', response);
        this.isLoading.set(false);

        if (!response) {
          this.errorMessage.set('Unable to verify VAT number. Please try again.');
          return;
        }

        if (response.exists) {
          const companyName = response.company_name ? ` (${response.company_name})` : '';
          this.errorMessage.set(`This VAT number is already registered${companyName}. Please use a different VAT number.`);
          return;
        }
      } catch (error: any) {
        this.isLoading.set(false);
        console.error('VAT check error:', error);

        if (error?.error?.message) {
          this.errorMessage.set(error.error.message);
        } else {
          this.errorMessage.set('Unable to verify VAT number. Please try again.');
        }
        return;
      }
    }

    this.currentStep.update(step => step + 1);
  }

  previousStep() {
    this.currentStep.update(step => step - 1);
  }

  validateCurrentStep(): boolean {
    this.errorMessage.set('');
    const step = this.currentStep();

    if (step === 1) {
      const personal = this.personalInfo();

      // Check all required fields
      if (!personal.firstName?.trim()) {
        this.errorMessage.set('First Name is required');
        return false;
      }
      if (!personal.lastName?.trim()) {
        this.errorMessage.set('Last Name is required');
        return false;
      }
      if (!personal.email?.trim()) {
        this.errorMessage.set('Email is required');
        return false;
      }

      // Validate email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(personal.email)) {
        this.errorMessage.set('Please enter a valid email address');
        return false;
      }

      if (!personal.password) {
        this.errorMessage.set('Password is required');
        return false;
      }

      // Validate password strength
      if (personal.password.length < 8) {
        this.errorMessage.set('Password must be at least 8 characters long');
        return false;
      }

      const hasUpperCase = /[A-Z]/.test(personal.password);
      const hasLowerCase = /[a-z]/.test(personal.password);
      const hasNumber = /\d/.test(personal.password);

      if (!hasUpperCase || !hasLowerCase || !hasNumber) {
        this.errorMessage.set('Password must contain uppercase, lowercase, and numbers');
        return false;
      }

      if (!personal.confirmPassword) {
        this.errorMessage.set('Please confirm your password');
        return false;
      }

      if (personal.password !== personal.confirmPassword) {
        this.errorMessage.set('Passwords do not match');
        return false;
      }

      return true;
    }

    if (step === 2) {
      const company = this.companyInfo();

      if (!company.companyName?.trim()) {
        this.errorMessage.set('Company Name is required');
        return false;
      }

      if (!company.countryCode) {
        this.errorMessage.set('Country is required');
        return false;
      }

      if (!company.vatNumber?.trim()) {
        this.errorMessage.set('VAT Number is required');
        return false;
      }

      // Basic VAT number validation (at least 5 characters)
      if (company.vatNumber.trim().length < 5) {
        this.errorMessage.set('Please enter a valid VAT Number');
        return false;
      }

      return true;
    }

    if (step === 3) {
      if (!this.selectedPlan()) {
        this.errorMessage.set('Please select a subscription plan');
        return false;
      }
      return true;
    }

    return true;
  }

  selectPlan(planId: string) {
    this.selectedPlan.set(planId);
  }

  async completeRegistration() {
    console.log('=== FRONTEND: completeRegistration() called ===');
    this.isLoading.set(true);
    this.errorMessage.set('');

    const country = this.selectedCountry();
    const registrationData = {
      // Company info
      company_name: this.companyInfo().companyName,
      vat_number: this.companyInfo().vatNumber,
      sdi_code: this.companyInfo().sdiCode || null,
      email: this.personalInfo().email,
      phone: this.personalInfo().phone,
      website: this.companyInfo().website,

      // Address
      address: this.companyInfo().address,
      city: this.companyInfo().city,
      province: this.companyInfo().province,
      postal_code: this.companyInfo().postalCode,
      country: country?.name || 'Italy',
      country_code: this.companyInfo().countryCode,

      // Company details
      industry: this.companyInfo().industry,
      employees_count: this.companyInfo().companySize,

      // Legal representative
      legal_rep_first_name: this.personalInfo().firstName,
      legal_rep_last_name: this.personalInfo().lastName,
      legal_rep_email: this.personalInfo().email,
      legal_rep_phone: this.personalInfo().phone,

      // Password
      password: this.personalInfo().password,

      // Plan
      subscription_plan: this.selectedPlan(),

      // Terms
      terms_accepted: true,
      privacy_accepted: true,
    };

    console.log('=== FRONTEND: Registration data prepared ===');
    console.log('Company:', registrationData.company_name);
    console.log('Email:', registrationData.email);
    console.log('VAT:', registrationData.vat_number);
    console.log('Plan:', registrationData.subscription_plan);

    console.log('=== FRONTEND: Sending POST request to API ===');
    console.log('Endpoint:', API_ENDPOINTS.PUBLIC_REGISTER);

    this.apiService.post<RegisterResponse>(
      API_ENDPOINTS.PUBLIC_REGISTER,
      registrationData,
      undefined,
      false // No credentials needed for registration
    ).subscribe({
      next: (response) => {
        console.log('=== FRONTEND: HTTP Response Received ===');
        console.log('Response type:', typeof response);
        console.log('Response is null?', response === null);
        console.log('Response is undefined?', response === undefined);
        console.log('Full response object:', response);

        if (response) {
          console.log('Response keys:', Object.keys(response));
          console.log('success:', response.success);
          console.log('message:', response.message);
          console.log('paypal_approval_url:', response.paypal_approval_url);
          console.log('company_id:', response.company_id);
        }

        if (!response) {
          console.error('❌ FRONTEND: Response is null or undefined');
          this.errorMessage.set('Invalid server response');
          this.isLoading.set(false);
          return;
        }

        if (response.success) {
          console.log('✅ FRONTEND: Registration successful');
          if (response.paypal_approval_url) {
            // Redirect to PayPal - don't reset loading, page will navigate away
            console.log('✅ FRONTEND: Redirecting to PayPal:', response.paypal_approval_url);
            window.location.href = response.paypal_approval_url;
          } else {
            // Registration successful without payment (shouldn't happen normally)
            console.warn('⚠️ FRONTEND: Registration successful but no PayPal URL provided');
            this.isLoading.set(false);
            this.errorMessage.set('Registration completed but payment setup failed. Please contact support.');
          }
        } else {
          // Error from backend
          console.error('❌ FRONTEND: Registration failed:', response.message);
          this.errorMessage.set(response.message || 'Registration failed');
          this.isLoading.set(false);
        }
      },
      error: (err) => {
        console.error('=== FRONTEND: HTTP Error Occurred ===');
        console.error('Error type:', typeof err);
        console.error('Full error object:', err);
        console.error('Status code:', err.status);
        console.error('Status text:', err.statusText);
        console.error('Error body:', err.error);
        console.error('Error message:', err.error?.message);
        console.error('Error success flag:', err.error?.success);
        console.error('=====================================');

        // Show more detailed error message
        let errorMsg = 'Connection error. Please try again.';
        if (err.error?.message) {
          errorMsg = err.error.message;
        } else if (err.status === 0) {
          errorMsg = 'Cannot connect to server. Please check if the backend is running.';
        } else if (err.status === 500) {
          errorMsg = 'Server error. Please try again or contact support.';
        }

        this.errorMessage.set(errorMsg);
        this.isLoading.set(false);
      }
    });
  }

  processPayPalPayment() {
    this.completeRegistration();
  }

  togglePasswordVisibility() {
    this.showPassword.update(v => !v);
  }

  toggleConfirmPasswordVisibility() {
    this.showConfirmPassword.update(v => !v);
  }

  checkPasswordStrength(password: string) {
    if (!password) {
      this.passwordStrength.set({level: 0, text: '', color: ''});
      return;
    }

    let strength = 0;
    const checks = {
      hasLower: /[a-z]/.test(password),
      hasUpper: /[A-Z]/.test(password),
      hasNumber: /\d/.test(password),
      hasSpecial: /[!@#$%^&*(),.?":{}|<>]/.test(password),
      minLength: password.length >= 8,
      goodLength: password.length >= 12
    };

    // Calculate strength
    if (checks.minLength) strength++;
    if (checks.hasLower) strength++;
    if (checks.hasUpper) strength++;
    if (checks.hasNumber) strength++;
    if (checks.hasSpecial) strength++;
    if (checks.goodLength) strength++;

    // Determine level and text
    let level = 0;
    let text = '';
    let color = '';

    if (strength <= 2) {
      level = 1;
      text = 'Weak';
      color = '#f7c7d9';
    } else if (strength === 3) {
      level = 2;
      text = 'Fair';
      color = '#f7c7d9';
    } else if (strength === 4) {
      level = 3;
      text = 'Good';
      color = '#d8ccf0';
    } else if (strength === 5) {
      level = 4;
      text = 'Strong';
      color = '#cfede3';
    } else {
      level = 5;
      text = 'Excellent';
      color = '#cfede3';
    }

    this.passwordStrength.set({level, text, color});
  }

  onPasswordChange(password: string) {
    this.personalInfo.update(info => ({...info, password}));
    this.checkPasswordStrength(password);
  }
}
