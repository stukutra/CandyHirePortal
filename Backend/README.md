# Backend PHP - CandyHire Waitlist

Questo backend gestisce le iscrizioni alla lista d'attesa di CandyHire.

## 📋 Requisiti

- PHP 7.4 o superiore
- Server web (Apache, Nginx) o PHP built-in server
- Funzione `mail()` abilitata per l'invio email

## 🚀 Installazione e Configurazione

### Opzione 1: PHP Built-in Server (per sviluppo)

1. Apri un terminale nella cartella `Backend`:
   ```bash
   cd Backend
   ```

2. Avvia il server PHP sulla porta 8080:
   ```bash
   php -S localhost:8080
   ```

3. Il backend sarà accessibile su `http://localhost:8080`

### Opzione 2: Apache/Nginx (per produzione)

1. Copia il file `waitlist-signup.php` nella cartella web del tuo server

2. Configura il virtual host per puntare alla cartella Backend

3. Assicurati che PHP sia abilitato e configurato correttamente

## 📧 Configurazione Email

### Test Locale

Per testare in locale, puoi usare uno strumento come **Mailhog** o **MailCatcher**:

```bash
# Installare Mailhog (su Mac)
brew install mailhog
mailhog

# Ora vai su http://localhost:8025 per vedere le email inviate
```

### Produzione

Per la produzione, assicurati che:

1. La funzione `mail()` di PHP sia configurata correttamente
2. Il server possa inviare email (verifica con il tuo hosting provider)
3. Configura SPF/DKIM/DMARC per evitare che le email finiscano nello spam

### Alternative SMTP

Se `mail()` non funziona, puoi usare librerie come **PHPMailer** o **SwiftMailer** per inviare via SMTP.

## 🔧 Personalizzazione

### Cambiare destinatario email

Nel file `waitlist-signup.php`, modifica questa riga:

```php
$to = "info@oneblade.it";  // Cambia con la tua email
```

### Personalizzare template email

Cerca le sezioni HTML nel file PHP e personalizza:
- Colori
- Testo
- Logo (aggiungi `<img src="...">` nell'header)
- Footer

## 🧪 Test

Per testare il backend:

1. Avvia il server PHP:
   ```bash
   cd Backend
   php -S localhost:8080
   ```

2. Nel browser, testa con questo comando curl:
   ```bash
   curl -X POST http://localhost:8080/waitlist-signup.php \
     -H "Content-Type: application/json" \
     -d '{"name":"Test User","email":"test@example.com","company":"Test Company"}'
   ```

3. Verifica che ricevi:
   - Email su info@oneblade.it
   - Email di conferma su test@example.com

## 📁 Struttura File

```
Backend/
├── README.md              # Questo file
└── waitlist-signup.php    # Script PHP principale
```

## 🔒 Sicurezza

Lo script include:

- ✅ CORS configurato
- ✅ Validazione input
- ✅ Sanitizzazione dati (FILTER_SANITIZE_STRING, FILTER_SANITIZE_EMAIL)
- ✅ Validazione email (FILTER_VALIDATE_EMAIL)
- ✅ Headers sicuri
- ✅ Protezione XSS (htmlspecialchars)

### Miglioramenti consigliati per produzione:

1. **Rate Limiting**: Limita il numero di richieste per IP
2. **CAPTCHA**: Aggiungi Google reCAPTCHA per prevenire spam
3. **Database**: Salva i lead in un database oltre che via email
4. **Logging**: Registra errori e richieste in un file di log
5. **HTTPS**: Usa sempre HTTPS in produzione

## 🐛 Troubleshooting

### Email non arrivano

1. Verifica che `mail()` funzioni:
   ```php
   <?php
   $test = mail('tua@email.com', 'Test', 'Funziona!');
   echo $test ? 'OK' : 'ERRORE';
   ?>
   ```

2. Controlla i log di PHP: `tail -f /var/log/php_errors.log`

3. Verifica che il server possa inviare email (alcuni hosting bloccano la porta 25)

### Errore CORS

Se hai errori CORS, verifica che:
- Gli headers CORS siano presenti nel file PHP
- Il frontend Angular stia chiamando l'URL corretto

### 500 Internal Server Error

1. Abilita error reporting nel file PHP:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

2. Controlla i permessi dei file (devono essere eseguibili)

## 📞 Supporto

Per problemi o domande, contatta info@oneblade.it
