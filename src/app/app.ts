import { Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet],
  templateUrl: './app.html',
  styleUrl: './app.scss'
})
export class App {
  protected readonly title = signal('CandyHirePortal');
  protected readonly bubblesArray = Array.from({ length: 20 }, (_, i) => i + 1);
}
