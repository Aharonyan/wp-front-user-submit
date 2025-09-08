<?php

namespace BFE;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 *  Tool for MoySklad
 */
class MenuSettings
{
	public static $only_if_pro_text;
	public static $default_select;
	public static $is_pro_version;

	/**
	 * The Init
	 */
	public static function init()
	{
		self::$only_if_pro_text = __('Buy pro version to use this functionality', 'front-editor');
		self::$is_pro_version = get_option('bfe_is_front_editor_pro_version_exist');

		self::$default_select = [
			'display' => __('Display', 'front-editor'),
			'require' => __('Display and require', 'front-editor'),
			'disable' => __('Disable this field', 'front-editor')
		];

		add_action(
			'admin_menu',
			function () {
				if (current_user_can('manage_options')) {
					$capability = 'manage_options';

					$menu = add_menu_page(
						$page_title = __('Front User Submit | Front Editor', 'front-editor'),
						$menu_title = __('Front User Submit', 'front-editor'),
						$capability,
						$menu_slug = 'front_editor_settings',
						$function = ['\BFE\Form', 'fe_post_forms_page'],
						$icon = FUS__PLUGIN_URL . 'assets/img/main-admin-image.png',
						'57.5'
					);

					$post_forms_submenu = add_submenu_page(
						'front_editor_settings',
						__('Post Forms', 'front-editor'),
						__('Post Forms', 'front-editor'),
						$capability,
						'fe-post-forms',
						['\BFE\Form', 'fe_post_forms_page']
					);
					remove_submenu_page('front_editor_settings', 'front_editor_settings');

					$settings_submenu = add_submenu_page(
						'front_editor_settings',
						__('Settings', 'front-editor'),
						__('Settings', 'front-editor'),
						$capability,
						'fe-global-settings',
						[__CLASS__, 'display_page']
					);
				}
			}
		);

		add_action('admin_init', [__CLASS__, 'settings_general'], $priority = 10, $accepted_args = 1);

		add_action('admin_init', [__CLASS__, 'login_registration_settings'], $priority = 10, $accepted_args = 1);

		add_action('admin_init', [__CLASS__, 'user_admin_settings'], $priority = 10, $accepted_args = 1);

		/**
		 * short information and instruction
		 */
		add_action('bfe_front_editor_settings_before_form', [__CLASS__, 'short_information_and_instruction']);

		/**
		 * Adding notice
		 */
		add_action('admin_notices', [__CLASS__, 'options_instructions_example']);

		/**
		 * Admin script
		 */
		add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_custom_option_page_scripts']);
	}


	/**
	 * Add admin message to edit page
	 *
	 * @return void
	 */
	public static function options_instructions_example()
	{
		global $my_admin_page;
		$screen = get_current_screen();

		if (is_admin()) {
			/**
			 * If post edited with Front Editor
			 */
			if (get_post_meta(get_the_ID(), 'bfe_editor_js_data', true)) {
				add_action('edit_form_after_title', [__CLASS__, 'add_content_after_editor']);
			}
		}
	}


	/**
	 * Print admin message
	 *
	 * @return void
	 */
	public static function add_content_after_editor()
	{
		global $post;
		$id = $post->ID;
		$class = 'notice notice-error';
		$message = sprintf(
			__('This post created with the Front Editor plugin. Please edit it using Front Editor to not have issues with the plugin!', 'front-editor')
		);

		printf('<div class="%s"><p>%s</p><a href="%s">%s</a></div>', esc_attr($class), $message, Editor::get_post_edit_link($id), __('Edit in front editor', 'front-editor'));
	}

	/**
	 * adding general settings
	 *x
	 * @return void
	 */
	public static function settings_general()
	{
		add_settings_section('bfe_front_editor_general_settings_section', __('Global settings', 'front-editor'), null, 'front_editor_settings');

		$settings = [
			'bfe_front_editor_edit_button_position' => [
				'title' => __('Edit Post button position in post', 'front-editor'),
				'callback' => 'edit_button_position'
			],
			'bfe_front_editor_edit_button_text' => [
				'title' => __('- Edit Button Text', 'front-editor'),
				'callback' => 'edit_button_text'
			],
			'bfe_front_editor_wp_admin_menu' => [
				'title' => __('WordPress admin bar', 'front-editor'),
				'callback' => 'wp_admin_menu'
			],
			'bfe_front_editor_google_map_api' => [
				'title' => __('Google Map API', 'front-editor'),
				'callback' => 'google_map_api'
			],
			// 'bfe_front_editor_yandex_map_api' => [
			// 	'title' => __('Yandex Map API', 'front-editor'),
			// 	'callback' => 'yandex_map_api',
			// 	'section' => 'bfe_front_editor_external_api_section'
			// ]
		];

		foreach ($settings as $option_name => $setting) {
			register_setting('front_editor_settings', $option_name);
			add_settings_field(
				$option_name,
				$setting['title'],
				[__CLASS__, $setting['callback']],
				'front_editor_settings',
				'bfe_front_editor_general_settings_section',
				['id' => $option_name, 'label_for' => $option_name]
			);
		}
	}

