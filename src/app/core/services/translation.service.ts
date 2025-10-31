import { Injectable, signal, PLATFORM_ID, inject } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';

export type Language = 'it' | 'es' | 'en';

export interface Translations {
  [key: string]: string | Translations;
}

@Injectable({
  providedIn: 'root'
})
export class TranslationService {
  private platformId = inject(PLATFORM_ID);
  private isBrowser = isPlatformBrowser(this.platformId);

  private translations: Record<Language, Translations> = {
    it: {},
    es: {},
    en: {}
  };

  currentLanguage = signal<Language>('it');

  constructor() {
    // Only access localStorage in browser environment
    if (this.isBrowser) {
      // Load saved language from localStorage or detect browser language
      const savedLanguage = localStorage.getItem('candyhire_language') as Language;
      if (savedLanguage && ['it', 'es', 'en'].includes(savedLanguage)) {
        this.currentLanguage.set(savedLanguage);
      } else {
        // Detect browser language
        const browserLang = navigator.language.split('-')[0];
        if (browserLang === 'it' || browserLang === 'es' || browserLang === 'en') {
          this.currentLanguage.set(browserLang as Language);
        }
      }
    }
  }

  setLanguage(lang: Language): void {
    this.currentLanguage.set(lang);
    if (this.isBrowser) {
      localStorage.setItem('candyhire_language', lang);
    }
  }

  setTranslations(lang: Language, translations: Translations): void {
    this.translations[lang] = translations;
  }

  translate(key: string): string {
    const keys = key.split('.');
    let value: any = this.translations[this.currentLanguage()];

    for (const k of keys) {
      if (value && typeof value === 'object' && k in value) {
        value = value[k];
      } else {
        return key; // Return key if translation not found
      }
    }

    return typeof value === 'string' ? value : key;
  }

  t(key: string): string {
    return this.translate(key);
  }
}
