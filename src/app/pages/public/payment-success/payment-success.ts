import { Component, inject, signal, OnInit, PLATFORM_ID } from '@angular/core';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { CommonModule, isPlatformBrowser } from '@angular/common';
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
  private platformId = inject(PLATFORM_ID);

  isProcessing = signal(true);
  isSuccess = signal(false);
  errorMessage = signal('');
  countdown = signal(5);
  paymentDetails = signal<PaymentCaptureData | null>(null);

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
          this.isProcessing.set(false);
          this.isSuccess.set(true);
          this.paymentDetails.set(response.data);

          // Start countdown from 5 to 0
          if (isPlatformBrowser(this.platformId)) {
            const countdownInterval = setInterval(() => {
              const current = this.countdown();
              if (current > 0) {
                this.countdown.set(current - 1);
              } else {
                clearInterval(countdownInterval);
              }
            }, 1000);

            // Redirect after 5 seconds
            setTimeout(() => {
              let redirectUrl = response.data.redirect_url || 'http://localhost:4202';

              // Add 'from=portal' parameter to indicate the user is coming from Portal
              const separator = redirectUrl.includes('?') ? '&' : '?';
              redirectUrl = `${redirectUrl}${separator}from=portal`;

              console.log('Redirecting to SaaS:', redirectUrl);
              window.location.href = redirectUrl;
            }, 5000);
          }
        } else {
          this.errorMessage.set(response.message || 'Payment processing failed');
          this.isProcessing.set(false);
        }
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
