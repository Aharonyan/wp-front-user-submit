<?php

/**
 * Gutenberg block to display Post Form.
 *
 * @package BFE;
 */

namespace BFE;

defined('ABSPATH') || exit;

/**
 * Class Post Form - registers custom gutenberg block.
 */
class Form
{
    /**
     * Init logic.
     */
    public static function init()
    {
        require_once __DIR__ . '/fields/PostTitleField.php';
        require_once __DIR__ . '/fields/PostThumbField.php';
        require_once __DIR__ . '/fields/TinyMCE.php';
        require_once __DIR__ . '/fields/EditorJsField.php';
        require_once __DIR__ . '/fields/TaxonomiesFields.php';
        require_once __DIR__ . '/fields/TextField.php';
        require_once __DIR__ . '/fields/HiddenField.php';
        require_once __DIR__ . '/fields/TextareaField.php';
        require_once __DIR__ . '/fields/MdEditor.php';
        require_once __DIR__ . '/fields/FileField.php';
        require_once __DIR__ . '/fields/SelectField.php';
        require_once __DIR__ . '/fields/RadioGroupField.php';
        require_once __DIR__ . '/fields/GoogleRecaptcha.php';
        require_once __DIR__ . '/fields/NumberField.php';
        require_once __DIR__ . '/fields/ButtonField.php';
        require_once __DIR__ . '/fields/HeaderField.php';
        require_once __DIR__ . '/fields/CheckboxGroupField.php';
        require_once __DIR__ . '/fields/ParagraphField.php';
        require_once __DIR__ . '/fields/GoogleMapField.php';
        require_once __DIR__ . '/fields/DateField.php';
        require_once __DIR__ . '/fields/hCaptcha.php';
        require_once __DIR__ . '/fields/ActionHook.php';
        require_once FE_PLUGIN_DIR_PATH . '/inc/PostFormsListTable.php';

        /**
         * Registering custom post type
         */
        add_action('init', [__CLASS__, 'register_post_types']);

        /**
         * Adding scripts to custom post type
         */
        add_action('admin_enqueue_scripts', [__CLASS__, 'add_admin_scripts'], 10, 1);

        /**
         * Get formBuilder data
         */
        add_action('wp_ajax_fe_get_formBuilder_data', [__CLASS__, 'fe_get_formBuilder_data']);

        add_action('wp_ajax_save_post_front_settings', [__CLASS__, 'save_post_front_settings']);

        add_action('wp_ajax_save_migration_settings', [__CLASS__, 'save_migration_settings']);

        add_action('rest_api_init', [__CLASS__, 'new_endpoints']);
    }


