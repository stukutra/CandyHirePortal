/**
 * Dashboard Models
 * Models for admin dashboard statistics and data
 */

export interface DashboardStats {
  total_companies: number;
  active_companies: number;
  payment_pending: number;
  paid_companies: number;
  total_revenue: number;
}

export interface Company {
  id: string;
  company_name: string;
  vat_number: string | null;
  sdi_code?: string | null;
  email: string;
  phone: string | null;
  website: string | null;
  address: string | null;
  city: string | null;
  postal_code: string | null;
  province: string | null;
  country: string | null;
  country_code?: string | null;
  industry: string | null;
  employees_count: string | null;
  legal_rep_first_name: string;
  legal_rep_last_name: string;
  legal_rep_email: string;
  legal_rep_phone: string | null;
  subscription_plan: string;
  registration_status: string;
  payment_status: string;
  is_active: boolean;
  created_at: string;
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
