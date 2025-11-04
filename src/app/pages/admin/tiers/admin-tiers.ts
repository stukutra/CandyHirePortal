import { Component, inject, signal, computed, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService, API_ENDPOINTS } from '../../../core/services/api.service';
import { ToastService } from '../../../core/services/toast.service';
import { SubscriptionTier, SubscriptionTiersResponse, CreateTierRequest, UpdateTierRequest } from '../../../models';
import { DataTableComponent } from '../../../shared/components/data-table/data-table.component';
import { TableConfig, TablePagination } from '../../../shared/models/table-config.model';
import { TableColumn } from '../../../shared/models/table-column.model';
import { TableAction } from '../../../shared/models/table-action.model';

@Component({
  selector: 'app-admin-tiers',
  standalone: true,
  imports: [CommonModule, FormsModule, DataTableComponent],
  templateUrl: './admin-tiers.html',
  styleUrl: './admin-tiers.scss',
})
export class AdminTiers implements OnInit {
  private apiService = inject(ApiService);
  private toastService = inject(ToastService);
  private router = inject(Router);

  allTiers = signal<SubscriptionTier[]>([]);
  isLoading = signal(true);
  errorMessage = signal('');

  // Computed stats for template
  enabledCount = computed(() => this.allTiers().filter(t => t.is_enabled).length);
  featuredCount = computed(() => this.allTiers().filter(t => t.is_featured).length);

  // Modal state
  showModal = signal(false);
  modalMode = signal<'add' | 'edit' | 'view'>('add');
  selectedTier = signal<SubscriptionTier | null>(null);

  // Form data
  tierForm = signal<Partial<CreateTierRequest>>({
    name: '',
    slug: '',
    category: '',
    description: '',
    price: 0,
    currency: 'EUR',
    billing_period: 'yearly',
    original_price: undefined,
    features: [],
    highlights: [],
    badge_text: '',
    badge_icon: '',
    is_featured: false,
    is_enabled: true,
    sort_order: 0
  });

  // Pagination signals
  currentPage = signal(1);
  itemsPerPage = signal(25);

  // Sort signals
  sortColumn = signal<string | null>(null);
  sortDirection = signal<'asc' | 'desc'>('asc');

  // Filter signals
  filterEnabled = signal<boolean | null>(null);
  searchQuery = signal('');

  // Computed filtered data
  filteredTiers = computed(() => {
    let tiers = [...this.allTiers()];
    const enabled = this.filterEnabled();
    const search = this.searchQuery().toLowerCase();

    // Apply enabled filter
    if (enabled !== null) {
      tiers = tiers.filter(t => t.is_enabled === enabled);
    }

    // Apply search filter
    if (search) {
      tiers = tiers.filter(t =>
        t.name.toLowerCase().includes(search) ||
        t.category.toLowerCase().includes(search) ||
        t.slug.toLowerCase().includes(search)
      );
    }

    return tiers;
  });

  // Computed sorted data
  sortedTiers = computed(() => {
    const tiers = [...this.filteredTiers()];
    const column = this.sortColumn();
    const direction = this.sortDirection();

    if (!column) return tiers;

    return tiers.sort((a, b) => {
      let aValue: any = (a as any)[column];
      let bValue: any = (b as any)[column];

      if (aValue === null || aValue === undefined) aValue = '';
      if (bValue === null || bValue === undefined) bValue = '';

      if (aValue < bValue) return direction === 'asc' ? -1 : 1;
      if (aValue > bValue) return direction === 'asc' ? 1 : -1;
      return 0;
    });
  });

  // Computed paginated data
  tiers = computed(() => {
    const startIndex = (this.currentPage() - 1) * this.itemsPerPage();
    const endIndex = startIndex + this.itemsPerPage();
    return this.sortedTiers().slice(startIndex, endIndex);
  });

  // Computed pagination object
  pagination = computed<TablePagination>(() => ({
    currentPage: this.currentPage(),
    itemsPerPage: this.itemsPerPage(),
    totalItems: this.sortedTiers().length,
    totalPages: Math.ceil(this.sortedTiers().length / this.itemsPerPage())
  }));

  // Table configuration
  tableConfig: TableConfig = {
    searchable: true,
    filterable: true,
    filters: [
      {
        key: 'enabled',
        label: 'Status',
        options: ['All', 'Enabled', 'Disabled']
      }
    ],
    itemsPerPageOptions: [10, 25, 50, 100],
    columns: this.getTableColumns(),
    actions: this.getTableActions(),
    emptyMessage: 'No subscription tiers found'
  };

  ngOnInit() {
    this.loadTiers();
  }

