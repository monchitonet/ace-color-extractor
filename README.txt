=== ACE Color Extractor ===
Contributors: leogg
Tags: color palette, svg
Requires at least: 5.8
Tested up to: 6.3.2
Requires PHP: 5.6
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Extract and display the most used colors from post thumbnails.

== Description ==

You can add the color palette to your posts in two ways:

1. Adding the shortcode [ace_palette] in your post.

2. Adding the following PHP code to your custom template, inside PHP tags:

// Display the color palette extracted by the plugin.
if( function_exists('extract_and_display_color_palette')) {
	$color_palette = extract_and_display_color_palette('');
	echo $color_palette;
} else {
	// do nothing
}

This will add both the palette and the download link to your post.

3. You can adjust the number of colors to extract from the image and the colors similarity threshold in the options page in Settings > ACE Color Extractor

= Links =
* [Website](https://logosnicas.com)

== Changelog ==

= 1.2.0 =

* Added an options page to configure the plugin's settings.
* Adjusted the width of the downloadable SVG file based on the number of color squares.
* Code cleanup and other small fixes and additions.

= 1.1.1 =

* Added better inline documentation.
* Added support for internationalization.
* Updated the code to properly detect the post slug and use it as the filename for the downloaded SVG.

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
