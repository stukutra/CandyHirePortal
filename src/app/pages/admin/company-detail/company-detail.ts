import { Component, inject, signal, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { environment } from '../../../../environments/environment';

interface CompanyDetailData {
  id: string;
  company_name: string;
  vat_number: string;
  email: string;
  phone: string;
  website: string;
  address: string;
  city: string;
  postal_code: string;
  province: string;
  country: string;
  industry: string;
  employees_count: string;
  description: string;
  legal_rep_first_name: string;
  legal_rep_last_name: string;
  legal_rep_email: string;
  legal_rep_phone: string;
  registration_status: string;
  payment_status: string;
  subscription_plan: string;
  subscription_start_date: string;
  subscription_end_date: string;
  tenant_schema: string;
  tenant_assigned_at: string;
  paypal_subscription_id: string;
  paypal_payer_id: string;
  is_active: boolean;
  email_verified: boolean;
  created_at: string;
  updated_at: string;
  last_login: string;
}

interface Transaction {
  id: string;
  transaction_type: string;
  amount: string;
  currency: string;
  status: string;
  created_at: string;
}

interface DetailResponse {
  success: boolean;
  company: CompanyDetailData;
  transactions: Transaction[];
  activity_logs: any[];
}

@Component({
  selector: 'app-company-detail',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './company-detail.html',
  styleUrl: './company-detail.scss',
})
export class CompanyDetail implements OnInit {
  private http = inject(HttpClient);
  private authService = inject(AuthService);
  private toastService = inject(ToastService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);

  private apiUrl = environment.apiUrl;

  company = signal<CompanyDetailData | null>(null);
  editedCompany: CompanyDetailData | null = null;
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

    const options = this.authService.getAuthOptions();

    this.http.get<DetailResponse>(`${this.apiUrl}/admin/company-detail.php?id=${companyId}`, options).subscribe({
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

    const options = this.authService.getAuthOptions();
    const body = {
      company_id: company.id,
      status: this.selectedStatus()
    };

    this.http.put<any>(`${this.apiUrl}/admin/company-update-status.php`, body, options).subscribe({
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

    const options = this.authService.getAuthOptions();

    this.http.put<any>(`${this.apiUrl}/admin/company-update.php`, this.editedCompany, options).subscribe({
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
