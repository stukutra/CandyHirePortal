/**
 * Admin Models
 * Shared interfaces for admin-related data
 */

export interface AdminUser {
  id: string;
  username: string;
  email: string;
  first_name: string | null;
  last_name: string | null;
  role: 'super_admin' | 'admin' | 'support';
  is_active?: boolean;
  created_at?: string;
  last_login?: string;
}

export interface AuthResponse {
  success: boolean;
  message: string;
  token?: string;
  admin?: AdminUser;
  error?: string;
}

export interface LogoutResponse {
  success: boolean;
  message: string;
}
