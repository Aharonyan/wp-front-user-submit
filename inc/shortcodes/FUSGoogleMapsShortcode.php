<?php

defined('ABSPATH') || exit;

class FUSGoogleMapsShortcode {
    public static function init() {
        add_shortcode('fus_google_map', array(__CLASS__, 'render_map'));
    }

    public static function render_map($atts) {
        $defaults = array(
            'meta_name' => '',
            'post_id' => '',
            'height' => '400px',
            'width' => '100%',
            'zoom' => 15,
            'marker_title' => '',
            'info_window' => 'true'
        );

        $atts = shortcode_atts($defaults, $atts, 'fus_google_map');
        
        $post_id = empty($atts['post_id']) ? get_the_ID() : $atts['post_id'];
        $meta_data = get_post_meta($post_id, $atts['meta_name'], true);
        
        if (empty($meta_data)) {
            return '<div class="fus-map-error">' . esc_html__('Location data not found', 'front-editor') . '</div>';
        }

        $location_data = json_decode($meta_data, true);
        if (!isset($location_data['geometry']['location'])) {
            return '<div class="fus-map-error">' . esc_html__('Invalid location data format', 'front-editor') . '</div>';
        }

        wp_enqueue_script('fus-google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr(get_option('bfe_front_editor_google_map_api')), array(), '1.0', true);

        $map_id = 'fus-map-' . uniqid();
        $lat = $location_data['geometry']['location']['lat'];
        $lng = $location_data['geometry']['location']['lng'];
        
        $map_data = array(
            'lat' => $lat,
            'lng' => $lng,
            'zoom' => intval($atts['zoom']),
            'markerTitle' => !empty($atts['marker_title']) ? $atts['marker_title'] : $location_data['formatted_address'],
            'showInfoWindow' => filter_var($atts['info_window'], FILTER_VALIDATE_BOOLEAN),
            'address' => $location_data['formatted_address']
        );

        $inline_script = self::get_inline_script($map_id, $map_data);
        wp_add_inline_script('fus-google-maps', $inline_script);

        $map_style = sprintf('height: %s; width: %s;', 
            esc_attr($atts['height']), 
            esc_attr($atts['width'])
        );

        return sprintf(
            '<div id="%s" class="fus-google-map" style="%s" data-map-id="%s"></div>',
            esc_attr($map_id),
            esc_attr($map_style),
            esc_attr($map_id)
        );
    }

    private static function get_inline_script($map_id, $map_data) {
        return "
            document.addEventListener('DOMContentLoaded', function() {
                const element = document.getElementById('{$map_id}');
                if (!element) return;

                const map = new google.maps.Map(element, {
                    center: { 
                        lat: parseFloat({$map_data['lat']}), 
                        lng: parseFloat({$map_data['lng']}) 
                    },
                    zoom: {$map_data['zoom']},
                    mapTypeControl: true,
                    streetViewControl: true,
                    fullscreenControl: true
                });

                const marker = new google.maps.Marker({
                    position: { 
                        lat: parseFloat({$map_data['lat']}), 
                        lng: parseFloat({$map_data['lng']}) 
                    },
                    map: map,
                    title: '" . esc_js($map_data['markerTitle']) . "'
                });

                " . ($map_data['showInfoWindow'] ? "
                    const infoWindow = new google.maps.InfoWindow({
                        content: '<div class=\"fus-map-info-window\"><p>" . esc_js($map_data['address']) . "</p></div>'
                    });

                    marker.addListener('click', function() {
                        infoWindow.open(map, marker);
                    });
                " : "") . "
            });
        ";
    }
}

add_action('init', array('FUSGoogleMapsShortcode', 'init'));