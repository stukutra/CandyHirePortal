/**
 * Subscription Tier Models
 */

export interface TierFeature {
  icon: string;
  title: string;
  description: string;
  isBonus?: boolean;
}

export interface SubscriptionTier {
  id: number;
  name: string;
  slug: string;
  category: string;
  description: string | null;
  price: number;
  currency: string;
  billing_period: 'monthly' | 'yearly' | 'one_time';
  original_price: number | null;
  features: TierFeature[];
  highlights: string[] | null;
  badge_text: string | null;
  badge_icon: string | null;
  is_featured: boolean;
  is_enabled: boolean;
  sort_order: number;
  metadata: any | null;
  created_at: string;
  updated_at: string;
}

export interface SubscriptionTiersResponse {
  success: boolean;
  message?: string;
  data?: {
    tiers?: SubscriptionTier[];
    tier?: SubscriptionTier;
    count?: number;
    total?: number;
  };
  // Legacy flat structure support
  tiers?: SubscriptionTier[];
  tier?: SubscriptionTier;
  count?: number;
  total?: number;
}

export interface CreateTierRequest {
  name: string;
  slug: string;
  category: string;
  description?: string;
  price: number;
  currency?: string;
  billing_period?: 'monthly' | 'yearly' | 'one_time';
  original_price?: number;
  features: TierFeature[];
  highlights?: string[];
  badge_text?: string;
  badge_icon?: string;
  is_featured?: boolean;
  is_enabled?: boolean;
  sort_order?: number;
  metadata?: any;
}

export interface UpdateTierRequest extends Partial<CreateTierRequest> {
  id: number;
}
