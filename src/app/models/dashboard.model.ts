/**
 * Dashboard Models
 * Models for admin dashboard statistics and data
 */

import { Company } from './company.model';

// Re-export Company for backwards compatibility
export type { Company };

export interface DashboardStats {
  total_companies: number;
  active_companies: number;
  payment_pending: number;
  paid_companies: number;
  total_revenue: number;
}

export interface DashboardResponse {
  success: boolean;
  stats: DashboardStats;
  companies_by_status: Array<{registration_status: string; count: number}>;
  recent_registrations: Array<{date: string; count: number}>;
  latest_companies: Company[];
  pagination?: {
    total_items: number;
    total_pages: number;
    current_page: number;
    items_per_page: number;
  };
}
