import { Component } from '@angular/core';
import { ScreenshotsCarousel } from '../../components/screenshots-carousel/screenshots-carousel';

@Component({
  selector: 'app-home',
  imports: [ScreenshotsCarousel],
  templateUrl: './home.html',
  styleUrl: './home.scss',
})
export class Home {
  protected readonly bubblesArray = Array.from({ length: 20 }, (_, i) => i + 1);
}
