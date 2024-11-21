import { Loader } from '@googlemaps/js-api-loader';

export default ($) => {

    const loader = new Loader({
        apiKey: editor_data.google_map_api,
        version: 'weekly',
        libraries: ['places']
    });

    loader.load().then(() => {
        const inputs = document.querySelectorAll('.autocomplete');

        if (inputs.length > 0) {
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
                    console.log('Selected place:', place);
                });

            });
        } else {
            console.log('No elements with the class "autocomplete" found.');
        }
    }).catch((error) => {
        console.error('Error loading Google Maps API:', error);
    });

}
