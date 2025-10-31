import { Component, inject, signal } from '@angular/core';
import { RouterOutlet, RouterLink, Router, NavigationEnd } from '@angular/router';
import { CommonModule } from '@angular/common';
import { filter } from 'rxjs/operators';
import { Footer } from './components/footer/footer';
import { ToastContainer } from './components/toast/toast-container';
import { AdminNavbar } from './components/admin-navbar/admin-navbar';

@Component({
  selector: 'app-root',
  imports: [CommonModule, RouterOutlet, RouterLink, Footer, ToastContainer, AdminNavbar],
  templateUrl: './app.html',
  styleUrl: './app.scss'
})
export class App {
  private router = inject(Router);

  isAdminArea = signal(false);
  isAuthPage = signal(false);

  constructor() {
    // Check if current route is admin area or auth page
    this.checkRoutes(this.router.url);

    // Listen to route changes
    this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe((event: any) => {
      this.checkRoutes(event.urlAfterRedirects);
    });
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
