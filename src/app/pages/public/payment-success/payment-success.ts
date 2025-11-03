import { Component, inject, signal, OnInit } from '@angular/core';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { ApiService, API_ENDPOINTS } from '../../../core/services/api.service';

interface PaymentCaptureData {
  payment_captured?: boolean;
  already_processed?: boolean;
  transaction_id?: string;
  amount?: number;
  currency?: string;
  tenant_assigned?: boolean;
  tenant_schema?: string;
  tenant_id?: string;
  user_id?: string;
  redirect_url?: string;
}

interface PaymentCaptureResponse {
  success: boolean;
  message: string;
  data: PaymentCaptureData;
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
        // Handle both successful capture and already processed cases
        if (response.success && response.data && (response.data.payment_captured || response.data.already_processed)) {
          this.isSuccess.set(true);
          this.paymentDetails.set({
            transaction_id: response.data.transaction_id,
            amount: response.data.amount,
            currency: response.data.currency,
            tenant_assigned: response.data.tenant_assigned,
            tenant_schema: response.data.tenant_schema,
            message: response.message
          });

          // Redirect to SaaS application after 3 seconds
          const redirectUrl = response.data.redirect_url || 'http://localhost:4202';
          setTimeout(() => {
            console.log('Redirecting to SaaS:', redirectUrl);
            // Use window.location.href for external redirect (different port/domain)
            // This ensures cookies are sent with the request
            window.location.href = redirectUrl;
          }, 3000);
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
