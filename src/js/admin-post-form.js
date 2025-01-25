export default ($, Swal) => {
    // Make plugin menu active if we in editing page
    $('li.wp-has-current-submenu').removeClass('wp-has-current-submenu')
    $('li.toplevel_page_front_editor_settings').addClass('wp-has-current-submenu')

    $('.nav-tab.top').click((ev) => {
        activateTopTab($(ev.target).attr('href'))
    });

    $('.nav-tab.sub').click((ev) => {
        activateSubTub($(ev.target).attr('href'))
    });

    // get current hash from url and activate needed settings
    $(window).on('load', () => {
        var hash = window.location.hash;
        if ($(hash)) {
            if ($(hash).hasClass('top')) {
                activateTopTab(hash)
            }

            if ($(hash).hasClass('sub')) {
                activateSubTub(hash)
                activateTopTab('#' + $(hash).closest('.group.top').attr('id'))
            }
        }
    })

    function activateTopTab(id) {
        $('.nav-tab.top').removeClass('nav-tab-active')
        $('.group.top').removeClass('active')

        $(`[href*="${id}"]`).addClass('nav-tab-active')
        $(id).addClass('active')
    }

    function activateSubTub(id) {

        $(id).closest('.group.top').find('.nav-tab.sub').removeClass('nav-tab-active')
        $(id).closest('.group.top').find('.group.sub').removeClass('active')

        $(`[href*="${id}"]`).addClass('nav-tab-active')
        $(id).addClass('active')
    }


    let localizeData = window.fe_post_form_data,
        admin_form_builder_nonce = $('#admin_form_builder_nonce').val(),
        formBuilderContainer = false,
        current_forBuilder_controls,
        updated_forBuilder_controls,
        formBuilderOptions;

    let options = {
        notify: {
            error: function (message) {
                return Swal.fire(message, '', 'error');
            },
            success: function (message) {
                return Swal.fire(message, '', 'success');

            },
            warning: function (message) {
                return Swal.fire(message);
            }
        },
        onAddField: function (fieldId) {
            const currentFieldId = fieldId,
                field = document.getElementById(`${fieldId}`),
                field_type = field.getAttribute('type'),
                field_type_el = $(`.form-field[type="${field_type}"]`),
                field_count_in_form = $(field_type_el).length,
                control_el = $(`.input-control[data-type="${field_type}"]`)


            /**
             * Add icons on left side to be easy understand for user
             */
            $(control_el).find('.control-icon').clone().prependTo(`#${currentFieldId}`)

            $(`#${currentFieldId} .field-label`).attr("class", `${$(`#${currentFieldId} .field-label`).attr("class")} ${$(control_el).attr("class")}`);

            /**
             * Disable fields for pro version
             */
            if (formBuilderOptions.disableProFields.includes(field_type)) {

                formBuilderContainer.actions.removeField(currentFieldId);

                Swal.fire({
                    title: formBuilderOptions.messages.for_pro_title,
                    text: formBuilderOptions.messages.for_pro_message,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: formBuilderOptions.messages.for_pro_button_text,
                }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                        window.open(formBuilderOptions.messages.for_pro_link, '_blank');
                    }
                })
            }

            /**
             * Disable extra settings fields
             */
            if (typeof formBuilderOptions.disableExtraSettingsFields !== 'undefined' && formBuilderOptions.disableExtraSettingsFields.includes(field_type)) {

                formBuilderContainer.actions.removeField(currentFieldId);

                Swal.fire({
                    html: formBuilderOptions.messages.for_google_map_message,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: formBuilderOptions.messages.for_google_map_button_text,
                }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                        window.open(formBuilderOptions.messages.for_google_map_button_link, '_blank');
                    }
                })
            }

            /**
             * Check for max of type exactable for form
             */
            if ((field_type in formBuilderOptions.temp_back)
                && ('max_in_form' in formBuilderOptions.temp_back[field_type])
                && (formBuilderOptions.temp_back[field_type].max_in_form < field_count_in_form)) {
                formBuilderContainer.actions.removeField(currentFieldId);
                Swal.fire(`Oops...`, formBuilderOptions.messages.max_fields_warning);
            }

            // Disable pro attributes
            formBuilderOptions.disable_attr.map((val) => {
                $(document).find(val).prop('disabled', true)

                if (!$(document).find(val).next('.pro-notice').length) {
                    $(document).find(val).parent().append('<p class="pro-notice">Available in pro version</p>');
                }
            });

            add_attribute_info_descriptions();


        }

    };

    /**
     * Activate form builder
     */
    $(window).on('load', () => {
        updateFormBuilder();
        if (window.location.hash == '#fe-metabox-settings-migration') {
            save_migration_settings();
        }
    })

    /**
     * Update form builder
     */
    async function updateFormBuilder(action = 'load') {
        let post_type = $('#fe_settings_post_type').val(),
            formArray_data = {
                action: 'fe_get_formBuilder_data',
                post_id: localizeData.post_id,
                post_type: post_type,
                admin_form_builder_nonce: admin_form_builder_nonce
            },
            formData = new FormData();

        if (post_type) {
            Swal.fire({
                position: "bottom-end",
            })
            Swal.showLoading()
        }

        for (var key in formArray_data) {
            formData.append(key, formArray_data[key]);
        }

        fetch(localizeData.rest_url_get, {
            method: 'POST',
            body: formData,
            headers: {
                'X-WP-Nonce': localizeData.nonce,
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let response = data.data
                    let formBuilderId = `form-builder`;

                    $(`#${formBuilderId}`).remove();
                    $('.formBuilder-wrapper').empty().append(`<div id="${formBuilderId}"></div>`);

                    formBuilderOptions = response.formBuilder_options;

                    let templates_obj = formBuilderOptions.temp_back;

                    /**
                     * Creating templates using data form backend
                     */
                    Object.keys(templates_obj).map((key, index) => {
                        formBuilderOptions.templates[key] = (fieldData) => {
                            return {
                                field: templates_obj[key].field,
                                onRender: function () {
                                    $(document.getElementById(fieldData.name));
                                }
                            }
                        }
                    });
                    /**
                    * Init formBuilder
                    */
                    formBuilderContainer = $(`#${formBuilderId}`).formBuilder(
                        { ...options, ...formBuilderOptions }
                    );
                    formBuilderContainer.promise.then(formBuilder => {
                        // Remove controls on ajax request if there do not needed
                        builder_control_controls(formBuilderOptions);

                        // Add groups
                        add_groups()

                        // Adding data 
                        if (action === 'load') {
                            formBuilderContainer.actions.setData(response.formBuilderData);
                        }

                        // Change style if is not pro version fields
                        formBuilderOptions.disableProFields.map((val) => {
                            $(`.input-control[data-type="${val}"]`).css('opacity', '0.7');
                        });

                        Swal.close()
                    });
                } else {
                    Swal.fire('Oops', 'Something goes wrong try later', 'error')
                }
            }).catch()


    }

    /**
     * Saving form data
     */
    async function save_form_data() {

        let formArray = $('#fe-fromBuilder').serializeArray(),
            formArray_data = objectifyForm(formArray),
            formData = new FormData();

        Swal.fire({
            position: "bottom-end",
        })
        Swal.showLoading()

        let formBuilderData = JSON.stringify(formBuilderContainer.actions.getData('js', true));

        formArray_data.formBuilderData = formBuilderData;
        formArray_data.action = 'save_post_front_settings';

        for (var key in formArray_data) {
            formData.append(key, formArray_data[key]);
        }

        await fetch(localizeData.rest_update, {
            method: 'POST',
            body: formData,
            headers: {
                'X-WP-Nonce': localizeData.nonce,
            }
        })
            .then(response => response.json())
            .then(async (data) => {
                if (data.success) {
                    let response = data.data
                    let message = response.message;
                    if (response.post_id) {

                        let is_new = $('#post_id').val() === 'new';

                        // update url if the form created successfully
                        if (is_new) {
                            window.history.pushState("", "", response.form_edit_url);
                        }
                        // update id in html
                        $('#post_id').val(response.post_id);
                        $('#form_shortcode').attr('data-post_id',response.post_id);
                        // update
                        $('#fe-fromBuilder .settings-header.primary code').html("[fe_form id=\"".concat(response.post_id, "\"]"));
                    }

                    await Swal.fire({
                        position: "bottom-end",
                        title: message.title,
                        icon: message.status,
                        text: message.message,
                        showConfirmButton: false,
                        timer: 2000
                    });
                    //await updateFormBuilder();

                } else {
                    Swal.fire('Oops', 'Something goes wrong try later', 'error')
                }
            }).catch()

    }

    function objectifyForm(formArray) {
        //serialize data function
        var returnArray = {};
        for (var i = 0; i < formArray.length; i++) {
            returnArray[formArray[i]['name']] = formArray[i]['value'];
        }
        return returnArray;
    }

    /**
     * Update for builder on post type update
     */
    $('#fe_settings_post_type').on('change', function (ev) {
        ev.preventDefault();
        updateFormBuilder('post_type_change');
    });

    /**
     * Save data
     */
    $('#save-form-post').on('click', function (ev) {
        ev.preventDefault();
        save_form_data();
    });

    /**
     * Remove controls on ajax request if there do not needed
     * @param {*} formBuilderOptions 
     */
    function builder_control_controls(formBuilderOptions) {
        current_forBuilder_controls = [];
        updated_forBuilder_controls = [];

        // Getting current controls
        $('.frmb-control li').each((index, elem) => {
            let data_type = $(elem).attr('data-type');
            current_forBuilder_controls.push(data_type)
        })

        /**
         * Creat array with updated controls
         */
        Object.keys(formBuilderOptions.temp_back).map((key, index) => {
            updated_forBuilder_controls.push(key)
        });

        /**
         *  default fields and custom fields
         */
        updated_forBuilder_controls = [...formBuilderOptions.defaultControls, ...updated_forBuilder_controls]
        /**
         * Find difference between controls
         */
        let difference = current_forBuilder_controls.filter(x => !updated_forBuilder_controls.includes(x));
        difference.map((val) => {
            $(`[data-type="${val}"]`).remove();
        })
    }

    /**
     * Adding group fields
     */
    function add_groups() {
        let controls_group = formBuilderOptions.controls_group
        Object.keys(controls_group).map((key, index) => {
            $(`<p class="group-name ${key}">${controls_group[key].label}</p>`).insertBefore(`[data-type="${controls_group[key].types[0]}"]`);
        })
    }

    /**
     * Array to show hide needed fields
     */
    const on_select_show = {
        post_redirect_to: { val: 'url', show_hide: 'post_redirect_to_link' },
        post_update_redirect_to: { val: 'url', show_hide: 'post_update_redirect_to_link' },
    }

    function show_hide(key) {
        if ($(`#${key}`).val() === on_select_show[key].val) {
            $(`#${on_select_show[key].show_hide}`).fadeIn();
        } else {
            $(`#${on_select_show[key].show_hide}`).fadeOut();
            // $(`#${on_select_show[key].show_hide}`).val('');
        }
    }

    $(window).on('load', () => {
        Object.keys(on_select_show).map((key, index) => {
            $(document).find(`#${key}`).on('change', function (ev) {
                show_hide(key)
            });

            show_hide(key)
        })
    })

    /**
     * Getting description array and adding description by loop for every attribute
     */
    function add_attribute_info_descriptions() {
        for (const [key, value] of Object.entries(formBuilderOptions.attr_descriptions)) {
            var field_attrs = document.querySelectorAll(`.${key}-wrap`)

            field_attrs.forEach((attrs) => {
                var label = attrs.querySelector(`label`)
                var openModalButton = label.querySelector('.open-modal')
                if (openModalButton === null) {
                    let newIcon = document.createElement("i");
                    newIcon.setAttribute("description", value);
                    newIcon.className += "tooltip-element open-modal";
                    newIcon.innerHTML = "?";

                    label.append(newIcon)
                }

                openModalButton = label.querySelector('.open-modal')

                if (openModalButton) {
                    openModalButton.addEventListener('click', (ev) => {
                        Swal.fire({ 'html': openModalButton.getAttribute('description'), 'icon': 'info' })
                    })
                }
            })
        }
    }

    /**
     * Check form is created
     */
    function isFormCreated() {
        const url = window.location.href;
    
        const queryParams = new URLSearchParams(window.location.search);
    
        const page = queryParams.get('page');
        const action = queryParams.get('action');
        
        if (page === 'fe-post-forms' && action === 'add-new') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Saving migration data
     */
    async function save_migration_settings() {
        if (!isFormCreated()) {
            await save_form_data();
        }
        let migrate_type = $(this).attr('data-migrate') !== undefined ? $(this).data('migrate') : 'export';
        let migrate_value = $('#fe-migrate-settings').val();
        let post_id = $('#form_shortcode').data('post_id');
        let formArray_data = {
            action: 'save_migration_settings',
            nonce: localizeData.nonce,
            post_id: localizeData.post_id != 'new' ? localizeData.post_id : post_id,
        },
        formData = new FormData();
        if (migrate_value) {
            formArray_data.migrate_value = migrate_value;
        }

        if (migrate_type) {
            formArray_data.migrate_type = migrate_type;
        }

        Swal.fire({
            position: "bottom-end",
        })
        Swal.showLoading()

        for (var key in formArray_data) {
            formData.append(key, formArray_data[key]);
        }

        await fetch(localizeData.ajax_url, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(async (data) => {
                if (data.success) {
                    // console.log(data);
                    let response = data.data
                    let message = response.message;
                    if (response.post_id) {
                        console.log(response.data);
                        // update json in textarea
                        $('#fe-migrate-settings').val(response.data);
                    }

                    await Swal.fire({
                        position: "bottom-end",
                        title: message.title,
                        icon: message.status,
                        text: message.message,
                        showConfirmButton: false,
                        timer: 2000
                    });

                    if (migrate_type == 'import') {
                        location.reload();
                    }
                } else {
                    Swal.fire('Oops', data.data.message.title, 'error')
                }
            }).catch()

    }

    $('.fe-migration-settings-tab, .fe-migrate-setting').on('click', function (e) {
        if ($(this).hasClass('fe-migrate-setting')) {
            e.preventDefault();
        }
        save_migration_settings.call(this);
    });
    
    $('.fe-copy-migrate-setting').on('click', function(e) {
        e.preventDefault();
        const jsonData = document.getElementById('fe-migrate-settings').value;
    
        navigator.clipboard.writeText(jsonData)
        .then(() => {
            $('.fe-copy-migrate-msg').show().delay(2000).fadeOut();
        })
        .catch(err => {
            console.error('Error copying JSON:', err);
        });
    });
    
}