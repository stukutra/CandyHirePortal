import { Component, inject } from '@angular/core';
import { ScreenshotsCarousel } from '../../components/screenshots-carousel/screenshots-carousel';
import { Pricing } from '../../components/pricing/pricing';
import { WaitlistModalService } from '../../services/waitlist-modal';
import { EarlyAdopterService } from '../../services/early-adopter';

@Component({
  selector: 'app-home',
  imports: [ScreenshotsCarousel, Pricing],
  templateUrl: './home.html',
  styleUrl: './home.scss',
})
export class Home {
  protected readonly bubblesArray = Array.from({ length: 20 }, (_, i) => i + 1);
  private modalService = inject(WaitlistModalService);
  earlyAdopterService = inject(EarlyAdopterService);

  openWaitlistModal(): void {
    this.modalService.open();
  }
}
