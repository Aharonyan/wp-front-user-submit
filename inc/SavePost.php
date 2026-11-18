<?php

/**
 * This file for uploading and saving data from front editor
 *
 * Long Description.
 *
 * @link    URL
 * @since   x.x.x (if available)
 *
 * @package bfee
 */
namespace BFE;

/**
 * Saving post logic
 */
class SavePost {
    /**
     * Meta key marking attachments a guest uploaded through a form
     */
    const GUEST_UPLOAD_META = '_fus_guest_upload';

    /**
     * ID of the post saved in the current request, used when attaching media
     *
     * @var int
     */
    public static $saving_post_id = 0;

    /**
     * Init function
     *
     * @return void
     */
    public static function init() {
        /**
         * When saving from Gutenberg
         */
        add_action(
            'save_post',
            [__CLASS__, 'gutenberg_save_post'],
            10,
            3
        );
        /**
         * Updating attachment parent
         */
        add_action(
            'fe_before_gallery_block_images_html_render',
            [__CLASS__, 'update_attachment_parent'],
            13,
            2
        );
        add_action(
            'fe_before_simple_image_block_images_html_render',
            [__CLASS__, 'update_attachment_parent'],
            13,
            2
        );
        /**
         * Redirect logic after updated or added
         */
        add_filter(
            'bfe_ajax_after_successfully_post_redirect',
            [__CLASS__, 'after_post_add_redirect'],
            10,
            2
        );
        add_filter(
            'bfe_ajax_after_successfully_post_edited',
            [__CLASS__, 'after_post_update_redirect'],
            10,
            2
        );
        /**
         * Gutenberg scripts
         */
        //add_action('admin_enqueue_scripts', [__CLASS__, 'gutenberg_scripts'], 10, 1);
        add_action( 'rest_api_init', [__CLASS__, 'new_endpoints'] );
    }

