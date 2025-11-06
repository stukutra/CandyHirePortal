import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { catchError } from 'rxjs/operators';
import { throwError } from 'rxjs';
import { ToastService } from '../services/toast.service';

/**
 * HTTP Interceptor that shows toast notifications for HTTP operations
 * - Shows error toasts for all HTTP errors (unless explicitly handled by the component)
 * - Does NOT show automatic success toasts (services should handle success messages explicitly)
 */
export const httpToastInterceptor: HttpInterceptorFn = (req, next) => {
  const toastService = inject(ToastService);

  // Skip toast for specific endpoints (e.g., health checks, polling endpoints)
  const skipToastUrls = [
    '/health-check',
    '/ping',
    '/assets/',
    '/i18n/'
  ];

  const shouldSkipToast = skipToastUrls.some(url => req.url.includes(url));

  return next(req).pipe(
    catchError((error: HttpErrorResponse) => {
      // Show error toast for all errors (unless in skip list)
      if (!shouldSkipToast) {
        let errorMessage = 'Si � verificato un errore';
        let errorTitle = 'Errore';

        // Extract error message from response (avoiding ErrorEvent check for SSR compatibility)
        const response = error.error as any;

        // Try to extract error message from response
        if (response?.message) {
          errorMessage = response.message;
        } else if (response?.error) {
          errorMessage = response.error;
        } else if (typeof response === 'string' && response.length > 0 && !response.includes('<!DOCTYPE')) {
          // Only use string if it's not HTML
          errorMessage = response;
        } else {
          // Default messages based on status code (in Italian for Portal)
          switch (error.status) {
              case 0:
                errorMessage = 'Impossibile connettersi al server. Controlla la tua connessione internet.';
                errorTitle = 'Errore di Connessione';
                break;
              case 400:
                errorMessage = 'Richiesta non valida. Controlla i dati inseriti.';
                errorTitle = 'Richiesta Non Valida';
                break;
              case 401:
                errorMessage = 'Non sei autorizzato. Effettua il login.';
                errorTitle = 'Non Autorizzato';
                break;
              case 403:
                errorMessage = 'Non hai i permessi per eseguire questa azione.';
                errorTitle = 'Accesso Negato';
                break;
              case 404:
                errorMessage = 'La risorsa richiesta non � stata trovata.';
                errorTitle = 'Non Trovato';
                break;
              case 409:
                errorMessage = 'Conflitto con i dati esistenti.';
                errorTitle = 'Conflitto';
                break;
              case 422:
                errorMessage = 'I dati forniti non sono validi.';
                errorTitle = 'Dati Non Validi';
                break;
              case 500:
                errorMessage = 'Errore del server. Riprova pi� tardi.';
                errorTitle = 'Errore del Server';
                break;
              case 503:
                errorMessage = 'Servizio temporaneamente non disponibile. Riprova pi� tardi.';
                errorTitle = 'Servizio Non Disponibile';
                break;
              default:
                errorMessage = `Si � verificato un errore: ${error.statusText || 'Errore sconosciuto'}`;
            }
        }

        // Show error toast with longer duration
        toastService.error(errorTitle, errorMessage, 6000);
      }

      return throwError(() => error);
    })
  );
};
