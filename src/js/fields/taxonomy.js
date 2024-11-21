export default ($, SlimSelect) => {

    window.fe_hooks.add_action('init_post_form', (options) => {
        add_slim_to_selects()
    })

    function add_slim_to_selects() {
        let deselectLabel = '<span>✖</span>';
        const selects = document.querySelectorAll('.taxonomy-select:not([data-ssid]');

        selects.forEach((select) => {
            let placeholder = select.hasAttribute("data-placeholder") ? select.getAttribute("data-placeholder") : '',
                add_new = select.hasAttribute("data-add-new") ? select.getAttribute("data-placeholder") : false,
                searchPlaceholder = select.hasAttribute("data-search-placeholder") ? select.getAttribute("data-search-placeholder") : 'Search',
                searchText = select.hasAttribute("data-search-text") ? select.getAttribute("data-search-text") : 'No Results';

            var select = new SlimSelect({
                text: 'text',
                select: select,
                placeholder: placeholder,
                hideSelectedOption: true,
                deselectLabel: deselectLabel,
                searchPlaceholder,
                searchText,
                ...(add_new
                    && { addable: (value) => { return AddableSlimSelect(value) } }
                )
            })
        })
    }


    /**
     * New value adding function
     * @param {*} value 
     */
    function AddableSlimSelect(value) {
        // // return false or null if you do not want to allow value to be submitted
        // if (value.length < 3) {
        //     callback('Need 3 characters')
        //     return
        // }

        // Optional - Return a valid data object. See methods/setData for list of valid options
        return {
            text: value,
            value: value.toLowerCase()
        }
    }

}