	/**
	 * Display UI
	 */
	public static function display_page()
	{
		$active_tab = $_GET['tab'] ?? 'global_settings';
		$page = $_GET['page'];

		$tabs = [
			'global_settings' => [
				'title' => __('Main Settings', 'front-editor'),
				'heading' => __('Settings Page', 'front-editor'),
				'fields_group' => 'front_editor_settings',
				'sections' => 'front_editor_settings',
				'shortcode' => ''
			],
			'registration_shortcode_settings' => [
				'title' => __('Login & Registration Settings', 'front-editor'),
				'heading' => __('Login & Registration Forms Shortcode Settings', 'front-editor'),
				'fields_group' => 'bfe_general_settings_register_group',
				'sections' => 'login_registration_shortcode_settings',
				'shortcode' => sprintf('Login form:<code>%s</code> & Registration Form:<code>%s</code>', '[fus_form_login]', '[fus_form_register]')
			],
			'user_admin_settings' => [
				'title' => __('User Admin', 'front-editor'),
				'heading' => __('User Admin Shortcode Settings', 'front-editor'),
				'fields_group' => 'bfe_general_user_admin_settings_group',
				'sections' => 'bfe_general_user_admin_settings',
				'shortcode' => sprintf('User admin Shortcode:<code>%s</code>', '[fe_fs_user_admin]')
			]
		];

		printf(
			'<div class="wrap">
				<div class="nav-tab-wrapper">%s</div>
				<form method="POST" action="options.php">',
			implode('', array_merge(
				array_map(fn($slug, $tab) => sprintf(
					'<a href="?page=%s&tab=%s" class="nav-tab %s">%s</a>',
					$page,
					$slug,
					($active_tab == $slug ? 'nav-tab-active' : ''),
					$tab['title']
				), array_keys($tabs), $tabs),
				['<a data-canny-link href="https://github.com/Aharonyan/wp-front-user-submit/issues" class="nav-tab" target="_blank">💡 Feedback/Issue/Idea</a>']
			))
		);

		if (isset($tabs[$active_tab])) {
			printf('<h1>%s</h1>%s', $tabs[$active_tab]['heading'], $tabs[$active_tab]['shortcode']);
			settings_fields($tabs[$active_tab]['fields_group']);
			do_settings_sections($tabs[$active_tab]['sections']);
		}

		submit_button();
		echo '</form></div>';
	}

