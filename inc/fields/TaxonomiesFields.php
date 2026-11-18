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
        add_action(
            'bfe_ajax_after_front_editor_post_update_or_creation',
            [__CLASS__, 'add_tax_after_post_created'],
            10,
            2
        );
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
            $data['formBuilder_options']['attr_descriptions']['show_search'] = __( 'Add new functionality will not work without activating this input.', 'front-editor' );
            $tutorial_link = '<a href="https://wpuserpublish.com/docs/fields/taxonomies/" target="_blank">link</a>';
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
        if ( empty( $data['tax'] ) || !is_array( $data['tax'] ) ) {
            return $post_data;
        }
        foreach ( $data['tax'] as $tax_name => $value ) {
            // only taxonomies the form has a field for
            $fields = Form::get_fields_of_type( $form_id, 'tax_' . $tax_name );
            if ( empty( $fields ) ) {
                continue;
            }
            $settings = $fields[0];
            $is_value_empty = false;
            if ( empty( $value['ids'] ) || $value['ids'] == 'null' ) {
                $is_value_empty = true;
            }
            if ( !empty( $settings['required'] ) && $is_value_empty ) {
                $message = sprintf( __( 'The %s selection is required', 'front-editor' ), esc_html( $tax_name ) );
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
    public static function add_tax_after_post_created( $post_id, $form_id = 0 ) {
        $form_id = Form::submitted_form_id( $form_id );
        // only taxonomies the form has a field for
        foreach ( Form::get_form_fields( $form_id ) as $field ) {
            if ( !isset( $field['type'] ) || strpos( $field['type'], 'tax_' ) !== 0 ) {
                continue;
            }
            $tax_name = substr( $field['type'], 4 );
            if ( !taxonomy_exists( $tax_name ) ) {
                continue;
            }
            $submitted = $_POST['tax'][$tax_name] ?? null;
            if ( !is_array( $submitted ) || !isset( $submitted['ids'] ) ) {
                continue;
            }
            $terms_ids = self::get_term_ids( $tax_name, ( is_scalar( $submitted['ids'] ) ? wp_unslash( (string) $submitted['ids'] ) : '' ), $field );
            wp_set_object_terms( $post_id, $terms_ids, $tax_name );
        }
    }

    /**
     * Term ids for a submitted value: existing terms of the taxonomy, new terms only when
     * the field allows adding new ones, and the default terms from the form settings
     *
     * @param string $tax_name
     * @param string $submitted comma separated term ids (or names of new terms).
     * @param array  $field     taxonomy field settings.
     * @return int[]
     */
    public static function get_term_ids( $tax_name, $submitted, array $field ) {
        $can_add_new = !empty( $field['add_new'] ) && $field['add_new'] !== 'false';
        $excluded = array_map( 'intval', array_filter( explode( ',', (string) ($field['exclude'] ?? '') ) ) );
        $default_terms = array_map( 'intval', array_filter( explode( ',', (string) ($field['default_terms'] ?? '') ) ) );
        $terms_ids = [];
        foreach ( explode( ',', $submitted ) as $term ) {
            $term = trim( $term );
            if ( $term === '' || $term === 'null' ) {
                continue;
            }
            $term_data = ( is_numeric( $term ) ? get_term_by( 'id', (int) $term, $tax_name ) : false );
            if ( !$term_data ) {
                $term_data = get_term_by( 'name', $term, $tax_name );
            }
            if ( $term_data ) {
                if ( !in_array( (int) $term_data->term_id, $excluded, true ) ) {
                    $terms_ids[] = (int) $term_data->term_id;
                }
                continue;
            }
            if ( !$can_add_new ) {
                continue;
            }
            $inserted = wp_insert_term( sanitize_text_field( $term ), $tax_name );
            if ( !is_wp_error( $inserted ) ) {
                $terms_ids[] = (int) $inserted['term_id'];
            }
        }
        foreach ( $default_terms as $term_id ) {
            if ( $term_id && get_term_by( 'id', $term_id, $tax_name ) ) {
                $terms_ids[] = $term_id;
            }
        }
        return array_values( array_unique( $terms_ids ) );
    }

}

TaxonomiesFields::init();