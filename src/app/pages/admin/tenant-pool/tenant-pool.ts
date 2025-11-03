import { Component, inject, signal, OnInit, computed } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../../core/services/auth.service';
import { environment } from '../../../../environments/environment';
import { DataTableComponent } from '../../../shared/components/data-table/data-table.component';
import { TableConfig, TablePagination } from '../../../shared/models/table-config.model';
import { TableColumn } from '../../../shared/models/table-column.model';

interface Tenant {
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
  imports: [CommonModule, DataTableComponent],
  templateUrl: './tenant-pool.html',
  styleUrl: './tenant-pool.scss',
})
export class TenantPool implements OnInit {
  private http = inject(HttpClient);
  private authService = inject(AuthService);

  private apiUrl = environment.apiUrl;

  allTenants = signal<Tenant[]>([]); // All tenants for client-side pagination
  stats = signal({
    total: 0,
    available: 0,
    assigned: 0,
    active: 0
  });
  isLoading = signal(true);
  errorMessage = signal('');

  // Pagination signals
  currentPage = signal(1);
  itemsPerPage = signal(25);

  // Sort signals
  sortColumn = signal<string | null>(null);
  sortDirection = signal<'asc' | 'desc'>('asc');

  // Computed sorted data
  sortedTenants = computed(() => {
    const tenants = [...this.allTenants()];
    const column = this.sortColumn();
    const direction = this.sortDirection();

    if (!column) return tenants;

    return tenants.sort((a, b) => {
      let aValue: any;
      let bValue: any;

      // Get values based on column
      switch (column) {
        case 'id':
          aValue = a.id;
          bValue = b.id;
          break;
        case 'schema_name':
          aValue = a.schema_name;
          bValue = b.schema_name;
          break;
        case 'company_name':
          aValue = a.company?.name || '';
          bValue = b.company?.name || '';
          break;
        case 'assigned_at':
          aValue = a.assigned_at ? new Date(a.assigned_at).getTime() : 0;
          bValue = b.assigned_at ? new Date(b.assigned_at).getTime() : 0;
          break;
        default:
          return 0;
      }

      // Compare values
      if (aValue < bValue) return direction === 'asc' ? -1 : 1;
      if (aValue > bValue) return direction === 'asc' ? 1 : -1;
      return 0;
    });
  });

  // Computed paginated data
  tenants = computed(() => {
    const startIndex = (this.currentPage() - 1) * this.itemsPerPage();
    const endIndex = startIndex + this.itemsPerPage();
    return this.sortedTenants().slice(startIndex, endIndex);
  });

  // Computed pagination object
  pagination = computed<TablePagination>(() => ({
    currentPage: this.currentPage(),
    itemsPerPage: this.itemsPerPage(),
    totalItems: this.sortedTenants().length,
    totalPages: Math.ceil(this.sortedTenants().length / this.itemsPerPage())
  }));

  // Table configuration
  tableConfig: TableConfig = {
    searchable: true,
    filterable: true,
    filters: [
      {
        key: 'status',
        label: 'Status',
        options: ['Tutti', 'Available', 'Assigned', 'Active']
      }
    ],
    itemsPerPageOptions: [25, 50, 100],
    columns: this.getTableColumns(),
    actions: [],
    emptyMessage: 'No tenant databases found'
  };

  ngOnInit() {
    this.loadTenantPool();
  }

  getTableColumns(): TableColumn[] {
    return [
      {
        key: 'id',
        label: 'ID',
        sortable: true,
        type: 'string'
      },
      {
        key: 'schema_name',
        label: 'Schema Name',
        sortable: true,
        type: 'string'
      },
      {
        key: 'status',
        label: 'Status',
        sortable: false,
        type: 'badge',
        badgeClasses: {
          'Available': 'badge-success',
          'Active': 'badge-primary',
          'Assigned (Pending)': 'badge-warning'
        },
        formatter: (row: Tenant) => {
          if (row.is_available) return 'Available';
          if (row.company?.registration_status === 'active') return 'Active';
          return 'Assigned (Pending)';
        }
      },
      {
        key: 'company_name',
        label: 'Company',
        sortable: true,
        type: 'string',
        formatter: (row: Tenant) => row.company?.name || '-'
      },
      {
        key: 'company_email',
        label: 'Email',
        sortable: false,
        type: 'string',
        formatter: (row: Tenant) => row.company?.email || '-'
      },
      {
        key: 'assigned_at',
        label: 'Assigned At',
        sortable: true,
        type: 'date',
        dateFormat: 'dd/MM/yyyy HH:mm',
        formatter: (row: Tenant) => row.assigned_at || '-'
      },
      {
        key: 'payment_status',
        label: 'Payment Status',
        sortable: false,
        type: 'badge',
        badgeClasses: {
          'Paid': 'badge-success',
          'completed': 'badge-success',
          'pending': 'badge-warning',
          '-': 'badge-secondary'
        },
        formatter: (row: Tenant) => {
          if (!row.company) return '-';
          return row.company.payment_status === 'completed' ? 'Paid' : row.company.payment_status;
        }
      }
    ];
  }

  loadTenantPool() {
    this.isLoading.set(true);
    this.errorMessage.set('');

    const headers = this.authService.getAuthHeaders();

    this.http.get<TenantPoolResponse>(`${this.apiUrl}/admin/tenant-pool.php`, { headers }).subscribe({
      next: (response) => {
        if (response.success) {
          this.allTenants.set(response.tenants);
          this.stats.set(response.stats);
          console.log('Loaded tenants:', response.tenants.length);
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

  onPageChange(page: number) {
    console.log('Page changed to:', page);
    this.currentPage.set(page);
  }

  onItemsPerPageChange(itemsPerPage: number) {
    console.log('Items per page changed to:', itemsPerPage);
    this.itemsPerPage.set(itemsPerPage);
    this.currentPage.set(1); // Reset to first page
  }

  onSortChange(event: { column: string; direction: 'asc' | 'desc' }) {
    console.log('Sort changed:', event);
    this.sortColumn.set(event.column);
    this.sortDirection.set(event.direction);
  }

  refresh() {
    this.loadTenantPool();
  }
}
