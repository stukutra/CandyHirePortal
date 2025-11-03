import { Component, inject, signal, OnInit } from '@angular/core';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { ApiService, API_ENDPOINTS } from '../../../core/services/api.service';

interface PaymentCaptureResponse {
  success: boolean;
  payment_captured: boolean;
  transaction_id: string;
  amount: number;
  currency: string;
  tenant_assigned: boolean;
  message: string;
}

@Component({
  selector: 'app-payment-success',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './payment-success.html',
  styleUrl: './payment-success.scss',
})
export class PaymentSuccess implements OnInit {
  private apiService = inject(ApiService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);

  isProcessing = signal(true);
  isSuccess = signal(false);
  errorMessage = signal('');
  paymentDetails = signal<any>(null);

  ngOnInit() {
    // Get PayPal token from query params
    this.route.queryParams.subscribe(params => {
      const token = params['token'];

      if (!token) {
        this.isProcessing.set(false);
        this.errorMessage.set('Invalid payment token. Please contact support.');
        return;
      }

      this.capturePayment(token);
    });
  }

  capturePayment(token: string) {
    this.isProcessing.set(true);

    this.apiService.post<PaymentCaptureResponse>(
      API_ENDPOINTS.PAYMENT_CAPTURE,
      { token }
    ).subscribe({
      next: (response) => {
        if (response.success && response.payment_captured) {
          this.isSuccess.set(true);
          this.paymentDetails.set({
            transaction_id: response.transaction_id,
            amount: response.amount,
            currency: response.currency,
            tenant_assigned: response.tenant_assigned,
            message: response.message
          });

          // Redirect to login after 5 seconds
          setTimeout(() => {
            this.router.navigate(['/login']);
          }, 5000);
        } else {
          this.errorMessage.set(response.message || 'Payment processing failed');
        }
        this.isProcessing.set(false);
      },
      error: (err) => {
        console.error('Payment capture error:', err);
        this.errorMessage.set('An error occurred while processing your payment. Please contact support.');
        this.isProcessing.set(false);
      }
    });
  }

  goToLogin() {
    this.router.navigate(['/login']);
  }
}
