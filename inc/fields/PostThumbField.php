<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */
namespace BFE\Field;

use BFE\Editor;
use BFE\Form;
defined( 'ABSPATH' ) || exit;
class PostThumbField {
    public static $field_label = 'Featured Image';

    public static $field_type = 'featured_image';

    public static function init() {
        add_filter( 'admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings'] );
        add_action(
            'bfe_editor_on_front_field_adding',
            [__CLASS__, 'add_post_image_selection'],
            10,
            3
        );
        add_filter(
            'bfe_ajax_before_front_editor_post_update_or_creation',
            [__CLASS__, 'image_on_save_check'],
            10,
            3
        );
        /**
         * Activating pro fields components activation
         */
        add_filter(
            'bfe_front_editor_localize_data',
            [__CLASS__, 'field_setting_for_frontend'],
            10,
            3
        );
    }

    /**
     * This settings for wp admin form builder
     *
     * @param [type] $data
     * @return void
     */
    public static function add_field_settings( $data ) {
        $field_label = __( 'Featured Image', 'front-editor' );
        /**
         * Adding field
         */
        $data['formBuilder_options']['fields'][] = [
            'label' => $field_label,
            'attrs' => [
                'type' => self::$field_type,
            ],
            'icon'  => '<span class="dashicons dashicons-format-image"></span>',
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
         * Adding attribute settings
         */
        $data['formBuilder_options']['typeUserAttrs'][self::$field_type] = [
            'wp_media_uploader' => [
                'label' => __( '(PRO) WP Media', 'front-editor' ),
                'value' => false,
                'type'  => 'checkbox',
            ],
        ];
        $data['formBuilder_options']['disable_attr'][] = '.fld-wp_media_uploader';
        $data['formBuilder_options']['disable_attr'][] = '.fld-max_file_size';
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
        return $data;
    }

    /**
     * Add post image selection
     *
     * @return void
     */
    public static function add_post_image_selection( $post_id, $attributes, $field ) {
        if ( $field['type'] !== self::$field_type ) {
            return;
        }
        require fe_template_path( 'front-editor/post-featured-image.php' );
    }

    /**
     * Image check
     *
     * @param [type] $post_data
     * @param [type] $data
     * @param [type] $file
     * @return void
     */
    public static function image_on_save_check( $post_data, $data, $file ) {
        $is_featured_image_exist = $_POST['thumb_exist'] ?? 0;
        if ( $is_featured_image_exist ) {
            return $post_data;
        }
        if ( isset( $_POST['post_image_required'] ) ) {
            if ( $_POST['post_image_required'] && empty( $_FILES['image'] ) && empty( $_POST['thumb_img_id'] ) ) {
                wp_send_json_error( [
                    'field'   => self::$field_type,
                    'message' => __( 'The featured image is required', 'front-editor' ),
                ] );
            }
        }
        return $post_data;
    }

    /**
     * Localize settings for form in front
     *
     * @param [type] $data
     * @param [type] $attributes
     * @param [type] $post_id
     * @return void
     */
    public static function field_setting_for_frontend( $data, $attributes, $post_id ) {
        $form_settings = get_post_meta( $attributes['id'], 'fe_form_settings', true );
        $settings = Form::get_form_field_settings( self::$field_type, $attributes['id'] );
        $data['post_thumb']['wp_media_uploader'] = ( isset( $settings['wp_media_uploader'] ) ? sanitize_text_field( $settings['wp_media_uploader'] ) : 0 );
        if ( Editor::guest_posting_enabled( $form_settings ) && !is_user_logged_in() ) {
            $data['post_thumb']['wp_media_uploader'] = 0;
        }
        $data['post_thumb']['max_file_size'] = ( isset( $settings['max_file_size'] ) ? sanitize_text_field( $settings['max_file_size'] ) : 10 );
        return $data;
    }

}

PostThumbField::init();