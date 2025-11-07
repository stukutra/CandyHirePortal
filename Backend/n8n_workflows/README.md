# n8n Workflows

Questa cartella contiene i workflow n8n esportati in formato JSON.

## Workflow Disponibili

### test-ollama-integration.json
Workflow di test per verificare l'integrazione tra n8n e Ollama.

**Come testare:**
1. Accedi a n8n: http://localhost:5678
2. Apri il workflow "Test Ollama Integration"
3. Clicca su "Test workflow" nel nodo iniziale
4. Verifica che Ollama risponda correttamente

**Cosa fa:**
- Invia una richiesta POST a Ollama (http://host.docker.internal:11434)
- Usa il modello `qwen2.5:7b`
- Chiede all'AI di presentarsi
- Formatta e restituisce la risposta

## Come esportare workflow

Per esportare i workflow dopo averli modificati:

```bash
./export-n8n-workflows.sh
```

## Come importare workflow

### Import automatico durante setup

I workflow vengono importati automaticamente quando esegui:
- `./setup.sh` (macOS)
- `./setupUbuntu.sh` (Ubuntu/Linux)

Lo script usa l'API REST di n8n per importare tutti i file JSON presenti in questa cartella.

### Import manuale

Se vuoi importare i workflow manualmente dopo il setup:

```bash
# Importa tutti i workflow dalla cartella n8n_workflows
./import-n8n-workflows-api.sh
```

oppure dall'interfaccia web di n8n:
1. Vai su http://localhost:5678
2. Clicca sul menu (3 puntini) in alto a destra
3. Seleziona "Import from file"
4. Seleziona tutti i file JSON (Ctrl+Click per selezione multipla)

## Struttura

Ogni file JSON rappresenta un workflow completo che può essere importato in n8n.

## Note Tecniche

- **host.docker.internal**: Permette ai container Docker di raggiungere servizi sull'host (come Ollama)
- I workflow sono versionati in Git
- Esportali regolarmente dopo modifiche importanti
- I workflow sono salvati anche nel database MySQL per backup

## Troubleshooting

### Ollama non risponde dal workflow n8n?

I container Docker devono poter raggiungere Ollama sull'host. Gli script di setup configurano automaticamente:

1. **docker-compose.yml**: Aggiunge `extra_hosts` per mappare `host.docker.internal`
2. **Ollama**: Configurato per ascoltare su `0.0.0.0:11434` invece di solo `127.0.0.1`

#### Verifica manuale:

```bash
# 1. Verifica che Ollama sia in esecuzione
pgrep -x ollama

# 2. Verifica che ascolti su tutte le interfacce (Linux)
ss -tuln | grep 11434
# Dovresti vedere: 0.0.0.0:11434

# 3. Su macOS usa:
lsof -i :11434 -P -n
# Dovresti vedere: *:11434 o 0.0.0.0:11434

# 4. Testa la connessione dal container n8n:
docker exec candyhire-n8n wget -q -O - http://host.docker.internal:11434/api/tags
```

#### Se Ollama è ancora su 127.0.0.1:

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
# Opzione 1: Modifica il launchd plist
# (gestito automaticamente dallo script setup.sh)

# Opzione 2: Avvia manualmente
killall ollama
OLLAMA_HOST=0.0.0.0:11434 ollama serve &
```

### Altri problemi comuni

```bash
# Verifica che il modello sia scaricato
ollama list

# Se manca, scaricalo
ollama pull qwen2.5:7b

# Riavvia n8n se hai modificato la configurazione
docker compose restart n8n
```
