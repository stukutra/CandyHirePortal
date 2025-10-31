import { Component, inject, signal, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../../core/services/auth.service';
import { environment } from '../../../../environments/environment';

interface Tenant {
  id: number;
  tenant_id: number;
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

interface TenantPoolResponse {
  success: boolean;
  tenants: Tenant[];
  stats: {
    total: number;
    available: number;
    assigned: number;
    active: number;
  };
}

@Component({
  selector: 'app-tenant-pool',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './tenant-pool.html',
  styleUrl: './tenant-pool.scss',
})
export class TenantPool implements OnInit {
  private http = inject(HttpClient);
  private authService = inject(AuthService);

  private apiUrl = environment.apiUrl;

  tenants = signal<Tenant[]>([]);
  stats = signal({
    total: 0,
    available: 0,
    assigned: 0,
    active: 0
  });
  isLoading = signal(true);
  errorMessage = signal('');

  ngOnInit() {
    this.loadTenantPool();
  }

  loadTenantPool() {
    this.isLoading.set(true);
    this.errorMessage.set('');

    const headers = this.authService.getAuthHeaders();

    this.http.get<TenantPoolResponse>(`${this.apiUrl}/admin/tenant-pool.php`, { headers }).subscribe({
      next: (response) => {
        if (response.success) {
          this.tenants.set(response.tenants);
          this.stats.set(response.stats);
        } else {
          this.errorMessage.set('Failed to load tenant pool');
        }
        this.isLoading.set(false);
      },
      error: (err) => {
        this.errorMessage.set('Failed to connect to API');
        this.isLoading.set(false);
        console.error('Tenant pool error:', err);
      }
    });
  }

  getStatusBadgeClass(tenant: Tenant): string {
    if (tenant.is_available) {
      return 'badge bg-success';
    }

    if (tenant.company?.registration_status === 'active') {
      return 'badge bg-primary';
    }

    return 'badge bg-warning';
  }

  getStatusText(tenant: Tenant): string {
    if (tenant.is_available) {
      return 'Available';
    }

    if (tenant.company?.registration_status === 'active') {
      return 'Active';
    }

    return 'Assigned (Pending)';
  }

  formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
  }

  refresh() {
    this.loadTenantPool();
  }
}
