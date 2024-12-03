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
            'permission_callback' => [__CLASS__, 'changing_rest_api_permission'],
        ] );
        register_rest_route( 'bfe/v1', '/upload_image', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'upload_image_rest_api'],
            'permission_callback' => '__return_true',
        ] );
        register_rest_route( 'bfe/v1', '/upload_file', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'upload_file'],
            'permission_callback' => '__return_true',
        ] );
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
        $form_id = sanitize_text_field( $_POST['form_id'] );
        $all_form_settings = Form::get_form_settings( $form_id );
        $form_settings = $all_form_settings['form_settings'];
        if ( empty( $form_settings ) ) {
            return false;
        }
        if ( !isset( $_POST['x-wp-nonce'] ) || !wp_verify_nonce( $_POST['x-wp-nonce'], 'wp_rest' ) ) {
            return false;
        }
        $is_user = is_user_logged_in();
        if ( !is_user_logged_in() && isset( $form_settings['guest_post'] ) && $form_settings['guest_post'] === 'false' ) {
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
        if ( empty( $image ) ) {
            if ( empty( $image_url ) ) {
                return false;
            }
            return false;
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        if ( !empty( $image ) ) {
            if ( !file_is_displayable_image( $image['tmp_name'] ) ) {
                wp_send_json_error( [
                    'message' => __( 'Image type is not supported, please select another image', 'front-editor' ),
                ] );
                return;
            }
            $cont = file_get_contents( $image['tmp_name'] );
            $new_file_name = $image['name'];
            $ext = sanitize_mime_type( $image['type'] );
        }
        if ( !empty( $image_url ) ) {
            $url = $image_url;
            $get = wp_remote_get( $url );
            $new_file_name = basename( $url );
            // to get file name
            $ext = 'image/' . pathinfo( $url, PATHINFO_EXTENSION );
            // to get extension
            if ( empty( $get['response']['code'] ) ) {
                return false;
            }
            $cont = wp_remote_retrieve_body( $get );
        }
        $new_file_name = sanitize_file_name( $new_file_name );
        $upload = wp_upload_bits( $new_file_name, null, $cont );
        if ( isset( $upload['error'] ) && !empty( $upload['error'] ) ) {
            return [
                'message' => $upload['error'],
            ];
        }
        $attachment = [
            'post_title'     => $new_file_name,
            'post_mime_type' => $ext,
            'post_status'    => 'inherit',
        ];
        $attach_id = wp_insert_attachment( $attachment, $upload['file'], $parent_post_id );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        return [
            'attach_data' => $attach_data,
            'upload'      => $upload,
            'attach_id'   => $attach_id,
        ];
    }

    public static function upload_file( \WP_REST_Request $request ) {
        if ( !isset( $_FILES['file'] ) || empty( $_FILES['file'] ) ) {
            wp_send_json_error( 'We can not find file' );
        }
        if ( empty( $_FILES['file'] ) ) {
            wp_send_json_error( 'We can not find file' );
        }
        if ( $_FILES['file']['size'] >= 5000000 ) {
            wp_send_json_error( 'File is too large ' . $_FILES['file']['size'] );
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $attach_id = media_handle_upload( 'file', 0 );
        $attach_info = get_post( $attach_id );
        $response_arr = [
            "success" => 1,
            "file"    => [
                "url"       => $attach_info->guid,
                "name"      => $attach_info->post_title,
                "size"      => $_FILES['file']['size'],
                "extension" => $_FILES['file']['type'],
                "title"     => $attach_info->post_title,
                "attach_id" => $attach_id,
            ],
        ];
        wp_send_json( $response_arr );
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
        $page_id = sanitize_text_field( $_POST['page_id'] );
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
        $post_data['post_title'] = '-';
        $post_data['post_content'] = '-';
        $post_data['post_status'] = ( isset( $form_settings['fe_post_status'] ) ? sanitize_text_field( $form_settings['fe_post_status'] ) : 'publish' );
        $post_data['comment_status'] = ( isset( $form_settings['comment_status'] ) ? sanitize_text_field( $form_settings['comment_status'] ) : 'open' );
        // If user clicked on save draft
        if ( $_POST['save_to_draft'] === 'true' ) {
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
            $post_data['post_author'] = $cur_user_id;
            $post_id = self::insert_post( $post_data );
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
            $post_data['ID'] = $post_id;
            self::update_post( $post_id, $post_data );
            do_action( 'bfe_ajax_after_front_editor_post_updated', $post_id, $form_id );
        }
        /**
         * Adding post thumbnail
         */
        $thumb_img_id = ( isset( $_POST['thumb_img_id'] ) ? intval( sanitize_text_field( $_POST['thumb_img_id'] ) ) : 0 );
        $thumb_exist = intval( sanitize_text_field( $_POST['thumb_exist'] ) ) ?? 0;
        if ( !$thumb_img_id ) {
            self::add_post_thumbnail( $post_id, self::fe_sanitize_image() ?? '', $thumb_exist );
        } else {
            set_post_thumbnail( $post_id, $thumb_img_id );
        }
        $update_message = ( isset( $form_settings['update_message'] ) ? $form_settings['update_message'] : __( 'Post updated', 'front-editor' ) );
        $post_added_message = ( isset( $form_settings['post_added_message'] ) ? $form_settings['post_added_message'] : __( 'New post created', 'front-editor' ) );
        // adding form_id which settings we used to add/edit post
        update_post_meta( $post_id, 'BFE_the_post_edited_by', $form_id );
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
            $medias = get_attached_media( '', $_POST['post_id'] );
            foreach ( $medias as $media ) {
                if ( $attach_id === $media->ID ) {
                    return false;
                }
            }
            if ( $attach->post_author === get_post( $_POST['post_id'] )->post_author ) {
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
        $media_post = wp_update_post( [
            'ID'          => $media_id,
            'post_parent' => $parent_id,
        ], true );
        if ( is_wp_error( $media_post ) ) {
            error_log( print_r( $media_post, 1 ) );
        }
    }

    /**
     * After post successfully added
     *
     * @return void
     */
    public static function after_post_add_redirect( $post_id, $form_id ) {
        $form_settings = get_post_meta( $form_id, 'fe_form_settings', true );
        $insert_post_redirect = $form_settings['post_redirect_to'];
        $redirect_url = $form_settings['post_redirect_to_link'];
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
        $form_settings = get_post_meta( $form_id, 'fe_form_settings', true );
        $insert_post_redirect = $form_settings['post_update_redirect_to'];
        $redirect_url = $form_settings['post_update_redirect_to_link'];
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