# CandyHire - Guida Deploy Produzione

## Prerequisiti

1. Server con Docker e Docker Compose installati
2. Ollama installato e configurato (per AI CV extraction)
3. Accesso al database MySQL di produzione
4. Certificati SSL configurati
5. Domini DNS configurati:
   - `portal.candyhire.cloud` → Portal
   - `app.candyhire.cloud` → SaaS Application
   - `n8n.candyhire.cloud` → n8n Workflows (opzionale, può essere interno)

## Configurazione Variabili d'Ambiente

### 1. Copia e configura il file .env.production

```bash
cd /home/guidosalzano/CandyHirePortal/Backend
cp .env.production .env
```

### 2. Modifica TUTTE le variabili con `CHANGE-THIS`:

**Critiche - Da cambiare SUBITO:**
- `JWT_SECRET` - Stringa random di 64+ caratteri
- `PASSWORD_PEPPER` - Stringa random di 64+ caratteri (**MAI** cambiare dopo il primo deploy!)
- `MYSQL_ROOT_PASSWORD` - Password root MySQL
- `DB_PASSWORD` - Password database
- `SAAS_DB_PASSWORD` - Password database SaaS
- `N8N_DB_PASSWORD` - Password database n8n
- `N8N_BASIC_AUTH_PASSWORD` - Password interfaccia n8n
- `N8N_ENCRYPTION_KEY` - Stringa random di 64+ caratteri

**PayPal:**
- `PAYPAL_CLIENT_ID` - Credenziali LIVE PayPal
- `PAYPAL_CLIENT_SECRET` - Credenziali LIVE PayPal

**Email:**
- `MAIL_PASSWORD` - Password SMTP Aruba

**Admin:**
- `ADMIN_DEFAULT_PASSWORD` - Password admin iniziale (cambiare dopo primo login!)

### 3. Configurazione Ollama

**Locale (per test):**
```bash
# .env
OLLAMA_HOST=http://host.docker.internal:11434
```

**Produzione (server separato):**
```bash
# .env.production
OLLAMA_HOST=https://ollama.candyhire.internal:11434
OLLAMA_API_KEY=your-api-key-if-needed
```

#### Installazione Ollama in produzione:

```bash
# Su server Ubuntu/Debian separato con GPU
curl -fsSL https://ollama.com/install.sh | sh

# Avvia Ollama
systemctl start ollama
systemctl enable ollama

# Download modello
ollama pull qwen2.5:7b

# Configurazione per accesso remoto
# Modifica /etc/systemd/system/ollama.service
[Service]
Environment="OLLAMA_HOST=0.0.0.0:11434"

# Riavvia
systemctl daemon-reload
systemctl restart ollama

# Verifica
curl http://localhost:11434/api/tags
```

## Deploy Step-by-Step

### 1. Preparazione Server

```bash
# Crea directory struttura
mkdir -p /home/guidosalzano/public_html/portal
mkdir -p /home/guidosalzano/public_html/portal/logs
mkdir -p /home/guidosalzano/public_html/portal/uploads
mkdir -p /home/guidosalzano/public_html/portal/temp_cv_uploads

# Permessi
chmod 755 /home/guidosalzano/public_html/portal
chmod 777 /home/guidosalzano/public_html/portal/logs
chmod 777 /home/guidosalzano/public_html/portal/uploads
chmod 777 /home/guidosalzano/public_html/portal/temp_cv_uploads
```

### 2. Upload Codice

```bash
# Carica tutto il codice backend
rsync -avz --exclude 'node_modules' --exclude '.git' \
  ./Backend/ user@server:/home/guidosalzano/public_html/portal/

# Carica workflow n8n
rsync -avz ./Backend/n8n_workflows/ \
  user@server:/home/guidosalzano/public_html/portal/n8n_workflows/
```

### 3. Database Setup

```bash
# Connetti al MySQL di produzione
mysql -h 31.11.39.251 -u Sql1897072 -p

# Esegui migration Portal
mysql -h 31.11.39.251 -u Sql1897072 -p Sql1897072_1 < migration/01_schema.sql
mysql -h 31.11.39.251 -u Sql1897072 -p Sql1897072_1 < migration/02_initial_data.sql
mysql -h 31.11.39.251 -u Sql1897072 -p Sql1897072_1 < migration/04_subscription_tiers.sql

# Crea tenant pool
mysql -h 31.11.39.251 -u Sql1897072 -p Sql1897072_1 < migration/03_create_tenants.sql
```

### 4. Docker Compose (Opzionale per produzione)

Se usi Docker in produzione:

```bash
# Usa .env.production
cp .env.production .env

# Avvia containers
docker-compose up -d

# Verifica
docker-compose ps
docker-compose logs -f
```

### 5. Setup n8n

#### Opzione A: Docker (Locale)

```bash
docker-compose up -d candyhire-n8n
```

#### Opzione B: Standalone (Produzione)

