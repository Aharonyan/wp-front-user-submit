<?php

/**
 * formBuilder Tax Fields
 *
 * @package BFE;
 */
namespace BFE\Field;

use BFE\Form;
defined( 'ABSPATH' ) || exit;
class TaxonomiesFields {
    public static function init() {
        add_filter( 'admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings'] );
        add_action(
            'bfe_editor_on_front_field_adding',
            [__CLASS__, 'front_tax_select'],
            10,
            3
        );
        add_filter(
            'bfe_ajax_before_front_editor_post_update_or_creation',
            [__CLASS__, 'validating_taxonomy_fields'],
            10,
            6
        );
        /**
         * Adding taxonomies
         */
        add_action( 'bfe_ajax_after_front_editor_post_update_or_creation', [__CLASS__, 'add_tax_after_post_created'], 10 );
    }

    /**
     * Adding in backend forBuilder field
     *
     * @param [type] $data
     * @return void
     */
    public static function add_field_settings( $data ) {
        $post_type = $data['settings']['post_type'];
        $post_taxonomies = get_object_taxonomies( $post_type, 'objects' );
        foreach ( $post_taxonomies as $tax ) {
            $tax_type = sprintf( 'tax_%s', $tax->name );
            /**
             * We do not need post_format
             */
            if ( $tax->name === 'post_format' ) {
                continue;
            }
            $data['formBuilder_options']['fields'][] = [
                'label' => $tax->label,
                'attrs' => [
                    'placeholder' => sprintf( '%s %s', __( 'Select', 'front-editor' ), $tax->label ),
                    'type'        => $tax_type,
                ],
                'icon'  => '<span class="dashicons dashicons-tag"></span>',
            ];
            /**
             * Templates
             */
            $data['formBuilder_options']['temp_back'][$tax_type] = [
                'field'       => sprintf( '<div class="%s tax" name="%s"></div>', $tax->name, $tax->name ),
                'onRender'    => '',
                'max_in_form' => 1,
            ];
            /**
             * Adding field to group
             */
            $data['formBuilder_options']['controls_group']['taxonomies']['types'][] = $tax_type;
            /**
             * Adding attribute settings
             */
            $data['formBuilder_options']['typeUserAttrs'][$tax_type] = [
                'order'               => [
                    'label'   => __( 'Order', 'front-editor' ),
                    'options' => [
                        'asc'  => 'ASC',
                        'desc' => 'Desc',
                    ],
                    'type'    => 'select',
                ],
                'multiple'            => [
                    'label' => __( 'Multiple Selections', 'front-editor' ),
                    'value' => false,
                    'type'  => 'checkbox',
                ],
                'show_empty'          => [
                    'label' => __( 'Show empty', 'front-editor' ),
                    'value' => false,
                    'type'  => 'checkbox',
                ],
                'add_new'             => [
                    'label' => __( 'Allow Add New', 'front-editor' ),
                    'value' => false,
                    'type'  => 'checkbox',
                ],
                'hierarchically'      => [
                    'label' => __( 'Show Hierarchically', 'front-editor' ),
                    'value' => false,
                    'type'  => 'checkbox',
                ],
                'show_search'         => [
                    'label' => __( 'Show Search Input', 'front-editor' ),
                    'value' => false,
                    'type'  => 'checkbox',
                ],
                'exclude'             => [
                    'label'       => __( 'Terms to excluded', 'front-editor' ),
                    'placeholder' => '556,778,993',
                    'value'       => '',
                ],
                'default_terms'       => [
                    'label'       => __( 'Default terms', 'front-editor' ),
                    'placeholder' => '128,99,73',
                    'value'       => '',
                ],
                'required_error_text' => [
                    'label'       => __( 'Required error text', 'front-editor' ),
                    'placeholder' => 'Term selection is required',
                    'value'       => '',
                ],
                'search_placeholder'  => [
                    'label'       => __( 'Search placeholder', 'front-editor' ),
                    'placeholder' => 'Search',
                    'value'       => '',
                ],
                'search_error_text'   => [
                    'label'       => __( 'No result text', 'front-editor' ),
                    'placeholder' => 'No Results',
                    'value'       => '',
                ],
            ];
            /**
             * Disable button
             */
            $data['formBuilder_options']['disabledFieldButtons'][$tax_type] = ['copy'];
            /**
             *
             * Disabling default settings
             */
            $data['formBuilder_options']['typeUserDisabledAttrs'][$tax_type] = [
                'name',
                'description',
                'inline',
                'toggle',
                'access',
                'value'
            ];
            $data['formBuilder_options']['disable_attr'][] = '.fld-multiple';
            $data['formBuilder_options']['disable_attr'][] = '.fld-add_new';
            $data['formBuilder_options']['disable_attr'][] = '.fld-hierarchically';
            $data['formBuilder_options']['attr_descriptions']['exclude'] = __( 'You can specify a comma-separated terms IDs that need to be excluded.', 'front-editor' );
            $tutorial_link = '<a href="https://wpfronteditor.com/docs/tutorials/tutorials/where-to-find-term-id/" target="_blank">link</a>';
            $data['formBuilder_options']['attr_descriptions']['default_terms'] = sprintf( __( 'You can specify a comma-separated term IDs that need to be included. Check the tutorial to find the term ID using this %s', 'front-editor' ), $tutorial_link );
        }
        return $data;
    }

    public static function front_tax_select( $post_id, $attributes, $field ) {
        if ( strpos( $field['type'], 'tax' ) === false ) {
            return;
        }
        $tax_name = str_replace( "tax_", "", $field['type'] );
        require fe_template_path( 'front-editor/taxonomy.php' );
    }

    /**
     * Checking tax fields on form save from front before post created
     *
     * @param [type] $post_data
     * @param [type] $data
     * @param [type] $file
     * @return void
     */
    public static function validating_taxonomy_fields(
        $post_data,
        $data,
        $file,
        $post_id,
        $form_id,
        $all_settings
    ) {
        if ( !isset( $data['tax'] ) || empty( $data['tax'] ) ) {
            return $post_data;
        }
        foreach ( $data['tax'] as $tax_name => $value ) {
            $settings = Form::get_form_field_settings_by_type( 'tax_' . $tax_name, $form_id, $all_settings['form_builder_json'] );
            $is_value_empty = false;
            if ( empty( $value['ids'] ) || $value['ids'] == 'null' ) {
                $is_value_empty = true;
            }
            if ( $settings['required'] && $is_value_empty ) {
                $message = sprintf( __( 'The %s selection is required', 'front-editor' ), $tax_name );
                if ( isset( $settings['required_error_text'] ) && !empty( $settings['required_error_text'] ) ) {
                    $message = $settings['required_error_text'];
                }
                wp_send_json_error( [
                    'field'   => 'tax_' . $tax_name,
                    'message' => $message,
                ] );
            }
        }
        return $post_data;
    }

    /**
     * Add post after post created
     *
     * @param [type] $post_id
     * @return void
     */
    public static function add_tax_after_post_created( $post_id ) {
        if ( empty( $_POST['tax'] ) ) {
            return;
        }
        foreach ( $_POST['tax'] as $tax_name => $settings ) {
            $terms = explode( ",", $settings['ids'] );
            $default_terms = explode( ",", $settings['default_terms'] );
            if ( !empty( $default_terms ) ) {
                $terms = array_merge( $terms, $default_terms );
            }
            if ( !empty( $terms ) ) {
                $terms_ids = [];
                foreach ( $terms as $term ) {
                    if ( empty( $term ) || $term === 'null' ) {
                        continue;
                    }
                    /**
                     * Checking if term exist if not creating it
                     */
                    $term_data = get_term_by( 'id', $term, $tax_name );
                    if ( !$term_data ) {
                        $term_data = get_term_by( 'name', $term, $tax_name );
                    }
                    if ( !$term_data ) {
                        $term_data = wp_insert_term( $term, $tax_name );
                        $term_id = $term_data['term_id'];
                    } else {
                        $term_id = $term_data->term_id;
                    }
                    $terms_ids[] = (int) $term_id;
                }
                // Attach to post
                wp_set_object_terms( $post_id, $terms_ids, $tax_name );
            }
        }
    }

}

TaxonomiesFields::init();