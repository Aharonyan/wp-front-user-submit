import { Loader } from '@googlemaps/js-api-loader';

export default ($) => {

    const inputs = document.querySelectorAll('input.autocomplete[name^="google_map_input["]');

    // Load the Google Maps API only on pages that have a Google Map field
    if (inputs.length === 0) {
        return;
    }

    const loader = new Loader({
        apiKey: editor_data.google_map_api,
        version: 'weekly',
        libraries: ['places']
    });

    loader.load().then(() => {
        inputs.forEach(function (input) {
            const autocomplete = new google.maps.places.Autocomplete(input);

            const country_code = input.getAttribute('data-country-code');
            autocomplete.setOptions({
                types: ['geocode'],
                componentRestrictions: { country: (country_code) ? country_code : 'us' }
            });

            const parentElement = input.parentElement;
            const targetElement = parentElement.querySelector('.google-map');
            if (targetElement.value != '') {
                const save_json =  JSON.parse(targetElement.value);
                input.value = save_json.formatted_address;
            }

            autocomplete.addListener('place_changed', function () {
                const place = autocomplete.getPlace();
                if (!place.geometry) {
                    console.log("No details available for input: '" + place.name + "'");
                    return;
                }
                targetElement.value = JSON.stringify(place);
            });

        });
    }).catch((error) => {
        console.error('Error loading Google Maps API:', error);
    });

}