  getTableColumns(): TableColumn[] {
    return [
      {
        key: 'sort_order',
        label: 'Order',
        sortable: true,
        type: 'string'
      },
      {
        key: 'name',
        label: 'Name',
        sortable: true,
        type: 'string'
      },
      {
        key: 'category',
        label: 'Category',
        sortable: true,
        type: 'string'
      },
      {
        key: 'price',
        label: 'Price',
        sortable: true,
        type: 'string',
        formatter: (row: SubscriptionTier) => `€${row.price.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}/${row.billing_period === 'yearly' ? 'year' : 'month'}`
      },
      {
        key: 'is_featured',
        label: 'Featured',
        sortable: true,
        type: 'badge',
        badgeClasses: {
          'Yes': 'badge-warning',
          'No': 'badge-secondary'
        },
        formatter: (row: SubscriptionTier) => row.is_featured ? 'Yes' : 'No'
      },
      {
        key: 'features_count',
        label: 'Features',
        sortable: false,
        type: 'string',
        formatter: (row: SubscriptionTier) => `${row.features.length} features`
      }
    ];
  }

  getTableActions(): TableAction[] {
    return [
      {
        label: 'Enable/Disable',
        type: 'toggle',
        emit: 'toggle-status',
        key: 'is_enabled',
        tooltip: 'Enable or disable this tier'
      },
      {
        label: 'View',
        icon: 'bi-eye',
        type: 'button',
        emit: 'view',
        buttonClass: 'btn-info'
      },
      {
        label: 'Edit',
        icon: 'bi-pencil',
        type: 'button',
        emit: 'edit',
        buttonClass: 'btn-primary'
      },
      {
        label: 'Duplicate',
        icon: 'bi-files',
        type: 'button',
        emit: 'duplicate',
        buttonClass: 'btn-warning'
      },
      {
        label: 'Delete',
        icon: 'bi-trash',
        type: 'button',
        emit: 'delete',
        buttonClass: 'btn-danger'
      }
    ];
  }

  loadTiers() {
    this.isLoading.set(true);
    this.errorMessage.set('');

    this.apiService.get<SubscriptionTiersResponse>(API_ENDPOINTS.ADMIN_TIERS_LIST).subscribe({
      next: (response) => {
        console.log('Tiers API response:', response);
        if (response.success) {
          // Support both data wrapper and flat structure
          const tiers = response.data?.tiers || response.tiers || [];
          this.allTiers.set(tiers);
          console.log('Loaded tiers:', tiers.length);
        } else {
          this.errorMessage.set(response.message || 'Failed to load subscription tiers');
        }
        this.isLoading.set(false);
      },
      error: (err) => {
        this.errorMessage.set('Failed to connect to API');
        this.isLoading.set(false);
        console.error('Tiers error:', err);
      }
    });
  }

  onTableAction(event: { action: string; row: SubscriptionTier }) {
    switch (event.action) {
      case 'toggle-status':
        this.toggleTierStatus(event.row);
        break;
      case 'view':
        this.viewTier(event.row);
        break;
      case 'edit':
        this.editTier(event.row);
        break;
      case 'duplicate':
        this.duplicateTier(event.row);
        break;
      case 'delete':
        this.deleteTier(event.row);
        break;
    }
  }

  toggleTierStatus(tier: SubscriptionTier) {
    this.apiService.post<SubscriptionTiersResponse>(API_ENDPOINTS.ADMIN_TIERS_TOGGLE_STATUS, { id: tier.id }).subscribe({
      next: (response) => {
        if (response.success) {
          const status = tier.is_enabled ? 'disabled' : 'enabled';
          this.toastService.success('Success', `Tier ${status} successfully`);
          this.loadTiers();
        } else {
          this.toastService.error('Error', response.message || 'Failed to toggle tier status');
        }
      },
      error: (err) => {
        this.toastService.error('Error', 'Failed to toggle tier status');
        console.error('Toggle tier status error:', err);
      }
    });
  }

  viewTier(tier: SubscriptionTier) {
    this.selectedTier.set(tier);
    this.modalMode.set('view');
    this.showModal.set(true);
  }

  editTier(tier: SubscriptionTier) {
    this.selectedTier.set(tier);
    this.tierForm.set({
      name: tier.name,
      slug: tier.slug,
      category: tier.category,
      description: tier.description || '',
      price: tier.price,
      currency: tier.currency,
      billing_period: tier.billing_period,
      original_price: tier.original_price || undefined,
      features: tier.features,
      highlights: tier.highlights || [],
      badge_text: tier.badge_text || '',
      badge_icon: tier.badge_icon || '',
      is_featured: tier.is_featured,
      is_enabled: tier.is_enabled,
      sort_order: tier.sort_order
    });
    this.modalMode.set('edit');
    this.showModal.set(true);
  }

  addTier() {
    this.selectedTier.set(null);
    this.tierForm.set({
      name: '',
      slug: '',
      category: '',
      description: '',
      price: 0,
      currency: 'EUR',
      billing_period: 'yearly',
      original_price: undefined,
      features: [],
      highlights: [],
      badge_text: '',
      badge_icon: '',
      is_featured: false,
      is_enabled: true,
      sort_order: 0
    });
    this.modalMode.set('add');
    this.showModal.set(true);
  }

