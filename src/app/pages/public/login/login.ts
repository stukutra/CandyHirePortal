import { Component, inject, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

interface LoginResponse {
  success: boolean;
  message: string;
  token?: string;
  company?: any;
  redirect_url?: string;
}

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule, CommonModule, RouterLink],
  templateUrl: './login.html',
  styleUrl: './login.scss',
})
export class Login {
  private http = inject(HttpClient);
  private router = inject(Router);

  private apiUrl = environment.apiUrl || 'http://localhost:8082';

  email = '';
  password = '';
  isLoading = signal(false);
  showPassword = signal(false);
  errorMessage = signal('');

  onSubmit() {
    this.isLoading.set(true);
    this.errorMessage.set('');

    this.http.post<LoginResponse>(`${this.apiUrl}/auth/login.php`, {
      email: this.email,
      password: this.password
    }, { withCredentials: true }).subscribe({
      next: (response) => {
        if (response.success) {
          // Store company info in localStorage (tokens are in httpOnly cookies)
          if (response.company) {
            localStorage.setItem('portal_company', JSON.stringify(response.company));
          }

          // If redirect_url is provided, redirect to SaaS application
          if (response.redirect_url) {
            console.log('Redirecting to SaaS:', response.redirect_url);
            window.location.href = response.redirect_url;
            return;
          }

          // Otherwise, redirect based on payment status
          if (response.company?.payment_status === 'completed') {
            // Payment completed - go to dashboard
            this.router.navigate(['/dashboard']);
          } else {
            // Payment pending - go to payment page
            this.router.navigate(['/payment-pending']);
          }
        } else {
          this.errorMessage.set(response.message || 'Login failed');
        }
        this.isLoading.set(false);
      },
      error: (err) => {
        console.error('Login error:', err);
        this.errorMessage.set('Connection error. Please try again.');
        this.isLoading.set(false);
      }
    });
  }

  togglePasswordVisibility() {
    this.showPassword.update(v => !v);
  }
}
