import { RenderMode, ServerRoute } from '@angular/ssr';

export const serverRoutes: ServerRoute[] = [
  // Admin routes - Server-side rendering only (require authentication)
  {
    path: 'admin/**',
    renderMode: RenderMode.Server
  },
  // Company auth routes - Server-side rendering only (authentication flows)
  {
    path: 'login',
    renderMode: RenderMode.Server
  },
  {
    path: 'register',
    renderMode: RenderMode.Server
  },
  // Public static pages - Prerender for SEO and performance
  {
    path: '',
    renderMode: RenderMode.Prerender
  },
  {
    path: 'privacy-policy',
    renderMode: RenderMode.Prerender
  },
  {
    path: 'cookie-policy',
    renderMode: RenderMode.Prerender
  },
  {
    path: 'terms-conditions',
    renderMode: RenderMode.Prerender
  },
  {
    path: 'early-access',
    renderMode: RenderMode.Prerender
  },
  // Catch-all for any other routes - Server-side rendering
  {
    path: '**',
    renderMode: RenderMode.Server
  }
];
