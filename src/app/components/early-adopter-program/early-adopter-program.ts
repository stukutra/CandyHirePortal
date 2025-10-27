import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

interface EarlyAdopter {
  companyName: string;
  email: string;
  role: string;
  date: string;
}

@Component({
  selector: 'app-early-adopter-program',
  imports: [CommonModule, FormsModule],
  templateUrl: './early-adopter-program.html',
  styleUrl: './early-adopter-program.scss',
})
export class EarlyAdopterProgram implements OnInit, OnDestroy {
  // Slot management
  registeredCompanies = 7;
  maxSlots = 20;

  // Early adopters data
  earlyAdopters: EarlyAdopter[] = [];

  // Modal state
  showModal = false;
  showSuccessMessage = false;
  isSubmitting = false;
  errorMessage = '';

  // Form data
  formData = {
    companyName: '',
    email: '',
    role: '',
    privacyConsent: false
  };

  // Simulation interval
  private simulationInterval: any;

  ngOnInit(): void {
    this.loadEarlyAdopters();
    this.startSimulation();
  }

  ngOnDestroy(): void {
    if (this.simulationInterval) {
      clearInterval(this.simulationInterval);
    }
  }

  /**
   * Load early adopters from localStorage
   */
  loadEarlyAdopters(): void {
    const stored = localStorage.getItem('candyhire_early_adopters');
    if (stored) {
      this.earlyAdopters = JSON.parse(stored);
      this.registeredCompanies = 7 + this.earlyAdopters.length; // Base 7 + real registrations
    }
  }

  /**
   * Save early adopters to localStorage
   */
  saveEarlyAdopters(): void {
    localStorage.setItem('candyhire_early_adopters', JSON.stringify(this.earlyAdopters));
  }

  /**
   * Start simulation of new registrations (every 30 seconds, 20% chance)
   */
  startSimulation(): void {
    this.simulationInterval = setInterval(() => {
      if (this.registeredCompanies < this.maxSlots && Math.random() < 0.2) {
        this.updateSlots(1);
      }
    }, 30000); // Every 30 seconds
  }

  /**
   * Update slots counter
   */
  updateSlots(increment: number): void {
    if (this.registeredCompanies + increment <= this.maxSlots) {
      this.registeredCompanies += increment;
    }
  }

  /**
   * Get remaining slots
   */
  get remainingSlots(): number {
    return this.maxSlots - this.registeredCompanies;
  }

  /**
   * Get percentage of filled slots
   */
  get slotsPercentage(): number {
    return (this.registeredCompanies / this.maxSlots) * 100;
  }

  /**
   * Check if slots are almost full (>80%)
   */
  get isAlmostFull(): boolean {
    return this.slotsPercentage > 80;
  }

  /**
   * Open modal
   */
  openModal(): void {
    this.showModal = true;
    this.showSuccessMessage = false;
    this.errorMessage = '';
    this.resetForm();
  }

  /**
   * Close modal
   */
  closeModal(): void {
    this.showModal = false;
    this.showSuccessMessage = false;
    this.errorMessage = '';
    this.resetForm();
  }

  /**
   * Reset form
   */
  resetForm(): void {
    this.formData = {
      companyName: '',
      email: '',
      role: '',
      privacyConsent: false
    };
  }

  /**
   * Validate form
   */
  validateForm(): boolean {
    if (!this.formData.companyName.trim()) {
      this.errorMessage = 'Il nome dell\'azienda è obbligatorio';
      return false;
    }

    if (!this.formData.email.trim()) {
      this.errorMessage = 'L\'email è obbligatoria';
      return false;
    }

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(this.formData.email)) {
      this.errorMessage = 'Inserisci un\'email valida';
      return false;
    }

    if (!this.formData.role.trim()) {
      this.errorMessage = 'Il ruolo è obbligatorio';
      return false;
    }

    if (!this.formData.privacyConsent) {
      this.errorMessage = 'Devi accettare l\'informativa sulla privacy';
      return false;
    }

    return true;
  }

  /**
   * Submit form
   */
  async submitForm(): Promise<void> {
    this.errorMessage = '';

    if (!this.validateForm()) {
      return;
    }

    if (this.remainingSlots <= 0) {
      this.errorMessage = 'Siamo spiacenti, tutti gli slot sono esauriti!';
      return;
    }

    this.isSubmitting = true;

    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500));

    // Add new early adopter
    const newAdopter: EarlyAdopter = {
      companyName: this.formData.companyName,
      email: this.formData.email,
      role: this.formData.role,
      date: new Date().toISOString()
    };

    this.earlyAdopters.push(newAdopter);
    this.saveEarlyAdopters();
    this.updateSlots(1);

    this.isSubmitting = false;
    this.showSuccessMessage = true;
    this.resetForm();
  }
}
