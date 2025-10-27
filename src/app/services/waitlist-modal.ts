import { Injectable, signal } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class WaitlistModalService {
  public showModal = signal(false);

  open(): void {
    this.showModal.set(true);
  }

  close(): void {
    this.showModal.set(false);
  }
}
