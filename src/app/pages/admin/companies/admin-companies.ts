import { Component, inject, signal, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { environment } from '../../../../environments/environment';

interface Company {
  id: string;
  company_name: string;
  email: string;
  vat_number: string;
  city: string;
  country: string;
  registration_status: string;
  payment_status: string;
  subscription_plan: string | null;
  tenant_schema: string | null;
  created_at: string;
}

interface CompaniesResponse {
  success: boolean;
  data: Company[];
  pagination: {
    current_page: number;
    total_pages: number;
    total_records: number;
    per_page: number;
  };
}

@Component({
  selector: 'app-admin-companies',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-companies.html',
  styleUrl: './admin-companies.scss',
})
export class AdminCompanies implements OnInit {
  private http = inject(HttpClient);
  private authService = inject(AuthService);
  private toastService = inject(ToastService);
  private router = inject(Router);

  private apiUrl = environment.apiUrl;

  companies = signal<Company[]>([]);
  isLoading = signal(true);
  errorMessage = signal('');

  // Filters
  searchQuery = signal('');
  statusFilter = signal('');
  paymentStatusFilter = signal('');

  // Pagination
  currentPage = signal(1);
  totalPages = signal(1);
  totalRecords = signal(0);
  perPage = signal(20);

  ngOnInit() {
    this.loadCompanies();
  }

  loadCompanies() {
    this.isLoading.set(true);
    this.errorMessage.set('');

    const headers = this.authService.getAuthHeaders();
    const params = new URLSearchParams({
      page: this.currentPage().toString(),
      limit: this.perPage().toString(),
      search: this.searchQuery(),
      status: this.statusFilter(),
      payment_status: this.paymentStatusFilter()
    });

    this.http.get<CompaniesResponse>(`${this.apiUrl}/admin/companies-list.php?${params}`, { headers }).subscribe({
      next: (response) => {
        if (response.success) {
          this.companies.set(response.data);
          this.currentPage.set(response.pagination.current_page);
          this.totalPages.set(response.pagination.total_pages);
          this.totalRecords.set(response.pagination.total_records);
        } else {
          this.errorMessage.set('Failed to load companies');
        }
        this.isLoading.set(false);
      },
      error: (err) => {
        this.errorMessage.set('Failed to connect to API');
        this.isLoading.set(false);
        console.error('Companies error:', err);
      }
    });
  }

  applyFilters() {
    this.currentPage.set(1);
    this.loadCompanies();
  }

  clearFilters() {
    this.searchQuery.set('');
    this.statusFilter.set('');
    this.paymentStatusFilter.set('');
    this.currentPage.set(1);
    this.loadCompanies();
  }

  goToPage(page: number) {
    this.currentPage.set(page);
    this.loadCompanies();
  }

  goToDashboard() {
    this.router.navigate(['/admin/dashboard']);
  }

  logout() {
    this.authService.logout();
  }

  getStatusBadgeClass(status: string): string {
    const statusMap: Record<string, string> = {
      'active': 'badge-success',
      'payment_pending': 'badge-warning',
      'pending': 'badge-secondary',
      'suspended': 'badge-danger',
      'cancelled': 'badge-dark',
      'completed': 'badge-success'
    };
    return statusMap[status] || 'badge-secondary';
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString();
  }

  viewDetail(companyId: string) {
    this.router.navigate(['/admin/companies', companyId]);
  }

  exportCSV() {
    const companies = this.companies();
    if (companies.length === 0) {
      this.toastService.warning('No Data', 'No companies to export');
      return;
    }

    // CSV headers
    const headers = [
      'Company Name',
      'Email',
      'VAT Number',
      'City',
      'Country',
      'Registration Status',
      'Payment Status',
      'Tenant Schema',
      'Created At'
    ];

    // CSV rows
    const rows = companies.map(company => [
      company.company_name,
      company.email,
      company.vat_number || '',
      company.city,
      company.country,
      company.registration_status,
      company.payment_status,
      company.tenant_schema || '',
      this.formatDate(company.created_at)
    ]);

    // Create CSV content
    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
    ].join('\n');

    // Create blob and download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', `companies_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    this.toastService.success('Export Complete', `Exported ${companies.length} companies to CSV`);
  }
}
