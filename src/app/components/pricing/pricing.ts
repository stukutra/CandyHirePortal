import { Component } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-pricing',
  imports: [],
  templateUrl: './pricing.html',
  styleUrl: './pricing.scss',
})
export class Pricing {
  constructor(private router: Router) {}

  goToRegister(): void {
    this.router.navigate(['/register']);
  }
}
