import { Routes } from '@angular/router';
import { Home } from './pages/home/home';
import { PrivacyPolicy } from './pages/privacy-policy/privacy-policy';
import { CookiePolicy } from './pages/cookie-policy/cookie-policy';
import { TermsConditions } from './pages/terms-conditions/terms-conditions';
import { EarlyAdopterProgram } from './components/early-adopter-program/early-adopter-program';

// Company auth
import { Login } from './pages/public/login/login';
import { Register } from './pages/public/register/register';

// Admin imports
import { AdminLogin } from './pages/admin/login/admin-login';
import { AdminDashboard } from './pages/admin/dashboard/admin-dashboard';
import { AdminCompanies } from './pages/admin/companies/admin-companies';
import { CompanyDetail } from './pages/admin/company-detail/company-detail';
import { TenantPool } from './pages/admin/tenant-pool/tenant-pool';
import { adminGuard, adminLoginGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  // Public routes
  { path: '', component: Home },
  { path: 'login', component: Login },
  { path: 'register', component: Register },
  { path: 'early-access', component: EarlyAdopterProgram },
  { path: 'privacy-policy', component: PrivacyPolicy },
  { path: 'cookie-policy', component: CookiePolicy },
  { path: 'terms-conditions', component: TermsConditions },

  // Admin routes
  {
    path: 'admin/login',
    component: AdminLogin,
    canActivate: [adminLoginGuard]
  },
  {
    path: 'admin/dashboard',
    component: AdminDashboard,
    canActivate: [adminGuard]
  },
  {
    path: 'admin/companies',
    component: AdminCompanies,
    canActivate: [adminGuard]
  },
  {
    path: 'admin/companies/:id',
    component: CompanyDetail,
    canActivate: [adminGuard]
  },
  {
    path: 'admin/tenant-pool',
    component: TenantPool,
    canActivate: [adminGuard]
  },

  // Redirect /admin to /admin/dashboard
  { path: 'admin', redirectTo: 'admin/dashboard', pathMatch: 'full' },

  // 404 - Redirect to home
  { path: '**', redirectTo: '' }
];
