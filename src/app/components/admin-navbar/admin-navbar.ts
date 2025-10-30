import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-admin-navbar',
  standalone: true,
  imports: [CommonModule, RouterLink, RouterLinkActive],
  templateUrl: './admin-navbar.html',
  styleUrl: './admin-navbar.scss',
})
export class AdminNavbar {
  private authService = inject(AuthService);
  private router = inject(Router);

  currentAdmin = this.authService.currentAdmin;
  adminFullName = this.authService.adminFullName;

  logout(): void {
    this.authService.logout();
  }
}
