import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import flatpickr from 'flatpickr';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';
import 'flatpickr/dist/flatpickr.min.css';

// Register Alpine plugins
Alpine.plugin(collapse);

// Configure flatpickr defaults
flatpickr.localize(Portuguese);
window.flatpickr = flatpickr;
window.Alpine = Alpine;