    public static function new_endpoints()
    {
        register_rest_route('bfe/v1', '/form', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'fe_get_formBuilder_data'],
            'permission_callback' => function () {
                return current_user_can('edit_others_posts');
            }
        ]);

        register_rest_route('bfe/v1', '/add-update-form', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'save_post_front_settings'],
            'permission_callback' => function () {
                return current_user_can('edit_others_posts');
            }
        ]);
    }

    /**
     * Get formBuilder data
     *
     * @return void
     */
    public static function fe_get_formBuilder_data(\WP_REST_Request $request)
    {

        /**
         * If this is auto save do nothing
         */
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        $post_ID = isset($request['post_id']) ? sanitize_text_field($request['post_id']) : false;

        $data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'settings' => [
                'post_type' => sanitize_text_field($request['post_type']),
                'post_id' => $post_ID,
            ],
            'formBuilder_options' => [
                //'prepend' => sprintf('<h2>%s</h2>', __('Post Title', 'front-editor')),
                'fields' => [], // New field creation
                'typeUserAttrs' => [], // Custom attr settings for fields,
                'disabledFieldButtons' => [],
                'disableProFields' => [],
                'defaultFields' => [],
                'typeUserDisabledAttrs' => [ // Disable attributes
                    'paragraph' => ['access']
                ],
                'disable_attr' => [],
                'attr_descriptions' => [
                    'name' =>  __('This field value will be saved into post meta using this name.', 'front-editor'),
                    'description' => __('Help text will be shown under this field on the front end form.', 'front-editor'),
                    'post_content' => __('Enable this option to save the field data directly to the post content.. Important: If multiple editors have this setting enabled, each new editor will overwrite the previous content in this field.', 'front-editor'),
                    'save_to' => __('Enable this option to save the field data directly to the post content, excerpt or meta.. Important: If multiple editors have selected post content or excerpt, each new editor will overwrite the previous content in this field.', 'front-editor'),
                ],
                'templates' => [],
                'temp_back' => [],
                'disableFields' => ['autocomplete'],
                'defaultControls' => ['text'],
                'controls_group' => [
                    'post_fields' => [
                        'label' => __('Post Fields', 'front-editor'),
                        'types' => []
                    ],
                    'editors' => [
                        'label' => __('Editors', 'front-editor'),
                        'types' => []
                    ],
                    'taxonomies' => [
                        'label' => __('Taxonomies', 'front-editor'),
                        'types' => []
                    ],
                    'custom_fields' => [
                        'label' => __('Custom Fields', 'front-editor'),
                        'types' => []
                    ],
                ],
                'disabledFieldButtons' => [],
                'controlOrder' => [],
                'disabledActionButtons' => ['data', 'clear', 'save'],
                'messages' => [
                    'max_fields_warning' => __('You already have this field in the form', 'front-editor'),
                    'for_pro_title' => __('Available in Pro version', 'front-editor'),
                    'for_pro_message' => __('Please upgrade to the Pro version to unlock all these awesome features', 'front-editor'),
                    'for_pro_link' => home_url('/wp-admin/admin.php?page=front_editor_settings-pricing'),
                    'for_pro_button_text' => __('Get the Pro version', 'front-editor'),
                ]
            ],
        ];


        if ($post_ID) {
            $data['formBuilderData'] = get_post_meta($post_ID, 'formBuilderData', true);
        }

        /**
         * Default controls
         */
        $data['formBuilder_options']['controls_group']['custom_fields']['types'] = $data['formBuilder_options']['defaultControls'];

        /**
         * Ability to add custom group
         */
        $data['formBuilder_options']['controls_group'] = apply_filters('admin_post_form_formBuilder_add_controls_group', $data['formBuilder_options']['controls_group']);

        $filter_data = apply_filters('admin_post_form_formBuilder_settings', $data);

        /**
         * Order Elements in control bar
         */
        foreach ($filter_data['formBuilder_options']['controls_group'] as $group) {

            if (empty($group['types'])) {
                continue;
            }

            foreach ($group['types'] as $types) {
                $filter_data['formBuilder_options']['controlOrder'][] = $types;
            }
        }
        wp_send_json_success($filter_data);
    }


    /**
     * Callback method for Post Forms submenu
     *
     * @since 2.5
     *
     * @return void
     */
    public static function fe_post_forms_page()
    {
        $action           = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : null;
        $post_ID           = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : 'new';
        $add_new_page_url = admin_url('admin.php?page=fe-post-forms&action=add-new');


        $data = [
            'post_id' => $post_ID,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_rest'),
            'rest_url_get' => get_rest_url(null, 'bfe/v1/form'),
            'rest_update' => get_rest_url(null, 'bfe/v1/add-update-form'),
        ];

        wp_localize_script('bfe-block-script', 'fe_post_form_data', apply_filters('bfe_fe_post_form_backend_block_localize_data', $data));

        switch ($action) {
            case 'edit':
                wp_enqueue_script('jquery-ui');
                wp_enqueue_script('bfe-block-script');
                wp_enqueue_script('bfe-form-builder');
                wp_enqueue_style('fe_post_form_CPT');
                require fe_template_path('admin/post-form.php');
                break;

            case 'add-new':
                wp_enqueue_script('jquery-ui');
                wp_enqueue_script('bfe-block-script');
                wp_enqueue_script('bfe-form-builder');
                wp_enqueue_style('fe_post_form_CPT');
                require fe_template_path('admin/post-form.php');
                break;
            case 'trash':
                if ($post_ID !== 'new') {
                    wp_trash_post($post_ID);
                }

                require_once fe_template_path('admin/post-forms-list-table-view.php');
                break;

            case 'delete':
                if ($post_ID !== 'new') {
                    wp_delete_post($post_ID, true);
                }

                require_once fe_template_path('admin/post-forms-list-table-view.php');
                break;
            case 'restore':
                if ($post_ID !== 'new') {
                    wp_untrash_post($post_ID);
                    wp_publish_post($post_ID);
                }

                require_once fe_template_path('admin/post-forms-list-table-view.php');
                break;

            default:
                require_once fe_template_path('admin/post-forms-list-table-view.php');
                break;
        }
    }

    /**
     * Updating post
     *
     * @return void
     */
    public static function save_post_front_settings(\WP_REST_Request $request)
    {
        /**
         * If this is auto save do nothing
         */
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        do_action('fe_before_wp_admin_form_create_save', $_POST);

        $title = isset($_POST['fe_title']) ? $_POST['fe_title'] : __('Sample Form', 'front-editor');
        if (!empty($_POST['post_id']) && $_POST['post_id'] !== 'new') {
            $post_ID = intval(sanitize_text_field($_POST['post_id']));
            wp_update_post([
                'ID'           => $post_ID,
                'post_title'   => $title,
            ]);
        } elseif (!empty($_POST['post_id']) && $_POST['post_id'] === 'new') {
            $post_ID = wp_insert_post([
                'post_title' => $title,
                'post_type' => 'fe_post_form',
                'post_status'   => 'publish',
            ]);
        }

        $form_builder_data = $_POST['formBuilderData'];
        $form_builder_data_array = json_decode(stripslashes($form_builder_data), true);

        /**
         * Saving data
         */
        if (empty($form_builder_data)) {
            wp_send_json_success([
                'post_id' => $post_ID,
                'message' => [
                    'title' => __('Oops', 'front-editor'),
                    'message' => __('Form builder cannot be empty', 'front-editor'),
                    'status' => 'warning'
                ]
            ]);
        }

        foreach ($form_builder_data_array as $key => $data_object) {
            if (is_array($data_object) && !empty($data_object)) {
                foreach ($data_object as $name => $input) {
                    if (is_string($input)) {
                        $form_builder_data_array[$key][$name] = self::sanitize_input($input);
                    }
                }
            }
        }

        /**
         * Escaping scripts
         */
        $form_builder_data = json_encode($form_builder_data_array, JSON_UNESCAPED_UNICODE);
        update_post_meta($post_ID, 'formBuilderData', $form_builder_data);

        /**
         * Adding all settings to meta fields
         */
        if (!empty($_POST['settings'])) {
            update_post_meta($post_ID, 'fe_form_settings', $_POST['settings']);
        }

        wp_send_json_success([
            'post_id' => $post_ID,
            'form_edit_url' => home_url(sprintf('/wp-admin/admin.php?page=fe-post-forms&action=edit&id=%s', $post_ID)),
            'message' => [
                'title' => __('Form Settings Saved', 'front-editor'),
                'status' => 'success'
            ]
        ]);
    }
    
    /**
     * Update setting in textarea
     *
     * @return void
     */
    public static function save_migration_settings()
    {
        /**
         * If this is auto save do nothing
         */
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        check_ajax_referer( 'wp_rest', 'nonce' );

        if (empty($_POST['post_id'])) {
            wp_send_json_error([
                'post_id' => '',
                'message' => [
                    'title' => __('post_id is empty.', 'front-editor'),
                    'status' => 'error'
                ],
                'data' => []
            ]);
        }

        if (!empty($_POST['post_id'])) {
            $post_ID = intval(sanitize_text_field($_POST['post_id']));
        }
        
        if ($_POST['migrate_type'] == 'export') {
            $json = [];
            $json['formBuilderData'] = json_decode(get_post_meta($post_ID, 'formBuilderData', true), true);
            $json['fe_form_settings'] = get_post_meta($post_ID, 'fe_form_settings', true);

            wp_send_json_success([
                'post_id' => $post_ID,
                'message' => [
                    'title' => __('Loading', 'front-editor'),
                    'status' => 'success'
                ],
                'data' => base64_encode(json_encode($json))
            ]);
        } else {
            if(empty($_POST['migrate_value']) || base64_decode($_POST['migrate_value'], true) == false ) {
                wp_send_json_error([
                    'post_id' => $post_ID,
                    'message' => [
                        'title' => __('This is not a valid record.', 'front-editor'),
                        'status' => 'error'
                    ],
                    'data' => []
                ]);
            }
            $base64_decode = base64_decode($_POST['migrate_value']);
            $migrate_value = json_decode(stripslashes($base64_decode), true);
            if (!isset($migrate_value['formBuilderData']) || !isset($migrate_value['fe_form_settings'])) {
                wp_send_json_error([
                    'post_id' => $post_ID,
                    'message' => [
                        'title' => __('This is not a valid record.', 'front-editor'),
                        'status' => 'error'
                    ],
                    'data' => $migrate_value
                ]);
            }
            $form_builder_data = json_encode($migrate_value['formBuilderData'], JSON_UNESCAPED_UNICODE);
            update_post_meta($post_ID, 'formBuilderData', $form_builder_data);
            update_post_meta($post_ID, 'fe_form_settings', $migrate_value['fe_form_settings']);

            wp_send_json_success([
                'post_id' => $post_ID,
                'message' => [
                    'title' => __('Form Settings Migrated', 'front-editor'),
                    'status' => 'success'
                ],
                'data' => ''
            ]);
        }
        wp_die();
    }

    /**
     * Get form settings by id
     */
    public static function get_form_settings($form_id = 0)
    {
        if (!$form_id) {
            return false;
        }

        $form_builder_json = get_post_meta($form_id, 'formBuilderData', true);
        $form_settings = get_post_meta($form_id, 'fe_form_settings', true);

        if(!$form_builder_json){
            return false;
        }

        if(!$form_settings){
            return false;
        }

        return [
            'form_builder_json' => $form_builder_json,
            'form_settings' => $form_settings
        ];
    }

    /**
     * sanitizing inputs saving from admin 
     *
     * @param string $input
     * @return string
     */
    public static function sanitize_input($input)
    {
        // Remove `<script>` and any script content
        $sanitized = preg_replace('/<\s*script[\s\S]*?>[\s\S]*?<\s*\/\s*script\s*>/i', '', $input);
        $escapers = ["\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c", '"', "\f", "\&gt;", "&gt;", "&lt;"];
        $replacements = ["\\\\", "\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b", '', '\\f', "", "", ""];
        $sanitized = sanitize_text_field($input);
        $sanitized = str_replace($escapers, $replacements, $sanitized);

        return $sanitized;
    }

    /**
     * Adding scripts to custom post type
     *
     * @param [type] $hook
     * @return void
     */
    public static function add_admin_scripts($hook)
    {

        global $post;
        $asset = require FE_PLUGIN_DIR_PATH . 'build/admin.asset.php';

        wp_register_script(
            'jquery-ui',
            plugins_url('assets/vendors/jquery-ui.min.js', dirname(__FILE__)),
            $asset['dependencies'],
            $asset['version'],
            true
        );
        wp_register_script(
            'bfe-form-builder',
            plugins_url('assets/vendors/form-builder.min.js', dirname(__FILE__)),
            $asset['dependencies'],
            $asset['version'],
            true
        );
        wp_register_style('fe_post_form_CPT', FE_PLUGIN_URL . '/build/adminStyle.css', [], $asset['version']);
        wp_register_script(
            'bfe-block-script',
            plugins_url('build/admin.js', dirname(__FILE__)),
            $asset['dependencies'],
            $asset['version'],
            true
        );
    }

    /**
     * Registering post type
     *
     * @return void
     */
    public static function register_post_types()
    {
        register_post_type('fe_post_form', [
            'label'  => null,
            'labels' => [
                'name'               => __('Post Form', 'front-editor'),
                'singular_name'      => __('Post Form', 'front-editor'),
                'add_new'            => __('Add Post Form', 'front-editor'),
                'add_new_item'       => __('Add Post Form', 'front-editor'),
                'edit_item'          => __('Edit Post Form', 'front-editor'),
                'new_item'           => __('New Post Form', 'front-editor'),
                'view_item'          => __('Watch Post Form', 'front-editor'),
                'search_items'       => __('Search Post Form', 'front-editor'),
                'not_found'          => __('Not Found', 'front-editor'),
                'not_found_in_trash' => __('Not found in trash', 'front-editor'),
                'parent_item_colon'  => '',
                'menu_name'          => __('All forms', 'front-editor'),
            ],
            'description'         => '',
            'public'              => false,
            'show_ui'            => true,
            'show_in_menu'       => '',
            'show_in_rest'        => true,
            'rest_base'           => 'fe_post_form',
            'menu_position'       => 10,
            'exclude_from_search' => true,
            'menu_icon'           => 'dashicons-format-quote',
            'capability_type'   => 'post',
            'capabilities'      => array(
                'edit_post'          => 'update_core',
                'read_post'          => 'update_core',
                'delete_post'        => 'update_core',
                'edit_posts'         => 'update_core',
                'edit_others_posts'  => 'update_core',
                'delete_posts'       => 'update_core',
                'publish_posts'      => 'update_core',
                'read_private_posts' => 'update_core'
            ),
            'map_meta_cap'      => null,
            'hierarchical'        => false,
            'supports'            => ['title', 'custom-fields'],
            'has_archive'         => false,
            'rewrite'             => true,
            'query_var'           => true,
        ]);
    }

    /**
     * Get Form field settings
     *
     * @param [type] $name
     * @param [type] $form_id
     */
    public static function get_form_field_settings($name, $form_id = 0, $form_settings = [])
    {
        if (empty($form_settings)) {
            $form_settings = get_post_meta($form_id, 'formBuilderData', true);
            $form_settings = json_decode($form_settings, true);
        } elseif (isset($_POST['formBuilderData'])) {
            $form_settings = json_decode(stripslashes($_POST['formBuilderData']), true);
        }

        if (empty($form_settings)) {

            fe_fs_add_sentry_error('Form settings is empty', __FUNCTION__, ['func_args' => func_get_args()]);

            return false;
        }

        // if (!empty($form_settings) && !$form_id) {
        //     $form_settings = json_decode(stripslashes($_POST['formBuilderData']), true);
        // }

        if (!is_array($form_settings)) {
            fe_fs_add_sentry_error('Form settings is empty (is not array)', __FUNCTION__, ['func_args' => func_get_args()]);

            return false;
        }

        foreach ($form_settings as $field) {
            if ($field['type'] === $name) {
                return $field;
            }
        }

        return false;
    }

    /**
     * Get Form field settings
     *
     * @param string $type
     * @param number $form_id
     * @return mixed
     */
    public static function get_form_field_settings_all($type, $form_id = 0, $form_settings = [])
    {
        if (empty($form_settings)) {
            $form_settings = json_decode(get_post_meta($form_id, 'formBuilderData', true), true);
        } elseif (isset($_POST['formBuilderData'])) {
            $form_settings = json_decode(stripslashes($_POST['formBuilderData']), true);
        }

        if (empty($form_settings)) {

            fe_fs_add_sentry_error('Form settings is empty', __FUNCTION__, ['func_args' => func_get_args()]);

            return false;
        }

        if (!is_array($form_settings)) {
            fe_fs_add_sentry_error('Form settings is empty (is not array)', __FUNCTION__, ['func_args' => func_get_args()]);

            return false;
        }

        $fields_array = [];
        foreach ($form_settings as $field) {
            if ($field['type'] === $type) {
                $fields_array[] = $field;
            }
        }

        return $fields_array;
    }

    public static function get_form_field_settings_by_type($type, $form_id = 0, $form_builder_data = [])
    {
        if (empty($form_builder_data)) {
            $form_builder_data = json_decode(get_post_meta($form_id, 'formBuilderData', true), true);
        }

        if (empty($form_builder_data)) {

            fe_fs_add_sentry_error('Form settings is empty', __FUNCTION__, ['func_args' => func_get_args()]);

            return false;
        }

        if (!empty($form_builder_data) && !$form_id) {
            $form_builder_data = json_decode(stripslashes($_POST['formBuilderData']), true);
        }

        if(!empty($form_builder_data)){
            $form_builder_data = json_decode(stripslashes($form_builder_data), true);
            if (!is_array($form_builder_data)) {
                fe_fs_add_sentry_error('Form settings is empty (is not array)', __FUNCTION__, ['func_args' => func_get_args()]);
    
                return false;
            }
        }

        foreach ($form_builder_data as $field) {
            if (isset($field['type'])) {
                if ($field['type'] === $type) {
                    return $field;
                }
            }
        }

        return false;
    }

    public static function get_form_field_settings_by_name($name, $form_id = 0, $form_builder_data = [])
    {
        if (empty($form_builder_data)) {
            $form_builder_data = json_decode(get_post_meta($form_id, 'formBuilderData', true), true);
        }

        if (empty($form_builder_data)) {

            fe_fs_add_sentry_error('Form settings is empty', __FUNCTION__, ['func_args' => func_get_args()]);

            return false;
        }

        if (!empty($form_builder_data) && !$form_id) {
            $form_builder_data = json_decode(stripslashes($_POST['formBuilderData']), true);
        }

        if(!empty($form_builder_data) && !is_array($form_builder_data)){
            $form_builder_data = json_decode(stripslashes($form_builder_data), true);
            if (!is_array($form_builder_data)) {
                fe_fs_add_sentry_error('Form settings is empty (is not array)', __FUNCTION__, ['func_args' => func_get_args()]);
    
                return false;
            }
        }

        foreach ($form_builder_data as $field) {
            if (isset($field['name'])) {
                if ($field['name'] === $name) {
                    return $field;
                }
            }
        }

        return false;
    }


    /**
     * Form Builder Demo Data
     *
     * @return void
     */
    public static function get_form_builder_demo_data()
    {
        return [
            [
                "type" => "post_title",
                "required" => true,
                "label" => __('Post Title', 'front-editor'),
                "placeholder" => __('Add Title', 'front-editor')
            ],
            [
                "type" => "post_content_editor_js",
                "required" => true,
                "label" => "Post Content (EditorJS)",
                "editor_image_plugin" => true,
                "editor_header_plugin" => true,
                "editor_embed_plugin" => true,
                "editor_list_plugin" => true,
                "editor_checklist_plugin" => true,
                "editor_quote_plugin" => true,
                "editor_marker_plugin" => true,
                "editor_code_plugin" => true,
                "editor_delimiter_plugin" => true,
                "editor_inlineCode_plugin" => true,
                "editor_linkTool_plugin" => true,
                "editor_warning_plugin" => false,
                "editor_gallery_plugin" => false,
                "editor_table_plugin" => false
            ]
        ];
    }
}

Form::init();
