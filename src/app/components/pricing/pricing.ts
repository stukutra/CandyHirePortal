import { Component, inject } from '@angular/core';
import { WaitlistModalService } from '../../services/waitlist-modal';

@Component({
  selector: 'app-pricing',
  imports: [],
  templateUrl: './pricing.html',
  styleUrl: './pricing.scss',
})
export class Pricing {
  private modalService = inject(WaitlistModalService);

  openWaitlist(): void {
    this.modalService.open();
  }
}
