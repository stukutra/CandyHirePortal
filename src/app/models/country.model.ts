/**
 * Country Model
 * Model for country data with VAT/tax information
 */

export interface Country {
  code: string;           // ISO 3166-1 alpha-2 code (IT, ES, FR, etc.)
  name: string;           // Country name
  name_it: string;        // Italian name
  name_es: string;        // Spanish name
  name_en: string;        // English name
  has_vat: boolean;       // Does this country use VAT system?
  vat_label: string;      // Label for VAT field (IVA, VAT, etc.)
  requires_sdi: boolean;  // Requires SDI code (Italy only)
  currency: string;       // Currency code (EUR, USD, etc.)
  phone_prefix: string;   // International phone prefix
  is_eu: boolean;         // Is in European Union?
}

export interface CountryListResponse {
  success: boolean;
  countries: Country[];
}
