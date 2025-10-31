import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TranslationService, Language } from '../../core/services/translation.service';

@Component({
  selector: 'app-language-switcher',
  imports: [CommonModule],
  templateUrl: './language-switcher.html',
  styleUrl: './language-switcher.scss',
  standalone: true
})
export class LanguageSwitcher {
  translationService = inject(TranslationService);

  languages: { code: Language; label: string; flag: string }[] = [
    { code: 'it', label: 'Italiano', flag: '🇮🇹' },
    { code: 'es', label: 'Español', flag: '🇪🇸' },
    { code: 'en', label: 'English', flag: '🇬🇧' }
  ];

  isOpen = false;

  toggleDropdown(): void {
    this.isOpen = !this.isOpen;
  }

  selectLanguage(lang: Language): void {
    this.translationService.setLanguage(lang);
    this.isOpen = false;
  }

  getCurrentLanguage() {
    return this.languages.find(l => l.code === this.translationService.currentLanguage());
  }
}
