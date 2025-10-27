import { Component } from '@angular/core';
import { RouterOutlet, RouterLink } from '@angular/router';
import { Footer } from './components/footer/footer';
import { WhatsappButton } from './components/whatsapp-button/whatsapp-button';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, RouterLink, Footer, WhatsappButton],
  templateUrl: './app.html',
  styleUrl: './app.scss'
})
export class App {
}
