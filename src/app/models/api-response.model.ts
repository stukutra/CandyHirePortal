/**
 * Common API Response Models
 * Generic response interfaces used across the application
 */

export interface ApiResponse<T = any> {
  success: boolean;
  message?: string;
  data?: T;
  error?: string;
}

export interface ApiErrorResponse {
  success: false;
  message: string;
  error?: string;
  code?: string;
}

export interface ApiSuccessResponse<T = any> {
  success: true;
  message?: string;
  data: T;
}
