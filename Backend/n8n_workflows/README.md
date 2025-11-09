# n8n Workflows for CandyHire

Questa cartella contiene i workflow n8n per l'automazione della piattaforma CandyHire.

## 📋 Workflows Disponibili

### 1. cv-extract-data-ollama.json ⭐ **PRODUCTION**
**Scopo**: Estrazione dati da CV usando Ollama AI (REALE)
**Trigger**: Webhook POST `/webhook/upload-cv`
**Descrizione**:
- Riceve CV caricato dal frontend (via upload-cv.php)
- Estrae il contenuto del file (max 8000 caratteri)
- Chiama Ollama AI (qwen2.5:7b) per estrarre i dati candidato
- Restituisce JSON strutturato con: nome, cognome, email, telefono, skills, esperienza, etc.
- Gestisce errori e fallback

**Output Example**:
```json
{
  "success": true,
  "message": "CV processed successfully",
  "extractedData": {
    "first_name": "Mario",
    "last_name": "Rossi",
    "email": "mario.rossi@example.com",
    "phone": "+39 123 456 7890",
    "current_position": "Senior Developer",
    "current_company": "Tech Company",
    "experience": 5,
    "skills": ["JavaScript", "React", "Node.js"],
    "status": "New",
    "candidate_type": "Employee"
  },
  "fileInfo": {
    "fileName": "cv_mario_rossi.pdf",
    "savedFileName": "cv_abc123_mario_rossi.pdf",
    "savedFilePath": "/var/www/html/Attach/temp_cv_uploads/cv_abc123_mario_rossi.pdf"
  }
}
```

---

### 2. cv-extract-data-mock.json 🧪 **DEVELOPMENT/TEST**
**Scopo**: Mock per testing senza AI (DATI FISSI)
**Trigger**: Webhook POST `/webhook/upload-cv`
**Descrizione**:
- Riceve CV caricato
- Restituisce dati mock predefiniti (Giuseppe Chiruzzi)
- Utile per testing frontend senza dipendere da Ollama

**Quando usarlo**:
- Sviluppo locale senza Ollama installato
- Test rapidi del flusso upload CV
- CI/CD pipeline testing

---

### 3. test-ollama-integration.json 🔧 **TESTING**
**Scopo**: Test connessione Ollama
**Trigger**: Manuale
**Descrizione**:
- Verifica che Ollama sia raggiungibile
- Testa il modello configurato (qwen2.5:7b)
- Utile per troubleshooting

---

## 🚀 Come Importare Workflows

### Metodo 1: Via UI (Consigliato per Development)
1. Accedi a n8n: `http://localhost:5678`
   - User: `admin`
   - Password: vedi `N8N_BASIC_AUTH_PASSWORD` in `.env`
2. Click su **"Workflows"** → **"Import from File"**
3. Seleziona il file JSON
4. Click **"Import"**
5. **Attiva il workflow** (toggle in alto a destra)

### Metodo 2: Via Script (Automatico)
```bash
cd /path/to/CandyHirePortal/Backend
./import-n8n-workflows-api.sh
```

---

## 🔧 Setup Ambiente

### Development (Locale)
```bash
# 1. Avvia Ollama
ollama serve

# 2. Download modello
ollama pull qwen2.5:7b

# 3. Avvia n8n
docker-compose up -d candyhire-n8n

# 4. Importa workflow di TEST
# Via UI importa: cv-extract-data-mock.json
# oppure: ./import-n8n-workflows-api.sh

# 5. Attiva workflow in n8n UI
```

### Production
```bash
# 1. Configura Ollama su server separato (preferibilmente con GPU)
# Vedi: DEPLOY_PRODUCTION.md sezione "Configurazione Ollama"

# 2. Configura .env.production
OLLAMA_HOST=https://ollama.candyhire.internal:11434

# 3. Avvia n8n production
npm install -g n8n
pm2 start n8n --name candyhire-n8n

# 4. Importa workflow PRODUCTION
# Via UI importa: cv-extract-data-ollama.json

# 5. Attiva workflow in n8n UI
```

---

## 📊 Workflow da Usare per Ambiente

| Ambiente | Workflow | File | Motivo |
|----------|----------|------|--------|
| **Development** | Mock | `cv-extract-data-mock.json` | No Ollama richiesto, test rapidi |
| **Staging** | Ollama | `cv-extract-data-ollama.json` | Test AI reale |
| **Production** | Ollama | `cv-extract-data-ollama.json` | Estrazione CV reale con AI |

---

## 🐛 Troubleshooting

### Workflow non si attiva
```bash
# Verifica webhook registrato in n8n
docker logs candyhire-n8n | grep "Webhook registered"

# Dovresti vedere: "Webhook registered: POST upload-cv"
```

### Ollama non risponde dal workflow
```bash
# 1. Verifica Ollama in esecuzione
pgrep -x ollama

# 2. Test connessione da host
curl http://localhost:11434/api/tags

# 3. Test connessione da container n8n
docker exec candyhire-n8n wget -qO- http://host.docker.internal:11434/api/tags

# 4. Verifica modello scaricato
ollama list
# Dovresti vedere: qwen2.5:7b

# 5. Se manca, scaricalo
ollama pull qwen2.5:7b
```

### Webhook ritorna 404
```bash
# Verifica che workflow sia ATTIVO in n8n UI
# Il toggle deve essere verde (ON)

# Webhook endpoint: POST http://localhost:5678/webhook/upload-cv
curl -X POST http://localhost:5678/webhook/upload-cv \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

### Ollama ascoltesu 127.0.0.1 invece di 0.0.0.0

**Ubuntu/Linux:**
```bash
sudo mkdir -p /etc/systemd/system/ollama.service.d
echo '[Service]
Environment="OLLAMA_HOST=0.0.0.0:11434"' | sudo tee /etc/systemd/system/ollama.service.d/override.conf
sudo systemctl daemon-reload
sudo systemctl restart ollama
```

**macOS:**
```bash
killall ollama
OLLAMA_HOST=0.0.0.0:11434 ollama serve &
```

---

## 📝 Sviluppo Nuovi Workflows

1. **Crea in n8n UI**: http://localhost:5678
2. **Testa accuratamente** con dati reali
3. **Esporta come JSON**: Settings → Export
4. **Salva in questa cartella** con nome descrittivo
5. **Documenta in questo README**
6. **Commit to git**

---

## 📚 Documentazione Correlata

- **Setup Completo**: `../setupUbuntu.sh` o `../setup.sh`
- **Deploy Production**: `../DEPLOY_PRODUCTION.md`
- **Configurazione Env**: `../.env` e `../.env.production`

---

**⚠️ Note Importanti**:
- **NON** committare credenziali nei workflow JSON
- Usa n8n Credentials per API keys, passwords, secrets
- Testa sempre in dev prima di deploy production
- Backup workflows prima di aggiornare n8n
- host.docker.internal funziona solo in Docker Desktop, in produzione usa IP/hostname reale

---

**Ultimo aggiornamento**: 2025-11-09
**Versione**: 2.0.0
