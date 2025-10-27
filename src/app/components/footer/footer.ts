import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-footer',
  imports: [RouterLink],
  templateUrl: './footer.html',
  styleUrl: './footer.scss',
})
export class Footer {
  protected readonly whatsappNumber = '393793101426';
  protected readonly whatsappMessage = `Ciao!
Ho visto CandyHire e sono interessato/a a scoprire come può rendere più dolce e semplice il recruiting del mio team HR!
Vorrei avere maggiori informazioni sul prodotto e capire come può aiutarmi nella gestione dei candidati.
Grazie!`;

  protected get whatsappUrl(): string {
    const encodedMessage = encodeURIComponent(this.whatsappMessage);
    return `https://wa.me/${this.whatsappNumber}?text=${encodedMessage}`;
  }
}
