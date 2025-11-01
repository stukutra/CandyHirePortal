import { Component, Input, Output, EventEmitter, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Company } from '../../../models/dashboard.model';

@Component({
  selector: 'app-company-details-drawer',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './company-details-drawer.component.html',
  styleUrls: ['./company-details-drawer.component.scss']
})
export class CompanyDetailsDrawerComponent {
  @Input() company: Company | null = null;
  @Input() isOpen = false;
  @Output() close = new EventEmitter<void>();

  onClose() {
    this.close.emit();
  }

  onOverlayClick(event: MouseEvent) {
    // Close only if clicking on overlay, not on drawer content
    if (event.target === event.currentTarget) {
      this.onClose();
    }
  }

  getStatusBadgeClass(status: string): string {
    const statusMap: Record<string, string> = {
      'active': 'badge-success',
      'payment_pending': 'badge-warning',
      'pending': 'badge-secondary',
      'suspended': 'badge-danger',
      'cancelled': 'badge-dark',
      'completed': 'badge-success',
      'failed': 'badge-danger'
    };
    return statusMap[status] || 'badge-secondary';
  }

  formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
}
