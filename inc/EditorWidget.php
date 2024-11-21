<?php

namespace BFE;

class EditorWidget
{
    public static $post_id;

    public static function init()
    {
        add_action('wp_footer', [__CLASS__, 'add_widget']);

        add_filter('the_content', [__CLASS__, 'add_edit_button_close_to_content']);
    }

    /**
     * add widget to footer 
     *
     * @param [type] $atts
     * @return void
     */
    public static function add_widget()
    {
        $widget = self::get_edit_post_widget();
        if(!$widget){
            return;
        }
        $widget_positions = ['left_bottom','left_top','right_bottom','right_top'];

        if(!in_array($widget['position'],$widget_positions)){
            return;
        }

        echo $widget['html'];
    }

    public static function add_edit_button_close_to_content($content)
    {
        $widget = self::get_edit_post_widget();

        if(!$widget){
            return $content;
        }

        $content_positions = ['before_content','after_content'];

        if(!in_array($widget['position'],$content_positions)){
            return $content;
        }

        if($widget['position'] == 'before_content'){
            $content = $widget['html'].$content;
        } else {
            $content = $content.$widget['html'];
        }

        return $content;
    }

    public static function get_edit_post_widget()
    {
        global $post;

        if (!is_single($post)) {
            return false;
        }

        $can_edit = Editor::can_edit_post(0, $post->ID);

		if (!$can_edit['status']) {
            return false;
        }

        $position = get_option('bfe_front_editor_edit_button_position');

        if($position === 'hide'){
            return false;
        }

        if (empty($position)) {
            $position = 'after_content';
        }

        wp_enqueue_style('bfe-block-style');

        $post_edit_link = Editor::get_post_edit_link($post->ID);

        $button_text = get_option('bfe_front_editor_edit_button_text');

        if(empty($button_text)){
            $button_text = __('Edit Current Post', 'front-editor');
        }

        $widget_html = sprintf('<a class="bfe-edit-post-button %s" href="%s">%s</a>', $position, $post_edit_link, $button_text);

        return ['position' => $position, 'html' => $widget_html = apply_filters('bfe-editor-post-edit-button-html', $widget_html)];
    }
}

EditorWidget::init();
