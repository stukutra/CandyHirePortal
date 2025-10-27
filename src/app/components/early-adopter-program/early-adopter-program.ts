import { Component, OnInit, OnDestroy, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { EarlyAdopterService, EarlyAdopter } from '../../services/early-adopter';

@Component({
  selector: 'app-early-adopter-program',
  imports: [CommonModule, FormsModule],
  templateUrl: './early-adopter-program.html',
  styleUrl: './early-adopter-program.scss',
})
export class EarlyAdopterProgram implements OnInit, OnDestroy {
  earlyAdopterService = inject(EarlyAdopterService);

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

  ngOnInit(): void {
    // Service handles all initialization
  }

  ngOnDestroy(): void {
    // Cleanup if needed
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

    if (this.earlyAdopterService.isFull()) {
      this.errorMessage = 'Siamo spiacenti, tutti gli slot sono esauriti!';
      return;
    }

    this.isSubmitting = true;

    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500));

    // Add new early adopter via service
    const newAdopter: EarlyAdopter = {
      companyName: this.formData.companyName,
      email: this.formData.email,
      role: this.formData.role,
      joinedDate: new Date().toISOString()
    };

    const success = this.earlyAdopterService.addEarlyAdopter(newAdopter);

    if (success) {
      this.isSubmitting = false;
      this.showSuccessMessage = true;
      this.resetForm();
    } else {
      this.isSubmitting = false;
      this.errorMessage = 'Errore durante l\'iscrizione. Riprova.';
    }
  }
}
