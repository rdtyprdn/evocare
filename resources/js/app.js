import Alpine from 'alpinejs';
import cityAutocomplete from './city-autocomplete';

window.Alpine = Alpine;
Alpine.data('cityAutocomplete', cityAutocomplete);

Alpine.start();
