import { Injectable, signal, computed, PLATFORM_ID, inject } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { HttpHeaders } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, tap, catchError, of } from 'rxjs';
import { ApiService, API_ENDPOINTS } from './api.service';
import { AdminUser, AuthResponse } from '../../models';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private platformId = inject(PLATFORM_ID);
  private router = inject(Router);
  private apiService = inject(ApiService);

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
   * Admin login (using httpOnly cookies for security)
   */
  loginAdmin(email: string, password: string): Observable<AuthResponse> {
    return this.apiService.post<AuthResponse>(
      API_ENDPOINTS.ADMIN_LOGIN,
      { email, password }
    ).pipe(
      tap(response => {
        // With httpOnly cookies, token is not in response - it's in the cookie
        if (response.success && response.admin) {
          this.setAuthData(response.admin);
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
   * Set authentication data in memory only (token is in httpOnly cookie)
   */
  private setAuthData(admin: AdminUser): void {
    this.currentAdminSignal.set(admin);
    this.isAuthenticatedSignal.set(true);
    this.authTokenSignal.set('cookie'); // Flag to indicate cookie-based auth

    // Store only admin data in localStorage for persistence across page reloads
    if (isPlatformBrowser(this.platformId)) {
      localStorage.setItem('portal_admin', JSON.stringify(admin));
    }
  }

  /**
   * Logout admin (clears httpOnly cookie on server)
   */
  logout(): void {
    // Call logout endpoint to clear httpOnly cookie
    this.apiService.post(API_ENDPOINTS.ADMIN_LOGOUT, {}).subscribe({
      next: () => {
        this.clearAuthData();
        this.router.navigate(['/admin/login']);
      },
      error: (err) => {
        console.error('Logout error:', err);
        // Clear local data anyway
        this.clearAuthData();
        this.router.navigate(['/admin/login']);
      }
    });
  }

  /**
   * Check if user is authenticated (from localStorage admin data + httpOnly cookie)
   */
  checkAuth(): boolean {
    if (!isPlatformBrowser(this.platformId)) {
      return false;
    }

    const adminStr = localStorage.getItem('portal_admin');

    if (adminStr) {
      try {
        const admin = JSON.parse(adminStr) as AdminUser;
        this.authTokenSignal.set('cookie'); // Flag for cookie-based auth
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
   * Clear all auth data (cookie is cleared by server on logout)
   */
  private clearAuthData(): void {
    if (isPlatformBrowser(this.platformId)) {
      localStorage.removeItem('portal_admin');
      // Remove legacy token if exists
      localStorage.removeItem('portal_auth_token');
    }
    this.authTokenSignal.set(null);
    this.currentAdminSignal.set(null);
    this.isAuthenticatedSignal.set(false);
  }

  /**
   * Get authorization headers for HTTP requests
   * Note: With httpOnly cookies, auth is handled via cookies sent with withCredentials: true
   * This method still exists for backwards compatibility
   */
  getAuthHeaders(): HttpHeaders {
    return new HttpHeaders({
      'Content-Type': 'application/json'
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
   * Get HTTP options with credentials for authenticated requests
   * Use this for all authenticated API calls to include httpOnly cookies
   */
  getAuthOptions(): { headers: HttpHeaders; withCredentials: boolean } {
    return {
      headers: this.getAuthHeaders(),
      withCredentials: true
    };
  }
}
