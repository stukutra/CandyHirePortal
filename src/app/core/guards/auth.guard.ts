import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '../services/auth.service';

/**
 * Guard for protecting admin routes
 * Redirects to /admin/login if not authenticated
 * Note: With httpOnly cookies, token validation is done server-side
 */
export const adminGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  // Check if authenticated (admin data exists in localStorage)
  if (authService.isAuthenticated()) {
    return true;
  }

  // Not authenticated, redirect to login
  return router.createUrlTree(['/admin/login']);
};

/**
 * Guard for admin login page
 * Redirects to /admin/dashboard if already authenticated
 */
export const adminLoginGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  // If already authenticated, redirect to admin dashboard
  if (authService.isAuthenticated()) {
    return router.createUrlTree(['/admin/dashboard']);
  }

  return true;
};

/**
 * Role-based guard for specific admin roles
 */
export const adminRoleGuard = (allowedRoles: ('super_admin' | 'admin' | 'support')[]): CanActivateFn => {
  return () => {
    const authService = inject(AuthService);
    const router = inject(Router);

    if (!authService.isAuthenticated()) {
      return router.createUrlTree(['/admin/login']);
    }

    if (!authService.hasRole(allowedRoles)) {
      // Redirect to dashboard if user doesn't have required role
      return router.createUrlTree(['/admin/dashboard']);
    }

    return true;
  };
};
