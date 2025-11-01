import { Component, inject, signal, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { environment } from '../../../../environments/environment';
import { DashboardStats, Company, DashboardResponse } from '../../../models/dashboard.model';
import { DataTableComponent } from '../../../shared/components/data-table/data-table.component';
import { CompanyDetailsDrawerComponent } from '../../../shared/components/company-details-drawer/company-details-drawer.component';
import { TableConfig, TablePagination, TableActionEvent, TableSortEvent } from '../../../shared/models/table-config.model';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, DataTableComponent, CompanyDetailsDrawerComponent],
  templateUrl: './admin-dashboard.html',
  styleUrl: './admin-dashboard.scss',
})
export class AdminDashboard implements OnInit {
  private http = inject(HttpClient);
  private authService = inject(AuthService);
  private router = inject(Router);
  private toastService = inject(ToastService);

  private apiUrl = environment.apiUrl;

  stats = signal<DashboardStats | null>(null);
  latestCompanies = signal<Company[]>([]);
  isLoading = signal(true);
  errorMessage = signal('');

  currentAdmin = this.authService.currentAdmin;
  adminFullName = this.authService.adminFullName;

  // Table configuration
  tableConfig: TableConfig = {
    columns: [
      { key: 'company_name', label: 'Azienda', type: 'string', sortable: true, width: '200px' },
      { key: 'legal_rep_first_name', label: 'Rappresentante Legale', type: 'string', sortable: false },
      { key: 'email', label: 'Email', type: 'string', sortable: true },
      { key: 'phone', label: 'Telefono', type: 'string', sortable: false },
      { key: 'vat_number', label: 'P.IVA', type: 'string', sortable: false },
      { key: 'city', label: 'Città', type: 'string', sortable: true },
      {
        key: 'subscription_plan',
        label: 'Piano',
        type: 'badge',
        sortable: true,
        badgeClasses: {
          'Basic': 'badge-secondary',
          'Pro': 'badge-info',
          'Enterprise': 'badge-primary'
        }
      },
      {
        key: 'registration_status',
        label: 'Stato Reg.',
        type: 'badge',
        sortable: true,
        badgeClasses: {
          'active': 'badge-success',
          'payment_pending': 'badge-warning',
          'pending': 'badge-secondary',
          'suspended': 'badge-danger',
          'cancelled': 'badge-dark'
        }
      },
      {
        key: 'payment_status',
        label: 'Pagamento',
        type: 'badge',
        sortable: true,
        badgeClasses: {
          'completed': 'badge-success',
          'pending': 'badge-warning',
          'failed': 'badge-danger'
        }
      },
      { key: 'created_at', label: 'Registrato il', type: 'date', sortable: true }
    ],
    actions: [
      { icon: 'bi-eye', label: 'View', tooltip: 'Visualizza dettagli', emit: 'view', buttonClass: 'btn-outline-primary' },
      { icon: 'bi-pencil', label: 'Edit', tooltip: 'Modifica', emit: 'edit', buttonClass: 'btn-outline-secondary' },
      { type: 'toggle', key: 'is_active', label: 'Toggle Active', emit: 'toggleActive', tooltip: 'Attiva/Disattiva account' }
    ],
    searchable: true,
    filterable: true,
    filters: [
      { key: 'registration_status', label: 'Stato', options: ['Tutti', 'pending', 'payment_pending', 'active', 'suspended'] },
      { key: 'payment_status', label: 'Pagamento', options: ['Tutti', 'pending', 'completed', 'failed'] },
      { key: 'subscription_plan', label: 'Piano', options: ['Tutti', 'Basic', 'Pro', 'Enterprise'] }
    ],
    emptyMessage: 'Nessuna azienda registrata'
  };

  tablePagination: TablePagination = {
    totalItems: 0,
    currentPage: 1,
    itemsPerPage: 10
  };

  // State per filtri e ordinamento
  currentFilters = signal<Record<string, string>>({});
  currentSearch = signal('');
  currentSort = signal<TableSortEvent | null>(null);

