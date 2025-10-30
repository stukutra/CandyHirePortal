# CandyHire Portal - Backend

Backend per il portale di registrazione e pagamento delle aziende che vogliono accedere al SaaS CandyHire.

## Quick Links - Accesso Risorse

### Servizi Attivi

| Servizio | URL | Descrizione |
|----------|-----|-------------|
| **API Backend** | http://localhost:8082 | Endpoint REST API |
| **PHPMyAdmin** | http://localhost:8083 | Gestione database visuale |
| **MySQL** | `localhost:3308` | Porta MySQL diretta |

### Credenziali Database

```
Host:     localhost
Port:     3308
Database: CandyHirePortal
User:     candyhire_portal_user
Password: candyhire_portal_pass

Root User: root
Root Pass: candyhire_portal_root_pass
```

## API Endpoints

### Autenticazione Aziende

```bash
# Registrazione nuova azienda
POST http://localhost:8082/auth/register.php
Content-Type: application/json

{
  "company_name": "Nome Azienda",
  "email": "email@azienda.com",
  "password": "Password123!",
  "vat_number": "IT12345678901",
  "address": "Via Roma 1",
  "city": "Milano",
  "country": "IT",
  "phone": "+39 02 1234567"
}
```

```bash
# Login azienda registrata
POST http://localhost:8082/auth/login.php
Content-Type: application/json

{
  "email": "email@azienda.com",
  "password": "Password123!"
}
```

### Admin API

```bash
# Login Admin
POST http://localhost:8082/admin/login.php
Content-Type: application/json

{
  "email": "admin@candyhire.com",
  "password": "Admin123!"
}
```

```bash
# Dashboard Statistics
GET http://localhost:8082/admin/dashboard-stats.php
Authorization: Bearer {jwt_token}
```

```bash
# Lista aziende registrate (con filtri e paginazione)
GET http://localhost:8082/admin/companies-list.php?page=1&limit=20&search=&status=active&payment_status=completed
Authorization: Bearer {jwt_token}
```

```bash
# Dettaglio azienda
GET http://localhost:8082/admin/company-detail.php?id={company_id}
Authorization: Bearer {jwt_token}
```

```bash
# Aggiorna stato azienda
PUT http://localhost:8082/admin/company-update-status.php
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "company_id": "comp_xxx",
  "status": "active"
}
```

### Pagamento

```bash
# Mock pagamento PayPal (per sviluppo)
POST http://localhost:8082/payment/paypal-mock.php
Content-Type: application/json

{
  "company_id": "comp_xxx",
  "payment_success": true,
  "paypal_subscription_id": "SUB-MOCK-12345",
  "paypal_payer_id": "PAYER-MOCK-67890"
}
```

### Aziende

```bash
# Lista aziende registrate (admin)
GET http://localhost:8082/companies/list.php
Authorization: Bearer {jwt_token}
```

```bash
# Dettaglio azienda
GET http://localhost:8082/companies/detail.php?id={company_id}
Authorization: Bearer {jwt_token}
```

## Comandi Docker

### Avvio

```bash
# Avvio completo (prima volta o rebuild)
./setup.sh

# Avvio servizi esistenti
docker-compose up -d

# Avvio con log in tempo reale
docker-compose up
```

### Gestione

```bash
# Ferma servizi
docker-compose down

# Riavvia servizi
docker-compose restart

# Riavvia singolo servizio
docker-compose restart portal-php
docker-compose restart portal-mysql

# Visualizza log
docker-compose logs -f

# Log singolo servizio
docker-compose logs -f portal-php
docker-compose logs -f portal-mysql
```

### Debug

```bash
# Entra nel container PHP
docker exec -it candyhire-portal-php bash

# Entra nel container MySQL
docker exec -it candyhire-portal-mysql mysql -uroot -pcandyhire_portal_root_pass

# Verifica stato servizi
docker-compose ps

# Verifica health dei container
docker ps --format "table {{.Names}}\t{{.Status}}"
```

## Struttura Database

### Tabelle Principali

#### `companies_registered`
Aziende che si registrano al servizio

- `id` - ID univoco azienda
- `company_name` - Nome azienda
- `email` - Email (univoca)
- `password_hash` - Password hashata con bcrypt
- `vat_number` - Partita IVA
- `registration_status` - Stato registrazione (pending, payment_pending, active, suspended)
- `payment_status` - Stato pagamento (pending, completed, failed)
- `tenant_id` - ID tenant assegnato dopo il pagamento
- `paypal_subscription_id` - ID sottoscrizione PayPal
- `subscription_start_date` - Data inizio sottoscrizione
- `subscription_end_date` - Data fine sottoscrizione

#### `admin_users`
Utenti amministratori del portale (non le aziende)

- `id` - ID univoco
- `username` - Username admin
- `email` - Email admin
- `password_hash` - Password hashata

#### `subscription_plans`
Piani di sottoscrizione disponibili

- `id` - ID piano
- `name` - Nome piano (Basic, Professional, Enterprise)
- `price_monthly` - Prezzo mensile
- `price_yearly` - Prezzo annuale
- `max_users` - Numero massimo utenti
- `max_job_postings` - Numero massimo offerte lavoro
- `features` - Features JSON

#### `payment_transactions`
Log di tutte le transazioni di pagamento

- `id` - ID transazione
- `company_id` - Riferimento azienda
- `amount` - Importo
- `currency` - Valuta
- `payment_method` - Metodo pagamento
- `transaction_status` - Stato transazione

