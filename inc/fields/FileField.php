<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */
namespace BFE\Field;

use BFE\Form;
use BFE\UploadSecurity;
use BFE\SavePost;
defined( 'ABSPATH' ) || exit;
class FileField {
    public static $field_label = 'File';

    public static $field_type = 'file';

    public static function init() {
        add_filter( 'admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings'] );
        add_filter( 'bfe_front_editor_localize_data', function ( $data ) {
            $data['get_rest_url'] = get_rest_url( null, 'bfe/v1' );
            return $data;
        } );
    }

    public static function file_upload_endpoints() {
        register_rest_route( 'bfe/v1', '/process', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'process_files'],
            'permission_callback' => [__CLASS__, 'upload_permission'],
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
            'permission_callback' => function ( \WP_REST_Request $request ) {
                return self::can_load_file( (int) $request['id'] );
            },
        ] );
    }

    /**
     * File uploads need a form with a file field (same rules as the other upload routes)
     *
     * @param \WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public static function upload_permission( \WP_REST_Request $request ) {
        return SavePost::check_upload_permission( $request, function ( $form_id ) {
            return !empty( Form::get_fields_of_type( $form_id, self::$field_type ) );
        } );
    }

    /**
     * Can the visitor download this file through /load: own or editable files, files
     * uploaded in this session, or files attached to a post the visitor can read
     *
     * @param int $attachment_id
     * @return bool
     */
    public static function can_load_file( $attachment_id ) {
        $attachment = get_post( (int) $attachment_id );
        if ( !$attachment || $attachment->post_type !== 'attachment' ) {
            return false;
        }
        $user_id = get_current_user_id();
        if ( $user_id > 0 && ((int) $attachment->post_author === $user_id || current_user_can( 'edit_post', $attachment->ID )) ) {
            return true;
        }
        self::start_session_if_needed();
        if ( !empty( $_SESSION['bfe_uploaded_files'] ) && in_array( $attachment->ID, (array) $_SESSION['bfe_uploaded_files'] ) ) {
            return true;
        }
        $parent_id = (int) $attachment->post_parent;
        return $parent_id > 0 && is_post_publicly_viewable( $parent_id ) && !post_password_required( $parent_id );
    }

    public static function process_files( \WP_REST_Request $request ) {
        self::start_session_if_needed();
        $files = $request->get_file_params();
        if ( empty( $files ) ) {
            return new \WP_Error('upload_error', __( 'File is not exist', 'front-editor' ), [
                'status' => 400,
            ]);
        }
        foreach ( $files as $file ) {
            apply_filters( 'bfe_before_file_filed_process_file', $file );
            $image = self::upload_file( $file );
            if ( is_wp_error( $image ) ) {
                return $image;
            }
            if ( !isset( $_SESSION['bfe_uploaded_files'] ) ) {
                $_SESSION['bfe_uploaded_files'] = [];
            }
            $_SESSION['bfe_uploaded_files'][] = $image['attach_id'];
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
        if ( empty( $file ) || empty( $file['tmp_name'] ) ) {
            return new \WP_Error('upload_error', __( 'File is not exist', 'front-editor' ), [
                'status' => 400,
            ]);
        }
        if ( !empty( $file['error'] ) || !is_uploaded_file( $file['tmp_name'] ) ) {
            return new \WP_Error('upload_error', __( 'Error during upload', 'front-editor' ), [
                'status' => 400,
            ]);
        }
        if ( filesize( $file['tmp_name'] ) > wp_max_upload_size() ) {
            return new \WP_Error('upload_error', __( 'File is too large', 'front-editor' ), [
                'status' => 413,
            ]);
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        // type comes from the file content, never from the client
        $checked = UploadSecurity::validate_file( $file['tmp_name'], $file['name'] );
        if ( is_wp_error( $checked ) ) {
            return $checked;
        }
        $cont = file_get_contents( $file['tmp_name'] );
        $new_file_name = $checked['name'];
        $ext = $checked['type'];
        $upload = wp_upload_bits( $new_file_name, null, $cont );
        if ( !empty( $upload['error'] ) ) {
            return new \WP_Error('upload_error', $upload['error']);
        }
        $attachment = array(
            'post_title'     => $new_file_name,
            'post_mime_type' => $ext,
            'post_status'    => 'inherit',
        );
        $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
        SavePost::mark_guest_upload( $attach_id );
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
        self::start_session_if_needed();
        $attachment_id = intval( $request->get_body() );
        if ( empty( $attachment_id ) ) {
            return new \WP_Error('invalid_id', __( 'Invalid attachment ID.', 'front-editor' ), [
                'status' => 400,
            ]);
        }
        $authorized = false;
        $is_guest_upload = false;
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'delete_post', $attachment_id ) ) {
                $authorized = true;
            }
        }
        if ( !$authorized && !empty( $_SESSION['bfe_uploaded_files'] ) && in_array( $attachment_id, $_SESSION['bfe_uploaded_files'] ) ) {
            $authorized = true;
            $is_guest_upload = true;
        }
        if ( !$authorized ) {
            return new \WP_Error('rest_forbidden', __( 'Sorry, you are not allowed to delete this attachment.', 'front-editor' ), [
                'status' => 403,
            ]);
        }
        $deleted = wp_delete_attachment( $attachment_id, true );
        if ( $deleted && $is_guest_upload ) {
            $index = array_search( $attachment_id, $_SESSION['bfe_uploaded_files'] );
            if ( $index !== false ) {
                unset($_SESSION['bfe_uploaded_files'][$index]);
            }
        }
    }

    private static function start_session_if_needed() {
        if ( session_status() === PHP_SESSION_NONE ) {
            session_start();
        }
    }

    public static function load_file( \WP_REST_Request $request ) {
        $att_id = (int) $request['id'];
        $path = ( 'attachment' === get_post_type( $att_id ) ? get_attached_file( $att_id ) : '' );
        if ( empty( $path ) || !is_file( $path ) ) {
            wp_send_json_error( [
                'field'    => self::$field_type,
                'messages' => __( 'File is not exist', 'front-editor' ),
            ], 404 );
        }
        UploadSecurity::send_file( $path );
        exit;
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
        if ( !isset( $_POST['file_field'] ) || !is_array( $_POST['file_field'] ) ) {
            return;
        }
        foreach ( $_POST['file_field'] as $name => $value ) {
            // only file fields of this form, so a visitor cannot write other post meta
            $settings = Form::get_submitted_field( $form_id, (string) $name, self::$field_type );
            if ( !$settings || !is_scalar( $value ) ) {
                continue;
            }
            $ids = json_decode( wp_unslash( (string) $value ), true );
            $ids = ( is_array( $ids ) ? array_map( 'intval', array_filter( $ids, 'is_numeric' ) ) : [] );
            // files the visitor may use, plus the files the post already had
            $current = array_map( 'intval', (array) get_post_meta( $post_id, $name, true ) );
            $ids = array_values( array_unique( array_filter( $ids, function ( $att_id ) use($current) {
                return in_array( $att_id, $current, true ) || SavePost::can_use_attachment( $att_id );
            } ) ) );
            if ( !empty( $settings['required'] ) && empty( $ids ) ) {
                /* Translators: Filed is required */
                wp_send_json_error( [
                    'field'   => sprintf( 'file-%s-wrap', $name ),
                    'message' => sprintf( __( '%s is required', 'front-editor' ), esc_html( $settings['label'] ?? $name ) ),
                ] );
            }
            update_post_meta( $post_id, $name, $ids );
            do_action(
                'bfe_after_file_field_updated_in_back',
                $ids,
                $form_id,
                $_POST
            );
            if ( empty( $settings['attach_to_post'] ) ) {
                continue;
            }
            foreach ( $ids as $att_id ) {
                SavePost::update_attachment_parent( $att_id, $post_id );
            }
        }
    }

}

FileField::init();