  // Drawer state
  selectedCompany = signal<Company | null>(null);
  drawerOpen = signal(false);

  ngOnInit() {
    this.loadDashboardData();
  }

  loadDashboardData() {
    this.isLoading.set(true);
    this.errorMessage.set('');

    const headers = this.authService.getAuthHeaders();

    // Build query params
    const params: any = {
      page: this.tablePagination.currentPage,
      limit: this.tablePagination.itemsPerPage
    };

    if (this.currentSearch()) {
      params.search = this.currentSearch();
    }

    if (this.currentSort()) {
      params.sort = this.currentSort()!.column;
      params.order = this.currentSort()!.direction;
    }

    // Add filters
    Object.keys(this.currentFilters()).forEach(key => {
      params[key] = this.currentFilters()[key];
    });

    this.http.get<DashboardResponse>(`${this.apiUrl}/admin/dashboard-stats.php`, { headers, params }).subscribe({
      next: (response) => {
        if (response.success) {
          this.stats.set(response.stats);
          this.latestCompanies.set(response.latest_companies);

          // Update pagination if backend provides it
          if (response.pagination) {
            this.tablePagination = {
              ...this.tablePagination,
              totalItems: response.pagination.total_items,
              totalPages: response.pagination.total_pages
            };
          } else {
            // For now, use companies length
            this.tablePagination.totalItems = response.latest_companies.length;
          }
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

  onTableAction(event: TableActionEvent) {
    const { action, row } = event;

    switch (action) {
      case 'view':
        this.viewCompany(row);
        break;
      case 'edit':
        this.editCompany(row);
        break;
      case 'toggleActive':
        this.toggleActiveStatus(row);
        break;
    }
  }

  viewCompany(company: Company) {
    this.selectedCompany.set(company);
    this.drawerOpen.set(true);
  }

  onCloseDrawer() {
    this.drawerOpen.set(false);
    // Delay clearing selected company to allow animation to complete
    setTimeout(() => {
      this.selectedCompany.set(null);
    }, 300);
  }

  editCompany(company: Company) {
    console.log('Edit company:', company);
    // TODO: Aprire modal edit
    this.router.navigate(['/admin/companies', company.id, 'edit']);
  }

  toggleActiveStatus(company: Company) {
    const headers = this.authService.getAuthHeaders();
    const newStatus = !company.is_active;

    this.http.put<any>(
      `${this.apiUrl}/admin/companies/${company.id}/toggle-active`,
      {},
      { headers }
    ).subscribe({
      next: (response) => {
        if (response.success) {
          // Update local data
          const companies = this.latestCompanies();
          const index = companies.findIndex(c => c.id === company.id);
          if (index !== -1) {
            companies[index].is_active = response.is_active;
            this.latestCompanies.set([...companies]);
          }

          this.toastService.success(
            'Operazione completata',
            `Azienda ${company.company_name} ${newStatus ? 'attivata' : 'disattivata'} con successo`
          );
        }
      },
      error: (err) => {
        console.error('Toggle active error:', err);
        this.toastService.error(
          'Errore',
          'Errore durante l\'aggiornamento dello stato dell\'azienda'
        );
      }
    });
  }

  onPageChange(page: number) {
    this.tablePagination.currentPage = page;
    this.loadDashboardData();
  }

  onSort(sort: TableSortEvent) {
    this.currentSort.set(sort);
    this.tablePagination.currentPage = 1;
    this.loadDashboardData();
  }

  onSearch(searchText: string) {
    this.currentSearch.set(searchText);
    this.tablePagination.currentPage = 1;
    this.loadDashboardData();
  }

  onFilterChange(filters: Record<string, string>) {
    this.currentFilters.set(filters);
    this.tablePagination.currentPage = 1;
    this.loadDashboardData();
  }

  onItemsPerPageChange(itemsPerPage: number) {
    this.tablePagination.itemsPerPage = itemsPerPage;
    this.tablePagination.currentPage = 1;
    this.loadDashboardData();
  }

  goToCompanies() {
    this.router.navigate(['/admin/companies']);
  }
}
