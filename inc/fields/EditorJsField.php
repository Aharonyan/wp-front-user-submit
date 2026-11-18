<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */

namespace BFE\Field;

use BFE\Editor;

use BFE\Form;

defined('ABSPATH') || exit;


class EditorJsField
{
    public static $field_label = 'EditorJS';
    public static $field_type =  'post_content_editor_js';

    public static function init()
    {
        /**
         * Adding setting to admin
         */
        add_filter('admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings']);

        add_filter('bfe_front_editor_localize_data', [__CLASS__, 'field_setting_for_frontend'], 10, 3);

        /**
         * Add or update editor content
         */
        add_action('bfe_ajax_after_front_editor_post_update_or_creation', [__CLASS__, 'add_or_update_editor_content_front'], 10, 4);

        // Add editor js blocks
        add_filter('bfe_front_editor_localize_data', [__CLASS__, 'add_editor_js_data'], 10, 3);

        // Post html content for editor js
        add_filter('fe_localize_post_html_content', [__CLASS__, 'localize_post_html_content'], 10, 3);

        add_action('bfe_editor_on_front_field_adding', [__CLASS__, 'add_field_to_front_form'], 10, 3);
    }


    /**
     * Add or update editor content
     */
    public static function add_or_update_editor_content_front($post_id, $form_id, $post_data, $all_settings)
    {
        if (empty($_POST['editor_js'])) {
            return;
        }

        if (!is_array($_POST['editor_js'])) {
            return;
        }

        foreach ($_POST['editor_js'] as $name => $content) {
            // only EditorJS fields of this form, so a visitor cannot write other post meta
            $settings = Form::get_submitted_field($form_id, (string) $name, self::$field_type);
            if (!$settings || !is_scalar($content)) {
                continue;
            }

            $editor_data  = self::sanitize_editor_data(json_decode(wp_unslash((string) $content), true));
            $content_html = '';

            if (empty($editor_data['blocks']) && !empty($settings['required'])) {
                $message = __('Please add content to EditorJS', 'front-editor');
                if (isset($settings['required_error_text']) && !empty(isset($settings['required_error_text']))) {
                    $message = $settings['required_error_text'];
                }
                wp_send_json_error(['field' => $name, 'message' => $message]);
            }

            if (!empty($editor_data['blocks'])) {
                foreach ($editor_data['blocks'] as $data) {

                    $single_html = self::data_to_html($data['type'], $data);

                    $content_html .= $single_html;
                }
            }

            $post_data = [
                'ID' => $post_id,
            ];

            if (isset($settings['save_to']) && !empty($settings['save_to'])) {
                if ($settings['save_to'] === 'post_content') {
                    $post_data['post_content'] = $content_html;
                    wp_update_post(wp_slash($post_data));
                } elseif ($settings['save_to'] === 'post_excerpt') {
                    $post_data['post_excerpt'] = sanitize_text_field($content_html);
                    wp_update_post(wp_slash($post_data));
                }
            } elseif (!empty($settings['post_content'])) {
                $post_data['post_content'] = $content_html;
                wp_update_post(wp_slash($post_data));
            }

            /**
             * Adding to meta json string.
             */
            update_post_meta($post_id, $name, wp_slash($editor_data));
        }
    }

    /**
     * Clean EditorJS data: block texts are rendered as HTML by the editor and by data_to_html()
     *
     * @param mixed $data decoded EditorJS data (array) or legacy HTML string.
     * @return mixed
     */
    public static function sanitize_editor_data($data)
    {
        if (is_string($data)) {
            return wp_kses_post($data);
        }

        if (!is_array($data)) {
            return [];
        }

        $clean = [];
        foreach ($data as $key => $value) {
            if ($key === 'blocks') {
                $clean['blocks'] = is_array($value) ? array_values(array_filter(array_map([__CLASS__, 'sanitize_block'], $value))) : [];
                continue;
            }
            $clean[$key] = self::kses_deep($value);
        }

        return $clean;
    }

    /**
     * @param mixed $block one EditorJS block.
     * @return array|null
     */
    private static function sanitize_block($block)
    {
        if (!is_array($block) || !isset($block['type']) || !is_string($block['type'])) {
            return null;
        }

        $block['type'] = preg_replace('/[^A-Za-z0-9_-]/', '', $block['type']);
        if (isset($block['id'])) {
            $block['id'] = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $block['id']);
        }

        // code block keeps its text as typed, it is shown as text (htmlentities) and never as HTML
        $plain_keys = $block['type'] === 'code' ? ['code'] : [];
        $block['data'] = self::kses_deep($block['data'] ?? [], $plain_keys);

        if (isset($block['tunes'])) {
            $block['tunes'] = self::kses_deep($block['tunes']);
        }

        return $block;
    }

    /**
     * @param mixed $value
     * @param array $plain_keys keys of this level that are kept as typed.
     * @return mixed
     */
    private static function kses_deep($value, array $plain_keys = [])
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (is_string($item) && in_array($key, $plain_keys, true)) {
                    continue;
                }
                // URLs are cleaned as URLs, kses would turn & into &amp;
                $value[$key] = (is_string($item) && in_array($key, self::URL_KEYS, true)) ? esc_url_raw($item) : self::kses_deep($item);
            }
            return $value;
        }

        return is_string($value) ? wp_kses_post($value) : $value;
    }

    /**
     * Block data keys that hold URLs
     */
    const URL_KEYS = ['url', 'embed', 'source', 'link', 'href'];

    /**
     * Hosts the EditorJS Embed block may show (the services of @editorjs/embed)
     *
     * @return string[]
     */
    public static function get_embed_hosts()
    {
        return apply_filters('bfe_editorjs_embed_hosts', [
            'www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com', 'coub.com', 'codepen.io',
            'www.instagram.com', 'twitframe.com', 'player.twitch.tv', 'miro.com', 'music.yandex.ru', 'imgur.com',
            'gfycat.com', 'assets.pinterest.com', 'www.facebook.com', 'www.aparat.com', 'vine.co',
        ]);
    }

    /**
     * Is an Embed block address allowed
     *
     * @param string $url
     * @return bool
     */
    public static function is_allowed_embed_url($url)
    {
        $host = strtolower((string) wp_parse_url((string) $url, PHP_URL_HOST));
        $scheme = strtolower((string) wp_parse_url((string) $url, PHP_URL_SCHEME));

        return $host !== '' && in_array($scheme, ['http', 'https'], true) && in_array($host, self::get_embed_hosts(), true);
    }

    /**
     * Is an Attaches block file address a file of this site's uploads
     *
     * @param string $url
     * @return bool
     */
    public static function is_upload_url($url)
    {
        $base = (string) wp_upload_dir(null, false)['baseurl'];
        $url = (string) $url;

        return $base !== '' && strpos(set_url_scheme($url, 'https'), trailingslashit(set_url_scheme($base, 'https'))) === 0 && strpos($url, '..') === false;
    }

    /**
     * Adding setting to admin
     */
    public static function add_field_settings($data)
    {
        /**
         * Adding field
         */
        $data['formBuilder_options']['fields'][] =
            [
                'label' => self::$field_label,
                'attrs' => [
                    'type' => self::$field_type
                ],
                'icon' => '<svg width="15" height="15" viewBox="0 0 84 84" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient x1="50%" y1="0%" x2="50%" y2="100%" id="editorjs-logo-a"><stop stop-color="#39FFD7" offset="0%"></stop><stop stop-color="#308EFF" offset="100%"></stop></linearGradient></defs><g fill-rule="nonzero" fill="none"><circle fill="url(#editorjs-logo-a)" cx="42" cy="42" r="42"></circle><rect fill="#FFF" x="38" y="17" width="8" height="50" rx="4"></rect><rect fill="#FFF" x="17" y="38" width="50" height="8" rx="4"></rect></g></svg>',
            ];

        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field' => sprintf('<div class="%s editor" name="%s"></div>', self::$field_type, self::$field_type),
            'onRender' => '',
            //'max_in_form' => 1,
            //'required' => 0
        ];

        /**
         * Adding attribute settings
         */
        $data['formBuilder_options']['typeUserAttrs'][self::$field_type] =
            [
                'save_to' => [
                    'label' => 'Save To',
                    'multiple' => false,
                    'options' => [
                        'post_content' => 'Post Content',
                        'post_meta' => 'Post Meta',
                        'post_excerpt' => 'Post Excerpt'
                    ],
                ],
                'editor_warning_plugin' => [
                    'label' => 'Warning',
                    'value' => false,
                    'type' => 'checkbox',
                ],
                'editor_table_plugin' => [
                    'label' => 'Table',
                    'value' => false,
                    'type' => 'checkbox',
                ],
                'editor_carousel_plugin' => [
                    'label' => 'Images Carousel (Gallery)',
                    'value' => false,
                    'type' => 'checkbox',
                ],
                'editor_gallery_plugin' => [
                    'label' => 'WP Gallery (Using only WP Media Uploader)',
                    'value' => false,
                    'type' => 'checkbox',
                ],
                'editor_attaches_plugin' => [
                    'label' => 'Attaches (Block for uploading files)',
                    'value' => false,
                    'type' => 'checkbox',
                ],
                'editor_image_plugin' => [
                    'label' => 'Image',
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'editor_header_plugin' => [
                    'label' => 'Header',
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'editor_header_placeholder_plugin' => [
                    'label' => '-- Header placeholder',
                    'value' => '',
                    'placeholder' => 'Enter a header',
                ],
                'editor_embed_plugin' => [
                    'label' => 'Embed',
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'editor_list_plugin' => [
                    'label' => 'List',
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'editor_checklist_plugin' => [
                    'label' => 'Checklist',
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'editor_quote_plugin' => [
                    'label' => 'Quote',
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'editor_quote_placeholder' => [
                    'label' => '-- Quote Placeholder',
                    'value' => '',
                    'placeholder' => 'Enter a quote',
                ],
                'editor_quote_caption_placeholder' => [
                    'label' => '-- Quote Caption Placeholder',
                    'value' => '',
                    'placeholder' => 'Quote\'s author',
                ],
                'editor_marker_plugin' => [
                    'label' => 'Marker',
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'editor_code_plugin' => [
                    'label' => 'Code',
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'editor_delimiter_plugin' => [
                    'label' => 'Delimiter',
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'editor_inlineCode_plugin' => [
                    'label' => 'Code',
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'editor_linkTool_plugin' => [
                    'label' => 'Link Tool',
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'required_error_text' => ['label' =>  __('Required error text', 'front-editor'), 'placeholder' => 'Please add number field', 'value' => ''],
            ];

        /**
         * Adding as default
         */
        $data['formBuilder_options']['defaultFields'][] = [
            'label' => self::$field_label,
            'type' => self::$field_type
        ];

        /**
         * Adding field to group
         */
        $data['formBuilder_options']['controls_group']['editors']['types'][] = self::$field_type;

        /**
         * Disable attr if there is no pro version
         */
        $is_premium = fe_fs()->can_use_premium_code__premium_only();
        if (!$is_premium) {
            $data['formBuilder_options']['disable_attr'][] = '.fld-editor_gallery_plugin';
            $data['formBuilder_options']['disable_attr'][] = '.fld-editor_table_plugin';
            $data['formBuilder_options']['disable_attr'][] = '.fld-editor_carousel_plugin';
            $data['formBuilder_options']['disable_attr'][] = '.fld-editor_attaches_plugin';
            $data['formBuilder_options']['disable_attr'][] = '.fld-editor_warning_plugin';
        }


        /**
         * Disabling default settings
         */
        $data['formBuilder_options']['typeUserDisabledAttrs'][self::$field_type] =
            [
                'inline',
                'toggle',
                'access',
                'value',
            ];

        return $data;
    }

    /**
     * Sanitize saved data to editor_js
     *
     * @param [type] $data
     * @param [type] $attributes
     * @param [type] $post_id
     * @return void
     */
    public static function add_editor_js_data($data, $attributes, $post_id)
    {
        // Initialize the editor_js data structure
        $data['editor_js'] = [];

        // Return early if post ID is empty
        if (empty($post_id)) {
            return $data;
        }

        // Retrieve all form field settings
        $fields = Form::get_form_field_settings_all(self::$field_type, $attributes['id']);

        // Return early if no fields are found
        if (empty($fields)) {
            return $data;
        }

        // Process each field
        foreach ($fields as $field) {
            $field_name = $field['name'];
            $data['editor_js'][$field_name]['settings'] = $field;

            // Handle new post scenario
            if ($post_id === 'new') {
                $default_content = apply_filters('fus_editor_js_default_content', []);
                $data['editor_js'][$field_name]['content'] = is_array($default_content) ? $default_content : [];
                continue;
            } else {
                // Retrieve and handle existing post meta data
                $editor_js_data = get_post_meta($post_id, $field_name, true);
                if ($editor_js_data) {
                    // stored data reaches the editor as HTML, clean it (also data saved by older versions)
                    $data['editor_js'][$field_name]['content'] = self::sanitize_editor_data($editor_js_data);
                }
            }
            // else {
            //     $data['editor_js'][$field_name]['content'] = [];
            //     $post_content_html = Editor::get_post_content($post_id);
            //     // Check if post was updated from admin
            //     $is_post_updated_from_admin = get_post_meta($post_id, 'fe_post_updated_from_admin', true);

            //     // we need this login in case user updated content from admin and we need to get fresh html from backend
            //     if ((isset($field['save_to']) && !empty($field['save_to'])) && $is_post_updated_from_admin) {
            //         if ($field['save_to'] === 'post_content') {
            //             $data['editor_js'][$field_name]['content'] = $post_content_html;
            //         } elseif ($field['save_to'] === 'post_excerpt') {
            //             $data['editor_js'][$field_name]['content'] = get_the_excerpt($post_id);
            //         }
            //     } // to support old version
            //     elseif ((isset($field['post_content']) && !empty($field['post_content'])) && $is_post_updated_from_admin) {
            //         $data['editor_js'][$field_name]['content'] = $post_content_html;
            //     }
            // }
        }

        return $data;
    }


    /**
     * Returning post content by id
     *
     * @param [type] $data
     * @param [type] $attributes
     * @param [type] $post_id
     * @return void
     */
    public static function localize_post_html_content($data, $attributes, $post_id)
    {
        // if this is a new post
        if ($post_id === 'new') {
            return '';
        }

        $post = get_post($post_id);
        $content = $post->post_content;
        $content = apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]>', $content);

        return $content;
    }

    /**
     * Example data for editor
     *
     * @return $data
     */
    public static function example_editor_data()
    {
        $data = [
            'time'   => time(),
            'blocks' => [
                [
                    'type' => 'paragraph',
                    'data' => [
                        'text' => __('Add content', 'front-editor'),
                    ],
                ]
            ]
        ];

        return $data;
    }

    /**
     * Show hidden field in form
     *
     * @return void
     */
    public static function add_field_to_front_form($post_id, $attributes, $field)
    {

        if ($field['type'] !== self::$field_type) {
            return;
        }

        require fe_template_path('front-editor/editorjs.php');
    }

    /**
     * Field setting for front end
     *
     * @param [type] $data
     * @param [type] $attributes
     * @param [type] $post_id
     * @return void
     */
    public static function field_setting_for_frontend($data, $attributes, $post_id)
    {
        $settings = Form::get_form_field_settings(self::$field_type, $attributes['id']);

        if (empty($settings)) {
            $data['editor_settings'] = [
                'editor_image_plugin' => $attributes['editor_image_plugin'] ?? true,
                'editor_header_plugin' => $attributes['editor_header_plugin'] ?? true,
                'editor_embed_plugin' => $attributes['editor_embed_plugin'] ?? true,
                'editor_list_plugin' => $attributes['editor_list_plugin'] ?? true,
                'editor_checklist_plugin' => $attributes['editor_checklist_plugin'] ?? true,
                'editor_quote_plugin' => $attributes['editor_quote_plugin'] ?? true,
                'editor_marker_plugin' => $attributes['editor_marker_plugin'] ?? true,
                'editor_code_plugin' => $attributes['editor_code_plugin'] ?? true,
                'editor_delimiter_plugin' => $attributes['editor_delimiter_plugin'] ?? true,
                'editor_inlineCode_plugin' => $attributes['editor_inlineCode_plugin'] ?? true,
                'editor_linkTool_plugin' => $attributes['editor_linkTool_plugin'] ?? true,
                'tags_add_new' => $attributes['tags_add_new'] ?? false,
                'wp_media_uploader' => false, // pro
                'editor_warning_plugin' => false, // pro
                'editor_table_plugin' => false, // pro
                'editor_gallery_plugin' => false, // pro
                'editor_carousel_plugin' => false, // pro
                'editor_attaches_plugin' => false // pro
            ];
        }

        if (is_array($settings) && !empty($settings)) {
            foreach ($settings as $name => $value) {
                $data['editor_settings'][$name] = $value;
            }
        }


        $data['translations']['i18n'] = [
            'messages' => [
                'ui' => [
                    "blockTunes" => [
                        "toggler" => [
                            "Click to tune" => __("Click to tune", 'front-editor'),
                            "or drag to move" => __("or drag to move", 'front-editor')
                        ]
                    ],
                    'inlineToolbar' => [
                        'converter' => [
                            "Convert to" => __("Convert to", 'front-editor')

                        ]
                    ],
                    "toolbar" => [
                        "toolbox" => [
                            "Add" => __("Add", 'front-editor')
                        ]
                    ]
                ],
                'toolNames' => [
                    "Text" => __("Text", 'front-editor'),
                    "Heading" => __("Heading", 'front-editor'),
                    "List" => __("List", 'front-editor'),
                    "Warning" => __("Warning", 'front-editor'),
                    "Checklist" => __("Checklist", 'front-editor'),
                    "Quote" => __("Quote", 'front-editor'),
                    "Code" => __("Code", 'front-editor'),
                    "Delimiter" => __("Delimiter", 'front-editor'),
                    "Raw HTML" => __("Raw HTML", 'front-editor'),
                    "Table" => __("Table", 'front-editor'),
                    "Link" => __("Link", 'front-editor'),
                    "Marker" => __("Marker", 'front-editor'),
                    "Bold" => __("Bold", 'front-editor'),
                    "Italic" => __("Italic", 'front-editor'),
                    "InlineCode" => __("InlineCode", 'front-editor'),
                    "Image & Gallery" => __("Image & Gallery", 'front-editor'),
                    "Image" => __("Image", 'front-editor')
                ],
                'tools' => [
                    'warning' => [
                        "Title" => __("Title", 'front-editor'),
                        "Message" => __("Message", 'front-editor')
                    ],
                    'link' => [
                        "Add a link" => __("Add a link", 'front-editor'),
                    ],
                    'stub' => [
                        "The block can not be displayed correctly." => __("The block can not be displayed correctly.", 'front-editor'),
                    ]
                ],
                'blockTunes' => [
                    'delete' => [
                        "Delete" => __("Delete", 'front-editor'),
                    ],
                    'moveUp' => [
                        "Move up" => __("Move up", 'front-editor'),
                    ],
                    'moveDown' => [
                        "Move down" => __("Move down", 'front-editor'),
                    ]
                ]
            ]
        ];

        $data['translations']['editor_field_placeholder'] = __('Start writing or enter Tab to choose a block', 'front-editor');


        return $data;
    }

    /**
     * Generating html from post data
     *
     * @param string $type type of field.
     * @param array  $data data of that field.
     * @return string
     */
    public static function data_to_html($type = '', $data = array())
    {
        $html = '';
        $tunes = false;
        if (isset($data['tunes'])) {
            $tunes = $data['tunes'];
        }
        $data = $data['data'];
        switch ($type) {
            case 'header';
                $class = '';
                if ($tunes) {
                    // has-text-align-center
                    if (isset($tunes['AlignmentTool'])) {
                        $alignment = $tunes['AlignmentTool']['alignment'] ?? ($tunes['AlignmentTool']['prop']['alignment'] ?? '');
                        $class = sprintf('class="has-text-align-%s"', sanitize_html_class((string) $alignment));
                    }
                }
                $guten_type = 'heading';
                $data['level'] = min(6, max(1, (int) ($data['level'] ?? 2)));
                $html       = sprintf(
                    '<!-- wp:%s {"level" =>%s} --><h%s %s>%s</h%s><!-- /wp:%s -->',
                    $guten_type,
                    $data['level'],
                    $data['level'],
                    $class,
                    $data['text'] ?? '',
                    $data['level'],
                    $guten_type
                );
                break;

            case 'paragraph';
                $class = '';
                if ($tunes) {
                    // has-text-align-center
                    if (isset($tunes['AlignmentTool'])) {
                        $alignment = $tunes['AlignmentTool']['alignment'] ?? ($tunes['AlignmentTool']['prop']['alignment'] ?? '');
                        $class = sprintf('class="has-text-align-%s"', sanitize_html_class((string) $alignment));
                    }
                }
                if (empty($data['text'])) {
                    $data['text'] = '\r\n';
                }
                $html = sprintf('<!-- wp:paragraph --><p %s>%s</p><!-- /wp:paragraph -->', $class, $data['text']);
                break;

            case 'list';
                $html = '<!-- wp:list --><ul>';
                foreach ((array) ($data['items'] ?? []) as $item) {
                    // List 2.x stores items as objects with content
                    $html .= sprintf('<li>%s</li>', is_array($item) ? ($item['content'] ?? '') : $item);
                }
                $html .= '</ul><!-- /wp:list -->';
                break;

            case 'code';
                $editor_data = htmlentities((string) ($data['code'] ?? ''));

                $html = sprintf('<!-- wp:code --><pre class="bfe-code wp-block-code"><code>%s</code></pre><!-- /wp:code -->', $editor_data);
                break;

            case 'delimiter':
                $html = '<!-- wp:separator --><hr class="wp-block-separator bfe-delimiter"/><!-- /wp:separator -->';
                break;

            case 'embed':
                ob_start();
                if (!empty($data['embed']) && self::is_allowed_embed_url($data['embed'])) {
                    require fe_template_path('editor/embed.php');
                }
                $html = trim(ob_get_clean());

                break;

            case 'image':
                ob_start();
                if (!empty($data['file']['url'])) {
                    $image_url = $data['file']['url'];
                    $caption = $data['caption'] ?? '';
                    $image_id  = attachment_url_to_postid($image_url);
                    require FE_Template_PATH . 'editor/image.php';
                }
                $html .= trim(ob_get_clean());
                break;

            case 'carousel':
                ob_start();
                if (!empty($data)) {
                    $images = $data;
                    require fe_template_path('editor/images-carousel.php');
                }

                $html = trim(ob_get_clean());
                break;

            case 'wpImageGallery':
                ob_start();
                // gallery items are attachment ids from the browser: keep only media the visitor may show
                $data = is_array($data) ? array_values(array_filter($data, [__CLASS__, 'is_usable_gallery_item'])) : [];
                if (!empty($data) && count($data) > 1) {
                    $image_ids = $data;
                    require fe_template_path('editor/gallery.php');
                } elseif (!empty($data[0])) {
                    $image_id  = is_array($data[0]) ? (int) ($data[0]['id'] ?? 0) : (int) $data[0];
                    $caption   = '';
                    require fe_template_path('editor/image.php');
                }

                $html = trim(ob_get_clean());
                break;

            case 'quote':
                ob_start();
                require fe_template_path('editor/quote.php');
                $html = ob_get_clean();
                break;

            case 'warning':
                $html = sprintf('<figure class="warning"><figcaption>%s</figcaption><p>%s</p></figure>', $data['title'] ?? '', $data['message'] ?? '');
                break;

            case 'table':
                ob_start();
                require fe_template_path('editor/table.php');
                $html = ob_get_clean();
                break;

            case 'checklist':
                ob_start();
                require fe_template_path('editor/checklist.php');
                $html = ob_get_clean();
                break;

            case 'attaches':
                ob_start();
                // only files uploaded to this site
                if (!empty($data['file']['url']) && self::is_upload_url($data['file']['url'])) {
                    require fe_template_path('editor/attaches.php');
                }
                $html = ob_get_clean();
                break;
        }
        $html .= htmlspecialchars("\n");

        /**
         * HTML that generated type of block and data of
         * that block you can use this filter for make your html elements
         */
        $html = apply_filters('bfe_editor_data_to_html_filter', self::esc_brackets($html), $type, $data);

        return $html;
    }

    /**
     * Gallery item the visitor may put in the post: own/editable media or media already attached to this post
     *
     * @param mixed $item ['id' => int] or an id.
     * @return bool
     */
    public static function is_usable_gallery_item($item)
    {
        $attachment_id = is_array($item) ? (int) ($item['id'] ?? 0) : (int) $item;
        if (!$attachment_id || get_post_type($attachment_id) !== 'attachment') {
            return false;
        }

        $saving_post_id = \BFE\SavePost::$saving_post_id;
        if ($saving_post_id && (int) get_post_field('post_parent', $attachment_id) === $saving_post_id) {
            return true;
        }

        return \BFE\SavePost::can_use_attachment($attachment_id);
    }

    /**
     * Escape brackets function
     *
     * @param string $text
     * @return void
     */
    public static function esc_brackets($text = '')
    {
        return str_replace(["[", "]"], ["[ ", " ]"], $text);
    }
}

EditorJsField::init();
