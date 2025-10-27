import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { WaitlistModalService } from '../../services/waitlist-modal';
import { EarlyAdopterService } from '../../services/early-adopter';

@Component({
  selector: 'app-pricing',
  imports: [CommonModule],
  templateUrl: './pricing.html',
  styleUrl: './pricing.scss',
})
export class Pricing {
  private modalService = inject(WaitlistModalService);
  earlyAdopterService = inject(EarlyAdopterService);

  openWaitlist(): void {
    this.modalService.open();
  }
}
