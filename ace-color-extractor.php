<?php
/*
Plugin Name: ACE Color Extractor
Description: Extract and display the most used colors from post thumbnails.
Version: 1.2.0
Author: LogosNicas x AI
Author URI: https://logosnicas.com/
Text Domain: ace-color-extractor
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Include the options page file
require_once plugin_dir_path(__FILE__) . 'admin/options-page.php';

/**
 * Initializes the localization (internationalization) of the plugin.
 *
 * This function loads the translation files for the plugin, enabling it to be translated
 * into different languages.
 *
 * @since 1.1.1
 */
function ace_init() {
    // Define the relative path to the plugin's language directory.
    $plugin_rel_path = basename(dirname(__FILE__)) . '/languages'; // Relative to WP_PLUGIN_DIR

    // Load the plugin text domain for localization.
    load_plugin_textdomain('ace-color-extractor', false, $plugin_rel_path);
}

// Hook into the 'plugins_loaded' action to execute the 'ace_init' function.
add_action('plugins_loaded', 'ace_init');


/**
 * Add a "Settings" link to the plugin's action links on the Plugins page.
 *
 * This function appends a "Settings" link with the correct URL for the plugin.
 *
 * @param array $links An array of existing plugin action links.
 * @return array The modified array of action links.
 *
 * @since 1.2.0
 */
function add_settings_link($links) {
    // Define the "Settings" link with the correct URL.
    $settings_link = '<a href="options-general.php?page=ace-color-extractor-options">' . esc_html__('Settings', 'ace-color-extractor') . '</a';

    // Add the "Settings" link to the action links array.
    array_push($links, $settings_link);

    return $links;
}

// Get the basename of the plugin file.
$plugin = plugin_basename(__FILE__);

// Check if the plugin basename contains 'ace-color-extractor/' to ensure it's the correct plugin.
if (strpos($plugin, 'ace-color-extractor/') !== false) {
    // Add the filter to modify the plugin's action links.
    add_filter("plugin_action_links_$plugin", 'add_settings_link');
}


/**
 * Extract the most used colors from an image.
 *
 * @since 1.0.0
 *
 * @param string $image_url               The URL of the image.
 * @param int    $max_colors              Maximum number of colors to extract.
 * @param int    $color_similarity_threshold Similarity threshold for colors.
 *
 * @return array An array of hexadecimal color values.
 */
function extract_colors_from_image($image_url) {

    // Fetch the values from options
    $max_colors = get_option('max_colors', 5);
    $color_similarity_threshold = get_option('color_similarity_threshold', 60);

    // Load the image
    $image = imagecreatefromstring(file_get_contents($image_url));

    // Resize the image for efficient processing (you can adjust the size)
    $width = 100;
    $height = 100;
    $resizedImage = imagecreatetruecolor($width, $height);
    imagecopyresampled(
        $resizedImage,
        $image,
        0,
        0,
        0,
        0,
        $width,
        $height,
        imagesx($image),
        imagesy($image)
    );

    // Initialize an array to store pixel colors
    $pixelColors = [];

    // Extract pixel colors
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $color = imagecolorat($resizedImage, $x, $y);
            $rgb = imagecolorsforindex($resizedImage, $color);
            $pixelColors[] = $rgb;
        }
    }

    // Calculate color frequencies
    $colorFrequencies = array_count_values(
        array_map("serialize", $pixelColors)
    );

    // Sort colors by frequency in descending order
    arsort($colorFrequencies);

    // Initialize an array to store representative colors
    $representativeColors = [];

    // Iterate through colors and group similar ones
    foreach ($colorFrequencies as $color => $frequency) {
        $color = unserialize($color);

        // Check if this color is similar to any existing representative color
        $foundSimilarColor = false;
        foreach ($representativeColors as $index => $repColor) {
            if (
                color_similarity($color, $repColor) <=
                $color_similarity_threshold
            ) {
                $foundSimilarColor = true;
                break;
            }
        }

        // If not similar to any existing representative color, add it
        if (!$foundSimilarColor) {
            $representativeColors[] = $color;
        }
    }

    // Limit the number of representative colors to $max_colors
    $representativeColors = array_slice($representativeColors, 0, $max_colors);

    // Cleanup
    imagedestroy($image);
    imagedestroy($resizedImage);

    // Convert to hex format
    $hexColors = [];
    foreach ($representativeColors as $color) {
        $hexColors[] = sprintf(
            "#%02x%02x%02x",
            $color["red"],
            $color["green"],
            $color["blue"]
        );
    }

    return $hexColors;
}

/**
 * Enqueue CSS for color palette.
 *
 * @since 1.0.0
 */
function enqueue_color_palette_styles()
{
    wp_enqueue_style(
        "color-palette-styles",
        plugins_url("css/color-palette.css", __FILE__)
    );
}

add_action("wp_enqueue_scripts", "enqueue_color_palette_styles");

/**
 * Enqueue JavaScript for SVG download with post slug.
 *
 * @since 1.0.0
 */
function enqueue_svg_download_script()
{
    wp_enqueue_script(
        "svg-download-script",
        plugins_url("js/svg-download.js", __FILE__),
        ["jquery"],
        "1.0",
        true
    );

    // Get the post slug
    $post_slug = get_post_field('post_name', get_post());

    // Pass the plugin URL, nonce, and post slug to JavaScript
    wp_localize_script("svg-download-script", "svgDownload", [
        "pluginUrl" => plugins_url("", __FILE__),
        "nonce" => wp_create_nonce("svg-download-nonce"),
        "postSlug" => $post_slug, // Pass the post slug to JavaScript
    ]);
}

