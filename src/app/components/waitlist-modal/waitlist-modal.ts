import { Component, EventEmitter, Output, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-waitlist-modal',
  imports: [FormsModule],
  templateUrl: './waitlist-modal.html',
  styleUrl: './waitlist-modal.scss',
})
export class WaitlistModal {
  @Output() closeModal = new EventEmitter<void>();
  private http = inject(HttpClient);

  protected formData = {
    name: '',
    email: '',
    company: ''
  };

  protected isSubmitting = false;
  protected errorMessage = '';

  close(): void {
    this.closeModal.emit();
  }

  submitForm(): void {
    if (this.isSubmitting) return;

    // Validazione base
    if (!this.formData.name || !this.formData.email || !this.formData.company) {
      this.errorMessage = 'Tutti i campi sono obbligatori';
      return;
    }

    this.isSubmitting = true;
    this.errorMessage = '';

    // Invia i dati al backend PHP
    this.http.post('http://localhost:8080/waitlist-signup.php', this.formData)
      .subscribe({
        next: (response: any) => {
          // Successo
          alert(`Grazie ${this.formData.name}! Ti abbiamo aggiunto alla lista d'attesa.\n\nRiceverai presto una email con:\n✓ Link alla demo con dati pre-compilati\n✓ Aggiornamenti esclusivi\n✓ Offerta early bird al lancio`);

          // Reset form e chiudi modale
          this.formData = { name: '', email: '', company: '' };
          this.close();
        },
        error: (error) => {
          console.error('Errore invio form:', error);
          this.errorMessage = error.error?.message || 'Si è verificato un errore. Riprova più tardi.';
          this.isSubmitting = false;
        },
        complete: () => {
          this.isSubmitting = false;
        }
      });
  }
}