  saveTier() {
    const mode = this.modalMode();
    const formData = this.tierForm();

    if (mode === 'add') {
      this.createTier(formData as CreateTierRequest);
    } else if (mode === 'edit') {
      const updateData: UpdateTierRequest = {
        id: this.selectedTier()!.id,
        ...formData
      };
      this.updateTier(updateData);
    }
  }

  createTier(data: CreateTierRequest) {
    this.apiService.post<SubscriptionTiersResponse>(API_ENDPOINTS.ADMIN_TIERS_CREATE, data).subscribe({
      next: (response) => {
        if (response.success) {
          this.toastService.success('Success', 'Tier created successfully');
          this.showModal.set(false);
          this.loadTiers();
        } else {
          this.toastService.error('Error', response.message || 'Failed to create tier');
        }
      },
      error: (err) => {
        this.toastService.error('Error', 'Failed to create tier');
        console.error('Create tier error:', err);
      }
    });
  }

  updateTier(data: UpdateTierRequest) {
    this.apiService.put<SubscriptionTiersResponse>(API_ENDPOINTS.ADMIN_TIERS_UPDATE, data).subscribe({
      next: (response) => {
        if (response.success) {
          this.toastService.success('Success', 'Tier updated successfully');
          this.showModal.set(false);
          this.loadTiers();
        } else {
          this.toastService.error('Error', response.message || 'Failed to update tier');
        }
      },
      error: (err) => {
        this.toastService.error('Error', 'Failed to update tier');
        console.error('Update tier error:', err);
      }
    });
  }

  duplicateTier(tier: SubscriptionTier) {
    if (!confirm(`Duplicate tier "${tier.name}"? The copy will be disabled by default.`)) {
      return;
    }

    this.apiService.post<SubscriptionTiersResponse>(API_ENDPOINTS.ADMIN_TIERS_DUPLICATE, { id: tier.id }).subscribe({
      next: (response) => {
        if (response.success) {
          this.toastService.success('Success', 'Tier duplicated successfully');
          this.loadTiers();
        } else {
          this.toastService.error('Error', response.message || 'Failed to duplicate tier');
        }
      },
      error: (err) => {
        this.toastService.error('Error', 'Failed to duplicate tier');
        console.error('Duplicate tier error:', err);
      }
    });
  }

  deleteTier(tier: SubscriptionTier) {
    if (!confirm(`Are you sure you want to delete "${tier.name}"? This action cannot be undone.`)) {
      return;
    }

    this.apiService.delete<SubscriptionTiersResponse>(`${API_ENDPOINTS.ADMIN_TIERS_DELETE}?id=${tier.id}`).subscribe({
      next: (response) => {
        if (response.success) {
          this.toastService.success('Success', 'Tier deleted successfully');
          this.loadTiers();
        } else {
          this.toastService.error('Error', response.message || 'Failed to delete tier');
        }
      },
      error: (err) => {
        this.toastService.error('Error', 'Failed to delete tier');
        console.error('Delete tier error:', err);
      }
    });
  }

  closeModal() {
    this.showModal.set(false);
  }

  onPageChange(page: number) {
    this.currentPage.set(page);
  }

  onItemsPerPageChange(itemsPerPage: number) {
    this.itemsPerPage.set(itemsPerPage);
    this.currentPage.set(1);
  }

  onSortChange(event: { column: string; direction: 'asc' | 'desc' }) {
    this.sortColumn.set(event.column);
    this.sortDirection.set(event.direction);
  }

  onSearchChange(search: string) {
    this.searchQuery.set(search);
    this.currentPage.set(1);
  }

  onFilterChange(filters: Record<string, string>) {
    if (filters['enabled']) {
      if (filters['enabled'] === 'Enabled') {
        this.filterEnabled.set(true);
      } else if (filters['enabled'] === 'Disabled') {
        this.filterEnabled.set(false);
      } else {
        this.filterEnabled.set(null);
      }
    } else {
      this.filterEnabled.set(null);
    }
    this.currentPage.set(1);
  }

  addFeature() {
    const currentForm = this.tierForm();
    const newFeature = {
      icon: 'bi-check-circle',
      title: '',
      description: '',
      isBonus: false
    };

    this.tierForm.set({
      ...currentForm,
      features: [...(currentForm.features || []), newFeature]
    });
  }

  removeFeature(index: number) {
    const currentForm = this.tierForm();
    const features = [...(currentForm.features || [])];
    features.splice(index, 1);

    this.tierForm.set({
      ...currentForm,
      features: features
    });
  }

  refresh() {
    this.loadTiers();
  }

  goToDashboard() {
    this.router.navigate(['/admin/dashboard']);
  }
}