	public static function login_registration_settings()
	{
		$page = 'login_registration_shortcode_settings';
		$option_group_reg = 'bfe_general_settings_register_group';
		$option_name_reg = 'bfe_general_settings_login_register_group_options';
		$option_group_login_section = 'bfe_general_settings_login_section';
		$option_group_reg_section = 'bfe_general_settings_register_section';

		// Add settings sections
		add_settings_section($option_group_login_section, __('Login Form Settings', 'front-editor'), null, $page);
		add_settings_section($option_group_reg_section, __('Registration Form Settings', 'front-editor'), null, $page);

		// Register settings groups
		register_setting($option_group_reg, $option_name_reg);

		// Get option values
		$option_value = get_option($option_name_reg);

		$field_name = 'form_design';
		add_settings_field(
			$field_name,
			__('Form Design', 'front-editor'),
			[__CLASS__, 'form_design_field'],
			$page,
			$option_group_login_section,
			['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]
		);

		// Login fields
		$option_value = get_option($option_name_reg);

		$field_name = 'login_username';
		add_settings_field($field_name, __('Username', 'front-editor'), [__CLASS__, 'login_username_field'], $page, $option_group_login_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);

		$field_name = 'login_pw';
		add_settings_field($field_name, __('Password', 'front-editor'), [__CLASS__, 'login_pw_field'], $page, $option_group_login_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);

		$field_name = 'login_redirect';
		add_settings_field($field_name, __('Redirect', 'front-editor'), [__CLASS__, 'login_redirect_field'], $page, $option_group_login_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);

		$field_name = 'login_button_name';
		add_settings_field($field_name, __('Button Text', 'front-editor'), [__CLASS__, 'login_button_name_field'], $page, $option_group_login_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);

		$field_name = 'login_remember_me';
		add_settings_field($field_name, __('Remember me', 'front-editor'), [__CLASS__, 'login_remember_me_field'], $page, $option_group_login_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);


		// Registration fields
		$option_value = get_option($option_name_reg);

		$field_name = 'registration_first_last_name';
		add_settings_field($field_name, __('Show User Name Inputs', 'front-editor'), [__CLASS__, 'registration_first_last_name_field'], $page, $option_group_reg_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);

		$field_name = 'registration_website_name';
		add_settings_field($field_name, __('WebSite', 'front-editor'), [__CLASS__, 'registration_website_field'], $page, $option_group_reg_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);

		$field_name = 'registration_username';
		add_settings_field($field_name, __('Username', 'front-editor'), [__CLASS__, 'registration_username_field'], $page, $option_group_reg_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);

		$field_name = 'registration_email';
		add_settings_field($field_name, __('Email', 'front-editor'), [__CLASS__, 'registration_email_field'], $page, $option_group_reg_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);

		$field_name = 'registration_redirect';
		add_settings_field($field_name, __('Redirect', 'front-editor'), [__CLASS__, 'registration_redirect_field'], $page, $option_group_reg_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);

		$field_name = 'registration_button_name';
		add_settings_field($field_name, __('Button Text', 'front-editor'), [__CLASS__, 'registration_button_name_field'], $page, $option_group_reg_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);

		$field_name = 'registration_email_content_field';
		add_settings_field($field_name, __('User Notification Email', 'front-editor'), [__CLASS__, 'registration_email_content_field'], $page, $option_group_reg_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);

		$field_name = 'code_editor_css';
		add_settings_field($field_name, __('Custom CSS', 'front-editor'), [__CLASS__, 'code_editor_css'], $page, $option_group_reg_section, ['field_name' => $field_name, 'name' => $option_name_reg, 'option' => $option_value]);
	}

	/**
	 * Form Design Selector Field (Updated to use template)
	 */
	public static function form_design_field($args)
	{
		$field_name = $args['field_name'];
		$current_design = $args['option'][$field_name] ?? 'modern-minimal';

		// Check if user has pro version
		$has_pro = function_exists('fe_fs') && fe_fs()->can_use_premium_code__premium_only();

		// Available designs
		$designs = [
			'modern-minimal' => [
				'name' => __('Modern Minimal', 'front-editor'),
				'pro' => false,
				'preview' => FE_PLUGIN_URL . '/assets/img/modern-minimal-preview.png'
			],
			'card-panel' => [
				'name' => __('Card/Panel Style', 'front-editor'),
				'pro' => true,
				'preview' => FE_PLUGIN_URL . '/assets/img/card-panel-preview.png'
			],
			'split-screen' => [
				'name' => __('Split Screen', 'front-editor'),
				'pro' => true,
				'preview' => FE_PLUGIN_URL . '/assets/img/split-screen-preview.png'
			],
			'gradient-bg' => [
				'name' => __('Gradient Background', 'front-editor'),
				'pro' => true,
				'preview' => FE_PLUGIN_URL . '/assets/img/gradient-bg-preview.png'
			],
			'glassmorphism' => [
				'name' => __('Glassmorphism', 'front-editor'),
				'pro' => true,
				'preview' => FE_PLUGIN_URL . '/assets/img/glassmorphism-preview.png'
			],
			'borderless-flat' => [
				'name' => __('Borderless/Flat', 'front-editor'),
				'pro' => true,
				'preview' => FE_PLUGIN_URL . '/assets/img/borderless-flat-preview.png'
			],
			'corporate' => [
				'name' => __('Corporate/Professional', 'front-editor'),
				'pro' => true,
				'preview' => FE_PLUGIN_URL . '/assets/img/corporate-preview.png'
			],
			'playful-colorful' => [
				'name' => __('Playful/Colorful', 'front-editor'),
				'pro' => true,
				'preview' => FE_PLUGIN_URL . '/assets/img/playful-colorful-preview.png'
			]
		];

		// Prepare template variables
		$template_vars = [
			'designs' => $designs,
			'current_design' => $current_design,
			'has_pro' => $has_pro,
			'field_name' => $field_name,
			'field_args' => $args
		];

		// Load template
		self::load_admin_template('design-selector', $template_vars);
	}

	/**
	 * Form Design Selector Field (Updated to use template)
	 */
	public static function user_admin_design_field($args)
	{
		$field_name = $args['field_name'];
		$current_design = $args['option'][$field_name] ?? 'modern-minimal';

		// Check if user has pro version
		$has_pro = function_exists('fe_fs') && fe_fs()->can_use_premium_code__premium_only();

		// Available designs
		$designs = [
			'modern-minimal' => [
				'name' => __('Modern Minimal', 'front-editor'),
				'pro' => false,
				'preview' => FE_PLUGIN_URL . '/assets/img/admin-modern-minimal-preview.png'
			],
			'no_style' => [
				'name' => __('No Style', 'front-editor'),
				'pro' => false,
				'preview' => FE_PLUGIN_URL . '/assets/img/admin-no-style-preview.png'
			],
			'card-panel' => [
				'name' => __('Card/Panel Style', 'front-editor'),
				'pro' => true,
				'preview' => FE_PLUGIN_URL . '/assets/img/admin-card-panel-preview.png'
			],
			'corporate-professional' => [
				'name' => __('Corporate/Professional', 'front-editor'),
				'pro' => true,
				'preview' => FE_PLUGIN_URL . '/assets/img/admin-corporate-preview.png'
			],
			'borderless-flat' => [
				'name' => __('Borderless/Flat', 'front-editor'),
				'pro' => true,
				'preview' => FE_PLUGIN_URL . '/assets/img/admin-borderless-flat-preview.png'
			]
		];

		// Prepare template variables
		$template_vars = [
			'designs' => $designs,
			'current_design' => $current_design,
			'has_pro' => $has_pro,
			'field_name' => $field_name,
			'field_args' => $args
		];

		// Load template
		self::load_admin_template('design-selector', $template_vars);
	}

	/**
	 * Helper method to load admin templates
	 */
	public static function load_admin_template($template_name, $vars = [])
	{
		$template_path = FE_PLUGIN_DIR_PATH . 'templates/admin/' . $template_name . '.php';

		if (file_exists($template_path)) {
			// Extract variables to template scope
			extract($vars);

			// Include template
			include $template_path;
		} else {
			echo '<p>' . sprintf(__('Template not found: %s', 'front-editor'), $template_name) . '</p>';
		}
	}

	/**
	 * Get current form design
	 */
	public static function get_current_form_design()
	{
		$options = get_option('bfe_general_settings_login_register_group_options', []);
		return $options['form_design'] ?? 'modern-minimal';
	}

	public static function user_admin_settings()
	{
		$page = 'bfe_general_user_admin_settings';
		$option_group_user = 'bfe_general_user_admin_settings_group';
		$option_name_user = 'bfe_general_user_admin_settings_group_options';
		$option_group_user_section = 'bfe_general_user_admin_settings_section';

		// Add settings sections
		add_settings_section($option_group_user_section, __('User Admin Settings', 'front-editor'), null, $page);

		// Register settings groups
		register_setting($option_group_user, $option_name_user);

		// Login fields
		$option_value = get_option($option_name_user);

		// Get option values

		$field_name = 'fe_admin_design';
		add_settings_field(
			$field_name,
			__('Select Template', 'front-editor'),
			[__CLASS__, 'user_admin_design_field'],
			$page,
			$option_group_user_section,
			['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]
		);

		// POST TYPE SELECTOR FIELD
		$field_name = 'allowed_post_types';
		add_settings_field(
			$field_name,
			__('Allowed Post Types', 'front-editor'),
			[__CLASS__, 'user_admin_post_types_field'],
			$page,
			$option_group_user_section,
			['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]
		);


		$field_name = 'publish_btn';
		add_settings_field($field_name, __('Publish button', 'front-editor'), [__CLASS__, 'user_publish_btn_field'], $page, $option_group_user_section, ['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]);

		$field_name = 'pending_btn';
		add_settings_field($field_name, __('Pending button', 'front-editor'), [__CLASS__, 'user_pending_btn_field'], $page, $option_group_user_section, ['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]);

		$field_name = 'draft_btn';
		add_settings_field($field_name, __('Draft button', 'front-editor'), [__CLASS__, 'user_draft_btn_field'], $page, $option_group_user_section, ['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]);

		$field_name = 'logout_btn';
		add_settings_field($field_name, __('Logout button', 'front-editor'), [__CLASS__, 'user_logout_btn_field'], $page, $option_group_user_section, ['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]);

		$field_name = 'show_all_user_post_link';
		add_settings_field($field_name, __('Show all users posts link', 'front-editor'), [__CLASS__, 'user_show_all_user_post_link_field'], $page, $option_group_user_section, ['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]);

		$field_name = 'remove_post_icon';
		add_settings_field($field_name, __('Remove post icon', 'front-editor'), [__CLASS__, 'user_remove_post_icon'], $page, $option_group_user_section, ['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]);

		$field_name = 'pagination';
		add_settings_field($field_name, __('Pagination', 'front-editor'), [__CLASS__, 'user_post_page_field'], $page, $option_group_user_section, ['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]);

		// $field_name = 'post_count';
		// add_settings_field($field_name, __('Post count', 'front-editor'), [__CLASS__, 'user_post_count_field'], $page, $option_group_user_section, ['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]);

		$field_name = 'no_post_found_text';
		add_settings_field($field_name, __('No post found text', 'front-editor'), [__CLASS__, 'no_post_found_text'], $page, $option_group_user_section, ['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]);

		$field_name = 'without_login_front_user_admin';
		add_settings_field($field_name, __('Front user admin settings without login ', 'front-editor'), [__CLASS__, 'front_user_admin_settings_without_login'], $page, $option_group_user_section, ['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]);

		$field_name = 'code_editor_css';
		add_settings_field($field_name, __('Custom CSS', 'front-editor'), [__CLASS__, 'code_editor_css'], $page, $option_group_user_section, ['field_name' => $field_name, 'name' => $option_name_user, 'option' => $option_value]);
	}

	/**
	 * Render post types multi-select field
	 *
	 * @param array $args Field arguments
	 * @return void
	 */
	public static function user_admin_post_types_field($args)
	{
		$field_name = $args['field_name'];
		$option_name = $args['name'];
		$option_value = $args['option'];

		// Get current selected post types (array)
		$selected_post_types = isset($option_value[$field_name]) ? (array) $option_value[$field_name] : ['post'];

		// Get all public post types
		$post_types = get_post_types(['public' => true], 'objects');

		// Remove attachment from the list as it's not typically needed for user admin
		unset($post_types['attachment']);

		$field_id = $option_name . '[' . $field_name . ']';

		echo '<select name="' . $field_id . '[]" id="' . $field_name . '" multiple="multiple" style="width: 300px; height: 120px;">';

		foreach ($post_types as $post_type_key => $post_type_obj) {
			$selected = in_array($post_type_key, $selected_post_types) ? 'selected="selected"' : '';
			echo '<option value="' . esc_attr($post_type_key) . '" ' . $selected . '>';
			echo esc_html($post_type_obj->labels->name . ' (' . $post_type_key . ')');
			echo '</option>';
		}

		echo '</select>';

		echo '<p class="description">';
		echo __('Hold Ctrl (Cmd on Mac) to select multiple post types. Users will be able to manage these post types in the frontend admin panel.', 'front-editor');
		echo '</p>';
	}

	/**
	 * Helper function to get allowed post types for user admin
	 * Use this function in your UserAdmin.php to filter post types
	 *
	 * @return array Array of allowed post type slugs
	 */
	public static function get_allowed_post_types_for_user_admin()
	{
		$options = get_option('bfe_general_user_admin_settings_group_options', []);
		$allowed_post_types = isset($options['allowed_post_types']) ? (array) $options['allowed_post_types'] : ['post'];

		// Ensure at least 'post' is always included as fallback
		if (empty($allowed_post_types)) {
			$allowed_post_types = ['post'];
		}

		return $allowed_post_types;
	}

	public static function my_sanitize_settings($input = NULL)
	{
		// Detect multiple sanitizing passes.
		static $pass_count = 0;
		$pass_count++;

		if ($pass_count <= 1) {
			// Handle any single-time / performane sensitive actions.

		}

		// Insert regular santizing code here.
	}

	/**
	 * What we have
	 *
	 * @return void
	 */
	public static function short_information_and_instruction()
	{
		printf('<h2>%s</h2>', __('Short information', 'front-editor'));

		$class = 'notice notice-warning is-dismissible';
		$message = sprintf(
			/* translators: If you have some ideas or questions please contact me. */
			__(
				'If you have some ideas or questions please contact us. 
				The contact information you can find on our website %s',
				'front-editor'
			),
			'<strong><a href="https://wpfronteditor.com" target="_blank">wpfronteditor.com</a></strong>'
		);
		printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);

		$github_link = '<a  target="_blank" href="https://t.me/+loTEjPRS6lw3NTli">Community</a>';
		$site_link = 'https://wpfronteditor.com/';
		printf(
			'<p>%s</p>',
			/* translators: You can buy pro version or find additional information. */
			sprintf(__('You can buy pro version or find additional information <a href="%1$s" target="_blank">here</a> or write some ideas or report issues in %2$s', 'front-editor'), $site_link, $github_link)
		);
	}

	public static function login_username_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Username',
				'slug' => 'fus_username'
			]
		];
		foreach ($subFields as $field) {
			printf('<label>%s</label>', __('Placeholder', 'front-editor'));
			$first_placeholder = isset($field_option['placeholder']) ? $field_option['placeholder'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][placeholder]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_placeholder);
			printf('<label>%s</label>', __('Label', 'front-editor'));
			$first_label = isset($field_option['label']) ? $field_option['label'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][label]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_label);
		}
	}

	public static function login_pw_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Password',
				'slug' => 'fus_password'
			]
		];
		foreach ($subFields as $field) {
			printf('<label>%s</label>', __('Placeholder', 'front-editor'));
			$first_placeholder = isset($field_option['placeholder']) ? $field_option['placeholder'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][placeholder]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_placeholder);
			printf('<label>%s</label>', __('Label', 'front-editor'));
			$first_label = isset($field_option['label']) ? $field_option['label'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][label]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_label);
		}
	}

	public static function login_button_name_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];

		$first_placeholder = !empty($field_option) ? $field_option : 'Login';
		printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_placeholder);
	}

	public static function login_redirect_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Redirect',
				'slug' => 'redirect'
			]
		];
		foreach ($subFields as $field) {
			printf('<label>%s</label>', __('Link', 'front-editor'));
			$redirect = isset($field_option['link']) ? $field_option['link'] : '';
			$description = 'Add redirection link after successful login';
			$placeholder = home_url('user_admin');
			printf('<p><input style="width: 300px;" type="url" id="%s" name="%s[%s][link]" value="%s" placeholder="%s"><div>%s</div></p>', $field_name, $args['name'], $field_name, $redirect, $placeholder, $description);
		}
	}

	public static function login_remember_me_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Remember Me',
				'slug' => 'remember_me'
			]
		];
		foreach ($subFields as $field) {
			printf('<label>Show</label>');
			$check = isset($field_option['checked']) ? 'checked' : '';
			printf('<p><input type="checkbox" id="%s" name="%s[%s][checked]" %s/></p>', $field_name, $args['name'], $field_name, $check);
			printf('<label>%s</label>', __('Label', 'front-editor'));
			$first_label = isset($field_option['label']) ? $field_option['label'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][label]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_label);
		}
	}

