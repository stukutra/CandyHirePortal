/**
 * Company Models
 * Shared interfaces for company-related data
 */

export interface Company {
  id: string;
  company_name: string;
  email: string;
  vat_number: string | null;
  city: string | null;
  country: string | null;
  registration_status: string;
  payment_status: string;
  subscription_plan: string | null;
  tenant_schema: string | null;
  created_at: string;
  phone?: string | null;
  website?: string | null;
  is_active?: boolean;
  legal_rep_first_name?: string;
  legal_rep_last_name?: string;
  legal_rep_email?: string;
  legal_rep_phone?: string | null;
  address?: string | null;
  postal_code?: string | null;
  province?: string | null;
  country_code?: string | null;
  sdi_code?: string | null;
  industry?: string | null;
  employees_count?: string | null;
}

export interface CompanyDetail {
  id: string;
  company_name: string;
  vat_number: string;
  email: string;
  phone: string;
  website: string;
  address: string;
  city: string;
  postal_code: string;
  province: string;
  country: string;
  industry: string;
  employees_count: string;
  description: string;
  legal_rep_first_name: string;
  legal_rep_last_name: string;
  legal_rep_email: string;
  legal_rep_phone: string;
  registration_status: string;
  payment_status: string;
  subscription_plan: string;
  subscription_start_date: string;
  subscription_end_date: string;
  tenant_schema: string;
  tenant_assigned_at: string;
  paypal_subscription_id: string;
  paypal_payer_id: string;
  is_active: boolean;
  email_verified: boolean;
  created_at: string;
  updated_at: string;
  last_login: string;
}

export interface Transaction {
  id: string;
  transaction_type: string;
  amount: string;
  currency: string;
  status: string;
  created_at: string;
}

export interface ActivityLog {
  id: string;
  action: string;
  description: string;
  created_at: string;
  user_id?: string;
  user_name?: string;
}

export interface Pagination {
  current_page: number;
  total_pages: number;
  total_records: number;
  per_page: number;
  total_items?: number;
  totalItems?: number;
  currentPage?: number;
  itemsPerPage?: number;
  totalPages?: number;
}

export interface CompaniesListResponse {
  success: boolean;
  data: Company[];
  pagination: Pagination;
}

export interface CompanyDetailResponse {
  success: boolean;
  company: CompanyDetail;
  transactions: Transaction[];
  activity_logs: ActivityLog[];
}

export interface CompanyUpdateResponse {
  success: boolean;
  message: string;
  company?: CompanyDetail;
}
