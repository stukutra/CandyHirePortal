import { Component, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-admin-login',
  standalone: true,
  imports: [FormsModule, CommonModule],
  templateUrl: './admin-login.html',
  styleUrl: './admin-login.scss',
})
export class AdminLogin {
  private authService = inject(AuthService);
  private router = inject(Router);

  email = signal('admin@candyhire.com'); // Pre-filled for dev
  password = signal('');
  isLoading = signal(false);
  showPassword = signal(false);
  errorMessage = signal('');

  onSubmit() {
    this.isLoading.set(true);
    this.errorMessage.set('');

    console.log('Login attempt:', { email: this.email(), password: '***' });

    this.authService.loginAdmin(this.email(), this.password()).subscribe({
      next: (response) => {
        console.log('Login response:', response);

        if (response.success) {
          console.log('Login successful, navigating to dashboard...');
          this.router.navigate(['/admin/dashboard']).then(
            success => console.log('Navigation success:', success),
            error => console.error('Navigation error:', error)
          );
        } else {
          console.error('Login failed:', response.message);
          this.errorMessage.set(response.message || 'Login failed');
          this.isLoading.set(false);
        }
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
