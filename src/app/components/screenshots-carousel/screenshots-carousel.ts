import { Component } from '@angular/core';

interface Screenshot {
  id: number;
  image: string;
  title: string;
  description: string;
}

@Component({
  selector: 'app-screenshots-carousel',
  imports: [],
  templateUrl: './screenshots-carousel.html',
  styleUrl: './screenshots-carousel.scss',
})
export class ScreenshotsCarousel {
  protected currentSlide = 0;

  protected readonly screenshots: Screenshot[] = [
    {
      id: 1,
      image: '/ScreenCandyHire/1_dashboard.png',
      title: 'Dashboard Intuitiva',
      description: 'Visualizza tutte le metriche chiave del tuo processo di recruiting in un colpo d\'occhio'
    },
    {
      id: 2,
      image: '/ScreenCandyHire/2_Jobs.png',
      title: 'Gestione Posizioni',
      description: 'Crea e pubblica annunci di lavoro su multiple piattaforme con un solo click'
    },
    {
      id: 3,
      image: '/ScreenCandyHire/3_Candidates.png',
      title: 'Database Candidati',
      description: 'Organizza e traccia tutti i tuoi candidati con filtri avanzati e ricerca veloce'
    },
    {
      id: 4,
      image: '/ScreenCandyHire/4_Recruiters.png',
      title: 'Team Recruiting',
      description: 'Gestisci il tuo team HR e assegna candidati ai recruiter appropriati'
    },
    {
      id: 5,
      image: '/ScreenCandyHire/5_Analytics.png',
      title: 'Analytics Avanzate',
      description: 'Analizza le performance del recruiting con report dettagliati e KPI in tempo reale'
    }
  ];

  protected nextSlide(): void {
    if (this.currentSlide < this.screenshots.length - 1) {
      this.currentSlide++;
    }
  }

  protected previousSlide(): void {
    if (this.currentSlide > 0) {
      this.currentSlide--;
    }
  }

  protected goToSlide(index: number): void {
    this.currentSlide = index;
  }
}
