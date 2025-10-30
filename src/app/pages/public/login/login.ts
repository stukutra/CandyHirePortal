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
    }).subscribe({
      next: (response) => {
        if (response.success && response.token) {
          // Store token in localStorage
          localStorage.setItem('portal_company_token', response.token);
          localStorage.setItem('portal_company', JSON.stringify(response.company));

          // Redirect to company dashboard
          this.router.navigate(['/dashboard']);
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
