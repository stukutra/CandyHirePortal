import { Injectable, signal, PLATFORM_ID, inject } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';

export interface EarlyAdopter {
  companyName: string;
  email: string;
  role: string;
  joinedDate: string;
}

@Injectable({
  providedIn: 'root'
})
export class EarlyAdopterService {
  private platformId = inject(PLATFORM_ID);
  private isBrowser: boolean;

  // Configuration
  private readonly MAX_SLOTS = 20;
  private readonly BASE_REGISTERED = 4;
  private readonly STORAGE_KEY = 'candyhire_early_adopters';

  // Signals for reactive state
  earlyAdopters = signal<EarlyAdopter[]>([]);
  registeredCompanies = signal<number>(this.BASE_REGISTERED);

  // Mock companies that have already joined (for ticker)
  mockCompanies = [
    'TechRecruit Italia',
    'HR Solutions Group',
    'StartUp Talents',
    'Digital Hiring Agency',
    'People & Growth',
    'Smart Recruiting Team',
    'Future HR Partners'
  ];

  constructor() {
    this.isBrowser = isPlatformBrowser(this.platformId);

    if (this.isBrowser) {
      this.loadData();
      this.startSimulation();
    }
  }

  /**
   * Load early adopters from localStorage
   */
  private loadData(): void {
    if (!this.isBrowser) return;

    try {
      const stored = localStorage.getItem(this.STORAGE_KEY);
      if (stored) {
        const data = JSON.parse(stored);
        this.earlyAdopters.set(data);
        this.registeredCompanies.set(this.BASE_REGISTERED + data.length);
      }
    } catch (error) {
      console.error('Error loading early adopters:', error);
    }
  }

  /**
   * Save early adopters to localStorage
   */
  private saveData(): void {
    if (!this.isBrowser) return;

    try {
      localStorage.setItem(this.STORAGE_KEY, JSON.stringify(this.earlyAdopters()));
    } catch (error) {
      console.error('Error saving early adopters:', error);
    }
  }

  /**
   * Add new early adopter
   */
  addEarlyAdopter(adopter: EarlyAdopter): boolean {
    if (this.remainingSlots() <= 0) {
      return false;
    }

    this.earlyAdopters.update(current => [...current, adopter]);
    this.registeredCompanies.update(count => count + 1);
    this.saveData();
    return true;
  }

  /**
   * Get remaining slots
   */
  remainingSlots(): number {
    return Math.max(0, this.MAX_SLOTS - this.registeredCompanies());
  }

  /**
   * Get percentage of filled slots
   */
  slotsPercentage(): number {
    return (this.registeredCompanies() / this.MAX_SLOTS) * 100;
  }

  /**
   * Check if slots are almost full (>80%)
   */
  isAlmostFull(): boolean {
    return this.slotsPercentage() > 80;
  }

  /**
   * Check if slots are full
   */
  isFull(): boolean {
    return this.remainingSlots() <= 0;
  }

  /**
   * Start simulation of new registrations
   */
  private startSimulation(): void {
    if (!this.isBrowser) return;

    setInterval(() => {
      if (this.registeredCompanies() < this.MAX_SLOTS && Math.random() < 0.15) {
        this.registeredCompanies.update(count => Math.min(this.MAX_SLOTS, count + 1));
      }
    }, 45000); // Every 45 seconds, 15% chance
  }

  /**
   * Get max slots
   */
  getMaxSlots(): number {
    return this.MAX_SLOTS;
  }
}
