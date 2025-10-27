import { Component, inject } from '@angular/core';
import { RouterOutlet, RouterLink } from '@angular/router';
import { Footer } from './components/footer/footer';
import { WaitlistModal } from './components/waitlist-modal/waitlist-modal';
import { WaitlistModalService } from './services/waitlist-modal';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, RouterLink, Footer, WaitlistModal],
  templateUrl: './app.html',
  styleUrl: './app.scss'
})
export class App {
  protected modalService = inject(WaitlistModalService);

  openWaitlistModal(): void {
    this.modalService.open();
  }

  closeWaitlistModal(): void {
    this.modalService.close();
  }
}
