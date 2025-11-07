# n8n Workflows

Questa cartella contiene i workflow n8n esportati in formato JSON.

## Come esportare workflow

Per esportare i workflow dopo averli modificati:

```bash
./export-n8n-workflows.sh
```

## Come importare workflow

I workflow vengono importati automaticamente quando esegui:
- `./setup.sh` (macOS)
- `./setupUbuntu.sh` (Ubuntu/Linux)

## Struttura

Ogni file JSON rappresenta un workflow completo che può essere importato in n8n.

## Note

- I workflow sono versionati in Git
- Esportali regolarmente dopo modifiche importanti
- I workflow sono salvati anche nel database MySQL per backup
