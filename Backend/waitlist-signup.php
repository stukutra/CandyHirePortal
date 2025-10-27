<?php
// Abilita CORS per permettere richieste dal frontend Angular
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Gestisce la richiesta OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verifica che sia una richiesta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit();
}

// Leggi i dati JSON inviati dal frontend
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validazione dei dati
if (empty($data['name']) || empty($data['email']) || empty($data['company'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tutti i campi sono obbligatori']);
    exit();
}

$name = filter_var($data['name'], FILTER_SANITIZE_STRING);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$company = filter_var($data['company'], FILTER_SANITIZE_STRING);

// Validazione email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email non valida']);
    exit();
}

// Configurazione email
$to = "info@oneblade.it";
$subject = "Nuova iscrizione alla Lista d'Attesa CandyHire";

// Corpo dell'email HTML
$message = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #F7C7D9 0%, #ffc1e0 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .header h1 { color: #d91b5c; margin: 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #F7C7D9; border-top: none; border-radius: 0 0 10px 10px; }
        .field { margin-bottom: 15px; }
        .field strong { color: #d91b5c; }
        .footer { text-align: center; margin-top: 20px; padding: 20px; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🚀 Nuova Iscrizione CandyHire</h1>
        </div>
        <div class='content'>
            <p>Hai ricevuto una nuova iscrizione alla lista d'attesa di CandyHire!</p>

            <div class='field'>
                <strong>Nome:</strong> " . htmlspecialchars($name) . "
            </div>

            <div class='field'>
                <strong>Email:</strong> <a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a>
            </div>

            <div class='field'>
                <strong>Azienda:</strong> " . htmlspecialchars($company) . "
            </div>

            <div class='field'>
                <strong>Data iscrizione:</strong> " . date('d/m/Y H:i:s') . "
            </div>
        </div>
        <div class='footer'>
            <p>Questo messaggio è stato generato automaticamente dal sistema CandyHire Portal</p>
        </div>
    </div>
</body>
</html>
";

// Headers per email HTML
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
$headers .= "From: CandyHire <noreply@candyhire.com>" . "\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Invia l'email
$emailSent = mail($to, $subject, $message, $headers);

if ($emailSent) {
    // Opzionale: Invia email di conferma all'utente
    $confirmSubject = "Benvenuto nella Lista d'Attesa di CandyHire!";
    $confirmMessage = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #F7C7D9 0%, #ffc1e0 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { color: #d91b5c; margin: 0; }
            .content { background: #fff; padding: 30px; border: 1px solid #F7C7D9; border-top: none; }
            .benefits { background: #FFF9FB; padding: 20px; border-radius: 10px; margin: 20px 0; }
            .benefit { margin-bottom: 15px; display: flex; align-items: start; }
            .benefit-icon { color: #d91b5c; margin-right: 10px; font-size: 20px; }
            .cta-button { display: inline-block; background: linear-gradient(135deg, #F7C7D9 0%, #ffc1e0 100%); color: #d91b5c; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; padding: 20px; color: #999; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Benvenuto in CandyHire!</h1>
            </div>
            <div class='content'>
                <p>Ciao <strong>" . htmlspecialchars($name) . "</strong>,</p>

                <p>Grazie per esserti iscritto alla lista d'attesa di CandyHire! Siamo entusiasti di averti con noi.</p>

                <div class='benefits'>
                    <h2 style='color: #d91b5c; margin-top: 0;'>Ecco cosa riceverai:</h2>

                    <div class='benefit'>
                        <span class='benefit-icon'>🎮</span>
                        <div><strong>Demo interattiva con dati pre-compilati</strong><br>Esplora tutte le funzionalità di CandyHire senza dover inserire dati</div>
                    </div>

                    <div class='benefit'>
                        <span class='benefit-icon'>🚀</span>
                        <div><strong>Accesso anticipato</strong><br>Sarai tra i primi a provare il prodotto finale</div>
                    </div>

                    <div class='benefit'>
                        <span class='benefit-icon'>🎁</span>
                        <div><strong>Sconto early bird</strong><br>Prezzo speciale riservato agli iscritti alla lista d'attesa</div>
                    </div>
                </div>

                <p><strong>Cosa succede ora?</strong></p>
                <p>Ti invieremo presto:</p>
                <ul>
                    <li>Il link alla demo con dati pre-compilati</li>
                    <li>Aggiornamenti esclusivi sullo sviluppo</li>
                    <li>L'invito al lancio ufficiale con l'offerta early bird</li>
                </ul>

                <p>Nel frattempo, se hai domande o vuoi condividere feedback, rispondi pure a questa email!</p>

                <p>A presto,<br><strong>Il Team CandyHire</strong></p>
            </div>
            <div class='footer'>
                <p>CandyHire - Il recruiting più dolce che c'è 🍬</p>
                <p style='margin-top: 10px;'>
                    <a href='http://localhost:4200' style='color: #F7C7D9;'>Visita il sito</a>
                </p>
            </div>
        </div>
    </body>
    </html>
    ";

    $confirmHeaders = "MIME-Version: 1.0" . "\r\n";
    $confirmHeaders .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $confirmHeaders .= "From: CandyHire <noreply@candyhire.com>" . "\r\n";
    $confirmHeaders .= "Reply-To: info@oneblade.it" . "\r\n";

    mail($email, $confirmSubject, $confirmMessage, $confirmHeaders);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Iscrizione completata con successo! Controlla la tua email per i prossimi passi.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore nell\'invio dell\'email. Riprova più tardi.'
    ]);
}
?>
