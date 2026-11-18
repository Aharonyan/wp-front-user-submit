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
     * Meta keys a form field may never write to and shortcodes never show:
     * plugin bookkeeping and WordPress internals
     */
    const RESERVED_META_KEYS = [
        'BFE_the_post_edited_by',
        'fus_post_created_from_page',
        'fus_user_ip_address',
        'fe_post_updated_from_admin',
        'formBuilderData',
        'fe_form_settings',
        'add_to_cart_custom_data',
        '_fus_ai_moderation',
        '_fus_seo_description',
        '_fus_guest_upload',
        '_thumbnail_id',
        '_wp_page_template',
        '_edit_lock',
        '_edit_last',
        '_wp_old_slug',
        '_wp_trash_meta_status',
        '_wp_trash_meta_time',
        '_wp_attached_file',
        '_wp_attachment_metadata',
    ];

    /**
     * Decoded formBuilderData per form, for the current request
     *
     * @var array
     */
    private static $form_fields_cache = [];

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
        // form builder load/save go through the REST routes below
        add_action('wp_ajax_save_migration_settings', [__CLASS__, 'save_migration_settings']);

        add_action('rest_api_init', [__CLASS__, 'new_endpoints']);
    }


    public static function new_endpoints()
    {
        register_rest_route('bfe/v1', '/form', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'fe_get_formBuilder_data'],
            'permission_callback' => [__CLASS__, 'can_manage_forms']
        ]);

        register_rest_route('bfe/v1', '/add-update-form', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'save_post_front_settings'],
            'permission_callback' => [__CLASS__, 'can_manage_forms']
        ]);
    }

    /**
     * Forms are managed by users with the plugin admin capability (manage_options by default)
     *
     * @return bool
     */
    public static function can_manage_forms()
    {
        return current_user_can(fe_admin_role());
    }

    /**
     * Is this post id a form
     *
     * @param mixed $post_id
     * @return bool
     */
    public static function is_form($post_id)
    {
        return is_numeric($post_id) && (int) $post_id > 0 && get_post_type((int) $post_id) === 'fe_post_form';
    }

    /**
     * Clean form builder fields the same way for builder saves and imports
     *
     * @param mixed $fields decoded formBuilderData.
     * @return array
     */
    public static function sanitize_form_builder_data($fields)
    {
        if (!is_array($fields)) {
            return [];
        }

        foreach ($fields as $key => $data_object) {
            if (!is_array($data_object)) {
                unset($fields[$key]);
                continue;
            }
            foreach ($data_object as $name => $input) {
                if (is_string($input)) {
                    $fields[$key][$name] = self::sanitize_input($input);
                }
            }
        }

        return array_values($fields);
    }

    /**
     * Form settings from the builder or an import: an array; users without unfiltered_html
     * (for example on multisite) cannot store scripts in texts like messages and emails
     *
     * @param mixed $settings unslashed settings.
     * @return array
     */
    public static function sanitize_form_settings($settings)
    {
        if (!is_array($settings)) {
            return [];
        }

        if (current_user_can('unfiltered_html')) {
            return $settings;
        }

        return map_deep($settings, function ($value) {
            return is_string($value) ? wp_kses_post($value) : $value;
        });
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
        if ($post_ID && $post_ID !== 'new' && !self::is_form($post_ID)) {
            $post_ID = false;
        }

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


        if ($post_ID && $post_ID !== 'new') {
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

            case 'duplicate':
                if ($post_ID !== 'new' && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'bulk-post-forms')) {
                    $source_post = get_post($post_ID);

                    if ($source_post && $source_post->post_type === 'fe_post_form') {
                        $new_post_id = wp_insert_post([
                            'post_title'  => sprintf(__('%s (Copy)', 'front-editor'), $source_post->post_title),
                            'post_type'   => 'fe_post_form',
                            'post_status' => 'publish',
                        ]);

                        if ($new_post_id && !is_wp_error($new_post_id)) {
                            $form_builder_data = get_post_meta($post_ID, 'formBuilderData', true);
                            $form_settings     = get_post_meta($post_ID, 'fe_form_settings', true);

                            if ($form_builder_data) {
                                update_post_meta($new_post_id, 'formBuilderData', wp_slash($form_builder_data));
                            }

                            if ($form_settings) {
                                update_post_meta($new_post_id, 'fe_form_settings', wp_slash($form_settings));
                            }
                        }
                    }
                }

                require_once fe_template_path('admin/post-forms-list-table-view.php');
                break;

            case 'trash':
                if ($post_ID !== 'new' && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'bulk-post-forms')) {
                    wp_trash_post($post_ID);
                }

                require_once fe_template_path('admin/post-forms-list-table-view.php');
                break;

            case 'delete':
                if ($post_ID !== 'new' && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'bulk-post-forms')) {
                    wp_delete_post($post_ID, true);
                }

                require_once fe_template_path('admin/post-forms-list-table-view.php');
                break;

            case 'restore':
                if ($post_ID !== 'new' && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'bulk-post-forms')) {
                    wp_untrash_post($post_ID);
                    wp_publish_post($post_ID);
                }

                require_once fe_template_path('admin/post-forms-list-table-view.php');
                break;

            case 'bulk_trash':
                if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'bulk-post-forms')) {
                    $post_ids = isset($_GET['post']) ? array_map('intval', $_GET['post']) : [];
                    foreach ($post_ids as $pid) {
                        wp_trash_post($pid);
                    }
                }

                require_once fe_template_path('admin/post-forms-list-table-view.php');
                break;

            case 'bulk_restore':
                if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'bulk-post-forms')) {
                    $post_ids = isset($_GET['post']) ? array_map('intval', $_GET['post']) : [];
                    foreach ($post_ids as $pid) {
                        wp_untrash_post($pid);
                        wp_publish_post($pid);
                    }
                }

                require_once fe_template_path('admin/post-forms-list-table-view.php');
                break;

            case 'bulk_delete':
                if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'bulk-post-forms')) {
                    $post_ids = isset($_GET['post']) ? array_map('intval', $_GET['post']) : [];
                    foreach ($post_ids as $pid) {
                        wp_delete_post($pid, true);
                    }
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

        $title = isset($_POST['fe_title']) ? sanitize_text_field(wp_unslash($_POST['fe_title'])) : __('Sample Form', 'front-editor');
        $post_ID = 0;
        if (!empty($_POST['post_id']) && $_POST['post_id'] !== 'new') {
            $post_ID = intval(sanitize_text_field($_POST['post_id']));
            // only forms can be updated here, never other posts
            if (!self::is_form($post_ID)) {
                wp_send_json_error([
                    'message' => [
                        'title' => __('Form not found', 'front-editor'),
                        'status' => 'error'
                    ]
                ]);
            }
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

        if (!$post_ID || is_wp_error($post_ID)) {
            wp_send_json_error([
                'message' => [
                    'title' => __('Form not found', 'front-editor'),
                    'status' => 'error'
                ]
            ]);
        }

        $form_builder_data = $_POST['formBuilderData'] ?? '';
        $form_builder_data_array = json_decode(stripslashes((string) $form_builder_data), true);

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

        $form_builder_data_array = self::sanitize_form_builder_data($form_builder_data_array);

        /**
         * Escaping scripts
         */
        $form_builder_data = json_encode($form_builder_data_array, JSON_UNESCAPED_UNICODE);
        update_post_meta($post_ID, 'formBuilderData', wp_slash($form_builder_data));
        self::$form_fields_cache = [];

        /**
         * Adding all settings to meta fields
         */
        if (!empty($_POST['settings'])) {
            update_post_meta($post_ID, 'fe_form_settings', wp_slash(self::sanitize_form_settings(wp_unslash($_POST['settings']))));
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

        if (!self::can_manage_forms()) {
            wp_send_json_error([
                'post_id' => '',
                'message' => [
                    'title' => __('Sorry, you are not allowed to do that.', 'front-editor'),
                    'status' => 'error'
                ],
                'data' => []
            ], 403);
        }

        if (empty($_POST['post_id']) || !self::is_form($_POST['post_id'])) {
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
            if (!isset($migrate_value['formBuilderData'], $migrate_value['fe_form_settings']) || !is_array($migrate_value['formBuilderData']) || !is_array($migrate_value['fe_form_settings'])) {
                wp_send_json_error([
                    'post_id' => $post_ID,
                    'message' => [
                        'title' => __('This is not a valid record.', 'front-editor'),
                        'status' => 'error'
                    ],
                    'data' => []
                ]);
            }
            // same cleaning as a save in the form builder
            $form_builder_data = wp_json_encode(self::sanitize_form_builder_data($migrate_value['formBuilderData']), JSON_UNESCAPED_UNICODE);
            update_post_meta($post_ID, 'formBuilderData', wp_slash($form_builder_data));
            update_post_meta($post_ID, 'fe_form_settings', wp_slash(self::sanitize_form_settings($migrate_value['fe_form_settings'])));
            self::$form_fields_cache = [];

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
        if (!$form_id || !is_numeric($form_id) || get_post_type((int) $form_id) !== 'fe_post_form') {
            return false;
        }
        $form_id = (int) $form_id;

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
     * @param string $meta_key
     * @return bool
     */
    public static function is_reserved_meta_key($meta_key)
    {
        return in_array((string) $meta_key, self::RESERVED_META_KEYS, true);
    }

    /**
     * Fields of a form, read from the saved form only (never from the request)
     *
     * @param int $form_id
     * @return array
     */
    public static function get_form_fields($form_id)
    {
        $form_id = (int) $form_id;
        if (!$form_id || get_post_type($form_id) !== 'fe_post_form') {
            return [];
        }

        if (!isset(self::$form_fields_cache[$form_id])) {
            $fields = json_decode((string) get_post_meta($form_id, 'formBuilderData', true), true);
            self::$form_fields_cache[$form_id] = is_array($fields) ? array_values(array_filter($fields, 'is_array')) : [];
        }

        return self::$form_fields_cache[$form_id];
    }

    /**
     * Settings of the form field a submitted value belongs to.
     * Visitors send values as group[name] => value; only names of fields that the form
     * really has (with one of the given types) may be saved, so a visitor cannot write
     * any other post meta.
     *
     * @param int          $form_id
     * @param string       $name  submitted field name (the meta key).
     * @param string|array $types allowed field types.
     * @return array|false
     */
    public static function get_submitted_field($form_id, $name, $types)
    {
        if (!is_string($name) || $name === '' || self::is_reserved_meta_key($name)) {
            return false;
        }

        foreach (self::get_form_fields($form_id) as $field) {
            if (isset($field['name'], $field['type']) && $field['name'] === $name && in_array($field['type'], (array) $types, true)) {
                return $field;
            }
        }

        return false;
    }

    /**
     * Save the values a visitor sent for one field group ($_POST['textarea'], $_POST['text_fields'] ...).
     * Only names of form fields with the given types are saved, each value through $sanitize.
     *
     * @param int          $post_id
     * @param int          $form_id
     * @param mixed        $values   slashed request values keyed by field name.
     * @param string|array $types    field types of this group.
     * @param callable     $sanitize function ($unslashed_value, $field) returning the value to store, or null to skip.
     * @return void
     */
    public static function save_submitted_meta($post_id, $form_id, $values, $types, callable $sanitize)
    {
        if (!is_array($values) || !$post_id) {
            return;
        }

        foreach ($values as $name => $value) {
            $field = self::get_submitted_field($form_id, (string) $name, $types);
            if (!$field) {
                continue;
            }

            $clean = $sanitize(wp_unslash($value), $field);
            if ($clean === null) {
                continue;
            }

            update_post_meta($post_id, (string) $name, wp_slash($clean));
        }
    }

    /**
     * Form id of the current submission (checked by the REST permission callback)
     *
     * @param int $form_id id passed by the hook, if any.
     * @return int
     */
    public static function submitted_form_id($form_id = 0)
    {
        if ($form_id) {
            return (int) $form_id;
        }

        return isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
    }

    /**
     * One-line text value
     *
     * @param mixed $value
     * @return string|null
     */
    public static function sanitize_text_value($value)
    {
        return is_scalar($value) ? sanitize_text_field((string) $value) : null;
    }

    /**
     * Value of a select/checkbox/radio group: ['ids' => string|array, ...]; sends an error when a required one is empty
     *
     * @param mixed $value
     * @param array $field
     * @return string|array|null
     */
    public static function sanitize_choice_value($value, array $field)
    {
        $ids = is_array($value) && isset($value['ids']) ? $value['ids'] : null;
        $is_empty = $ids === null || $ids === '' || $ids === 'null' || $ids === [];

        if ($is_empty && !empty($field['required'])) {
            /* Translators: field label */
            wp_send_json_error(['field' => $field['name'], 'message' => sprintf(__('%s is required', 'front-editor'), esc_html($field['label'] ?? $field['name']))]);
        }

        if (is_array($ids)) {
            return array_values(array_map('sanitize_text_field', array_filter($ids, 'is_scalar')));
        }

        return is_scalar($ids) ? sanitize_text_field((string) $ids) : null;
    }

    /**
     * Fields of a form with one of the given types
     *
     * @param int          $form_id
     * @param string|array $types
     * @return array
     */
    public static function get_fields_of_type($form_id, $types)
    {
        return array_values(array_filter(self::get_form_fields($form_id), function ($field) use ($types) {
            return isset($field['type']) && in_array($field['type'], (array) $types, true);
        }));
    }

    /**
     * sanitizing inputs saving from admin 
     *
     * @param string $input
     * @return string
     */
    public static function sanitize_input($input)
    {
        // Remove `<script>` and any script content; JSON escaping is done by wp_json_encode()
        $sanitized = preg_replace('/<\s*script[\s\S]*?>[\s\S]*?<\s*\/\s*script\s*>/i', '', $input);
        $sanitized = sanitize_text_field($sanitized);

        return str_replace(['&gt;', '&lt;'], '', $sanitized);
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
