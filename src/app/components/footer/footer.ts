import { Component, inject, computed } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TranslationService } from '../../core/services/translation.service';

@Component({
  selector: 'app-footer',
  imports: [RouterLink],
  templateUrl: './footer.html',
  styleUrl: './footer.scss',
})
export class Footer {
  protected translationService = inject(TranslationService);
  protected readonly whatsappNumber = '393793101426';

  protected whatsappUrl = computed(() => {
    const message = this.translationService.t('footer.whatsapp.message');
    const encodedMessage = encodeURIComponent(message);
    return `https://wa.me/${this.whatsappNumber}?text=${encodedMessage}`;
  });

  t(key: string): string {
    return this.translationService.t(key);
  }
}
