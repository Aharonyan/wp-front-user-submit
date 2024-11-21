export default (form, $, SlimSelect, Swal) => {
    let post_form = $(form),
        post_form_id = post_form.attr('id'),
        fe_data = window.editor_data,
        button_menu = post_form.find('.fus-form-block-header .sub-header.top'),
        saveButton = post_form.find('.form-submit'),
        saveDraftButton = post_form.find('.form-save-draft'),
        fixmeTop = button_menu.offset().top,
        post_link = post_form.find('.view-page'),
        post_id = post_form.find('.fus_post_id');

    const currentForm = document.getElementById(post_form_id);

    /**
    * Init post form hook
    */
    window.fe_hooks.do_action('init_post_form')


    $(window).scroll(function () {
        var currentScroll = $(window).scrollTop();
        if (currentScroll >= fixmeTop) {
            button_menu.addClass('sticky');
        } else {                                   // apply position: static
            button_menu.removeClass('sticky');
        }

    });

    /**
     * On form submit
     */
    saveButton.on('click', (ev) => {
        ev.preventDefault()
        window.fe_hooks.do_action('on_post_form_save')
        save_data()
    });

    /**
     * On form draft submit
     */
    saveDraftButton.on('click', (ev) => {
        ev.preventDefault()
        window.fe_hooks.do_action('on_post_form_save')
        save_data(true)
    });


    /**
     * Save data from admin block
     * @param {*} data 
     */
    function save_data(save_to_draft = false) {
        let save_button_messages = fe_data.translations.save_button,
            save_button = document.querySelector(`#${post_form_id} .form-submit`),
            draft_button = document.querySelector(`#${post_form_id} .form-save-draft`),
            thumb_exist = document.querySelector(`#${post_form_id} .image_loader`),
            show_loading = document.querySelector(`#${post_form_id} #fus-loader`);

        show_loading.classList.toggle("active");
        const formData = new FormData(form);
        formData.append('action', 'bfe_update_post')
        formData.append('save_to_draft', save_to_draft)

        /**
         * Sending exist or not post image to understand delete or not it from post
         */
        if (thumb_exist) {
            formData.append('thumb_exist', thumb_exist.getAttribute('thumb_exist'))
        } else {
            formData.append('thumb_exist', 0)
        }

        /**
         * Updating taxonomy fields
         */
        $('.taxonomy-select').each((index, element) => {
            let element_val = $(element).val(),
                selected_element = element_val.toString(),
                name = $(element).attr('name');
            if (element_val) {
                formData.append(name, selected_element)
            } else {
                formData.append(name, 'null')
            }
        })

        save_button.disabled = true;

        if (save_to_draft) {
            draft_button.disabled = true;
        }

        window.fe_hooks.do_action('before_sending_ajax_request')

        formData.append('x-wp-nonce', fe_data.nonce)

        fetch(fe_data.rest_url_update_form, {
            method: 'POST',
            body: formData,
            headers: {
                'X-WP-Nonce': fe_data.nonce
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    save_button.disabled = false;

                    if (save_to_draft) {
                        draft_button.disabled = false;
                    }

                    /**
                     * New post add link and show the button
                     */
                    post_link.attr('href', data.data.url);
                    post_link.removeClass("hidden");

                    post_form.attr('post_id', data.data.post_id)
                    post_id.val(data.data.post_id)

                    if (data.data.message_type) {
                        show_message(data.data.message_type, data.data.message)
                    } else {
                        show_message('success', data.data.message)
                    }

                    // if we have redirection
                    if (window.fus_is_valid_url(data.data.redirect_to)) {
                        window.location.replace(data.data.redirect_to);
                    }

                    save_button.innerHTML = save_button_messages.update;
                    update_url_param('post_id', data.data.post_id)
                    show_loading.classList.toggle("active");
                    currentForm.querySelectorAll(`.field_error`).forEach((elem) => { elem.classList.remove('field_error') });
                } else {
                    save_button.disabled = false;

                    if (save_to_draft) {
                        draft_button.disabled = false;
                    }

                    currentForm.querySelectorAll(`.field_error`).forEach((elem) => { elem.classList.remove('field_error') });
                    show_message('error', data.data.message)
                    show_loading.classList.toggle("active");

                    if (data.data.field) {
                        const allFields = currentForm.querySelectorAll(`.${data.data.field}`);
                        console.log(allFields)
                        if (allFields.length <= 0) {
                            return;
                        }
                        allFields.forEach(
                            (elem) => {
                                elem.classList.add('field_error')
                            }
                        );

                        const lastField = allFields[allFields.length - 1];

                        lastField.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',    // Align to the top of the viewport
                            inline: 'nearest'  // Horizontal alignment if needed
                        });

                        currentForm.querySelectorAll(`.field_error`).forEach((elem) => {
                            elem.addEventListener('click', (elem) => {
                                elem.currentTarget.classList.remove('field_error')
                            })
                        }
                        );
                    }
                }
            }).catch((response) => {
                show_message('error', response.statusText)
                show_loading.classList.toggle("active");
            })
    }

    function update_url_param(key, value) {
        let url = new URL(window.location.href);
        let params = new URLSearchParams(url.search);

        // Add a third parameter.
        params.set(key, value);

        window.history.replaceState({}, '', `${location.pathname}?${params}`);
    }


    function show_message(type, message) {
        let place = 'bottom';

        if (!type) {
            type = 'error';
        }

        if (!message) {
            message = fe_data.translations.default_error_message
        }

        if (fe_data.form_settings.error_success_messages) {
            place = fe_data.form_settings.error_success_messages;
        }

        if (place == 'default') {
            Swal.fire({
                position: 'bottom-end',
                icon: type,
                html: message,
                showConfirmButton: false,
                timer: 2500
            })
        } else {
            let message_wrapper = document.getElementById('fus-message-wrap')
            let error_types = ['error', 'success', 'warning']
            message_wrapper.classList.remove(...error_types)
            message_wrapper.classList.add(type)
            message_wrapper.innerHTML = message
        }
    }

    function objectifyForm(formArray) {
        //serialize data function
        var returnArray = {};
        for (var i = 0; i < formArray.length; i++) {
            returnArray[formArray[i]['name']] = formArray[i]['value'];
        }
        return returnArray;
    }


}



