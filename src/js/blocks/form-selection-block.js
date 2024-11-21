import { withState } from '@wordpress/compose';

(function (blocks, i18n, element, blockEditor, components, compose, editor_block_data, wp) {
    console.log(element)
    if (element === undefined) {
        return
    }

    var el = element.createElement,
        __ = i18n.__,
        AlignmentToolbar = blockEditor.AlignmentToolbar,
        ToggleControl = components.ToggleControl,
        Dropdown = components.Dropdown,
        Button = components.Button,
        BlockControls = blockEditor.BlockControls,
        Disabled = blockEditor.Disabled,
        InputControl = components.__experimentalInputControl,
        SelectControl = components.SelectControl,
        withState = compose.withState,
        translations = editor_block_data.translations,
        editor_pro_settings = editor_block_data.editor_pro_settings;

    /**
     * Message in post
     */
    if (editor_block_data.fe_show_warning_message) {
        wp.data.dispatch('core/notices').createNotice(
            'error', // Can be one of: success, info, warning, error.
            translations.fe_edit_message, // Text string to display.
            {
                isDismissible: false, // Whether the user can dismiss the notice.
                // Any actions the user can perform.
                actions: [
                    {
                        url: editor_block_data.fe_edit_link,
                        label: translations.fe_edit_link_text,
                    },
                ],
            }
        );
    }


    blocks.registerBlockType('bfe/bfe-block', {
        title: __('Front Editor', 'front-editor'),
        icon: 'edit',
        category: 'common',
        multiple: false,
        html: false,
        attributes: {
            id: {
                type: 'string',
                default: 'false'
            },
        },
        edit: function (props) {
            console.log(props.attributes.id)
            return (
                <div className="editor-block-settings">
                    <h3 className="title">{translations.title}</h3>
                    <div className="setting-wrap header-settings">
                        <SelectControl
                            label={translations.form_builder_id}
                            value={props.attributes.id}
                            onChange={(value) => {
                                props.setAttributes({
                                    id: value
                                })
                            }}
                            options={editor_block_data.post_form_list}
                        />
                        <Button
                            isSecondary href={editor_block_data.create_new_post_form_link}
                            target="_blank"
                            >
                            {translations.create_new_form}
                        </Button>
                    </div>

                </div >
            );
        },
        save: function (props) {
            return null;
        },
    });
})(window.wp.blocks, window.wp.i18n, window.wp.element, wp.blockEditor, window.wp.components, window.wp.compose, window.editor_block_data, window.wp);
