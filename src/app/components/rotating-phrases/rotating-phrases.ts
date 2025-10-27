import { Component, OnInit, OnDestroy, signal, Inject, PLATFORM_ID } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';

@Component({
  selector: 'app-rotating-phrases',
  imports: [],
  templateUrl: './rotating-phrases.html',
  styleUrl: './rotating-phrases.scss',
})
export class RotatingPhrases implements OnInit, OnDestroy {
  protected currentTestimonial = signal('');
  protected currentAuthor = signal('');
  protected isVisible = signal(true);

  private currentIndex = 0;
  private intervalId?: ReturnType<typeof setInterval>;

  constructor(@Inject(PLATFORM_ID) private platformId: object) {}

  protected readonly testimonials = [
    {
      text: "\"Ho visto l'anteprima e già non vedo l'ora di usarlo. Sembra davvero semplice!\"",
      author: "Laura, HR Manager - Beta Tester"
    },
    {
      text: "\"Durante la demo ho pensato: finalmente qualcosa che capisco al volo.\"",
      author: "Marco, Talent Acquisition - Early Access"
    },
    {
      text: "\"Nella preview mi ha colpito quanto sia intuitivo. Non vedo l'ora!\"",
      author: "Giulia, People & Culture - Beta Tester"
    },
    {
      text: "\"Testato in anteprima: se sarà così anche live, sarà una rivoluzione.\"",
      author: "Andrea, HR Specialist - Early Tester"
    },
    {
      text: "\"Ho partecipato ai test e posso dire: è il tool che cercavo da anni.\"",
      author: "Sara, Recruiter - Beta Program"
    },
    {
      text: "\"La demo mi ha convinto. Aspetto con ansia il lancio ufficiale!\"",
      author: "Davide, Head of HR - Preview Access"
    }
  ];

  ngOnInit(): void {
    // Imposta la prima testimonianza
    this.currentTestimonial.set(this.testimonials[0].text);
    this.currentAuthor.set(this.testimonials[0].author);

    // Avvia il ciclo di rotazione solo nel browser
    if (isPlatformBrowser(this.platformId)) {
      this.startRotation();
    }
  }

  ngOnDestroy(): void {
    if (this.intervalId) {
      clearInterval(this.intervalId);
    }
  }

  private startRotation(): void {
    // Durata più lunga per dare tempo di leggere le testimonianze
    const isMobile = window.innerWidth <= 768;
    const intervalDuration = isMobile ? 4000 : 5000; // 5s desktop, 4s mobile

    this.intervalId = setInterval(() => {
      // Fade out
      this.isVisible.set(false);

      // Dopo 500ms (tempo fade out), cambia frase e fade in
      setTimeout(() => {
        this.currentIndex = (this.currentIndex + 1) % this.testimonials.length;
        this.currentTestimonial.set(this.testimonials[this.currentIndex].text);
        this.currentAuthor.set(this.testimonials[this.currentIndex].author);
        this.isVisible.set(true);
      }, 500);
    }, intervalDuration);
  }
}
