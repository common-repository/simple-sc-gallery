<?php
/*
Plugin Name: Simple SC Gallery
Description: A simple gallery that uses the shortcode [simple-sc-gallery ids="1,2,3"], where ids are the IDs of the images in your WordPress media library.
Version: 1.0.2
Author: Sergіy Voloshchenko
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

// Register styles and scripts
function simple_sc_gallery_scripts() {
    if (!is_admin()) {
        $version = '1.0.2'; // Define the version of your scripts and styles

        wp_enqueue_style('simple-sc-gallery-css', plugins_url('/css/gallery.css', __FILE__), array(), $version);
        wp_enqueue_script('simple-sc-gallery-js', plugins_url('/js/gallery.js', __FILE__), array('jquery'), $version, true);

        $show_arrows = sanitize_text_field(get_option('simple_sc_gallery_show_arrows', 1));
        $arrow_color = sanitize_hex_color(get_option('simple_sc_gallery_arrow_color', '#333'));
        $show_close_cross = sanitize_text_field(get_option('simple_sc_gallery_show_close_cross', 1));
        $close_cross_color = sanitize_hex_color(get_option('simple_sc_gallery_close_cross_color', '#fff'));
        $object_fit = sanitize_text_field(get_option('simple_sc_gallery_object_fit', 'cover'));
        $transition_effect = sanitize_text_field(get_option('simple_sc_gallery_transition_effect', 'fade'));
        $fullscreen_bg_color = sanitize_hex_color(get_option('simple_sc_gallery_fullscreen_bg_color', '#000000'));
        $fullscreen_bg_opacity = floatval(get_option('simple_sc_gallery_fullscreen_bg_opacity', 0.5));

        wp_localize_script('simple-sc-gallery-js', 'simpleScGallerySettings', array(
            'show_arrows' => esc_js($show_arrows),
            'arrow_color' => esc_js($arrow_color),
            'show_close_cross' => esc_js($show_close_cross),
            'close_cross_color' => esc_js($close_cross_color),
            'object_fit' => esc_js($object_fit),
            'transition_effect' => esc_js($transition_effect),
            'fullscreen_bg_color' => esc_js($fullscreen_bg_color),
            'fullscreen_bg_opacity' => esc_js($fullscreen_bg_opacity),
        ));
    }
}
add_action('wp_enqueue_scripts', 'simple_sc_gallery_scripts');

// Shortcode handling with sanitization
function simple_sc_gallery_shortcode($atts) {
    $atts = shortcode_atts(array(
        'ids' => ''
    ), $atts, 'simple-sc-gallery');

    $ids = wp_unslash($atts['ids']); // Unslash incoming data
    $images = array_map('intval', explode(',', sanitize_text_field($ids)));
    
    $columns = intval(get_option('simple_sc_gallery_columns', 4));
    $uniform_thumbnails = intval(get_option('simple_sc_gallery_uniform_thumbnails', 0));
    $border_type = sanitize_text_field(get_option('simple_sc_gallery_border_type', 'none'));
    $border_width = intval(get_option('simple_sc_gallery_border_width', 0));
    $border_color = sanitize_hex_color(get_option('simple_sc_gallery_border_color', '#000000'));
    $lazyload_images = intval(get_option('simple_sc_gallery_lazyload_images', 0));
    $thumb_width = intval(get_option('simple_sc_gallery_thumb_width', 150));
    $thumb_height = intval(get_option('simple_sc_gallery_thumb_height', 150));
    $effect = sanitize_text_field(get_option('simple_sc_gallery_effect', 'none'));
    $scale = floatval(get_option('simple_sc_gallery_scale', 1.1));
    $blur = intval(get_option('simple_sc_gallery_blur', 5));
    $grayscale = intval(get_option('simple_sc_gallery_grayscale', 100));
    $hue_rotate = intval(get_option('simple_sc_gallery_hue_rotate', 90));
    $transition_effect = sanitize_text_field(get_option('simple_sc_gallery_transition_effect', 'all 0.3s ease'));
    $pagination_enabled = intval(get_option('simple_sc_gallery_pagination_enabled', 0));
    $thumbnails_per_page = intval(get_option('simple_sc_gallery_thumbnails_per_page', 10));

    // Verify nonce and sanitize GET request
$page = isset($_GET['gallery_page']) ? intval(sanitize_text_field(wp_unslash($_GET['gallery_page']))) : 1;

if (isset($_GET['_wpnonce']) && !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'simple_sc_gallery_nonce')) {
    return; // Nonce check failed
}

    $total_images = count($images);
    $total_pages = ceil($total_images / $thumbnails_per_page);
    $offset = ($page - 1) * $thumbnails_per_page;

    if ($pagination_enabled) {
        $images = array_slice($images, $offset, $thumbnails_per_page);
    }

    // Output gallery HTML
    $output = "<div class='simple-sc-gallery' style='grid-template-columns: repeat(" . esc_attr($columns) . ", 1fr); --thumb-width: " . esc_attr($thumb_width) . "px; --thumb-height: " . esc_attr($thumb_height) . "px; --scale-factor: " . esc_attr($scale) . "; --blur-amount: " . esc_attr($blur) . "px; --grayscale-amount: " . esc_attr($grayscale) . "%; --hue-rotate-amount: " . esc_attr($hue_rotate) . "deg; --transition-effect: " . esc_attr($transition_effect) . ";'>";
    foreach ($images as $image) {
        $img_url = wp_get_attachment_url($image);
        $thumbnail_class = $uniform_thumbnails ? 'uniform' : 'fixed';
        $thumbnail_style = $uniform_thumbnails ? "width: 100%; height: 100%; object-fit: cover;" : "width: " . esc_attr($thumb_width) . "px; height: " . esc_attr($thumb_height) . "px; object-fit: cover;";
        $loading_attr = $lazyload_images ? 'loading="lazy"' : '';

        $effect_class = $effect != 'none' ? "effect-" . esc_attr($effect) : '';

        $output .= "<div class='simple-sc-gallery-thumbnail " . esc_attr($thumbnail_class) . " " . esc_attr($effect_class) . "' style='border: " . esc_attr($border_width) . "px " . esc_attr($border_type) . " " . esc_attr($border_color) . ";'>";
        $output .= "<img src='" . esc_url($img_url) . "' alt='' class='thumbnail' style='" . esc_attr($thumbnail_style) . "' " . $loading_attr . " />";
        $output .= "<img src='" . esc_url($img_url) . "' alt='' class='fullscreen' style='display:none;' />";
        $output .= "</div>";
    }
    $output .= '</div>';

    // Pagination
    if ($pagination_enabled && $total_pages > 1) {
        $output .= '<div class="simple-sc-gallery-pagination" style="text-align: center;">';
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $page) {
                $output .= "<span class='current-page'>" . esc_html($i) . "</span>";
            } else {
                $output .= "<a href='" . esc_url(add_query_arg(array(
                    'gallery_page' => $i,
                    '_wpnonce' => wp_create_nonce('simple_sc_gallery_nonce')
                ))) . "' class='pagination-link'>" . esc_html($i) . "</a>";
            }
        }
        $output .= '</div>';
    }

    return $output;
}
add_shortcode('simple-sc-gallery', 'simple_sc_gallery_shortcode');

// Admin settings menu
function simple_sc_gallery_menu() {
    add_menu_page('Simple SC Gallery Settings', 'Simple SC Gallery', 'manage_options', 'simple-sc-gallery', 'simple_sc_gallery_settings_page', 'dashicons-format-gallery');
}
add_action('admin_menu', 'simple_sc_gallery_menu');

function simple_sc_gallery_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Simple SC Gallery Settings', 'text-domain'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('simple_sc_gallery_options_group');
            do_settings_sections('simple-sc-gallery');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function simple_sc_gallery_settings_init() {
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_columns', array(
        'sanitize_callback' => 'absint',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_uniform_thumbnails', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_border_type', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_border_width', array(
        'sanitize_callback' => 'absint',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_border_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_object_fit', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_show_arrows', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_arrow_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_show_close_cross', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_close_cross_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_lazyload_images', array(
        'sanitize_callback' => 'absint',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_thumb_width', array(
        'sanitize_callback' => 'absint',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_thumb_height', array(
        'sanitize_callback' => 'absint',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_effect', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_scale', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_blur', array(
        'sanitize_callback' => 'absint',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_grayscale', array(
        'sanitize_callback' => 'absint',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_hue_rotate', array(
        'sanitize_callback' => 'absint',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_transition_effect', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_fullscreen_bg_color', array(
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_fullscreen_bg_opacity', array(
    'sanitize_callback' => 'sanitize_opacity_value',
	));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_pagination_enabled', array(
        'sanitize_callback' => 'absint',
    ));
    register_setting('simple_sc_gallery_options_group', 'simple_sc_gallery_thumbnails_per_page', array(
        'sanitize_callback' => 'absint',
    ));


    add_settings_section(
        'simple_sc_gallery_settings_section',
        'Thumbnail Gallery Settings',
        'simple_sc_gallery_settings_section_callback',
        'simple-sc-gallery'
    );

   add_settings_field(
    'simple_sc_gallery_columns',
    'Number of Columns <span title="Specify how many columns the gallery should have." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_columns_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_uniform_thumbnails',
    'Uniform Thumbnails <span title="Enable or disable uniform sizing for thumbnails. If this option is enabled, the thumbnail width and height settings will not take effect." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_uniform_thumbnails_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_border_type',
    'Image Border Type <span title="Choose the type of border for gallery images (solid, dashed, etc.)." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_border_type_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_border_width',
    'Image Border Width (px) <span title="Set the width of the border around each gallery image in pixels." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_border_width_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_border_color',
    'Image Border Color <span title="Select the color of the border around each gallery image." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_border_color_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_thumb_width',
    'Thumbnail Width (px) <span title="Set the width of the thumbnail images in pixels." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_thumb_width_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_thumb_height',
    'Thumbnail Height (px) <span title="Set the height of the thumbnail images in pixels." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_thumb_height_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_effect',
    'Thumbnail Effect <span title="Choose the visual effect applied to thumbnails on hover." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_effect_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_scale',
    'Scale Factor <span title="Adjust the scale factor for images (e.g., zoom in/out)." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_scale_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_blur',
    'Blur Amount (px) <span title="Set the amount of blur effect on images, measured in pixels." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_blur_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_grayscale',
    'Grayscale Amount (%) <span title="Adjust the grayscale level of images, from 0% to 100%." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_grayscale_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_hue_rotate',
    'Hue Rotate (deg) <span title="Adjust the hue rotation in degrees to change image colors." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_hue_rotate_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_lazyload_images',
    'Enable Lazy Load <span title="Enable this option to lazy load images, improving performance." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_lazyload_images_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_section(
    'simple_sc_gallery_settings_section_fullscreen',
    'Fullscreen Gallery Image Settings',
    'simple_sc_gallery_settings_section_fullscreen_callback',
    'simple-sc-gallery'
	);

	add_settings_field(
    'simple_sc_gallery_object_fit',
    'Object Fit <span title="Control how the image fits within its container in fullscreen mode." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_object_fit_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section_fullscreen'
	);

	add_settings_field(
    'simple_sc_gallery_show_arrows',
    'Show Navigation Arrows <span title="Enable or disable navigation arrows for the gallery." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_show_arrows_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section_fullscreen'
	);

	add_settings_field(
    'simple_sc_gallery_arrow_color',
    'Arrow Color <span title="Choose the color for the navigation arrows." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_arrow_color_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section_fullscreen'
	);

	add_settings_field(
    'simple_sc_gallery_show_close_cross',
    'Show Close Cross <span title="Enable or disable the close cross in fullscreen mode." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_show_close_cross_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section_fullscreen'
	);

	add_settings_field(
    'simple_sc_gallery_close_cross_color',
    'Close Cross Color <span title="Set the color for the close cross in fullscreen mode." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_close_cross_color_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section_fullscreen'
	);

	add_settings_field(
    'simple_sc_gallery_transition_effect',
    'Transition Effect <span title="Choose the transition effect for the gallery (e.g., fade, twirl)." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_transition_effect_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section_fullscreen'
	);

	add_settings_field(
    'simple_sc_gallery_fullscreen_bg_color',
    'Fullscreen Background Color <span title="Choose the background color when the gallery is in fullscreen mode." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_fullscreen_bg_color_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section_fullscreen'
	);

	add_settings_field(
    'simple_sc_gallery_fullscreen_bg_opacity',
    'Fullscreen Background Opacity <span title="Adjust the opacity of the fullscreen background." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_fullscreen_bg_opacity_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section_fullscreen'
	);

	add_settings_field(
    'simple_sc_gallery_pagination_enabled',
    'Enable Pagination <span title="Enable pagination for the gallery to split images across multiple pages." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_pagination_enabled_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

	add_settings_field(
    'simple_sc_gallery_thumbnails_per_page',
    'Thumbnails per Page <span title="Specify how many thumbnails should be displayed per page." style="cursor: help;">(?)</span>',
    'simple_sc_gallery_thumbnails_per_page_callback',
    'simple-sc-gallery',
    'simple_sc_gallery_settings_section'
	);

}
add_action('admin_init', 'simple_sc_gallery_settings_init');

function simple_sc_gallery_settings_section_callback() {
    echo '<p>Configure the settings for the Simple SC Gallery plugin thumbnails.</p>';
}

function simple_sc_gallery_settings_section_fullscreen_callback() {
    echo '<p>Configure the settings for the Simple SC Gallery plugin fullscreen.</p>';
}

function simple_sc_gallery_columns_callback() {
    $columns = get_option('simple_sc_gallery_columns', 4);
    echo '<input type="number" name="simple_sc_gallery_columns" value="' . esc_attr($columns) . '" min="1" max="12" />';
}

function simple_sc_gallery_uniform_thumbnails_callback() {
    $uniform_thumbnails = get_option('simple_sc_gallery_uniform_thumbnails', 0);
    echo '<input type="checkbox" name="simple_sc_gallery_uniform_thumbnails" value="1" ' . checked(1, $uniform_thumbnails, false) . ' />';
}

function simple_sc_gallery_border_type_callback() {
    $border_type = get_option('simple_sc_gallery_border_type', 'none');
    $options = array('none', 'solid', 'dotted', 'dashed');
    echo '<select name="simple_sc_gallery_border_type">';
    foreach ($options as $option) {
        echo '<option value="' . esc_attr($option) . '" ' . selected($border_type, $option, false) . '>' . esc_html(ucfirst($option)) . '</option>';
    }
    echo '</select>';
}

function simple_sc_gallery_border_width_callback() {
    $border_width = get_option('simple_sc_gallery_border_width', 0);
    echo '<input type="number" name="simple_sc_gallery_border_width" value="' . esc_attr($border_width) . '" min="0" max="20" />';
}

function simple_sc_gallery_border_color_callback() {
    $border_color = get_option('simple_sc_gallery_border_color', '#000000');
    echo '<input type="color" name="simple_sc_gallery_border_color" value="' . esc_attr($border_color) . '" />';
}

function simple_sc_gallery_object_fit_callback() {
    $object_fit = get_option('simple_sc_gallery_object_fit', 'cover');
    $options = array('fill', 'contain', 'cover', 'none', 'scale-down');
    echo '<select name="simple_sc_gallery_object_fit">';
    foreach ($options as $option) {
        echo '<option value="' . esc_attr($option) . '" ' . selected($object_fit, $option, false) . '>' . esc_html(ucfirst($option)) . '</option>';
    }
    echo '</select>';
}

function simple_sc_gallery_show_arrows_callback() {
    $show_arrows = get_option('simple_sc_gallery_show_arrows', 1);
    echo '<input type="checkbox" name="simple_sc_gallery_show_arrows" value="1" ' . checked(1, $show_arrows, false) . ' />';
}

function simple_sc_gallery_arrow_color_callback() {
    $arrow_color = get_option('simple_sc_gallery_arrow_color', '#333');
    echo '<input type="color" name="simple_sc_gallery_arrow_color" value="' . esc_attr($arrow_color) . '" />';
}

function simple_sc_gallery_show_close_cross_callback() {
    $show_close_cross = get_option('simple_sc_gallery_show_close_cross', 1);
    echo '<input type="checkbox" name="simple_sc_gallery_show_close_cross" value="1" ' . checked(1, $show_close_cross, false) . ' />';
}

function simple_sc_gallery_close_cross_color_callback() {
    $close_cross_color = get_option('simple_sc_gallery_close_cross_color', '#fff');
    echo '<input type="color" name="simple_sc_gallery_close_cross_color" value="' . esc_attr($close_cross_color) . '" />';
}

function simple_sc_gallery_lazyload_images_callback() {
    $lazyload_images = get_option('simple_sc_gallery_lazyload_images', 0);
    echo '<input type="checkbox" name="simple_sc_gallery_lazyload_images" value="1" ' . checked(1, $lazyload_images, false) . ' />';
}

function simple_sc_gallery_thumb_width_callback() {
    $thumb_width = get_option('simple_sc_gallery_thumb_width', 150);
    echo '<input type="number" name="simple_sc_gallery_thumb_width" value="' . esc_attr($thumb_width) . '" min="0" />';
}

function simple_sc_gallery_thumb_height_callback() {
    $thumb_height = get_option('simple_sc_gallery_thumb_height', 150);
    echo '<input type="number" name="simple_sc_gallery_thumb_height" value="' . esc_attr($thumb_height) . '" min="0" />';
}

function simple_sc_gallery_effect_callback() {
    $effect = get_option('simple_sc_gallery_effect', 'none');
    $options = array(
        'none' => 'None',
        'scale' => 'Scale',
        'blur' => 'Blur',
        'grayscale' => 'Grayscale',
        'hue-rotate' => 'Hue Rotate'
    );
    foreach ($options as $value => $label) {
        echo '<input type="radio" name="simple_sc_gallery_effect" value="' . esc_attr($value) . '" ' . checked($effect, $value, false) . '> ' . esc_html($label) . '<br>';
    }
}

function simple_sc_gallery_scale_callback() {
    $scale = get_option('simple_sc_gallery_scale', 1);
    echo '<input type="number" name="simple_sc_gallery_scale" value="' . esc_attr($scale) . '" step="0.1" min="0.1" max="5">';
}

function simple_sc_gallery_blur_callback() {
    $blur = get_option('simple_sc_gallery_blur', 0);
    echo '<input type="number" name="simple_sc_gallery_blur" value="' . esc_attr($blur) . '" step="1" min="0" max="20">';
}

function simple_sc_gallery_grayscale_callback() {
    $grayscale = get_option('simple_sc_gallery_grayscale', 0);
    echo '<input type="number" name="simple_sc_gallery_grayscale" value="' . esc_attr($grayscale) . '" step="1" min="0" max="100">';
}

function simple_sc_gallery_hue_rotate_callback() {
    $hue_rotate = get_option('simple_sc_gallery_hue_rotate', 0);
    echo '<input type="number" name="simple_sc_gallery_hue_rotate" value="' . esc_attr($hue_rotate) . '" step="1" min="0" max="360">';
}

function simple_sc_gallery_transition_effect_callback() {
    $effect = get_option('simple_sc_gallery_transition_effect', 'fade');
    $options = array('none', 'fade', 'twirl', 'stretch');
    foreach ($options as $option) {
        echo '<input type="radio" name="simple_sc_gallery_transition_effect" value="' . esc_attr($option) . '" ' . checked($effect, $option, false) . '> ' . esc_html(ucfirst($option)) . '<br>';
    }
}

function simple_sc_gallery_fullscreen_bg_color_callback() {
    $color = get_option('simple_sc_gallery_fullscreen_bg_color', '#000000');
    echo '<input type="color" name="simple_sc_gallery_fullscreen_bg_color" value="' . esc_attr($color) . '" />';
}

function simple_sc_gallery_fullscreen_bg_opacity_callback() {
    $opacity = get_option('simple_sc_gallery_fullscreen_bg_opacity', 0.5);
    echo '<input type="number" name="simple_sc_gallery_fullscreen_bg_opacity" value="' . esc_attr($opacity) . '" step="0.1" min="0" max="1" />';
}

function sanitize_opacity_value($value) {
    $value = floatval($value);
    if ($value < 0 || $value > 1) {
        return 1; // Default to 1 if the value is outside the 0-1 range
    }
    return $value;
}

function simple_sc_gallery_pagination_enabled_callback() {
    $pagination_enabled = get_option('simple_sc_gallery_pagination_enabled', 0);
    echo '<input type="checkbox" name="simple_sc_gallery_pagination_enabled" value="1" ' . checked(1, $pagination_enabled, false) . ' />';
}

function simple_sc_gallery_thumbnails_per_page_callback() {
    $thumbnails_per_page = get_option('simple_sc_gallery_thumbnails_per_page', 10);
    echo '<input type="number" name="simple_sc_gallery_thumbnails_per_page" value="' . esc_attr($thumbnails_per_page) . '" min="6" max="20" />';
}
