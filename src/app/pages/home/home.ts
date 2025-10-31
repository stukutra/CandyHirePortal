import { Component, inject } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { ScreenshotsCarousel } from '../../components/screenshots-carousel/screenshots-carousel';
import { Pricing } from '../../components/pricing/pricing';
import { RotatingPhrases } from '../../components/rotating-phrases/rotating-phrases';
import { TranslationService } from '../../core/services/translation.service';

@Component({
  selector: 'app-home',
  imports: [ScreenshotsCarousel, Pricing, RotatingPhrases, RouterLink],
  templateUrl: './home.html',
  styleUrl: './home.scss',
})
export class Home {
  protected readonly bubblesArray = Array.from({ length: 20 }, (_, i) => i + 1);
  protected translationService = inject(TranslationService);

  constructor(private router: Router) {}

  goToRegister(): void {
    this.router.navigate(['/register']);
  }

  t(key: string): string {
    return this.translationService.t(key);
  }
}
