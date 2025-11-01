export type ActionType = 'button' | 'toggle';

export interface TableAction {
  type?: ActionType;              // Tipo di azione (default: 'button')
  icon?: string;                  // Icona Bootstrap Icons (es: 'bi-eye')
  label: string;                  // Label visibile
  tooltip?: string;               // Tooltip al hover
  emit: string;                   // Nome evento emesso al parent
  key?: string;                   // Campo da toggleare (per type='toggle')
  buttonClass?: string;           // Classe CSS custom per il bottone
  condition?: (row: any) => boolean;  // Condizione per mostrare/nascondere l'azione
}
