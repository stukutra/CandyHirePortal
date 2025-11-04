import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { ApiService, API_ENDPOINTS } from '../../core/services/api.service';
import { TranslationService } from '../../core/services/translation.service';
import { SubscriptionTier, SubscriptionTiersResponse } from '../../models';

@Component({
  selector: 'app-tiers-display',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './tiers-display.html',
  styleUrl: './tiers-display.scss',
})
export class TiersDisplay implements OnInit {
  private apiService = inject(ApiService);
  protected translationService = inject(TranslationService);
  private router = inject(Router);

  tiers = signal<SubscriptionTier[]>([]);
  isLoading = signal(true);
  errorMessage = signal('');

  ngOnInit() {
    this.loadTiers();
  }

  loadTiers() {
    this.isLoading.set(true);
    this.errorMessage.set('');

    this.apiService.get<SubscriptionTiersResponse>(API_ENDPOINTS.PUBLIC_TIERS_LIST).subscribe({
      next: (response) => {
        if (response.success) {
          // Support both data wrapper and flat structure
          const tiers = response.data?.tiers || response.tiers || [];
          // Sort by sort_order and featured status
          const sortedTiers = tiers.sort((a, b) => {
            if (a.is_featured && !b.is_featured) return -1;
            if (!a.is_featured && b.is_featured) return 1;
            return a.sort_order - b.sort_order;
          });
          this.tiers.set(sortedTiers);
        } else {
          this.errorMessage.set('Unable to load pricing information');
        }
        this.isLoading.set(false);
      },
      error: (err) => {
        console.error('Error loading tiers:', err);
        this.errorMessage.set('Unable to load pricing information');
        this.isLoading.set(false);
      }
    });
  }

  goToRegister(tier?: SubscriptionTier): void {
    if (tier) {
      // Navigate with tier slug as query parameter
      this.router.navigate(['/register'], { queryParams: { tier: tier.slug } });
    } else {
      this.router.navigate(['/register']);
    }
  }

  formatPrice(tier: SubscriptionTier): string {
    return `€${tier.price.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  }

  formatOriginalPrice(tier: SubscriptionTier): string {
    if (!tier.original_price) return '';
    return `€${tier.original_price.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  }

  formatPeriod(tier: SubscriptionTier): string {
    switch (tier.billing_period) {
      case 'yearly':
        return '/anno';
      case 'monthly':
        return '/mese';
      case 'one_time':
        return 'una tantum';
      default:
        return '';
    }
  }

  hasDiscount(tier: SubscriptionTier): boolean {
    return tier.original_price !== null && tier.original_price > tier.price;
  }

  calculateSavings(tier: SubscriptionTier): string {
    if (!this.hasDiscount(tier) || !tier.original_price) return '';
    const savings = tier.original_price - tier.price;
    const percentage = Math.round((savings / tier.original_price) * 100);
    return `Risparmi €${savings.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (${percentage}%)`;
  }

  t(key: string): string {
    return this.translationService.t(key);
  }
}