## Flusso Registrazione e Provisioning

### 1. Registrazione Azienda
```
POST /auth/register.php
↓
Crea record in companies_registered
↓
Stato: registration_status = 'payment_pending'
↓
Ritorna company_id + JWT temporaneo
```

### 2. Pagamento (Mock per sviluppo)
```
POST /payment/paypal-mock.php
↓
Verifica company_id esistente
↓
Aggiorna payment_status = 'completed'
↓
Genera tenant_id univoco
```

### 3. Provisioning Automatico
```
Provisioning Service
↓
Crea admin user nel DB CandyHire (SaaS)
  - tenant_id = tenant_id generato
  - email = email azienda
  - password = password scelta
  - role = 'admin'
↓
Aggiorna companies_registered
  - tenant_id assegnato
  - registration_status = 'active'
  - subscription_start_date = now()
```

### 4. Accesso al SaaS
```
L'azienda può ora fare login su CandyHire
↓
Login su http://localhost:4200 (frontend CandyHire)
↓
JWT contiene tenant_id
↓
Tutti i dati sono isolati per tenant_id
```

## Integrazione con CandyHire SaaS

Il backend del Portal comunica con il database del SaaS CandyHire per creare gli utenti dopo il pagamento.

### Variabili d'Ambiente

Nel file `.env` del Portal ci sono le credenziali per connettersi al DB CandyHire:

```env
# CandyHire SaaS DB Connection (for tenant provisioning)
SAAS_DB_HOST=localhost
SAAS_DB_PORT=3307
SAAS_DB_NAME=CandyHire
SAAS_DB_USER=candyhire_user
SAAS_DB_PASSWORD=candyhire_pass
```

### Tenant Isolation

Ogni azienda riceve un `tenant_id` univoco che viene:
- Salvato nel Portal DB (`companies_registered.tenant_id`)
- Usato per creare l'admin user nel CandyHire DB
- Incluso nel JWT al login
- Usato per filtrare tutti i dati nel SaaS

## Test Rapidi

### 1. Registra un'azienda
```bash
curl -X POST http://localhost:8082/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "Test Company",
    "email": "test@example.com",
    "password": "Test123!",
    "vat_number": "IT12345678901"
  }'
```

### 2. Simula pagamento
```bash
curl -X POST http://localhost:8082/payment/paypal-mock.php \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": "comp_xxx",
    "payment_success": true,
    "paypal_subscription_id": "SUB-TEST-123",
    "paypal_payer_id": "PAYER-TEST-456"
  }'
```

### 3. Login
```bash
curl -X POST http://localhost:8082/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Test123!"
  }'
```

## File Importanti

| File | Descrizione |
|------|-------------|
| `setup.sh` | Script setup iniziale + avvio Docker |
| `docker-compose.yml` | Configurazione servizi Docker |
| `Dockerfile` | Immagine PHP 8.2 con estensioni |
| `.env` | Variabili d'ambiente (NON commitare!) |
| `.env.example` | Template variabili d'ambiente |
| `migration/01_schema.sql` | Schema database Portal |
| `migration/02_initial_data.sql` | Dati iniziali (piani, admin) |
| `api/config/database.php` | Connessione database |
| `api/config/jwt.php` | Gestione JWT |
| `api/auth/register.php` | Endpoint registrazione |
| `api/auth/login.php` | Endpoint login |
| `api/payment/paypal-mock.php` | Mock PayPal |
| `api/services/TenantProvisioning.php` | Provisioning tenant |

## Troubleshooting

### MySQL non si avvia
```bash
# Verifica log
docker-compose logs portal-mysql

# Rimuovi volume e ricrea
docker-compose down -v
./setup.sh
```

### PHP non risponde
```bash
# Verifica log
docker-compose logs portal-php

# Entra nel container e testa
docker exec -it candyhire-portal-php bash
curl localhost/health.php
```

### Errore connessione database
```bash
# Verifica che MySQL sia healthy
docker ps

# Testa connessione da PHP
docker exec -it candyhire-portal-php bash
php -r "new PDO('mysql:host=portal-mysql;dbname=CandyHirePortal', 'candyhire_portal_user', 'candyhire_portal_pass');"
```

### Reset completo
```bash
# Ferma e rimuovi tutto
docker-compose down -v

# Rimuovi anche le immagini
docker rmi candyhire-portal-php

# Riavvia da zero
./setup.sh
```

## Note di Sviluppo

- **Multi-tenancy**: Shared schema con `tenant_id` in tutte le tabelle
- **Password**: Hash con `bcrypt` (cost factor 12)
- **JWT**: Scadenza 24h, refresh 7 giorni
- **PayPal**: Mock per sviluppo, flag `payment_success` per simulare successo/fallimento
- **CORS**: Configurato per permettere richieste da Angular (porta 4201)

## Prossimi Step

1. [ ] Implementare integrazione PayPal reale
2. [ ] Copiare form registrazione da CandyHire frontend
3. [ ] Creare dashboard admin per gestire aziende
4. [ ] Implementare email di conferma registrazione
5. [ ] Aggiungere webhook PayPal per rinnovi automatici
6. [ ] Implementare cancellazione/sospensione sottoscrizioni

---

**Ultima modifica**: 2025-10-30
**Versione API**: 1.0
**Stack**: PHP 8.2 + MySQL 8.0 + Docker
