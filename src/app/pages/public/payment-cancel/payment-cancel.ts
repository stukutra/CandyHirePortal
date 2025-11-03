import { Component, inject, signal, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { ApiService, API_ENDPOINTS } from '../../../core/services/api.service';

@Component({
  selector: 'app-payment-cancel',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './payment-cancel.html',
  styleUrl: './payment-cancel.scss',
})
export class PaymentCancel implements OnInit {
  private apiService = inject(ApiService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);

  ngOnInit() {
    // Get PayPal token from query params if available
    this.route.queryParams.subscribe(params => {
      const token = params['token'];

      if (token) {
        // Notify backend about cancellation
        this.notifyCancellation(token);
      }
    });
  }

  notifyCancellation(token: string) {
    this.apiService.post(
      API_ENDPOINTS.PAYMENT_CANCEL,
      { token }
    ).subscribe({
      next: (response) => {
        console.log('Cancellation recorded:', response);
      },
      error: (err) => {
        console.error('Error recording cancellation:', err);
      }
    });
  }

  retryPayment() {
    this.router.navigate(['/register']);
  }

  goToLogin() {
    this.router.navigate(['/login']);
  }
}
