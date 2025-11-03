import { Component, inject, signal, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpParams } from '@angular/common/http';
import { ApiService, API_ENDPOINTS } from '../../../core/services/api.service';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { CompanyDetail as CompanyDetailModel, Transaction, CompanyDetailResponse, CompanyUpdateResponse } from '../../../models';

@Component({
  selector: 'app-company-detail',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './company-detail.html',
  styleUrl: './company-detail.scss',
})
export class CompanyDetail implements OnInit {
  private apiService = inject(ApiService);
  private authService = inject(AuthService);
  private toastService = inject(ToastService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);

  company = signal<CompanyDetailModel | null>(null);
  editedCompany: CompanyDetailModel | null = null;
  transactions = signal<Transaction[]>([]);
  isLoading = signal(true);
  errorMessage = signal('');
  isSaving = signal(false);

  showStatusModal = signal(false);
  selectedStatus = signal('');
  isUpdatingStatus = signal(false);

  statusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'payment_pending', label: 'Payment Pending' },
    { value: 'payment_completed', label: 'Payment Completed' },
    { value: 'active', label: 'Active' },
    { value: 'suspended', label: 'Suspended' },
    { value: 'cancelled', label: 'Cancelled' }
  ];

  ngOnInit() {
    const companyId = this.route.snapshot.paramMap.get('id');
    if (companyId) {
      this.loadCompanyDetail(companyId);
    } else {
      this.router.navigate(['/admin/companies']);
    }
  }

  loadCompanyDetail(companyId: string) {
    this.isLoading.set(true);
    this.errorMessage.set('');

    const params = new HttpParams().set('id', companyId);

    this.apiService.get<CompanyDetailResponse>(API_ENDPOINTS.ADMIN_COMPANY_DETAIL, params).subscribe({
      next: (response) => {
        if (response.success) {
          this.company.set(response.company);
          this.editedCompany = { ...response.company };
          this.transactions.set(response.transactions);
          this.selectedStatus.set(response.company.registration_status);
        } else {
          this.errorMessage.set('Company not found');
        }
        this.isLoading.set(false);
      },
      error: (err) => {
        this.errorMessage.set('Failed to load company details');
        this.isLoading.set(false);
        console.error('Detail error:', err);
      }
    });
  }

  openStatusModal() {
    this.showStatusModal.set(true);
  }

  closeStatusModal() {
    this.showStatusModal.set(false);
    this.selectedStatus.set(this.company()?.registration_status || '');
  }

  updateStatus() {
    const company = this.company();
    if (!company) return;

    this.isUpdatingStatus.set(true);

    const body = {
      company_id: company.id,
      status: this.selectedStatus()
    };

    this.apiService.put<CompanyUpdateResponse>(API_ENDPOINTS.ADMIN_COMPANY_UPDATE_STATUS, body).subscribe({
      next: (response) => {
        if (response.success) {
          this.toastService.success('Status Updated', `Company status changed to ${this.selectedStatus()}`);
          // Update editedCompany as well
          if (this.editedCompany) {
            this.editedCompany.registration_status = this.selectedStatus();
          }
          this.loadCompanyDetail(company.id);
          this.closeStatusModal();
        } else {
          this.toastService.error('Update Failed', response.message || 'Failed to update status');
        }
        this.isUpdatingStatus.set(false);
      },
      error: (err) => {
        this.toastService.error('Error', 'Failed to update company status');
        this.isUpdatingStatus.set(false);
        console.error('Update error:', err);
      }
    });
  }

  saveChanges() {
    if (!this.editedCompany) return;

    this.isSaving.set(true);

    this.apiService.put<CompanyUpdateResponse>(API_ENDPOINTS.ADMIN_COMPANY_UPDATE, this.editedCompany).subscribe({
      next: (response) => {
        if (response.success) {
          this.toastService.success('Salvato', 'Modifiche salvate con successo');
          // Reload data to get fresh data from server
          this.loadCompanyDetail(this.editedCompany!.id);
        } else {
          this.toastService.error('Errore', response.message || 'Impossibile salvare le modifiche');
        }
        this.isSaving.set(false);
      },
      error: (err) => {
        this.toastService.error('Errore', 'Impossibile salvare le modifiche');
        this.isSaving.set(false);
        console.error('Save error:', err);
      }
    });
  }

  cancelChanges() {
    const original = this.company();
    if (original) {
      this.editedCompany = { ...original };
      this.toastService.info('Annullato', 'Modifiche annullate');
    }
  }

  goBack() {
    this.router.navigate(['/admin/dashboard']);
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
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
  }

  formatCurrency(amount: string): string {
    return `€${parseFloat(amount).toFixed(2)}`;
  }
}
