import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

/**
 * Centralized API endpoints configuration
 * All API endpoints are defined here for easy maintenance
 */
export const API_ENDPOINTS = {
  // Admin Authentication
  ADMIN_LOGIN: '/admin/login.php',
  ADMIN_LOGOUT: '/admin/logout.php',

  // Admin Dashboard
  ADMIN_DASHBOARD_STATS: '/admin/dashboard-stats.php',

  // Companies Management
  ADMIN_COMPANIES_LIST: '/admin/companies-list.php',
  ADMIN_COMPANIES: '/admin/companies.php',
  ADMIN_COMPANY_DETAIL: '/admin/company-detail.php',
  ADMIN_COMPANY_UPDATE: '/admin/company-update.php',
  ADMIN_COMPANY_UPDATE_STATUS: '/admin/company-update-status.php',
  ADMIN_COMPANY_TOGGLE_ACTIVE: '/admin/companies/{id}/toggle-active',

  // Tenant Pool
  ADMIN_TENANT_POOL: '/admin/tenant-pool.php',

  // Subscription Tiers Management
  ADMIN_TIERS_LIST: '/admin/tiers/list.php',
  ADMIN_TIERS_GET: '/admin/tiers/get.php',
  ADMIN_TIERS_CREATE: '/admin/tiers/create.php',
  ADMIN_TIERS_UPDATE: '/admin/tiers/update.php',
  ADMIN_TIERS_DELETE: '/admin/tiers/delete.php',
  ADMIN_TIERS_DUPLICATE: '/admin/tiers/duplicate.php',
  ADMIN_TIERS_TOGGLE_STATUS: '/admin/tiers/toggle-status.php',
  PUBLIC_TIERS_LIST: '/tiers/list.php',

  // Public Auth
  PUBLIC_LOGIN: '/auth/login.php',
  PUBLIC_REGISTER: '/auth/register.php',
  CHECK_EMAIL: '/auth/check-email.php',
  CHECK_VAT: '/auth/check-vat.php',
  PUBLIC_VERIFY_EMAIL: '/auth/verify-email.php',
  PUBLIC_FORGOT_PASSWORD: '/auth/forgot-password.php',
  PUBLIC_RESET_PASSWORD: '/auth/reset-password.php',

  // Public Onboarding
  PUBLIC_ONBOARDING_STEP1: '/onboarding/step1.php',
  PUBLIC_ONBOARDING_STEP2: '/onboarding/step2.php',
  PUBLIC_ONBOARDING_STEP3: '/onboarding/step3.php',

  // Countries
  PUBLIC_COUNTRIES: '/public/countries.php',

  // Payment (PayPal)
  PAYMENT_CAPTURE: '/payment/capture.php',
  PAYMENT_CANCEL: '/payment/cancel.php',
} as const;

/**
 * Centralized API Service
 * Handles all HTTP requests with consistent configuration
 * Automatically includes withCredentials for authenticated requests
 */
@Injectable({
  providedIn: 'root',
})
export class ApiService {
  private http = inject(HttpClient);
  private baseUrl = environment.apiUrl;

  /**
   * Build full URL for an endpoint
   */
  private buildUrl(endpoint: string, pathParams?: Record<string, string>): string {
    let url = `${this.baseUrl}${endpoint}`;

    // Replace path parameters like {id}
    if (pathParams) {
      Object.keys(pathParams).forEach(key => {
        url = url.replace(`{${key}}`, pathParams[key]);
      });
    }

    return url;
  }

  /**
   * Get default HTTP options with credentials
   */
  private getOptions(includeCredentials = true): {
    headers: HttpHeaders;
    withCredentials?: boolean
  } {
    const options: any = {
      headers: new HttpHeaders({
        'Content-Type': 'application/json'
      })
    };

    if (includeCredentials) {
      options.withCredentials = true;
    }

    return options;
  }

  /**
   * GET request
   */
  get<T>(
    endpoint: string,
    params?: HttpParams | { [key: string]: any },
    pathParams?: Record<string, string>,
    includeCredentials = true
  ): Observable<T> {
    const url = this.buildUrl(endpoint, pathParams);
    const options = { ...this.getOptions(includeCredentials), params };
    return this.http.get<T>(url, options);
  }

  /**
   * POST request
   */
  post<T>(
    endpoint: string,
    body: any,
    pathParams?: Record<string, string>,
    includeCredentials = true
  ): Observable<T> {
    const url = this.buildUrl(endpoint, pathParams);
    const options = this.getOptions(includeCredentials);
    return this.http.post<T>(url, body, options);
  }

  /**
   * PUT request
   */
  put<T>(
    endpoint: string,
    body: any,
    pathParams?: Record<string, string>,
    includeCredentials = true
  ): Observable<T> {
    const url = this.buildUrl(endpoint, pathParams);
    const options = this.getOptions(includeCredentials);
    return this.http.put<T>(url, body, options);
  }

  /**
   * DELETE request
   */
  delete<T>(
    endpoint: string,
    pathParams?: Record<string, string>,
    includeCredentials = true
  ): Observable<T> {
    const url = this.buildUrl(endpoint, pathParams);
    const options = this.getOptions(includeCredentials);
    return this.http.delete<T>(url, options);
  }

  /**
   * PATCH request
   */
  patch<T>(
    endpoint: string,
    body: any,
    pathParams?: Record<string, string>,
    includeCredentials = true
  ): Observable<T> {
    const url = this.buildUrl(endpoint, pathParams);
    const options = this.getOptions(includeCredentials);
    return this.http.patch<T>(url, body, options);
  }
}