```bash
# Installa n8n globalmente
npm install -g n8n

# Configura variabili
export N8N_ENCRYPTION_KEY="your-encryption-key"
export N8N_BASIC_AUTH_USER="admin"
export N8N_BASIC_AUTH_PASSWORD="your-password"
export WEBHOOK_URL="https://n8n.candyhire.cloud/"

# Avvia come servizio
pm2 start n8n --name candyhire-n8n
pm2 save
pm2 startup
```

#### Importa Workflows:

```bash
# Accedi a n8n UI: https://n8n.candyhire.cloud
# Vai su Settings → Import from file
# Importa:
- cv-extract-data-ollama.json (PRODUCTION - con AI reale)
- cv-extract-data-mock.json (TEST/DEV - con dati mock)

# Attiva il workflow di produzione
# Settings → Workflows → "CV Extract Data (Ollama AI)" → Set Active
```

### 6. Verifica Installazione

```bash
# Test connessione database
php -r "
require 'config/database.php';
\$db = getPortalDB();
echo 'Portal DB: OK' . PHP_EOL;
\$db = getTenantDB((object)['tenant_id' => 1]);
echo 'Tenant DB: OK' . PHP_EOL;
"

# Test upload CV
curl -X POST http://your-server.com/n8n/upload-cv.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "file=@test-cv.pdf"

# Test n8n webhook
curl -X POST http://localhost:5678/webhook/upload-cv \
  -H "Content-Type: application/json" \
  -d '{"fileName":"test.pdf","fileContent":"base64content..."}'
```

## Monitoraggio

### Logs da monitorare:

```bash
# PHP logs
tail -f /home/guidosalzano/public_html/portal/logs/error.log
tail -f /var/log/apache2/error.log

# n8n logs
pm2 logs candyhire-n8n

# Docker logs (se applicabile)
docker-compose logs -f
```

### Health Checks:

```bash
# API Health
curl https://portal.candyhire.cloud/api/health

# n8n Health
curl https://n8n.candyhire.cloud/healthz

# Ollama Health
curl http://ollama-server:11434/api/tags
```

## Backup

```bash
# Database backup
mysqldump -h 31.11.39.251 -u Sql1897072 -p Sql1897072_1 > backup_$(date +%Y%m%d).sql

# File uploads backup
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz \
  /home/guidosalzano/public_html/portal/uploads/

# n8n workflows backup
tar -czf n8n_backup_$(date +%Y%m%d).tar.gz \
  /home/guidosalzano/public_html/portal/n8n_workflows/
```

## Rollback Procedure

```bash
# 1. Stop services
docker-compose down  # o pm2 stop all

# 2. Restore database
mysql -h 31.11.39.251 -u Sql1897072 -p Sql1897072_1 < backup_YYYYMMDD.sql

# 3. Restore code
rsync -avz backup_code/ /home/guidosalzano/public_html/portal/

# 4. Restart services
docker-compose up -d  # o pm2 start all
```

## Troubleshooting

### CV Upload non funziona

```bash
# Verifica permessi directory
ls -la /home/guidosalzano/public_html/portal/temp_cv_uploads/
chmod 777 /home/guidosalzano/public_html/portal/temp_cv_uploads/

# Verifica log PHP
tail -f /var/log/apache2/error.log | grep "CV Attach"
tail -f /var/log/apache2/error.log | grep "N8N Upload"

# Verifica n8n webhook
curl -X POST http://localhost:5678/webhook/upload-cv \
  -H "Content-Type: application/json" \
  -d '{"test":"data"}'
```

### Ollama non risponde

```bash
# Verifica Ollama
curl http://ollama-server:11434/api/tags

# Verifica modello scaricato
ollama list

# Download modello se manca
ollama pull qwen2.5:7b

# Restart Ollama
systemctl restart ollama
```

### n8n Webhook 404

```bash
# Verifica workflow attivo
curl http://n8n-server:5678/rest/workflows

# Re-importa workflow
# Via UI: Settings → Import → cv-extract-data-ollama.json

# Verifica webhook registrato
# Log n8n dovrebbe mostrare: "Webhook registered: POST upload-cv"
```

## Sicurezza

### 1. Firewall

```bash
# Consenti solo porte necessarie
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 3306/tcp  # Solo da IP fidati
ufw enable
```

### 2. SSL/TLS

```bash
# Let's Encrypt
certbot --apache -d portal.candyhire.cloud -d app.candyhire.cloud
```

### 3. Backup JWT Secret & Pepper

```bash
# Salva in cassaforte sicura (1Password, LastPass, etc.)
grep JWT_SECRET .env
grep PASSWORD_PEPPER .env

# IMPORTANTE: Se perdi PASSWORD_PEPPER, TUTTI gli utenti perdono accesso!
```

## Support

- **Logs**: `/home/guidosalzano/public_html/portal/logs/`
- **Email**: support@candyhire.cloud
- **Documentazione**: https://docs.candyhire.cloud

---

**Ultimo aggiornamento**: 2025-11-09
**Versione**: 1.0.0
