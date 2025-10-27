import { Routes } from '@angular/router';
import { Home } from './pages/home/home';
import { PrivacyPolicy } from './pages/privacy-policy/privacy-policy';
import { CookiePolicy } from './pages/cookie-policy/cookie-policy';
import { TermsConditions } from './pages/terms-conditions/terms-conditions';
import { EarlyAdopterProgram } from './components/early-adopter-program/early-adopter-program';

export const routes: Routes = [
  { path: '', component: Home },
  { path: 'early-access', component: EarlyAdopterProgram },
  { path: 'privacy-policy', component: PrivacyPolicy },
  { path: 'cookie-policy', component: CookiePolicy },
  { path: 'terms-conditions', component: TermsConditions }
];
