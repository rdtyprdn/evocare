import Alpine from 'alpinejs';
import cityAutocomplete from './city-autocomplete';
import eventImageForm from './event-image-form';
import createGallery from './create-gallery';
import questionBuilder from './question-builder';
import registrationForm from './registration-form';

window.Alpine = Alpine;
Alpine.data('cityAutocomplete', cityAutocomplete);
Alpine.data('eventImageForm', eventImageForm);
Alpine.data('createGallery', createGallery);
Alpine.data('questionBuilder', questionBuilder);
Alpine.data('registrationForm', registrationForm);

Alpine.start();
