<?php
/*
Plugin Name: ACE Color Extractor
Description: Extract and display the most used colors from post thumbnails.
Version: 1.0
Author: LogosNicas x AI
Author URI: https://logosnicas.com/
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Define the extract_colors_from_image() function here.
function extract_colors_from_image(
    $image_url,
    $max_colors = 5,
    $color_similarity_threshold = 80
) {
    // Function to extract the most used colors from an image.

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

// Enqueue CSS
function enqueue_color_palette_styles()
{
    wp_enqueue_style(
        "color-palette-styles",
        plugins_url("css/color-palette.css", __FILE__)
    );
}

add_action("wp_enqueue_scripts", "enqueue_color_palette_styles");

// Enqueue JavaScript for SVG download with filename
function enqueue_svg_download_script()
{
    wp_enqueue_script(
        "svg-download-script",
        plugins_url("js/svg-download.js", __FILE__),
        ["jquery"],
        "1.0",
        true
    );

    // Pass the plugin URL and nonce to JavaScript
    wp_localize_script("svg-download-script", "svgDownload", [
        "pluginUrl" => plugins_url("", __FILE__),
        "nonce" => wp_create_nonce("svg-download-nonce"),
    ]);
}

add_action("wp_enqueue_scripts", "enqueue_svg_download_script");

// Modify the existing extract_and_display_color_palette() function
function extract_and_display_color_palette($content)
{
    global $post;

    // Check if this is a single post and has a thumbnail.
    if (is_single() && has_post_thumbnail($post->ID)) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $thumbnail_url = wp_get_attachment_url($thumbnail_id);

        // Extract the file name from the featured image URL.
        $thumbnail_filename = pathinfo($thumbnail_url, PATHINFO_FILENAME);

        // Extract the most used colors from the thumbnail.
        $colors = extract_colors_from_image($thumbnail_url, 5);

        if (!empty($colors)) {
            $color_palette_html = '<div class="color-palette-wrapper"><div class="color-palette">';
            foreach ($colors as $color) {
                $color_palette_html .=
                    '<div class="color-square" style="background-color:' .
                    $color .
                    ';"></div>';
            }
            $color_palette_html .= "</div>";

            // Create an SVG download link with the post title as the filename.
            $post_title = get_the_title();
            $svg_filename = sanitize_title_with_dashes($post_title) . ".svg"; // Sanitize and add .svg extension.
            $svg_download_link =
                '<a href="#" class="svg-download-link" data-filename="' .
                esc_attr($svg_filename) .
                '">Download Palette (SVG)</a></div>';

            // Add the SVG download link.
            $content = $color_palette_html . $svg_download_link . $content;
        }
    }

    return $content;
}

// Define a function to calculate the similarity between two colors
function color_similarity($color1, $color2)
{
    return abs($color1["red"] - $color2["red"]) +
        abs($color1["green"] - $color2["green"]) +
        abs($color1["blue"] - $color2["blue"]);
}

//Add a shortcode for the function
function display_color_palette_shortcode($atts, $content = null) {
    if (function_exists('extract_and_display_color_palette')) {
        $color_palette = extract_and_display_color_palette($content);
        return $color_palette;
    } else {
        return ''; // Return an empty string if the function doesn't exist
    }
}
add_shortcode('ace_palette', 'display_color_palette_shortcode');