	public static function user_publish_btn_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Publish',
				'slug' => 'publish'
			]
		];
		foreach ($subFields as $field) {
			$check = isset($field_option['checked']) ? 'checked' : '';
			printf('<p><label><input type="checkbox" id="%s" name="%s[%s][checked]" %s/> %s</label></p>', $field_name, $args['name'], $field_name, $check, __('Hide', 'front-editor'));
			printf('<label>%s</label>', __('Label', 'front-editor'));
			$first_label = isset($field_option['label']) ? $field_option['label'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][label]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_label);
		}
	}

	public static function user_pending_btn_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Pending',
				'slug' => 'pending'
			]
		];
		foreach ($subFields as $field) {
			$check = isset($field_option['checked']) ? 'checked' : '';
			printf('<p><label><input type="checkbox" id="%s" name="%s[%s][checked]" %s/> %s</label></p>', $field_name, $args['name'], $field_name, $check, __('Hide', 'front-editor'));
			printf('<label>%s</label>', __('Label', 'front-editor'));
			$first_label = isset($field_option['label']) ? $field_option['label'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][label]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_label);
		}
	}

	public static function user_draft_btn_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Draft',
				'slug' => 'draft'
			]
		];
		foreach ($subFields as $field) {
			$check = isset($field_option['checked']) ? 'checked' : '';
			printf('<p><label><input type="checkbox" id="%s" name="%s[%s][checked]" %s/> %s</label></p>', $field_name, $args['name'], $field_name, $check, __('Hide', 'front-editor'));
			printf('<label>%s</label>', __('Label', 'front-editor'));
			$first_label = isset($field_option['label']) ? $field_option['label'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][label]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_label);
		}
	}

	public static function user_logout_btn_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Logout',
				'slug' => 'logout'
			]
		];
		foreach ($subFields as $field) {
			$check = isset($field_option['checked']) ? 'checked' : '';
			printf('<p><label><input type="checkbox" id="%s" name="%s[%s][checked]" %s/> %s</label></p>', $field_name, $args['name'], $field_name, $check, __('Hide', 'front-editor'));
			printf('<label>%s</label>', __('Label', 'front-editor'));
			$first_label = isset($field_option['label']) ? $field_option['label'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][label]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_label);
		}
	}

	public static function user_show_all_user_post_link_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => __('Show all users posts', 'front-editor'),
				'slug' => 'post_link'
			]
		];
		foreach ($subFields as $field) {
			$check = isset($field_option['checked']) ? 'checked' : '';
			printf('<p><label><input type="checkbox" id="%s" name="%s[%s][checked]" %s/> %s</label></p>', $field_name, $args['name'], $field_name, $check, __('Hide', 'front-editor'));
			printf('<label>%s</label>', __('Label', 'front-editor'));
			$first_label = isset($field_option['label']) ? $field_option['label'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][label]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_label);
		}
	}

	public static function front_user_admin_settings_without_login($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];

		$check = isset($field_option['checked']) ? 'checked' : '';
		printf('<p><label><input type="checkbox" id="%s" name="%s[%s][checked]" %s/> %s</label></p>', $field_name, $args['name'], $field_name, $check, __('Activate Login Form', 'front-editor'));
		printf('<label>%s</label>', __('User Not Logged In message.', 'front-editor'));
		$text = isset($field_option['text']) ? $field_option['text'] : '';
		printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][text]" value="%s"></p>', $field_name, $args['name'], $field_name, sanitize_text_field($text));
	}

	public static function user_remove_post_icon($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Remove post icon',
				'slug' => 'remove_post_icon'
			]
		];
		foreach ($subFields as $field) {
			$check = isset($field_option['checked']) ? 'checked' : '';
			printf('<p><label><input type="checkbox" id="%s" name="%s[%s][checked]" %s/> %s</label></p>', $field_name, $args['name'], $field_name, $check, __('Hide', 'front-editor'));
		}
	}

	public static function user_post_page_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Pagination',
				'slug' => 'pagination'
			]
		];
		foreach ($subFields as $field) {
			$check = isset($field_option['checked']) ? 'checked' : '';
			printf('<p><label><input type="checkbox" id="%s" name="%s[%s][checked]" %s/> %s</label></p>', $field_name, $args['name'], $field_name, $check, __('Hide', 'front-editor'));
			printf('<label>%s</label>', __('Previous text', 'front-editor'));
			$first_previous = isset($field_option['previous']) ? $field_option['previous'] : __('Previous', 'front-editor');
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][previous]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_previous);
			printf('<label>%s</label>', __('Next text', 'front-editor'));
			$first_next = isset($field_option['next']) ? $field_option['next'] : __('Next', 'front-editor');
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][next]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_next);
		}
	}

	public static function user_post_count_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Post count',
				'slug' => 'post_count'
			]
		];
		foreach ($subFields as $field) {
			printf('<label>%s</label>', __('Post count', 'front-editor'));
			$first_post_count = isset($field_option['post_count']) ? $field_option['post_count'] : 6;
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][post_count]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_post_count);
		}
	}

	public static function no_post_found_text($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'No post found',
				'slug' => 'no_post_found'
			]
		];
		foreach ($subFields as $field) {
			printf('<label>%s</label>', __('No post found', 'front-editor'));
			$first_no_post_found = isset($field_option['no_post_found']) ? $field_option['no_post_found'] : __('No post found', 'front-editor');
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][no_post_found]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_no_post_found);
		}
	}

	public static function code_editor_css($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'] ?? [];

		printf('<label>%s</label>', __('CSS code to customize design', 'front-editor'));
		$first_code_editor_css = isset($field_option['code_editor_css']) ? $field_option['code_editor_css'] : '.class{height:50px;}';
		printf('<p><textarea  class="code-mirror-editor-css" style="width: 300px;" id="%s" name="%s[code_editor_css]">%s</textarea></p>', str_replace('_', '-', $field_name), $args['name'], $first_code_editor_css);
	}

	public static function registration_first_last_name_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'First Name',
				'slug' => 'first_name'
			],
			[
				'name' => 'Last Name',
				'slug' => 'last_name'
			]
		];
		foreach ($subFields as $field) {
			printf('<h4>%s field</h4>', $field['name']);
			printf('<label>Show</label>');
			$check = !empty($field_option[$field['slug']]['checked']) ? 'checked' : '';
			printf('<p><input type="checkbox" id="%s" name="%s[%s][%s][checked]" %s/></p>', $field_name, $args['name'], $field_name, $field['slug'], $check);
			printf('<label>Required</label>');
			$required = isset($field_option[$field['slug']]['required']) ? 'checked' : '';
			printf('<p><input type="checkbox" id="%s" name="%s[%s][%s][required]" %s/></p>', $field_name, $args['name'], $field_name, $field['slug'], $required);
			printf('<label>%s</label>', __('Placeholder', 'front-editor'));
			$first_placeholder = isset($field_option[$field['slug']]['placeholder']) ? $field_option[$field['slug']]['placeholder'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][%s][placeholder]" value="%s"></p>', $field_name, $args['name'], $field_name, $field['slug'], $first_placeholder);
			printf('<label>%s</label>', __('Label', 'front-editor'));
			$first_label = isset($field_option[$field['slug']]['label']) ? $field_option[$field['slug']]['label'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][%s][label]" value="%s"></p>', $field_name, $args['name'], $field_name, $field['slug'], $first_label);
		}
	}

	public static function registration_redirect_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Redirect',
				'slug' => 'redirect'
			]
		];
		foreach ($subFields as $field) {
			printf('<label>%s</label>', __('Link', 'front-editor'));
			$redirect = isset($field_option['link']) ? $field_option['link'] : '';
			$description = 'Add redirection link after successful registration';
			$placeholder = home_url('user_admin');
			printf('<p><input style="width: 300px;" type="url" id="%s" name="%s[%s][link]" value="%s" placeholder="%s"><div>%s</div></p>', $field_name, $args['name'], $field_name, $redirect, $placeholder, $description);
		}
	}

	public static function registration_website_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Website',
				'slug' => 'website'
			]
		];
		foreach ($subFields as $field) {
			printf('<label>Show</label>');
			$check = isset($field_option['checked']) ? 'checked' : '';
			printf('<p><input type="checkbox" id="%s" name="%s[%s][checked]" %s/></p>', $field_name, $args['name'], $field_name, $check);
			printf('<label>Required</label>');
			$required = isset($field_option['required']) ? 'checked' : '';
			printf('<p><input type="checkbox" id="%s" name="%s[%s][required]" %s/></p>', $field_name, $args['name'], $field_name, $required);
			printf('<label>%s</label>', __('Placeholder', 'front-editor'));
			$first_placeholder = isset($field_option['placeholder']) ? $field_option['placeholder'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][placeholder]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_placeholder);
			printf('<label>%s</label>', __('Label', 'front-editor'));
			$first_label = isset($field_option['label']) ? $field_option['label'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][label]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_label);
		}
	}

	public static function registration_username_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Username',
				'slug' => 'fus_username'
			]
		];
		foreach ($subFields as $field) {
			printf('<label>%s</label>', __('Placeholder', 'front-editor'));
			$first_placeholder = isset($field_option['placeholder']) ? $field_option['placeholder'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][placeholder]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_placeholder);
			printf('<label>%s</label>', __('Label', 'front-editor'));
			$first_label = isset($field_option['label']) ? $field_option['label'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][label]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_label);
		}
	}

	public static function registration_email_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];
		$subFields = [
			[
				'name' => 'Email',
				'slug' => 'fus_email'
			]
		];
		foreach ($subFields as $field) {
			printf('<label>%s</label>', __('Placeholder', 'front-editor'));
			$first_placeholder = isset($field_option['placeholder']) ? $field_option['placeholder'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][placeholder]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_placeholder);
			printf('<label>%s</label>', __('Label', 'front-editor'));
			$first_label = isset($field_option['label']) ? $field_option['label'] : $field['name'];
			printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][label]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_label);
		}
	}

	public static function registration_button_name_field($args)
	{
		$field_name = $args['field_name'];
		$field_option = $args['option'][$field_name] ?? [];

		$first_placeholder = !empty($field_option) ? $field_option : 'Register';
		printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s]" value="%s"></p>', $field_name, $args['name'], $field_name, $first_placeholder);
	}

	public static function registration_email_content_field($args)
	{
		$field_name = $args['field_name'];
		$notification_text =  sprintf('Hi [username],%sLogin: [user_login]%sPassword: [user_password]', PHP_EOL, PHP_EOL);
		$notification_subject = sprintf('[blog_name] Registration successful');
		$notification_value = isset($args['option'][$field_name]) ? $args['option'][$field_name]['subject'] : $notification_subject;
		printf('<label>%s</label>', 'Email Subject');
		printf('<p><input style="width: 300px;" type="text" id="%s" name="%s[%s][subject]" value="%s"></p>', $field_name, $args['name'], $field_name, $notification_value);
		$value = isset($args['option'][$field_name]) ? $args['option'][$field_name]['message'] : $notification_text;
		printf('<label>%s</label>', 'Message Template');
		printf('<p><textarea type="checkbox" rows="10" cols="80"  id="%s" name="%s[%s][message]"/>%s</textarea></p>', $field_name, $args['name'], $field_name, $value);
	}

	public static function edit_button_position($val)
	{

		$id = $val['id'];

		echo sprintf('<select name="%s">', $id);

		$options = [
			'after_content' => __('After Post Content', 'front-editor'),
			'before_content' => __('Before Post Content', 'front-editor'),
			'left_bottom' => __('Left bottom', 'front-editor'),
			'left_top' => __('Left top', 'front-editor'),
			'right_bottom' => __('Right bottom', 'front-editor'),
			'right_top' => __('Right top', 'front-editor'),
			'hide' => __('Hide', 'front-editor'),
		];

		foreach ($options as $val => $option) {
			echo sprintf('<option value="%s" %s >%s</option>', $val, selected($val, get_option($id), false), $option);
		}
		echo '</select>';
		echo sprintf('<p>%s</p>', __('Select Edit Post button position on created post', 'front-editor'));
	}

	public static function edit_button_text($val)
	{

		$id = $val['id'];
		$input_value = get_option($id);

		if (empty($input_value)) {
			$input_value = __('Edit Current Post', 'front-editor');
		}

		printf('<input name="%s" value="%s">', $id, $input_value);

		echo sprintf('<p>%s</p>', __('Add edit button text', 'front-editor'));
	}

	public static function google_map_api($val)
	{

		$id = $val['id'];
		$input_value = get_option($id);

		if (empty($input_value)) {
			$input_value = '';
		}

		printf('<input class="regular-text" name="%s" value="%s">', $id, $input_value);

		echo sprintf(
			'<p>%s</p>',
			sprintf(
				__('You can check %1$s how to get Google Map API Key and how to set it up.', 'front-editor'),
				'<a target="_blank" href="https://wpfronteditor.com/docs/overview-wp-user-frontend/create-form/google-map/">' . __('here', 'front-editor') . '</a>'
			)
		);
	}

	/**
	 * Yandex Map API field callback
	 */
	public static function yandex_map_api($val)
	{
		$id = $val['id'];
		$input_value = get_option($id);

		if (empty($input_value)) {
			$input_value = '';
		}

		printf('<input class="regular-text" name="%s" value="%s">', $id, $input_value);

		echo sprintf(
			'<p>%s</p>',
			sprintf(
				__('You can find information about getting a Yandex Maps API key %1$s here %2$s', 'front-editor'),
				'<a target="_blank" href="https://developer.tech.yandex.com/">',
				'</a>'
			)
		);
	}

	/**
	 * Display featured image
	 *
	 * @param [type] $val
	 * @return void
	 */
	public static function wp_admin_menu($val)
	{
		$id = $val['id'];

		echo sprintf('<select name="%s">', $id);

		$options = [
			'default' => __('Default', 'front-editor'),
			'display' => __('Display', 'front-editor'),
			'display_logged_in' => __('Display only for logged in users', 'front-editor'),
			'disable' => __('Disable for all', 'front-editor'),
			'disable_but_admin' => __('Disable for all but admin', 'front-editor'),
		];

		foreach ($options as $val => $option) {
			echo sprintf('<option value="%s" %s >%s</option>', $val, selected($val, get_option($id), false), $option);
		}
		echo '</select>';
	}

	/**
	 * category selector settings
	 *
	 * @param [type] $val
	 * @return void
	 */
	public static function display_category_selector($val)
	{
		$disabled = 0;

		$id = $val['id'];
		echo sprintf('<select name="%s" %s>', $id, disabled($disabled, true, false));
		$data = get_option($id);
		foreach (self::$default_select as $val => $option) {
			echo sprintf('<option value="%s" %s >%s</option>', $val, selected($val, get_option($id), false), $option);
		}
		echo '</select>';
	}

	/**
	 * post type selector settings
	 *
	 * @param [type] $val
	 * @return void
	 */
	public static function display_post_type_selector($val)
	{
		$disabled = 1;

		if (self::$is_pro_version) {
			$disabled = 0;
		}

		$id = $val['id'];
		echo sprintf('<select name="%s" %s>', $id, disabled($disabled, true, false));
		$data = get_option($id);
		foreach (self::$default_select as $val => $option) {
			echo sprintf('<option value="%s" %s >%s</option>', $val, selected($val, get_option($id), false), $option);
		}
		echo '</select>';

		if ($disabled) {
			echo sprintf('<p>%s</p>', self::$only_if_pro_text);
		}
	}

	public static function enqueue_custom_option_page_scripts()
	{
		// Check if we're on the custom options page
		$screen = get_current_screen();
		if ($screen->id === 'front-user-submit_page_fe-global-settings') {
			// Get asset file for admin build
			$asset_file = FE_PLUGIN_DIR_PATH . 'build/adminStyle.asset.php';
			$asset = file_exists($asset_file) ? require $asset_file : ['version' => '1.0.0', 'dependencies' => []];

			// Enqueue admin styles (includes design selector styles)
			wp_enqueue_style(
				'fe-admin-styles',
				FE_PLUGIN_URL . '/build/adminStyle.css',
				[],
				$asset['version']
			);

			wp_enqueue_script('codemirror', FUS__PLUGIN_URL . 'assets/vendors/codemirror.min.js', array('jquery'), '1.0.0', true);
			wp_enqueue_script('codemirror-css', FUS__PLUGIN_URL . 'assets/vendors/css.min.js', array('jquery'), '1.0.0', true);
			wp_enqueue_script('codemirror-init', FUS__PLUGIN_URL . 'assets/vendors/codemirror-init.js', array('codemirror-css'), '1.0.1', true);
			wp_enqueue_style('codemirror', FUS__PLUGIN_URL . 'assets/vendors/codemirror.min.css', array(), '1.0.0');
			wp_enqueue_style('dracula', FUS__PLUGIN_URL . 'assets/vendors/dracula.min.css', array(), '1.0.0');
		}
	}
}

MenuSettings::init();
