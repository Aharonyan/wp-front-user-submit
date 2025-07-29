<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */
namespace BFE\Field;

use BFE\Form;
defined( 'ABSPATH' ) || exit;
class PostTitleField {
    public static $field_label = 'Post Title';

    public static $field_type = 'post_title';

    public static function init() {
        // add field settings to wp admin form
        add_filter( 'admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings'] );
        // Validate field on wp admin form
        add_action( 'fe_before_wp_admin_form_create_save', [__CLASS__, 'validate_field_before_wp_admin_form_save'] );
        // Front form template
        add_action(
            'bfe_editor_on_front_field_adding',
            [__CLASS__, 'display_field_on_front_form'],
            10,
            3
        );
        // Validate field on front form submit
        add_action(
            'bfe_ajax_before_post_update_or_creation',
            [__CLASS__, 'validate_field_on_front_form_submit'],
            10,
            3
        );
        // Add data before creation the post
        add_filter(
            'bfe_ajax_before_front_editor_post_update_or_creation',
            [__CLASS__, 'add_title_if_exist'],
            10,
            5
        );
        // Update title if generate title is exist
        add_filter(
            'bfe_ajax_before_front_editor_post_update_or_creation',
            [__CLASS__, 'check_if_generate_title'],
            11,
            5
        );
    }

    /**
     * Validate field on wp admin form save
     *
     * @param [type] $data
     * @return void
     */
    public static function validate_field_before_wp_admin_form_save( $data ) {
        $settings = Form::get_form_field_settings( self::$field_type, 0, $_POST['formBuilderData'] );
        if ( !$settings ) {
            wp_send_json_success( [
                'message' => [
                    'title'   => __( 'Oops', 'front-editor' ),
                    'message' => __( 'Post Title Field is missing', 'front-editor' ),
                    'status'  => 'warning',
                ],
            ] );
        }
    }

    /**
     * Validate on front for submit
     *
     * @param int $post_id
     * @param int $form_id
     * @return void
     */
    public static function validate_field_on_front_form_submit( $post_id, $form_id, $all_settings ) {
        $settings = Form::get_form_field_settings_by_type( self::$field_type, $form_id, $all_settings['form_builder_json'] );
        if ( !isset( $settings['generate_title'] ) ) {
            if ( empty( $_POST['post_title'] ) && $settings['required'] ) {
                $message = __( 'Please add post title', 'front-editor' );
                if ( isset( $settings['required_error_text'] ) && !empty( isset( $settings['required_error_text'] ) ) ) {
                    $message = $settings['required_error_text'];
                }
                wp_send_json_error( [
                    'field'   => self::$field_type,
                    'message' => $message,
                ] );
            }
            $post_title = sanitize_text_field( $_POST['post_title'] );
            if ( empty( $post_title ) ) {
                $message = __( 'Please add correct post title', 'front-editor' );
                if ( isset( $settings['not_correct_post_title_text'] ) && !empty( isset( $settings['not_correct_post_title_text'] ) ) ) {
                    $message = $settings['not_correct_post_title_text'];
                }
                wp_send_json_error( [
                    'field'   => self::$field_type,
                    'message' => $message,
                ] );
            }
        }
    }

    /**
     * Front form template
     *
     * @param int $post_id
     * @param array $attributes
     * @param array $field
     * @return void
     */
    public static function display_field_on_front_form( $post_id, $attributes, $field ) {
        if ( $field['type'] !== self::$field_type ) {
            return;
        }
        require fe_template_path( 'front-editor/post-title.php' );
    }

    /**
     * Add data before creation the post
     *
     * @param array $post_data
     * @param array $_POST
     * @param array $_FILES
     * @param int $post_id
     * @param int $form_id
     * @return void
     */
    public static function add_title_if_exist(
        $post_data,
        $post,
        $files,
        $post_id,
        $form_id
    ) {
        $post_data['post_title'] = $post['post_title'];
        return $post_data;
    }

    /**
     * Generate title if needed
     * @param mixed $post_id
     * @param mixed $form_id
     * @param mixed $post_data
     * @return void
     */
    public static function check_if_generate_title(
        $post_data,
        $post,
        $files,
        $post_id,
        $form_id
    ) {
        $settings = Form::get_form_field_settings( self::$field_type, $form_id );
        if ( !isset( $settings['generate_title'] ) ) {
            return $post_data;
        }
        if ( empty( $settings['generate_title'] ) ) {
            return $post_data;
        }
        $post_title = '';
        $regex = '/\\[%s]/';
        $post_title = $settings['generate_title'];
        foreach ( $_POST as $key => $value ) {
            if ( $key === 'tax' ) {
                foreach ( $_POST['tax'] as $tax_name => $settings ) {
                    $terms = explode( ",", $settings['ids'] );
                    if ( !empty( $terms ) ) {
                        $terms_string = '';
                        foreach ( $terms as $term ) {
                            if ( empty( $term ) || $term === 'null' ) {
                                continue;
                            }
                            if ( is_numeric( $term ) ) {
                                $term_data = get_term_by( 'term_id', $term, $tax_name );
                            } else {
                                $term_data = get_term_by( 'name', $term, $tax_name );
                            }
                            if ( !empty( $term_data ) ) {
                                $terms_string .= ' ' . $term_data->name;
                            }
                        }
                        $post_title = preg_replace( sprintf( $regex, $tax_name ), $terms_string, $post_title );
                    }
                }
            } elseif ( $key === 'text_fields' ) {
                foreach ( $value as $text_key => $text_value ) {
                    $post_title = preg_replace( sprintf( $regex, $text_key ), $text_value, $post_title );
                }
            }
        }
        $post_data['post_title'] = $post_title;
        return $post_data;
    }

    public static function add_field_settings( $data ) {
        $field_label = __( 'Post Title', 'front-editor' );
        /**
         * Adding field
         */
        $data['formBuilder_options']['fields'][] = [
            'label' => $field_label,
            'attrs' => [
                'type' => self::$field_type,
            ],
            'icon'  => '<span class="dashicons dashicons-editor-textcolor"></span>',
        ];
        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field'       => sprintf( '<input type="text" class="%s" name="%s">', self::$field_type, self::$field_type ),
            'onRender'    => '',
            'max_in_form' => 1,
        ];
        /**
         * Adding as default
         */
        $data['formBuilder_options']['defaultFields'][] = [
            'label' => $field_label,
            'type'  => self::$field_type,
        ];
        /**
         * Adding field to group
         */
        $data['formBuilder_options']['controls_group']['post_fields']['types'][] = self::$field_type;
        $data['formBuilder_options']['disabledFieldButtons'][self::$field_type] = ['copy'];
        /**
         * Disabling default settings
         */
        $data['formBuilder_options']['typeUserDisabledAttrs'][self::$field_type] = [
            'name',
            'description',
            'inline',
            'toggle',
            'access',
            'value'
        ];
        /**
         * Adding attribute settings
         */
        $data['formBuilder_options']['typeUserAttrs'][self::$field_type] = [
            'placeholder'                 => [
                'label'       => sprintf( '%s', __( 'Placeholder', 'front-editor' ) ),
                'value'       => '',
                'placeholder' => 'Add Title',
            ],
            'title_element'               => [
                'label'    => 'HTML element',
                'multiple' => false,
                'options'  => [
                    'input'    => 'Input',
                    'textarea' => 'TextArea',
                ],
            ],
            'hide_field'                  => [
                'label' => __( 'Hide Field', 'front-editor' ),
                'value' => false,
                'type'  => 'checkbox',
            ],
            'generate_title'              => [
                'label'       => sprintf( '%s', __( 'Generate title', 'front-editor' ) ),
                'value'       => '',
                'placeholder' => 'New Post [taxonomy_name] [field_name]',
            ],
            'required_error_text'         => [
                'label'       => __( 'Required error text', 'front-editor' ),
                'placeholder' => 'Please add post title',
                'value'       => '',
            ],
            'not_correct_post_title_text' => [
                'label'       => __( 'Not correct content', 'front-editor' ),
                'placeholder' => 'Please add correct post title',
                'value'       => '',
            ],
        ];
        $data['formBuilder_options']['attr_descriptions']['generate_title'] = __( 'Generate title using params: [{taxonomy_name}] [{field name}] <br> Example: Post [category] [text-1695547664968] <br> If empty will not work', 'front-editor' );
        $data['formBuilder_options']['attr_descriptions']['hide_field'] = __( 'Hide field so users will not see it', 'front-editor' );
        $data['formBuilder_options']['disable_attr'][] = '.fld-generate_title';
        $data['formBuilder_options']['disable_attr'][] = '.fld-hide_field';
        return $data;
    }

}

PostTitleField::init();