import { Component } from '@angular/core';

@Component({
  selector: 'app-whatsapp-button',
  imports: [],
  templateUrl: './whatsapp-button.html',
  styleUrl: './whatsapp-button.scss',
})
export class WhatsappButton {
  protected readonly whatsappNumber = '393793101426'; // Numero senza spazi e simboli
  protected readonly whatsappMessage = `Ciao!
Ho visto CandyHire e sono interessato/a a scoprire come può rendere più dolce e semplice il recruiting del mio team HR!
Vorrei avere maggiori informazioni sul prodotto e capire come può aiutarmi nella gestione dei candidati.
Grazie!`;

  protected get whatsappUrl(): string {
    const encodedMessage = encodeURIComponent(this.whatsappMessage);
    return `https://wa.me/${this.whatsappNumber}?text=${encodedMessage}`;
  }
}
