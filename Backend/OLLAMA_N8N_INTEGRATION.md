# Integrazione Ollama con n8n

## Configurazione

Ollama è installato automaticamente dagli script di setup (`setup.sh` per macOS, `setupUbuntu.sh` per Ubuntu/Linux).

### Endpoint API
- **URL**: `http://localhost:11434`
- **Modello**: `qwen2.5:7b`

## Usare Ollama in n8n

### 1. Nodo HTTP Request

In n8n, crea un nodo **HTTP Request** con questa configurazione:

**Metodo**: POST
**URL**: `http://localhost:11434/api/generate`
**Body** (JSON):
```json
{
  "model": "qwen2.5:7b",
  "prompt": "{{$json.userQuestion}}",
  "stream": false
}
```

**Headers**:
```
Content-Type: application/json
```

### 2. Nodo Ollama (se disponibile)

Se n8n ha il nodo Ollama nativo:
1. Aggiungi nodo **Ollama**
2. Host: `http://localhost:11434`
3. Model: `qwen2.5:7b`
4. Prompt: il tuo prompt dinamico

## Esempi di Utilizzo

### Esempio 1: Analisi Email
```
Webhook Trigger → Extract Email → Ollama → Send Response
```

### Esempio 2: Chatbot
```
Webhook → Ollama (chat) → Format Response → Respond
```

### Esempio 3: Generazione Contenuti
```
Schedule → Get Topic → Ollama (generate) → Save to DB
```

## Comandi Utili

```bash
# Lista modelli installati
ollama list

# Test rapido
curl http://localhost:11434/api/generate -d '{
  "model": "qwen2.5:7b",
  "prompt": "Ciao, come va?",
  "stream": false
}'

# Scaricare altri modelli
ollama pull llama3.2
ollama pull mistral

# Verificare che Ollama sia in esecuzione
pgrep -x ollama
```

## Troubleshooting

**Ollama non risponde?**
```bash
# Riavvia il servizio
pkill ollama
ollama serve &
```

**Modello non trovato?**
```bash
# Scarica di nuovo il modello
ollama pull qwen2.5:7b
```

## Performance

- **qwen2.5:7b** richiede ~4GB RAM
- Per modelli più leggeri: `qwen2.5:3b` o `phi3`
- Per modelli più potenti: `qwen2.5:14b` o `llama3.2:70b` (richiede GPU)
