/**
 * Tenant Pool Models
 * Shared interfaces for tenant pool data
 */

export interface Tenant {
  id: number;
  schema_name: string;
  is_available: boolean;
  company_id: string | null;
  assigned_at: string | null;
  created_at: string;
  company: {
    name: string;
    email: string;
    registration_status: string;
    payment_status: string;
  } | null;
}

export interface TenantStats {
  total: number;
  available: number;
  assigned: number;
  active: number;
}

export interface TenantPoolResponse {
  success: boolean;
  tenants: Tenant[];
  stats: TenantStats;
}
