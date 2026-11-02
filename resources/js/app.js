import Alpine from 'alpinejs';
import cityAutocomplete from './city-autocomplete';
import eventImageForm from './event-image-form';
import createGallery from './create-gallery';

window.Alpine = Alpine;
Alpine.data('cityAutocomplete', cityAutocomplete);
Alpine.data('eventImageForm', eventImageForm);
Alpine.data('createGallery', createGallery);

Alpine.start();
