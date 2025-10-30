import { Component, inject, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

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
export class Register {
  private http = inject(HttpClient);
  private router = inject(Router);

  private apiUrl = environment.apiUrl || 'http://localhost:8082';

  currentStep = signal(1);
  isLoading = signal(false);
  showPassword = signal(false);
  showConfirmPassword = signal(false);
  errorMessage = signal('');
  passwordStrength = signal<{level: number, text: string, color: string}>({level: 0, text: '', color: ''});

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
    companySize: '',
    industry: '',
    website: '',
    address: '',
    city: '',
    province: '',
    postalCode: '',
    country: 'Italy',
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

  nextStep() {
    if (this.validateCurrentStep()) {
      this.currentStep.update(step => step + 1);
    }
  }

  previousStep() {
    this.currentStep.update(step => step - 1);
  }

  validateCurrentStep(): boolean {
    this.errorMessage.set('');
    const step = this.currentStep();

    if (step === 1) {
      const personal = this.personalInfo();
      if (!personal.firstName || !personal.lastName || !personal.email || !personal.password) {
        this.errorMessage.set('Please fill in all required fields');
        return false;
      }
      if (personal.password !== personal.confirmPassword) {
        this.errorMessage.set('Passwords do not match');
        return false;
      }
      if (personal.password.length < 8) {
        this.errorMessage.set('Password must be at least 8 characters');
        return false;
      }
      return true;
    }

    if (step === 2) {
      const company = this.companyInfo();
      if (!company.companyName || !company.vatNumber) {
        this.errorMessage.set('Please fill in all required fields');
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
    this.isLoading.set(true);
    this.errorMessage.set('');

    const registrationData = {
      // Company info
      company_name: this.companyInfo().companyName,
      vat_number: this.companyInfo().vatNumber,
      email: this.personalInfo().email,
      phone: this.personalInfo().phone,
      website: this.companyInfo().website,

      // Address
      address: this.companyInfo().address,
      city: this.companyInfo().city,
      province: this.companyInfo().province,
      postal_code: this.companyInfo().postalCode,
      country: this.companyInfo().country,

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

    this.http.post<RegisterResponse>(`${this.apiUrl}/auth/register.php`, registrationData)
      .subscribe({
        next: (response) => {
          if (response.success) {
            if (response.paypal_approval_url) {
              // Redirect to PayPal
              window.location.href = response.paypal_approval_url;
            } else {
              // Registration successful
              alert('Registration completed! Please check your email to verify your account.');
              this.router.navigate(['/login']);
            }
          } else {
            this.errorMessage.set(response.message || 'Registration failed');
          }
          this.isLoading.set(false);
        },
        error: (err) => {
          console.error('Registration error:', err);
          this.errorMessage.set('Connection error. Please try again.');
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
