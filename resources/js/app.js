import Alpine from 'alpinejs';
import cityAutocomplete from './city-autocomplete';
import eventImageForm from './event-image-form';

window.Alpine = Alpine;
Alpine.data('cityAutocomplete', cityAutocomplete);
Alpine.data('eventImageForm', eventImageForm);

Alpine.start();
