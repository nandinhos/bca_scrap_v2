import flatpickr from 'flatpickr';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';
import 'flatpickr/dist/flatpickr.min.css';
// Configure flatpickr defaults
flatpickr.localize(Portuguese);
window.flatpickr = flatpickr;
