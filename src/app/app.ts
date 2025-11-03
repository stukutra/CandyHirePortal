import { Component, inject, signal } from '@angular/core';
import { RouterOutlet, RouterLink, Router, NavigationEnd } from '@angular/router';
import { CommonModule } from '@angular/common';
import { filter } from 'rxjs/operators';
import { Footer } from './components/footer/footer';
import { ToastContainer } from './components/toast/toast-container';
import { AdminNavbar } from './components/admin-navbar/admin-navbar';
import { LanguageSwitcher } from './components/language-switcher/language-switcher';
import { TranslationService } from './core/services/translation.service';
import { environment } from '../environments/environment';
import { it } from './i18n/it';
import { es } from './i18n/es';
import { en } from './i18n/en';

@Component({
  selector: 'app-root',
  imports: [CommonModule, RouterOutlet, RouterLink, Footer, ToastContainer, AdminNavbar, LanguageSwitcher],
  templateUrl: './app.html',
  styleUrl: './app.scss'
})
export class App {
  private router = inject(Router);
  private translationService = inject(TranslationService);

  isAdminArea = signal(false);
  isAuthPage = signal(false);
  saasUrl = environment.saasUrl || 'http://localhost:4202';

  constructor() {
    // Initialize translations
    this.translationService.setTranslations('it', it);
    this.translationService.setTranslations('es', es);
    this.translationService.setTranslations('en', en);

    // Check if current route is admin area or auth page
    this.checkRoutes(this.router.url);

    // Listen to route changes
    this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe((event: any) => {
      this.checkRoutes(event.urlAfterRedirects);
    });
  }

  t(key: string): string {
    return this.translationService.t(key);
  }

  private checkRoutes(url: string): void {
    // Show admin navbar only in admin area but NOT on login page
    const isAdmin = url.startsWith('/admin') && !url.startsWith('/admin/login');
    this.isAdminArea.set(isAdmin);

    // Check if we're on login or register page
    const isAuth = url === '/login' || url === '/register' || url.startsWith('/login') || url.startsWith('/register');
    this.isAuthPage.set(isAuth);
  }

  private checkIfAdminArea(url: string): void {
    // Deprecated: use checkRoutes instead
    this.checkRoutes(url);
  }
}
