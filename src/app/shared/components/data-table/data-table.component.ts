import { Component, Input, Output, EventEmitter, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TableConfig, TablePagination, TableSortEvent, TableActionEvent } from '../../models/table-config.model';
import { TableColumn } from '../../models/table-column.model';
import { TableAction } from '../../models/table-action.model';

@Component({
  selector: 'app-data-table',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './data-table.component.html',
  styleUrls: ['./data-table.component.scss']
})
export class DataTableComponent {
  @Input() config!: TableConfig;
  @Input() data: any[] = [];
  @Input() pagination!: TablePagination;
  @Input() loading: boolean = false;

  @Output() actionClick = new EventEmitter<TableActionEvent>();
  @Output() pageChange = new EventEmitter<number>();
  @Output() sortChange = new EventEmitter<TableSortEvent>();
  @Output() searchChange = new EventEmitter<string>();
  @Output() filterChange = new EventEmitter<Record<string, string>>();
  @Output() itemsPerPageChange = new EventEmitter<number>();

  // State
  searchText = signal('');
  currentSort = signal<TableSortEvent | null>(null);
  activeFilters = signal<Record<string, string>>({});
  itemsPerPage = signal(25);

  // Computed
  itemsPerPageOptions = computed(() =>
    this.config?.itemsPerPageOptions || [10, 25, 50, 100]
  );

  totalPages = computed(() =>
    Math.ceil((this.pagination?.totalItems || 0) / (this.pagination?.itemsPerPage || 25))
  );

  paginationStart = computed(() =>
    ((this.pagination?.currentPage || 1) - 1) * (this.pagination?.itemsPerPage || 25) + 1
  );

  paginationEnd = computed(() => {
    const end = (this.pagination?.currentPage || 1) * (this.pagination?.itemsPerPage || 25);
    return Math.min(end, this.pagination?.totalItems || 0);
  });

  paginationPages = computed(() => {
    const total = this.totalPages();
    const current = this.pagination?.currentPage || 1;
    const pages: (number | string)[] = [];

    if (total <= 7) {
      // Mostra tutte le pagine se sono poche
      for (let i = 1; i <= total; i++) {
        pages.push(i);
      }
    } else {
      // Mostra prima, ultima e pagine intorno alla corrente
      pages.push(1);

      if (current > 3) {
        pages.push('...');
      }

      for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
        pages.push(i);
      }

      if (current < total - 2) {
        pages.push('...');
      }

      pages.push(total);
    }

    return pages;
  });

  /**
   * Gestisce il click sull'header per ordinamento
   */
  onSort(column: TableColumn): void {
    if (!column.sortable) return;

    const current = this.currentSort();
    let direction: 'asc' | 'desc' = 'asc';

    if (current?.column === column.key) {
      direction = current.direction === 'asc' ? 'desc' : 'asc';
    }

    const sortEvent: TableSortEvent = { column: column.key, direction };
    this.currentSort.set(sortEvent);
    this.sortChange.emit(sortEvent);
  }

  /**
   * Ottiene l'icona per l'ordinamento
   */
  getSortIcon(column: TableColumn): string {
    if (!column.sortable) return '';

    const current = this.currentSort();
    if (current?.column !== column.key) return 'bi-arrow-down-up';

    return current.direction === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down';
  }

  /**
   * Gestisce la ricerca con debounce
   */
  onSearch(event: Event): void {
    const value = (event.target as HTMLInputElement).value;
    this.searchText.set(value);

    // Emetti dopo debounce
    setTimeout(() => {
      if (this.searchText() === value) {
        this.searchChange.emit(value);
      }
    }, 300);
  }

  /**
   * Gestisce il cambio di un filtro
   */
  onFilterChange(filterKey: string, value: string): void {
    const filters = { ...this.activeFilters() };

    if (value === 'Tutti' || !value) {
      delete filters[filterKey];
    } else {
      filters[filterKey] = value;
    }

    this.activeFilters.set(filters);
    this.filterChange.emit(filters);
  }

  /**
   * Gestisce il cambio pagina
   */
  onPageChange(page: number | string): void {
    if (typeof page === 'string') return;
    if (page === this.pagination.currentPage) return;

    this.pageChange.emit(page);
  }

  /**
   * Gestisce il cambio items per pagina
   */
  onItemsPerPageChange(value: number): void {
    this.itemsPerPage.set(value);
    this.itemsPerPageChange.emit(value);
  }

  /**
   * Gestisce il click su un'azione
   */
  onActionClick(action: TableAction, row: any): void {
    this.actionClick.emit({ action: action.emit, row });
  }

  /**
   * Gestisce il toggle switch
   */
  onToggle(action: TableAction, row: any, event: Event): void {
    event.stopPropagation();
    this.actionClick.emit({ action: action.emit, row });
  }

  /**
   * Verifica se un'azione deve essere mostrata per una riga
   */
  shouldShowAction(action: TableAction, row: any): boolean {
    if (!action.condition) return true;
    return action.condition(row);
  }

  /**
   * Ottiene il valore formattato di una cella
   */
  getCellValue(row: any, column: TableColumn): any {
    const value = row[column.key];

    if (value === null || value === undefined) {
      return '-';
    }

    return value;
  }

  /**
   * Ottiene la classe CSS per un badge
   */
  getBadgeClass(column: TableColumn, value: string): string {
    if (!column.badgeClasses) return 'badge-secondary';
    return column.badgeClasses[value] || 'badge-secondary';
  }

  /**
   * Ottiene il formato data
   */
  getDateFormat(column: TableColumn): string {
    return column.dateFormat || 'dd/MM/yyyy HH:mm';
  }

  /**
   * Verifica se un valore booleano è true
   */
  getBooleanValue(value: any): boolean {
    return value === true || value === 1 || value === '1' || value === 'true';
  }
}
