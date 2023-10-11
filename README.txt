=== ACE Color Extractor ===
Contributors: leogg
Tags: color palette, svg
Requires at least: 5.8
Tested up to: 6.3.1
Requires PHP: 5.6
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Extract and display the most used colors from post thumbnails.

== Description ==

You can add the color palette to your posts in two ways:

1. Adding the shortcode [ace_palette] in your post.

2. Adding the following PHP code to your custom template, inside PHP tags:

// Display the color palette extracted by the plugin.
if( function_exists('extract_and_display_color_palette')) {
	$color_palette = extract_and_display_color_palette($content);
	echo $color_palette;
} else {
	// do nothing
}

This will add both the palette and the download link to your post.

= Links =
* [Website](https://logosnicas.com)

== Changelog ==

= 1.1.0 =
* Improved color similarity calculation using the CIE76 (ΔE 1976) formula.
* Added functions for CIE76 color similarity and conversion from RGB to Lab color space.
* Updated the color extraction process to use CIE76 for more accurate perceptual similarity.
* Improved perceptual similarity threshold for color grouping.
* Enhanced color extraction accuracy for better color palette generation.

= 1.0.0 =
* Initial release of ACE Color Extractor plugin.
* Extract and display the most used colors from post thumbnails.
* Supports color grouping and palette generation.
* SVG download link with the post title as the filename.
* Customizable color similarity threshold.