    public static function new_endpoints() {
        register_rest_route( 'bfe/v1', '/add_or_update_post', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'update_or_add_post'],
            'permission_callback' => [__CLASS__, 'changing_rest_api_permission'],
        ] );
        register_rest_route( 'bfe/v1', '/post_thumb_uploading_image', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'post_thumb_uploading_image'],
            'permission_callback' => [__CLASS__, 'thumb_upload_permission'],
        ] );
        register_rest_route( 'bfe/v1', '/upload_image', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'upload_image_rest_api'],
            'permission_callback' => [__CLASS__, 'image_upload_permission'],
        ] );
        register_rest_route( 'bfe/v1', '/upload_file', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'upload_file'],
            'permission_callback' => [__CLASS__, 'file_upload_permission'],
        ] );
    }

    /**
     * Who may upload through a form: the form must exist, the nonce must be valid,
     * guests only on guest forms, the form must have a field that uploads this way,
     * and guests are limited per IP and hour.
     *
     * @param \WP_REST_Request $request
     * @param callable         $form_has_field function ($form_id): bool.
     * @return bool|\WP_Error
     */
    public static function check_upload_permission( \WP_REST_Request $request, callable $form_has_field ) {
        $form_id = (int) $request->get_param( 'form_id' );
        $all_form_settings = Form::get_form_settings( $form_id );
        $form_settings = $all_form_settings['form_settings'] ?? [];
        if ( empty( $form_settings ) || !is_array( $form_settings ) ) {
            return false;
        }
        $nonce = ( $request->get_header( 'X-WP-Nonce' ) ?: (( $request->get_param( 'x-wp-nonce' ) ?: $request->get_param( '_wpnonce' ) )) );
        if ( !$nonce || !wp_verify_nonce( sanitize_text_field( (string) $nonce ), 'wp_rest' ) ) {
            return false;
        }
        if ( !is_user_logged_in() && !Editor::guest_posting_enabled( $form_settings ) ) {
            return false;
        }
        if ( !$form_has_field( $form_id ) ) {
            return false;
        }
        if ( !is_user_logged_in() && !UploadSecurity::guest_upload_allowed() ) {
            return new \WP_Error('fus_upload_limit', __( 'Too many uploads, please try again later.', 'front-editor' ), [
                'status' => 429,
            ]);
        }
        return true;
    }

    /**
     * Is an EditorJS option switched on in any EditorJS field of the form
     *
     * @param int    $form_id
     * @param string $option  e.g. editor_carousel_plugin.
     * @param bool   $default value when the field does not have the option.
     * @return bool
     */
    public static function form_has_editor_js_option( $form_id, $option, $default = false ) {
        foreach ( Form::get_fields_of_type( $form_id, 'post_content_editor_js' ) as $field ) {
            $value = ( array_key_exists( $option, $field ) ? $field[$option] : $default );
            if ( !empty( $value ) && $value !== 'false' ) {
                return true;
            }
        }
        return false;
    }

    /**
     * EditorJS carousel block uploads
     */
    public static function image_upload_permission( \WP_REST_Request $request ) {
        return self::check_upload_permission( $request, function ( $form_id ) {
            return self::form_has_editor_js_option( $form_id, 'editor_carousel_plugin' );
        } );
    }

    /**
     * EditorJS attaches block uploads
     */
    public static function file_upload_permission( \WP_REST_Request $request ) {
        return self::check_upload_permission( $request, function ( $form_id ) {
            return self::form_has_editor_js_option( $form_id, 'editor_attaches_plugin' );
        } );
    }

    /**
     * Featured image and EditorJS image block uploads
     */
    public static function thumb_upload_permission( \WP_REST_Request $request ) {
        return self::check_upload_permission( $request, function ( $form_id ) {
            return !empty( Form::get_fields_of_type( $form_id, 'featured_image' ) ) || self::form_has_editor_js_option( $form_id, 'editor_image_plugin', true );
        } );
    }

    /**
     * Check if user have permission to make changes using rest api
     * @param mixed $request
     * @return bool
     */
    public static function changing_rest_api_permission( $request ) {
        if ( !isset( $_POST['form_id'] ) ) {
            return false;
        }
        $form_id = sanitize_text_field( wp_unslash( $_POST['form_id'] ) );
        $all_form_settings = Form::get_form_settings( $form_id );
        $form_settings = $all_form_settings['form_settings'] ?? [];
        if ( empty( $form_settings ) || !is_array( $form_settings ) ) {
            return false;
        }
        if ( !isset( $_POST['x-wp-nonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['x-wp-nonce'] ) ), 'wp_rest' ) ) {
            return false;
        }
        // guests only on forms where guest posting is switched on
        if ( !is_user_logged_in() && !Editor::guest_posting_enabled( $form_settings ) ) {
            return false;
        }
        return true;
    }

    /**
     * Upload image rest api
     *
     * @param \WP_REST_Request $request
     *
     * @return void
     */
    public static function upload_image_rest_api( \WP_REST_Request $request ) {
        if ( !isset( $_FILES['image'] ) ) {
            wp_send_json_error( 'We can not find image' );
        }
        $upload_info = self::upload_image( $_FILES['image'] );
        if ( empty( $upload_info['upload']['url'] ) ) {
            wp_send_json_error( [
                'message' => __( 'Image type is not supported, please select another image', 'front-editor' ),
            ] );
        }
        $string = [
            "success" => 1,
            "file"    => [
                "url" => $upload_info['upload']['url'],
            ],
        ];
        wp_send_json( $string );
    }

    /**
     * Uploading image logic
     *
     * @param array  $image     image array.
     * @param string $image_url image url.
     */
    public static function upload_image( $image = [], $image_url = '', $parent_post_id = 0 ) {
        if ( empty( $image ) && empty( $image_url ) ) {
            return false;
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $downloaded = '';
        if ( empty( $image ) ) {
            $downloaded = self::download_image( $image_url );
            if ( !$downloaded ) {
                return false;
            }
            $image = [
                'tmp_name' => $downloaded,
                'name'     => basename( (string) wp_parse_url( $image_url, PHP_URL_PATH ) ),
            ];
        }
        $checked = ( file_is_displayable_image( $image['tmp_name'] ) ? UploadSecurity::validate_file( $image['tmp_name'], $image['name'] ) : null );
        if ( empty( $checked ) || is_wp_error( $checked ) ) {
            if ( $downloaded ) {
                wp_delete_file( $downloaded );
            }
            wp_send_json_error( [
                'message' => __( 'Image type is not supported, please select another image', 'front-editor' ),
            ] );
            return;
        }
        $cont = file_get_contents( $image['tmp_name'] );
        if ( $downloaded ) {
            wp_delete_file( $downloaded );
        }
        $upload = wp_upload_bits( $checked['name'], null, $cont );
        if ( isset( $upload['error'] ) && !empty( $upload['error'] ) ) {
            return [
                'message' => $upload['error'],
            ];
        }
        $attachment = [
            'post_title'     => $checked['name'],
            'post_mime_type' => $checked['type'],
            'post_status'    => 'inherit',
        ];
        $attach_id = wp_insert_attachment( $attachment, $upload['file'], $parent_post_id );
        self::mark_guest_upload( $attach_id );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        return [
            'attach_data' => $attach_data,
            'upload'      => $upload,
            'attach_id'   => $attach_id,
        ];
    }

    /**
     * Download an image by URL into a temp file: no internal hosts, limited size
     *
     * @param string $image_url
     * @return string|false temp file path
     */
    public static function download_image( $image_url ) {
        $tmp_file = wp_tempnam( (string) wp_parse_url( $image_url, PHP_URL_PATH ) );
        if ( !$tmp_file ) {
            return false;
        }
        $max_size = (int) apply_filters( 'bfe_max_image_download_size', min( wp_max_upload_size(), 10 * MB_IN_BYTES ) );
        $response = wp_safe_remote_get( $image_url, [
            'timeout'             => 30,
            'stream'              => true,
            'filename'            => $tmp_file,
            'limit_response_size' => $max_size,
        ] );
        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) || !filesize( $tmp_file ) ) {
            wp_delete_file( $tmp_file );
            return false;
        }
        return $tmp_file;
    }

    public static function upload_file( \WP_REST_Request $request ) {
        $file = ( isset( $_FILES['file'] ) && is_array( $_FILES['file'] ) ? $_FILES['file'] : [] );
        if ( empty( $file['tmp_name'] ) || !empty( $file['error'] ) ) {
            wp_send_json_error( [
                'message' => __( 'We can not find file', 'front-editor' ),
            ] );
        }
        if ( (int) $file['size'] >= 5000000 ) {
            wp_send_json_error( [
                'message' => __( 'File is too large', 'front-editor' ),
            ] );
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        // type from the file content, never from the browser; html/svg/js and other unsafe types are refused
        $checked = UploadSecurity::validate_file( $file['tmp_name'], (string) $file['name'] );
        if ( is_wp_error( $checked ) ) {
            wp_send_json_error( [
                'message' => $checked->get_error_message(),
            ] );
        }
        $upload = wp_upload_bits( $checked['name'], null, file_get_contents( $file['tmp_name'] ) );
        if ( !empty( $upload['error'] ) ) {
            wp_send_json_error( [
                'message' => $upload['error'],
            ] );
        }
        $title = sanitize_text_field( pathinfo( $checked['name'], PATHINFO_FILENAME ) );
        $attach_id = wp_insert_attachment( [
            'post_title'     => $title,
            'post_mime_type' => $checked['type'],
            'post_status'    => 'inherit',
        ], $upload['file'] );
        if ( is_wp_error( $attach_id ) || !$attach_id ) {
            wp_send_json_error( [
                'message' => __( 'Error during upload', 'front-editor' ),
            ] );
        }
        self::mark_guest_upload( $attach_id );
        wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $upload['file'] ) );
        wp_send_json( [
            "success" => 1,
            "file"    => [
                "url"       => wp_get_attachment_url( $attach_id ),
                "name"      => $title,
                "size"      => (int) $file['size'],
                "extension" => $checked['ext'],
                "title"     => $title,
                "attach_id" => $attach_id,
            ],
        ] );
    }

    /**
     * Save post from front
     */
    public static function update_or_add_post( \WP_REST_Request $request ) {
        $cur_user_id = get_current_user_id();
        $post_id = sanitize_text_field( $_POST['post_id'] );
        $is_new_post = 'new' === $post_id;
        $form_id = sanitize_text_field( $_POST['form_id'] );
        $all_form_settings = Form::get_form_settings( $form_id );
        $form_settings = $all_form_settings['form_settings'];
        $page_id = ( isset( $_POST['page_id'] ) ? intval( $_POST['page_id'] ) : 0 );
        self::$saving_post_id = 0;
        // shortcodes from visitors who cannot write posts in wp-admin are shown as text
        if ( !current_user_can( 'edit_posts' ) ) {
            add_filter(
                'wp_insert_post_data',
                [__CLASS__, 'escape_shortcodes_in_post_data'],
                10,
                1
            );
        }
        do_action(
            'bfe_ajax_before_post_update_or_creation',
            $post_id,
            $form_id,
            $all_form_settings
        );
        if ( !empty( $post_id ) && 'new' !== $post_id ) {
            $post_id = intval( $post_id );
            if ( !$post_id ) {
                wp_send_json_error( [
                    'message' => __( 'The post you trying to edit is not exist, please create a new one', 'front-editor' ),
                ] );
            }
            if ( !get_post_status( $post_id ) ) {
                wp_send_json_error( [
                    'message' => __( 'The post you trying to edit is not exist, please create a new one', 'front-editor' ),
                ] );
            }
        }
        $post_data = [];
        // this fields is required not to be empty
        $post_data['post_status'] = ( isset( $form_settings['fe_post_status'] ) ? sanitize_text_field( $form_settings['fe_post_status'] ) : 'publish' );
        $post_data['comment_status'] = ( isset( $form_settings['comment_status'] ) ? sanitize_text_field( $form_settings['comment_status'] ) : 'open' );
        // If user clicked on save draft
        if ( isset( $_POST['save_to_draft'] ) && $_POST['save_to_draft'] === 'true' ) {
            $post_data['post_status'] = 'draft';
        }
        /**
         * Before post creation or update
         */
        $post_data = apply_filters(
            'bfe_ajax_before_front_editor_post_update_or_creation',
            $post_data,
            $_POST,
            $_FILES,
            $post_id,
            $form_id,
            $all_form_settings
        );
        if ( $is_new_post ) {
            if ( isset( $_POST['post_title'] ) && !empty( $_POST['post_title'] ) ) {
                $post_data['post_title'] = sanitize_text_field( $_POST['post_title'] );
            }
            $post_data['post_content'] = '';
            if ( isset( $_POST['post_content'] ) && !empty( $_POST['post_content'] ) ) {
                $post_data['post_content'] = wp_kses_post( $_POST['post_content'] );
            }
            $can_create = Editor::can_edit_post( $cur_user_id, 'new', $form_id );
            if ( !$can_create['status'] ) {
                wp_send_json_error( [
                    'message' => $can_create['message'],
                ] );
            }
            $post_data['post_author'] = $cur_user_id;
            $post_id = self::insert_post( $post_data );
            self::$saving_post_id = (int) $post_id;
            do_action( 'bfe_ajax_after_front_editor_post_inserted', $post_id, $form_id );
        } else {
            $post_id = intval( $post_id );
            /**
             * Checking is user has access to edit post
             */
            $can_edit = Editor::can_edit_post( $cur_user_id, $post_id, $form_id );
            if ( !$can_edit['status'] ) {
                wp_send_json_error( [
                    'message' => $can_edit['message'],
                ] );
            }
            if ( 'trash' === get_post_status( $post_id ) ) {
                wp_send_json_error( [
                    'message' => __( 'This post is in the trash and cannot be edited.', 'front-editor' ),
                ] );
            }
            $post_data['ID'] = $post_id;
            if ( isset( $post_data['post_status'] ) ) {
                $post_data['post_status'] = self::get_update_status( $post_id, $post_data['post_status'] );
            }
            self::$saving_post_id = $post_id;
            self::update_post( $post_id, $post_data );
            do_action( 'bfe_ajax_after_front_editor_post_updated', $post_id, $form_id );
        }
        /**
         * Adding post thumbnail
         */
        $thumb_img_id = ( isset( $_POST['thumb_img_id'] ) ? intval( sanitize_text_field( $_POST['thumb_img_id'] ) ) : 0 );
        $thumb_exist = ( isset( $_POST['thumb_exist'] ) ? intval( sanitize_text_field( $_POST['thumb_exist'] ) ) : 0 );
        if ( !$thumb_img_id ) {
            self::add_post_thumbnail( $post_id, self::fe_sanitize_image() ?? '', $thumb_exist );
        } elseif ( $thumb_img_id !== (int) get_post_thumbnail_id( $post_id ) && self::can_use_attachment( $thumb_img_id ) && wp_attachment_is_image( $thumb_img_id ) ) {
            set_post_thumbnail( $post_id, $thumb_img_id );
        }
        $update_message = ( isset( $form_settings['update_message'] ) ? $form_settings['update_message'] : __( 'Post updated', 'front-editor' ) );
        $post_added_message = ( isset( $form_settings['post_added_message'] ) ? $form_settings['post_added_message'] : __( 'New post created', 'front-editor' ) );
        // form the post was created with: its edit settings (time lock) keep applying, so authors
        // cannot switch it by editing through another form; editors may move a post to another form
        if ( $is_new_post || !get_post_meta( $post_id, 'BFE_the_post_edited_by', true ) || current_user_can( 'edit_others_posts' ) ) {
            update_post_meta( $post_id, 'BFE_the_post_edited_by', (int) $form_id );
        }
        if ( $page_id ) {
            update_post_meta( $post_id, 'fus_post_created_from_page', $page_id );
        }
        do_action(
            'bfe_ajax_after_front_editor_post_update_or_creation',
            $post_id,
            $form_id,
            $post_data,
            $all_form_settings
        );
        remove_filter( 'wp_insert_post_data', [__CLASS__, 'escape_shortcodes_in_post_data'], 10 );
        wp_send_json_success( [
            'url'         => get_the_permalink( $post_id ),
            'post_id'     => $post_id,
            'message'     => ( $is_new_post ? $post_added_message : $update_message ),
            'redirect_to' => ( $is_new_post ? apply_filters( 'bfe_ajax_after_successfully_post_redirect', $post_id, $form_id ) : apply_filters( 'bfe_ajax_after_successfully_post_edited', $post_id, $form_id ) ),
        ] );
    }

    /**
     * Update post
     *
     * @param integer $post_id   id of post.
     * @param array   $post_data post data array.
     *
     * @return void
     */
    public static function update_post( $post_id = 0, $post_data = [] ) {
        $post_id = wp_update_post( $post_data );
        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( $post_id->get_error_message() );
        }
    }

    /**
     * Create post
     *
     * @param array $post_data post data array.
     *
     * @return $post_id
     */
    public static function insert_post( $post_data = [] ) {
        $post_id = wp_insert_post( $post_data );
        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( $post_id->get_error_message() );
        }
        if ( empty( $post_id ) ) {
            wp_send_json_error( __( 'No post created.', 'front_editor' ) );
        }
        return $post_id;
    }

    /**
     * Adding post thumbnail or delete
     *
     * @param integer $post_id     post id.
     * @param array   $image       array of file['image'].
     * @param integer $thumb_exist is post already has image or not.
     *
     * @return void
     */
    public static function add_post_thumbnail( $post_id = 0, $image = [], $thumb_exist = 0 ) {
        /**
         * Downloading image and adding to post
         */
        if ( !empty( $image ) ) {
            $post_thumbnail = $image;
            $upload_data = self::upload_image( $post_thumbnail, '', $post_id );
            set_post_thumbnail( $post_id, (int) $upload_data['attach_id'] );
            return;
        }
        if ( !(int) $thumb_exist ) {
            delete_post_thumbnail( $post_id );
        }
    }

    /**
     * Undocumented function
     *
     * @param array $image file array where we can find img.
     *
     * @return $image
     */
    public static function fe_sanitize_image( $image = [] ) {
        if ( empty( $image ) ) {
            $image = [];
            $file = ( isset( $_FILES['image'] ) ? $_FILES['image'] : [] );
            if ( isset( $file ) ) {
                foreach ( $file as $key => $val ) {
                    switch ( $key ) {
                        case 'tmp_name':
                            $image[$key] = sanitize_text_field( $val );
                            break;
                        case 'name':
                            $image[$key] = sanitize_file_name( $val );
                            break;
                        case 'type':
                            $image[$key] = sanitize_mime_type( $val );
                            break;
                        case 'size':
                            $image[$key] = intval( sanitize_text_field( $val ) );
                            break;
                    }
                }
            }
        }
        return $image;
    }

    /**
     * Uploading image by url and by file
     *
     * @return void
     */
    public static function post_thumb_uploading_image( \WP_REST_Request $request ) {
        $image = self::fe_sanitize_image();
        $image_url = ( isset( $_POST['image_url'] ) ? esc_url( $_POST['image_url'] ) : null );
        if ( !empty( $image_url ) ) {
            if ( !self::is_image_upload_needed() ) {
                wp_send_json_success( [
                    'url' => $image_url,
                ] );
            }
        }
        $upload_data = self::upload_image( $image, $image_url );
        if ( empty( $upload_data['attach_id'] ) ) {
            wp_send_json_error( [
                'message' => __( 'Image type is not supported, please select another image', 'front-editor' ),
            ] );
        }
        wp_send_json_success( [
            'url' => $upload_data['upload']['url'],
            'id'  => $upload_data['attach_id'],
        ] );
    }

    /**
     * Check if we need upload the image again or it is already exist
     *
     * @return boolean
     */
    public static function is_image_upload_needed() {
        if ( !isset( $_POST['post_id'] ) ) {
            return false;
        }
        if ( empty( $_POST['post_id'] ) ) {
            return false;
        }
        if ( $_POST['post_id'] === 'new' ) {
            return true;
        }
        $image_url = ( isset( $_POST['image_url'] ) ? esc_url( $_POST['image_url'] ) : null );
        $attach_id = attachment_url_to_postid( $image_url );
        if ( $attach_id ) {
            $attach = get_post( $attach_id );
            $medias = get_attached_media( '', (int) $_POST['post_id'] );
            foreach ( $medias as $media ) {
                if ( $attach_id === $media->ID ) {
                    return false;
                }
            }
            $post = get_post( (int) $_POST['post_id'] );
            if ( $post && (int) $attach->post_author > 0 && (int) $attach->post_author === (int) $post->post_author ) {
                return false;
            }
            return true;
        }
        return true;
    }

    /**
     * On post update from admin panel
     *
     * @param [type] $post_ID
     * @param [type] $post
     * @param [type] $update
     *
     * @return void
     */
    public static function gutenberg_save_post( $post_ID, $post, $update ) {
        // Bail if we're doing an auto save
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( empty( $_POST['action'] ) ) {
            return;
        }
        if ( $_POST['action'] === 'bfe_update_post' ) {
            return;
        }
        update_post_meta( $post_ID, 'fe_post_updated_from_admin', 1 );
    }

    /**
     * Updating parent post for attachments
     *
     * @param [type] $media_id
     * @param [type] $parent_id
     *
     * @return void
     */
    public static function update_attachment_parent( $media_id, $parent_id ) {
        if ( is_array( $media_id ) ) {
            $media_id = $media_id['id'] ?? 0;
        }
        $media_id = (int) $media_id;
        // the post being saved, never the id the browser sent
        $parent_id = self::$saving_post_id;
        if ( !$media_id || !$parent_id || !self::can_use_attachment( $media_id ) ) {
            return;
        }
        if ( (int) get_post_field( 'post_parent', $media_id ) === $parent_id ) {
            return;
        }
        $media_post = wp_update_post( [
            'ID'          => $media_id,
            'post_parent' => $parent_id,
        ], true );
        if ( is_wp_error( $media_post ) ) {
            error_log( print_r( $media_post, 1 ) );
        }
    }

    /**
     * Can the current visitor attach this media to their post or use it as featured image.
     * Logged in users: own uploads or media they can edit. Guests: only files a guest
     * uploaded through a form that are not attached to any post yet.
     *
     * @param int $attachment_id
     * @return bool
     */
    public static function can_use_attachment( $attachment_id ) {
        $attachment = get_post( (int) $attachment_id );
        if ( !$attachment || $attachment->post_type !== 'attachment' ) {
            return false;
        }
        $user_id = get_current_user_id();
        if ( $user_id > 0 ) {
            return (int) $attachment->post_author === $user_id || current_user_can( 'edit_post', $attachment->ID );
        }
        // unattached files uploaded by this guest browser (marker = hash of its cookie id)
        $marker = (string) get_post_meta( $attachment->ID, self::GUEST_UPLOAD_META, true );
        return (int) $attachment->post_author === 0 && (int) $attachment->post_parent === 0 && $marker !== '' && hash_equals( $marker, UploadSecurity::guest_upload_marker() );
    }

    /**
     * Mark a file a guest uploaded, so it can be attached to the guest post later
     *
     * @param int $attachment_id
     * @return void
     */
    public static function mark_guest_upload( $attachment_id ) {
        if ( $attachment_id && !is_user_logged_in() ) {
            update_post_meta( (int) $attachment_id, self::GUEST_UPLOAD_META, UploadSecurity::guest_upload_marker() );
            GuestUploadCleanup::mark_unclaimed( $attachment_id );
        }
    }

    /**
     * Status of an edited post. Users who cannot publish this post type keep the
     * current status (pending, private, scheduled) so an edit never undoes moderation;
     * their own drafts can still be submitted and published posts can go back to draft/pending.
     *
     * @param int    $post_id
     * @param string $status status from the form settings and filters.
     * @return string
     */
    public static function get_update_status( $post_id, $status ) {
        $post = get_post( $post_id );
        if ( !$post ) {
            return $status;
        }
        $post_type = get_post_type_object( $post->post_type );
        if ( $post_type && current_user_can( $post_type->cap->publish_posts ) ) {
            return $status;
        }
        $current = $post->post_status;
        // held by AI moderation (Pro): only an editor decides, an edit keeps the status
        $moderation = get_post_meta( $post->ID, '_fus_ai_moderation', true );
        if ( is_array( $moderation ) && !empty( $moderation['flagged'] ) ) {
            return $current;
        }
        if ( in_array( $current, ['draft', 'auto-draft'], true ) ) {
            return $status;
        }
        if ( $current === 'publish' && in_array( $status, ['draft', 'pending'], true ) ) {
            return $status;
        }
        return $current;
    }

    /**
     * Show shortcodes as text: [tag] becomes [[tag]], which WordPress prints as [tag]
     *
     * @param array $data slashed post data.
     * @return array
     */
    public static function escape_shortcodes_in_post_data( $data ) {
        foreach ( ['post_content', 'post_excerpt'] as $key ) {
            if ( !empty( $data[$key] ) && is_string( $data[$key] ) ) {
                $data[$key] = self::escape_shortcodes( $data[$key] );
            }
        }
        return $data;
    }

    /**
     * @param string $content
     * @return string
     */
    public static function escape_shortcodes( $content ) {
        if ( strpos( $content, '[' ) === false ) {
            return $content;
        }
        return preg_replace_callback( '/' . get_shortcode_regex() . '/', function ( $match ) {
            // already escaped [[tag]]
            if ( $match[1] === '[' && $match[6] === ']' ) {
                return $match[0];
            }
            return '[' . $match[0] . ']';
        }, $content );
    }

    /**
     * After post successfully added
     *
     * @return void
     */
    public static function after_post_add_redirect( $post_id, $form_id ) {
        $form_settings = (array) get_post_meta( $form_id, 'fe_form_settings', true );
        $insert_post_redirect = $form_settings['post_redirect_to'] ?? '';
        $redirect_url = $form_settings['post_redirect_to_link'] ?? '';
        if ( $insert_post_redirect === 'disable' ) {
            return false;
        }
        if ( $insert_post_redirect === 'post' ) {
            return get_post_permalink( $post_id );
        }
        if ( filter_var( $redirect_url, FILTER_VALIDATE_URL ) === false ) {
            return false;
        }
        return $redirect_url;
    }

    /**
     * After post successfully updated
     *
     * @return void
     */
    public static function after_post_update_redirect( $post_id, $form_id ) {
        $form_settings = (array) get_post_meta( $form_id, 'fe_form_settings', true );
        $insert_post_redirect = $form_settings['post_update_redirect_to'] ?? '';
        $redirect_url = $form_settings['post_update_redirect_to_link'] ?? '';
        if ( $insert_post_redirect === 'disable' ) {
            return false;
        }
        if ( $insert_post_redirect === 'post' ) {
            return get_post_permalink( $post_id );
        }
        if ( filter_var( $redirect_url, FILTER_VALIDATE_URL ) === false ) {
            return false;
        }
        return $redirect_url;
    }

    /**
     * Gutenberg scripts
     *
     * @return void
     */
    public static function gutenberg_scripts( $hook ) {
        global $post;
        if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
            if ( 'recipes' === $post->post_type ) {
                $asset = (require FE_PLUGIN_DIR_PATH . 'build/front.asset.php');
                wp_enqueue_script(
                    'disable_editing',
                    FE_PLUGIN_URL . '/build/admin.js',
                    $asset['version'],
                    1,
                    true
                );
            }
        }
    }

}

SavePost::init();