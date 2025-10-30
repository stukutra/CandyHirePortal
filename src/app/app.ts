import { Component, inject, signal } from '@angular/core';
import { RouterOutlet, RouterLink, Router, NavigationEnd } from '@angular/router';
import { CommonModule } from '@angular/common';
import { filter } from 'rxjs/operators';
import { Footer } from './components/footer/footer';
import { WaitlistModal } from './components/waitlist-modal/waitlist-modal';
import { WaitlistModalService } from './services/waitlist-modal';
import { ToastContainer } from './components/toast/toast-container';
import { AdminNavbar } from './components/admin-navbar/admin-navbar';

@Component({
  selector: 'app-root',
  imports: [CommonModule, RouterOutlet, RouterLink, Footer, WaitlistModal, ToastContainer, AdminNavbar],
  templateUrl: './app.html',
  styleUrl: './app.scss'
})
export class App {
  protected modalService = inject(WaitlistModalService);
  private router = inject(Router);

  isAdminArea = signal(false);

  constructor() {
    // Check if current route is admin area
    this.checkIfAdminArea(this.router.url);

    // Listen to route changes
    this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe((event: any) => {
      this.checkIfAdminArea(event.urlAfterRedirects);
    });
  }

  private checkIfAdminArea(url: string): void {
    this.isAdminArea.set(url.startsWith('/admin'));
  }

  openWaitlistModal(): void {
    this.modalService.open();
  }

  closeWaitlistModal(): void {
    this.modalService.close();
  }
}
