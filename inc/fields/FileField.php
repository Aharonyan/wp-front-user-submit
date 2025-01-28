<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */
namespace BFE\Field;

use BFE\Form;
defined( 'ABSPATH' ) || exit;
class FileField {
    public static $field_label = 'File';

    public static $field_type = 'file';

    public static function init() {
        add_filter( 'admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings'] );
        add_action( 'rest_api_init', [__CLASS__, 'file_upload_endpoints'] );
        add_filter( 'bfe_front_editor_localize_data', function ( $data ) {
            $data['get_rest_url'] = get_rest_url( null, 'bfe/v1' );
            return $data;
        } );
    }

    public static function file_upload_endpoints() {
        register_rest_route( 'bfe/v1', '/process', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'process_files'],
            'permission_callback' => function () {
                return true;
            },
        ] );
        register_rest_route( 'bfe/v1', '/revert', [
            'methods'             => 'DELETE',
            'callback'            => [__CLASS__, 'revert_file'],
            'permission_callback' => function () {
                return true;
            },
        ] );
        register_rest_route( 'bfe/v1', '/load/(?P<id>\\d+)', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'load_file'],
            'permission_callback' => function () {
                return true;
            },
        ] );
    }

    public static function process_files( \WP_REST_Request $request ) {
        $files = $request->get_file_params();
        foreach ( $files as $file ) {
            apply_filters( 'bfe_before_file_filed_process_file', $file );
            $image = self::upload_file( $file );
            return $image['attach_id'];
        }
    }

    /**
     * Uploading image logic
     *
     * @param array  $file image array.
     * @return array
     */
    public static function upload_file( $file = [] ) {
        if ( empty( $file ) ) {
            return false;
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        if ( !empty( $file ) ) {
            $cont = file_get_contents( $file['tmp_name'] );
            $new_file_name = $file['name'];
            $ext = sanitize_mime_type( $file['type'] );
        }
        $new_file_name = sanitize_file_name( $new_file_name );
        $upload = wp_upload_bits( $new_file_name, null, $cont );
        $attachment = array(
            'post_title'     => $new_file_name,
            'post_mime_type' => $ext,
            'post_status'    => 'inherit',
        );
        $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
        // Generate and update attachment metadata
        $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        return array(
            'attach_data' => $attach_data,
            'upload'      => $upload,
            'attach_id'   => $attach_id,
        );
    }

    public static function revert_file( \WP_REST_Request $request ) {
        $attachment_id = intval( $request->get_body() );
        if ( !empty( $attachment_id ) && $attachment_id ) {
            $deleted = wp_delete_attachment( $attachment_id, true );
        }
    }

    public static function load_file( \WP_REST_Request $request ) {
        $att_id = (int) $request['id'];
        $att = wp_get_attachment_metadata( $att_id );
        $type = get_post_mime_type( $att_id );
        $path = get_attached_file( $att_id );
        $file = self::getTempFile( $att_id );
        if ( empty( $file ) ) {
            wp_send_json_error( [
                'field'    => self::$field_type,
                'messages' => __( 'File is not exist', 'front-editor' ),
            ] );
        }
        header( 'Access-Control-Expose-Headers: Content-Disposition' );
        header( 'Content-Type: ' . $file['type'] );
        header( 'Content-Length: ' . $file['length'] );
        header( 'Content-Disposition: inline; filename="' . $file['name'] . '"' );
        echo $file['content'];
        exit;
    }

    public static function getTempFile( $att_id ) {
        $file = get_attached_file( $att_id );
        try {
            if ( file_exists( $file ) ) {
                $handle = fopen( $file, 'r' );
                $content = fread( $handle, filesize( $file ) );
                fclose( $handle );
                return array(
                    'name'    => basename( $file ),
                    'content' => $content,
                    'type'    => mime_content_type( $file ),
                    'length'  => filesize( $file ),
                );
            }
        } catch ( \Exception $e ) {
            return null;
        }
        return false;
    }

    /**
     * This settings for wp admin form builder
     *
     * @param [type] $data
     * @return void
     */
    public static function add_field_settings( $data ) {
        $data['formBuilder_options']['disableProFields'][] = self::$field_type;
        $data['formBuilder_options']['defaultControls'][] = self::$field_type;
        /**
         * Adding attribute settings
         */
        $data['formBuilder_options']['typeUserAttrs'][self::$field_type] = [
            'accept'                             => [
                'label' => sprintf( '%s', __( 'Accept', 'front-editor' ) ),
                'value' => 'image/*,application/pdf',
            ],
            'max_size'                           => [
                'label' => __( 'Max File Size', 'front-editor' ),
                'value' => 500,
                'type'  => 'number',
            ],
            'max_files'                          => [
                'label' => __( 'Max Files', 'front-editor' ),
                'value' => 3,
                'type'  => 'number',
            ],
            'attach_to_post'                     => [
                'label' => __( 'Attach to post', 'front-editor' ),
                'value' => true,
                'type'  => 'checkbox',
            ],
            'placeholder'                        => [
                'label' => sprintf( '%s', __( 'Placeholder', 'front-editor' ) ),
                'value' => 'Drag & Drop your files or Browse',
            ],
            'labelInvalidField'                  => [
                'label' => sprintf( '%s', __( 'Label invalid field', 'front-editor' ) ),
                'value' => 'Field contains invalid files',
            ],
            'labelFileWaitingForSize'            => [
                'label' => sprintf( '%s', __( 'Label waiting for size', 'front-editor' ) ),
                'value' => 'Waiting for size',
            ],
            'labelFileSizeNotAvailable'          => [
                'label' => sprintf( '%s', __( 'Label Size not available', 'front-editor' ) ),
                'value' => 'Size not available',
            ],
            'labelFileLoading'                   => [
                'label' => sprintf( '%s', __( 'Label File Loading', 'front-editor' ) ),
                'value' => 'Loading',
            ],
            'labelFileLoadError'                 => [
                'label' => sprintf( '%s', __( 'Label Load Error', 'front-editor' ) ),
                'value' => 'Error during load',
            ],
            'labelFileProcessing'                => [
                'label' => sprintf( '%s', __( 'Label File Processing', 'front-editor' ) ),
                'value' => 'Uploading',
            ],
            'labelFileProcessingComplete'        => [
                'label' => sprintf( '%s', __( 'Label Processing Complete', 'front-editor' ) ),
                'value' => 'Upload complete',
            ],
            'labelFileProcessingAborted'         => [
                'label' => sprintf( '%s', __( 'Label Processing Aborted', 'front-editor' ) ),
                'value' => 'Upload cancelled',
            ],
            'labelFileProcessingError'           => [
                'label' => sprintf( '%s', __( 'Label Processing Error', 'front-editor' ) ),
                'value' => 'Error during upload',
            ],
            'labelFileProcessingRevertError'     => [
                'label' => sprintf( '%s', __( 'Label Processing Revert Error', 'front-editor' ) ),
                'value' => 'Error during revert',
            ],
            'labelFileRemoveError'               => [
                'label' => sprintf( '%s', __( 'Label File Remove Error', 'front-editor' ) ),
                'value' => 'Error during remove',
            ],
            'labelTapToCancel'                   => [
                'label' => sprintf( '%s', __( 'Label Tap To Cancel', 'front-editor' ) ),
                'value' => 'tap to cancel',
            ],
            'labelTapToRetry'                    => [
                'label' => sprintf( '%s', __( 'Label Tap To Retry', 'front-editor' ) ),
                'value' => 'tap to retry',
            ],
            'labelTapToUndo'                     => [
                'label' => sprintf( '%s', __( 'Label Tap To Undo', 'front-editor' ) ),
                'value' => 'tap to undo',
            ],
            'labelButtonRemoveItem'              => [
                'label' => sprintf( '%s', __( 'Label Remove Item', 'front-editor' ) ),
                'value' => 'Remove',
            ],
            'labelButtonAbortItemLoad'           => [
                'label' => sprintf( '%s', __( 'Label Abort Item Load', 'front-editor' ) ),
                'value' => 'Abort',
            ],
            'labelButtonRetryItemLoad'           => [
                'label' => sprintf( '%s', __( 'Label Retry Item Load', 'front-editor' ) ),
                'value' => 'Retry',
            ],
            'labelButtonAbortItemProcessing'     => [
                'label' => sprintf( '%s', __( 'Label Cancel Item Load', 'front-editor' ) ),
                'value' => 'Cancel',
            ],
            'labelButtonUndoItemProcessing'      => [
                'label' => sprintf( '%s', __( 'Label Undo Item Load', 'front-editor' ) ),
                'value' => 'Undo',
            ],
            'labelButtonRetryItemProcessing'     => [
                'label' => sprintf( '%s', __( 'Label Retry Item Load', 'front-editor' ) ),
                'value' => 'Retry',
            ],
            'labelButtonProcessItem'             => [
                'label' => sprintf( '%s', __( 'Label Upload Item Load', 'front-editor' ) ),
                'value' => 'Upload',
            ],
            'labelFileTypeNotAllowed'            => [
                'label' => sprintf( '%s', __( 'Label File Type not Allowed', 'front-editor' ) ),
                'value' => 'File of invalid type',
            ],
            'fileValidateTypeLabelExpectedTypes' => [
                'label' => sprintf( '%s', __( 'Label Expected Type', 'front-editor' ) ),
                'value' => 'Expects {allButLastType} or {lastType}',
            ],
            'labelMaxFileSize'                   => [
                'label' => sprintf( '%s', __( 'Label size must be', 'front-editor' ) ),
                'value' => 'Maximum file size is {filesize}',
            ],
            'labelMaxTotalFileSizeExceeded'      => [
                'label' => sprintf( '%s', __( 'Maximum total size exceeded', 'front-editor' ) ),
                'value' => 'Maximum total size exceeded',
            ],
            'labelMaxTotalFileSize'              => [
                'label' => sprintf( '%s', __( 'Maximum total size is', 'front-editor' ) ),
                'value' => 'Maximum total file size is {filesize}',
            ],
            'labelMaxFileSizeExceeded'           => [
                'label' => sprintf( '%s', __( 'Label size exceeded', 'front-editor' ) ),
                'value' => 'File is too large',
            ],
        ];
        /**
         * Disabling default settings
         */
        $data['formBuilder_options']['typeUserDisabledAttrs'][self::$field_type] = ['subtype', 'access'];
        $data['formBuilder_options']['disable_attr'][] = '.fld-max_size';
        $data['formBuilder_options']['disable_attr'][] = '.fld-max_files';
        $data['formBuilder_options']['disable_attr'][] = '.fld-accept';
        /* translators: The accept attribute takes as its value a comma-separated list of one or more file types. */
        $description_for_accept = sprintf( __( "The accept attribute takes as its value a comma-separated list of one or more file types, <a href='%s' target='_blank'>or unique file type specifiers</a>, describing which file types to allow.", 'front-editor' ), 'https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/accept#unique_file_type_specifiers' );
        $data['formBuilder_options']['attr_descriptions']['accept'] = $description_for_accept;
        $data['formBuilder_options']['attr_descriptions']['max_files'] = __( 'Max count of files user can select', 'front-editor' );
        $data['formBuilder_options']['attr_descriptions']['max_size'] = __( 'Max size in KB', 'front-editor' );
        return $data;
    }

    /**
     * Add post image selection
     *
     * @return void
     */
    public static function add_field_to_front_form( $post_id, $attributes, $field ) {
        if ( $field['type'] !== self::$field_type ) {
            return;
        }
        require fe_template_path( 'front-editor/file.php' );
    }

    /**
     * Validate file input
     *
     * @return void
     */
    public static function validate_field_on_front_form_submit( $post_id, $form_id ) {
        if ( !isset( $_POST['file_field'] ) ) {
            return;
        }
        $file_field = $_POST['file_field'];
        if ( !is_array( $file_field ) ) {
            return;
        }
        foreach ( $file_field as $name => $value ) {
            $settings = Form::get_form_field_settings_by_name( $name, $form_id );
            if ( !empty( $value ) ) {
                $value = str_replace( "\\", "", $value );
                $value = json_decode( $value );
            }
            if ( isset( $settings['required'] ) ) {
                if ( $settings['required'] && empty( $value ) ) {
                    /* Translators: Filed is required */
                    wp_send_json_error( [
                        'field'   => sprintf( 'file-%s-wrap', $name ),
                        'message' => sprintf( __( '%s is required', 'front-editor' ), $settings['label'] ),
                    ] );
                }
            }
        }
    }

    /**
     * Image check
     *
     * @param [type] $post_data
     * @param [type] $data
     * @param [type] $file
     * @return void
     */
    public static function save_field_to_front_form( $post_id, $form_id ) {
        if ( !isset( $_POST['file_field'] ) ) {
            return;
        }
        foreach ( $_POST['file_field'] as $name => $value ) {
            $settings = Form::get_form_field_settings_by_name( $name, $form_id );
            if ( !empty( $value ) ) {
                $value = str_replace( "\\", "", $value );
                $value = json_decode( $value );
            }
            if ( isset( $settings['required'] ) ) {
                if ( $settings['required'] && empty( $value ) ) {
                    /* Translators: Filed is required */
                    wp_send_json_error( [
                        'field'   => sprintf( 'file-%s-wrap', $name ),
                        'message' => sprintf( __( '%s is required', 'front-editor' ), $settings['label'] ),
                    ] );
                }
            }
            update_post_meta( $post_id, $name, $value );
            do_action(
                'bfe_after_file_field_updated_in_back',
                $value,
                $form_id,
                $_POST
            );
            foreach ( $value as $att_id ) {
                if ( isset( $settings['attach_to_post'] ) ) {
                    if ( $settings['attach_to_post'] ) {
                        $media_post = wp_update_post( [
                            'ID'          => $att_id,
                            'post_parent' => $post_id,
                        ], true );
                    }
                }
            }
        }
    }

}

FileField::init();