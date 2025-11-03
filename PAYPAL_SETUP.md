# PayPal Integration Setup Guide

Questa guida ti spiega come configurare PayPal per l'integrazione con CandyHire Portal.

## Indice
1. [Configurazione PayPal Sandbox (Sviluppo)](#configurazione-paypal-sandbox)
2. [Configurazione PayPal Live (Produzione)](#configurazione-paypal-live)
3. [Testing](#testing)
4. [Troubleshooting](#troubleshooting)

---

## Configurazione PayPal Sandbox

### Passo 1: Crea un Account Developer PayPal

1. Vai su [https://developer.paypal.com](https://developer.paypal.com)
2. Clicca su **"Log in to Dashboard"** in alto a destra
3. Accedi con il tuo account PayPal personale (o creane uno se non ce l'hai)

### Passo 2: Crea una REST API App

1. Una volta loggato, vai su **Dashboard** → **My Apps & Credentials**
2. Assicurati di essere nella tab **"Sandbox"**
3. Nella sezione **"REST API apps"**, clicca su **"Create App"**
4. Compila il form:
   - **App Name**: `CandyHire Portal` (o un nome a tua scelta)
   - **Sandbox Business Account**: Seleziona un account sandbox (verrà creato automaticamente se non ne hai)
5. Clicca su **"Create App"**

### Passo 3: Copia le Credenziali

Dopo aver creato l'app, vedrai le credenziali:

- **Client ID**: Una stringa come `AZaQ7pFxEr...`
- **Secret**: Clicca su "Show" per visualizzarlo

### Passo 4: Configura il File .env

Apri il file `Backend/.env` e aggiorna le seguenti righe:

```bash
# PayPal Configuration (Sandbox)
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=<tuo-client-id-sandbox>
PAYPAL_CLIENT_SECRET=<tuo-client-secret-sandbox>
```

Sostituisci `<tuo-client-id-sandbox>` e `<tuo-client-secret-sandbox>` con le credenziali che hai copiato.

### Passo 5: Crea Account di Test (Opzionale ma Consigliato)

Per testare i pagamenti, hai bisogno di account sandbox per buyer e seller:

1. Vai su **Dashboard** → **Sandbox** → **Accounts**
2. PayPal crea automaticamente 2 account:
   - **Business Account**: Riceve i pagamenti (seller)
   - **Personal Account**: Effettua i pagamenti (buyer)
3. Clicca sull'account **Personal** e copia email e password (usa "..." → System Generated Password)

**Esempio:**
- Email: `sb-buyer123@personal.example.com`
- Password: `12345678`

---

## Configurazione PayPal Live (Produzione)

### Quando Passare a Live?

⚠️ **NON passare a Live fino a quando non hai:**
- Testato completamente il flusso di pagamento in Sandbox
- Verificato che tutti i webhook funzionino
- Revisionato i termini e le politiche di PayPal

### Passo 1: Completa il Processo di Verifica

1. Vai su [https://developer.paypal.com](https://developer.paypal.com)
2. Dashboard → **My Apps & Credentials**
3. Passa alla tab **"Live"**
4. Potrebbe essere richiesto di:
   - Verificare il tuo account PayPal Business
   - Fornire documenti di identità
   - Configurare le informazioni bancarie

### Passo 2: Crea Live App

1. Nella tab **"Live"**, clicca su **"Create App"**
2. Compila il form come fatto per Sandbox
3. Copia **Client ID** e **Secret** di produzione

### Passo 3: Configura .env.production

Apri il file `Backend/.env.production` e aggiorna:

```bash
# PayPal Configuration (Production)
PAYPAL_MODE=live
PAYPAL_CLIENT_ID=<tuo-client-id-live>
PAYPAL_CLIENT_SECRET=<tuo-client-secret-live>
```

---

## Testing

### Test Completo del Flusso di Registrazione

1. **Avvia il progetto:**
   ```bash
   cd CandyHirePortal
   npm start
   ```

2. **Vai su** [http://localhost:4200/register](http://localhost:4200/register)

3. **Compila il form di registrazione** con dati fittizi

4. **Procedi al pagamento:**
   - Verrai reindirizzato al PayPal Sandbox
   - URL dovrebbe essere: `https://www.sandbox.paypal.com/checkoutnow?token=...`

5. **Login con account Sandbox Buyer:**
   - Usa l'email e password dell'account Personal creato prima
   - Es: `sb-buyer123@personal.example.com` / `12345678`

6. **Completa il pagamento:**
   - Clicca su "Pay Now"
   - Verrai reindirizzato a `/payment/success`

7. **Verifica nel database:**
   ```sql
   SELECT * FROM payment_transactions ORDER BY created_at DESC LIMIT 1;
   SELECT * FROM companies_registered WHERE payment_status = 'completed';
   SELECT * FROM tenant_pool WHERE is_available = FALSE;
   ```

### Test di Cancellazione

1. Durante il checkout PayPal, clicca su **"Cancel and return to merchant"**
2. Verrai reindirizzato a `/payment/cancel`
3. Verifica che la transazione sia marcata come `failed`

---

## Flusso Completo PayPal

```
1. Utente Registrazione
   └─> Frontend: /register
       └─> Backend: POST /api/auth/register.php
           └─> Crea Company (status: payment_pending)
           └─> Crea PayPal Order
           └─> Ritorna paypal_approval_url

2. Redirect a PayPal
   └─> window.location.href = paypal_approval_url
       └─> Utente fa login e paga su PayPal

3a. Successo
    └─> PayPal redirect: /payment/success?token=PAYPAL_ORDER_ID
        └─> Frontend: PaymentSuccess component
            └─> Backend: POST /api/payment/capture.php
                └─> Cattura pagamento PayPal
                └─> Aggiorna Company (status: active)
                └─> Assegna Tenant
                └─> Ritorna successo

3b. Cancellazione
    └─> PayPal redirect: /payment/cancel?token=PAYPAL_ORDER_ID
        └─> Frontend: PaymentCancel component
            └─> Backend: POST /api/payment/cancel.php
                └─> Marca transazione come failed
```

---

## Troubleshooting

### Errore: "Failed to get PayPal access token"

**Causa:** Credenziali errate o scadute

**Soluzione:**
1. Verifica che `PAYPAL_CLIENT_ID` e `PAYPAL_CLIENT_SECRET` siano corretti
2. Assicurati che `PAYPAL_MODE` sia `sandbox` per sviluppo
3. Controlla che non ci siano spazi extra nelle variabili .env

### Errore: "Failed to create PayPal order"

**Causa:** Configurazione errata o problema di rete

**Soluzione:**
1. Controlla i log PHP: `tail -f /var/log/php-errors.log`
2. Verifica che il prezzo sia > 0
3. Verifica che la valuta sia supportata (EUR, USD, GBP)

### Redirect a PayPal non funziona

**Causa:** CORS o URL errati

**Soluzione:**
1. Verifica che `APP_URL` in `.env` sia corretto
2. Assicurati che il frontend sia in esecuzione
3. Controlla la console del browser per errori CORS

### Pagamento completato ma company non è attiva

**Causa:** Errore durante il capture

**Soluzione:**
1. Controlla la tabella `payment_transactions`:
   ```sql
   SELECT * FROM payment_transactions WHERE status = 'pending';
   ```
2. Verifica i log dell'endpoint capture
3. Riprova manualmente il capture con l'order ID

---

## Link Utili

- **PayPal Developer Dashboard**: https://developer.paypal.com/dashboard
- **PayPal API Reference**: https://developer.paypal.com/docs/api/orders/v2/
- **PayPal Sandbox Accounts**: https://developer.paypal.com/dashboard/accounts
- **PayPal Webhooks**: https://developer.paypal.com/dashboard/webhooks

---

## Note Importanti

1. **Non committare le credenziali!** I file `.env` e `.env.production` sono già in `.gitignore`
2. **Sandbox vs Live:** Usa sempre Sandbox per sviluppo. Live solo per produzione.
3. **Importi Sandbox:** Puoi usare qualsiasi importo in Sandbox, anche 0.01€
4. **Account Sandbox:** Gli account sandbox hanno saldo illimitato per test
5. **Webhook:** Per produzione, configura i webhook PayPal per notifiche in tempo reale

---

## Supporto

Per problemi o domande:
- Controlla i log: `Backend/logs/`
- Documentazione PayPal: https://developer.paypal.com/docs
- Support: support@candyhire.cloud
