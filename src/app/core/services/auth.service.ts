import { Injectable, signal, computed, PLATFORM_ID, inject } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, tap, catchError, of } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface AdminUser {
  id: string;
  username: string;
  email: string;
  first_name: string | null;
  last_name: string | null;
  role: 'super_admin' | 'admin' | 'support';
}

export interface AuthResponse {
  success: boolean;
  message: string;
  token?: string;
  admin?: AdminUser;
  error?: string;
}

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private platformId = inject(PLATFORM_ID);
  private router = inject(Router);
  private http = inject(HttpClient);

  private apiUrl = environment.apiUrl || 'http://localhost:8082';

  private currentAdminSignal = signal<AdminUser | null>(null);
  private isAuthenticatedSignal = signal<boolean>(false);
  private authTokenSignal = signal<string | null>(null);

  currentAdmin = this.currentAdminSignal.asReadonly();
  isAuthenticated = this.isAuthenticatedSignal.asReadonly();
  authToken = this.authTokenSignal.asReadonly();

  adminFullName = computed(() => {
    const admin = this.currentAdmin();
    if (!admin) return '';
    return `${admin.first_name || ''} ${admin.last_name || ''}`.trim() || admin.username;
  });

  adminRole = computed(() => this.currentAdmin()?.role);

  constructor() {
    // Check auth on service initialization
    this.checkAuth();
  }

  /**
   * Admin login
   */
  loginAdmin(email: string, password: string): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/admin/login.php`, {
      email,
      password
    }).pipe(
      tap(response => {
        if (response.success && response.token && response.admin) {
          this.setAuthData(response.token, response.admin);
        }
      }),
      catchError(error => {
        console.error('Login error:', error);
        return of({
          success: false,
          message: error.error?.message || 'Login failed',
          error: error.message
        });
      })
    );
  }

  /**
   * Set authentication data in memory and localStorage
   */
  private setAuthData(token: string, admin: AdminUser): void {
    this.authTokenSignal.set(token);
    this.currentAdminSignal.set(admin);
    this.isAuthenticatedSignal.set(true);

    if (isPlatformBrowser(this.platformId)) {
      localStorage.setItem('portal_auth_token', token);
      localStorage.setItem('portal_admin', JSON.stringify(admin));
    }
  }

  /**
   * Logout admin
   */
  logout(): void {
    this.authTokenSignal.set(null);
    this.currentAdminSignal.set(null);
    this.isAuthenticatedSignal.set(false);

    if (isPlatformBrowser(this.platformId)) {
      localStorage.removeItem('portal_auth_token');
      localStorage.removeItem('portal_admin');
    }

    this.router.navigate(['/admin/login']);
  }

  /**
   * Check if user is authenticated (from localStorage)
   */
  checkAuth(): boolean {
    if (!isPlatformBrowser(this.platformId)) {
      return false;
    }

    const token = localStorage.getItem('portal_auth_token');
    const adminStr = localStorage.getItem('portal_admin');

    if (token && adminStr) {
      try {
        const admin = JSON.parse(adminStr) as AdminUser;
        this.authTokenSignal.set(token);
        this.currentAdminSignal.set(admin);
        this.isAuthenticatedSignal.set(true);
        return true;
      } catch (e) {
        console.error('Error parsing admin data:', e);
        this.clearAuthData();
        return false;
      }
    }

    return false;
  }

  /**
   * Clear all auth data
   */
  private clearAuthData(): void {
    if (isPlatformBrowser(this.platformId)) {
      localStorage.removeItem('portal_auth_token');
      localStorage.removeItem('portal_admin');
    }
    this.authTokenSignal.set(null);
    this.currentAdminSignal.set(null);
    this.isAuthenticatedSignal.set(false);
  }

  /**
   * Get authorization headers for HTTP requests
   */
  getAuthHeaders(): HttpHeaders {
    const token = this.authToken();
    return new HttpHeaders({
      'Content-Type': 'application/json',
      'Authorization': token ? `Bearer ${token}` : ''
    });
  }

  /**
   * Check if admin has specific role
   */
  hasRole(roles: ('super_admin' | 'admin' | 'support')[]): boolean {
    const currentRole = this.adminRole();
    return currentRole ? roles.includes(currentRole) : false;
  }

  /**
   * Check if token is expired (basic check)
   */
  isTokenExpired(): boolean {
    const token = this.authToken();
    if (!token) return true;

    try {
      // JWT token has 3 parts separated by dots
      const payload = token.split('.')[1];
      const decodedPayload = JSON.parse(atob(payload));

      // Check expiration
      if (decodedPayload.exp) {
        const expirationDate = new Date(decodedPayload.exp * 1000);
        return expirationDate < new Date();
      }

      return false;
    } catch (e) {
      console.error('Error checking token expiration:', e);
      return true;
    }
  }
}
