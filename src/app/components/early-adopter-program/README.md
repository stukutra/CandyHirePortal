# Early Adopter Program Component

## 📋 Descrizione

Componente Angular standalone per la pagina del programma Early Adopter di CandyHire. Permette alle aziende di iscriversi al programma beta con vantaggi esclusivi.

## 🎯 Obiettivo

Visualizzare un pannello promozionale per il programma "Early Adopter" con:
- Hero section accattivante
- Contatore dinamico di slot disponibili
- 3 card con vantaggi principali
- Modal di registrazione
- Messaggio di conferma

## 🧱 Struttura

### Hero Section
- Titolo: "Programma Early Adopter"
- Badge esclusivo animato
- Descrizione del programma

### Contatore Slot
- Variabile `registeredCompanies` (default: 7)
- Variabile `maxSlots` (default: 20)
- Barra di progresso visuale
- Testo dinamico con slot rimanenti
- Alert urgenza quando >80% pieno

### Card Vantaggi (3 colonne)
1. **Prezzo Bloccato a Vita**
   - Da €2.000 → €1.500
   - Risparmio garantito per sempre

2. **Supporto Diretto del Team** (Featured)
   - Onboarding personalizzato
   - Risposta entro 2 ore
   - Consulenza strategica

3. **Accesso Anticipato Beta**
   - Nuove feature in anteprima
   - Feedback diretto con i dev
   - Personalizzazioni prioritarie

### Modal di Registrazione
Campi form:
- Nome Azienda (text, required)
- Email Aziendale (email, required)
- Ruolo (select, required)
  - HR Manager
  - Recruiter
  - CEO/Founder
  - Operations Manager
  - Talent Acquisition
  - Altro
- Consenso Privacy (checkbox, required)

## ⚙️ Logica

### Gestione Dati
- I dati vengono salvati in `localStorage` con chiave `candyhire_early_adopters`
- Struttura dati: `EarlyAdopter[]`
  ```typescript
  interface EarlyAdopter {
    companyName: string;
    email: string;
    role: string;
    date: string; // ISO format
  }
  ```

### Simulazione Engagement
- Intervallo: ogni 30 secondi
- Probabilità: 20% di incrementare il contatore
- Solo se ci sono ancora slot disponibili

### Funzioni Principali

#### `loadEarlyAdopters()`
Carica le iscrizioni da localStorage e aggiorna il contatore

#### `saveEarlyAdopters()`
Salva le iscrizioni in localStorage

#### `startSimulation()`
Avvia la simulazione di nuove registrazioni (ogni 30s, 20% chance)

#### `updateSlots(increment: number)`
Aggiorna il contatore di aziende registrate

#### `validateForm()`
Valida i campi del form prima dell'invio

#### `submitForm()`
Gestisce l'invio del form:
1. Valida i dati
2. Simula chiamata API (1.5s delay)
3. Salva in localStorage
4. Aggiorna contatore
5. Mostra messaggio di successo

### Proprietà Computed

#### `remainingSlots: number`
Calcola gli slot rimanenti (maxSlots - registeredCompanies)

#### `slotsPercentage: number`
Calcola la percentuale di slot occupati per la barra di progresso

#### `isAlmostFull: boolean`
Verifica se >80% degli slot sono occupati (per mostrare alert urgenza)

## 🎨 Stile

- **Palette**: CandyHire (rosa pastello, bianco, ombre leggere)
- **Framework**: SCSS con variabili CSS custom
- **Responsive**: Mobile-first con breakpoints a 768px e 1024px
- **Animazioni**:
  - `pulse`: Badge hero
  - `sparkle`: Icone stella
  - `rotate`: Clessidra contatore
  - `bounce`: Icona modal
  - `blink`: Alert urgenza
  - `scaleIn`: Icona successo

### Bubble Background
Effetto "bubble floating" di background usando `styles-bubbles.scss`

## 📦 File Creati

1. **early-adopter-program.ts** - Component TypeScript con logica
2. **early-adopter-program.html** - Template HTML
3. **early-adopter-program.scss** - Stili SCSS
4. **mock-early-adopters.json** - Dati mock di esempio (7 aziende)
5. **README.md** - Questa documentazione

## 🚀 Routing

La pagina è accessibile all'URL: `/early-access`

Configurazione in `app.routes.ts`:
```typescript
{ path: 'early-access', component: EarlyAdopterProgram }
```

## 📊 Mock Data

File: `src/assets/mock-early-adopters.json`

Contiene 7 aziende di esempio che hanno già fatto l'iscrizione, utilizzate per inizializzare il contatore a 7.

## 💾 LocalStorage

Chiave: `candyhire_early_adopters`

Formato:
```json
[
  {
    "companyName": "Tech Innovators SRL",
    "email": "hr@techinnovators.it",
    "role": "HR Manager",
    "date": "2025-01-15T10:30:00.000Z"
  }
]
```

## 🔄 Lifecycle

### OnInit
- Carica early adopters da localStorage
- Avvia simulazione di nuove registrazioni

### OnDestroy
- Pulisce l'intervallo di simulazione

## ✅ Features

- ✅ Contatore dinamico slot
- ✅ Simulazione engagement (ogni 30s)
- ✅ 3 card vantaggi responsive
- ✅ Modal registrazione
- ✅ Validazione form completa
- ✅ Messaggio successo
- ✅ Persistenza dati localStorage
- ✅ Animazioni fluide
- ✅ Design mobile-responsive
- ✅ Bubble background effect

## 🎯 TODO Futuro

- [ ] Collegamento a backend reale
- [ ] Invio email di conferma
- [ ] Dashboard admin per gestire iscrizioni
- [ ] Export dati in CSV
- [ ] Contatore reale da backend
- [ ] Integrare con analytics

## 🔗 Link Utili

- Rotta: `/early-access`
- Privacy Policy: `/privacy-policy`
- Cookie Policy: `/cookie-policy`
- Terms: `/terms-conditions`
