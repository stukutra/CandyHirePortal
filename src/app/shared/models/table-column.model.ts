export type ColumnType = 'string' | 'date' | 'currency' | 'badge' | 'boolean' | 'image';

export interface TableColumn {
  key: string;                    // Campo del model da visualizzare
  label: string;                  // Header della colonna
  type: ColumnType;               // Tipo di dato
  sortable: boolean;              // Può essere ordinata?
  width?: string;                 // Larghezza opzionale (es: '200px', '15%')
  align?: 'left' | 'center' | 'right';  // Allineamento testo
  badgeClasses?: Record<string, string>;  // Mapping valore → classe CSS per type='badge'
  dateFormat?: string;            // Formato custom per date (default: 'dd/MM/yyyy HH:mm')
}
