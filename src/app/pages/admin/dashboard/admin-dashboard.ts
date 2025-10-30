import { Component, inject, signal, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../../core/services/auth.service';
import { environment } from '../../../../environments/environment';

interface DashboardStats {
  total_companies: number;
  active_companies: number;
  payment_pending: number;
  paid_companies: number;
  total_revenue: number;
}

interface Company {
  id: string;
  company_name: string;
  email: string;
  registration_status: string;
  payment_status: string;
  created_at: string;
}

interface DashboardResponse {
  success: boolean;
  stats: DashboardStats;
  companies_by_status: Array<{registration_status: string; count: number}>;
  recent_registrations: Array<{date: string; count: number}>;
  latest_companies: Company[];
}

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './admin-dashboard.html',
  styleUrl: './admin-dashboard.scss',
})
export class AdminDashboard implements OnInit {
  private http = inject(HttpClient);
  private authService = inject(AuthService);
  private router = inject(Router);

  private apiUrl = environment.apiUrl;

  stats = signal<DashboardStats | null>(null);
  latestCompanies = signal<Company[]>([]);
  isLoading = signal(true);
  errorMessage = signal('');

  currentAdmin = this.authService.currentAdmin;
  adminFullName = this.authService.adminFullName;

  ngOnInit() {
    this.loadDashboardData();
  }

  loadDashboardData() {
    this.isLoading.set(true);
    this.errorMessage.set('');

    const headers = this.authService.getAuthHeaders();

    this.http.get<DashboardResponse>(`${this.apiUrl}/admin/dashboard-stats.php`, { headers }).subscribe({
      next: (response) => {
        if (response.success) {
          this.stats.set(response.stats);
          this.latestCompanies.set(response.latest_companies);
        } else {
          this.errorMessage.set('Failed to load dashboard data');
        }
        this.isLoading.set(false);
      },
      error: (err) => {
        this.errorMessage.set('Failed to connect to API');
        this.isLoading.set(false);
        console.error('Dashboard error:', err);
      }
    });
  }

  logout() {
    this.authService.logout();
  }

  goToCompanies() {
    this.router.navigate(['/admin/companies']);
  }

  getStatusBadgeClass(status: string): string {
    const statusMap: Record<string, string> = {
      'active': 'badge-success',
      'payment_pending': 'badge-warning',
      'pending': 'badge-secondary',
      'suspended': 'badge-danger',
      'cancelled': 'badge-dark'
    };
    return statusMap[status] || 'badge-secondary';
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString();
  }
}
