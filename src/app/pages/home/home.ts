import { Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { ScreenshotsCarousel } from '../../components/screenshots-carousel/screenshots-carousel';
import { TiersDisplay } from '../../components/tiers-display/tiers-display';
import { RotatingPhrases } from '../../components/rotating-phrases/rotating-phrases';
import { TranslationService } from '../../core/services/translation.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-home',
  imports: [ScreenshotsCarousel, TiersDisplay, RotatingPhrases],
  templateUrl: './home.html',
  styleUrl: './home.scss',
})
export class Home {
  protected readonly bubblesArray = Array.from({ length: 20 }, (_, i) => i + 1);
  protected translationService = inject(TranslationService);
  saasUrl = environment.saasUrl || 'http://localhost:4202';

  constructor(private router: Router) {}

  goToRegister(): void {
    this.router.navigate(['/register']);
  }

  t(key: string): string {
    return this.translationService.t(key);
  }
}