add_action("wp_enqueue_scripts", "enqueue_svg_download_script");

/**
 * Modify the post content to extract and display a color palette and SVG download link.
 *
 * This function checks if the current post is a single post with a thumbnail (featured image).
 * If so, it extracts the most used colors from the thumbnail, generates a color palette, and adds
 * an SVG download link to the post content.
 *
 * @since 1.0.0
 *
 * @param string $content The content of the post.
 *
 * @return string The modified post content with the color palette and download link.
 */
function extract_and_display_color_palette($content)
{
    global $post;

    // Check if this is a single post and has a thumbnail.
    if (is_single() || is_page() && has_post_thumbnail($post->ID)) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $thumbnail_url = wp_get_attachment_url($thumbnail_id);

        // Extract the file name from the featured image URL.
        $thumbnail_filename = pathinfo($thumbnail_url, PATHINFO_FILENAME);

        // Extract the most used colors from the thumbnail.
        $colors = extract_colors_from_image($thumbnail_url, 5);

        if (!empty($colors)) {
            $color_palette_html = '<div class="ace-color-palette-wrapper"><div class="ace-color-palette">';
            foreach ($colors as $color) {
                $color_palette_html .=
                    '<div class="ace-color-square" style="background-color:' .
                    $color .
                    ';"></div>';
            }
            $color_palette_html .= "</div>";

            // Get the post slug.
            $post_slug = sanitize_title_with_dashes($post->post_name);

            // Create an SVG download link with the post slug as the filename.
            $svg_filename = $post_slug . ".svg"; // Use post slug as the filename.
            $svg_download_link =
                '<a href="#" class="svg-download-link" data-filename="' .
                esc_attr($svg_filename) .
                '">' . __('Download Palette (SVG)', 'ace-color-extractor') . '</a></div>';

           // Add the SVG download link.
           $content = $color_palette_html . $svg_download_link . $content;
        }
    }

    return $content;
}

/**
 * Calculate the similarity between two colors in CIE76 color space.
 *
 * @since 1.1.0
 *
 * @param array $color1 The first color.
 * @param array $color2 The second color.
 *
 * @return float The color similarity in CIE76 space.
 */
function color_similarity_cie76($color1, $color2)
{
    // Convert RGB to Lab color space
    $lab1 = rgb_to_lab($color1);
    $lab2 = rgb_to_lab($color2);

    // Calculate the differences in L*, a*, and b* values
    $deltaL = $lab1['L'] - $lab2['L'];
    $deltaA = $lab1['a'] - $lab2['a'];
    $deltaB = $lab1['b'] - $lab2['b'];

    // Calculate the CIE76 color difference
    return sqrt($deltaL * $deltaL + $deltaA * $deltaA + $deltaB * $deltaB);
}

/**
 * Helper function to convert RGB to Lab color space.
 *
 * @since 1.1.0
 *
 * @param array $color The color in RGB format.
 *
 * @return array The color in Lab format.
 */
function rgb_to_lab($color)
{
    $r = $color['red'] / 255.0;
    $g = $color['green'] / 255.0;
    $b = $color['blue'] / 255.0;

    // Convert RGB to XYZ color space
    $r = ($r > 0.04045) ? pow(($r + 0.055) / 1.055, 2.4) : $r / 12.92;
    $g = ($g > 0.04045) ? pow(($g + 0.055) / 1.055, 2.4) : $g / 12.92;
    $b = ($b > 0.04045) ? pow(($b + 0.055) / 1.055, 2.4) : $b / 12.92;

    $r *= 100.0;
    $g *= 100.0;
    $b *= 100.0;

    $x = $r * 0.4124564 + $g * 0.3575761 + $b * 0.1804375;
    $y = $r * 0.2126729 + $g * 0.7151522 + $b * 0.0721750;
    $z = $r * 0.0193339 + $g * 0.1191920 + $b * 0.9503041;

    // Convert XYZ to Lab color space
    $x /= 95.047;
    $y /= 100.000;
    $z /= 108.883;

    $x = ($x > 0.008856) ? pow($x, 1.0 / 3.0) : (903.3 * $x + 16.0) / 116.0;
    $y = ($y > 0.008856) ? pow($y, 1.0 / 3.0) : (903.3 * $y + 16.0) / 116.0;
    $z = ($z > 0.008856) ? pow($z, 1.0 / 3.0) : (903.3 * $z + 16.0) / 116.0;

    $lab['L'] = max(0.0, min(100.0, 116.0 * $y - 16.0));
    $lab['a'] = max(-128.0, min(127.0, 500.0 * ($x - $y)));
    $lab['b'] = max(-128.0, min(127.0, 200.0 * ($y - $z)));

    return $lab;

}

/**
 * Calculate the similarity between two colors.
 *
 * @since 1.0.0
 *
 * @param array $color1 The first color.
 * @param array $color2 The second color.
 *
 * @return float The color similarity.
 */
function color_similarity($color1, $color2)
{
    return color_similarity_cie76($color1, $color2);
}

/**
 * Shortcode to display the color palette.
 *
 * @since 1.0.0
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content The content inside the shortcode.
 *
 * @return string The color palette HTML.
 */
function display_color_palette_shortcode($atts, $content = null) {
    if (function_exists('extract_and_display_color_palette')) {
        $color_palette = extract_and_display_color_palette($content);
        return $color_palette;
    } else {
        return ''; // Return an empty string if the function doesn't exist
    }
}
add_shortcode('ace_palette', 'display_color_palette_shortcode');
