/**
 * Form builder: generate fields from a text description
 */

const escapeHtml = (text) => String(text).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));

/**
 * Field types available in the builder with labels
 * @param {object} options formBuilder options
 * @param {jQuery} $
 * @returns {Array<{type: string, label: string}>}
 */
const getAvailableTypes = (options, $) => (options.controlOrder || [])
    .filter((type) => !(options.disableProFields || []).includes(type))
    .map((type) => ({
        type,
        label: $(`.input-control[data-type="${type}"]`).first().text().trim() || type
    }));

/**
 * Respect "max_in_form" limits of the builder
 * @param {Array} existing current fields
 * @param {Array} generated new fields
 * @param {object} limits formBuilder temp_back
 * @returns {Array}
 */
export const limitFieldsPerType = (existing, generated, limits = {}) => {
    const counts = existing.reduce((acc, field) => ({ ...acc, [field.type]: (acc[field.type] || 0) + 1 }), {});
    return generated.filter((field) => {
        const max = limits[field.type]?.max_in_form;
        counts[field.type] = (counts[field.type] || 0) + 1;
        return !max || counts[field.type] <= max;
    });
};

export default ($, Swal, getBuilder, getOptions) => {
    const localizeData = window.fe_post_form_data || {};
    const ai = localizeData.ai;
    if (!ai) {
        return () => {};
    }
    const i18n = ai.i18n;

    const showNotConfiguredDialog = () => Swal.fire({
        text: i18n.not_configured,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: i18n.open_settings
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = ai.settings_url;
        }
    });

    const requestFields = async (description, existingFields) => {
        const formData = new FormData();
        formData.append('description', description);
        formData.append('types', JSON.stringify(getAvailableTypes(getOptions(), $)));
        formData.append('existing_names', JSON.stringify(existingFields.map((field) => field.name).filter(Boolean)));

        const response = await fetch(ai.rest_generate_form, {
            method: 'POST',
            body: formData,
            headers: { 'X-WP-Nonce': localizeData.nonce }
        });
        const json = await response.json();
        if (!response.ok || !json.success) {
            throw new Error(json.message || i18n.error);
        }
        return json.data.fields;
    };

    const openGenerator = async () => {
        if (!ai.configured) {
            showNotConfiguredDialog();
            return;
        }

        const builder = getBuilder();
        const existingFields = builder ? builder.actions.getData('js', true) : [];

        const result = await Swal.fire({
            title: i18n.title,
            customClass: { popup: 'fus-ai-form-generator' },
            html: `
                <textarea id="fus-ai-form-description" class="swal2-textarea" rows="5" placeholder="${escapeHtml(i18n.placeholder)}"></textarea>
                <div class="fus-ai-mode">
                    <label><input type="radio" name="fus-ai-mode" value="append" checked> ${escapeHtml(i18n.mode_append)}</label>
                    <label><input type="radio" name="fus-ai-mode" value="replace"> ${escapeHtml(i18n.mode_replace)}</label>
                </div>`,
            showCancelButton: true,
            confirmButtonText: i18n.generate,
            cancelButtonText: i18n.cancel,
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            didOpen: () => document.getElementById('fus-ai-form-description').focus(),
            preConfirm: async () => {
                const description = document.getElementById('fus-ai-form-description').value.trim();
                const mode = document.querySelector('input[name="fus-ai-mode"]:checked').value;
                if (!description) {
                    Swal.showValidationMessage(i18n.empty);
                    return false;
                }
                try {
                    const fields = await requestFields(description, mode === 'append' ? existingFields : []);
                    return { fields, mode };
                } catch (error) {
                    Swal.showValidationMessage(escapeHtml(error.message));
                    return false;
                }
            }
        });

        if (!result.isConfirmed || !result.value) {
            return;
        }

        const { fields, mode } = result.value;
        const baseFields = mode === 'append' ? existingFields : [];
        const newFields = limitFieldsPerType(baseFields, fields, getOptions().temp_back);
        getBuilder().actions.setData([...baseFields, ...newFields]);
        Swal.fire({ text: i18n.success, icon: 'success', timer: 2500, showConfirmButton: false });
    };

    /**
     * Add the button once, builder can be re-created on post type change
     */
    return () => {
        if (document.getElementById('fus-ai-generate-form')) {
            return;
        }
        const button = $('<button type="button" id="fus-ai-generate-form" class="button fus-ai-generate-form"></button>')
            .text(i18n.button)
            .on('click', openGenerator);
        $('#post-form-builder .post_type_selection').append(button);
    };
};
