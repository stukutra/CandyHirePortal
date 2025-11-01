import { TableColumn } from './table-column.model';
import { TableAction } from './table-action.model';

export interface TableFilter {
  key: string;                    // Campo da filtrare
  label: string;                  // Label visibile
  options: string[];              // Opzioni disponibili ['Tutti', 'option1', 'option2']
}

export interface TablePagination {
  totalItems: number;             // Totale elementi
  currentPage: number;            // Pagina corrente (1-based)
  itemsPerPage: number;           // Elementi per pagina
  totalPages?: number;            // Totale pagine (calcolato)
}

export interface TableConfig {
  columns: TableColumn[];         // Definizione colonne
  actions?: TableAction[];        // Azioni disponibili
  searchable?: boolean;           // Mostra search bar? (default: false)
  filterable?: boolean;           // Mostra filtri? (default: false)
  filters?: TableFilter[];        // Definizione filtri
  itemsPerPageOptions?: number[]; // Opzioni per items per pagina (default: [10, 25, 50, 100])
  emptyMessage?: string;          // Messaggio quando non ci sono dati
}

export interface TableSortEvent {
  column: string;
  direction: 'asc' | 'desc';
}

export interface TableActionEvent {
  action: string;
  row: any;
}
