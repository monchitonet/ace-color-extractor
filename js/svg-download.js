/**
 * @summary Initializes a click event handler for SVG download links.
 *
 * @since 1.0.0
 *
 * @listens click
 * @param {Event} e - The click event object.
 */
jQuery(document).ready(function($) {
    $('.svg-download-link').on('click', function(e) {
        e.preventDefault();

        /**
         * Create a new SVG element containing the color palette.
         *
         * @type {string}
         */

        var svgWidth = $('.color-square').length * 20; // Calculate the total width

        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + svgWidth + '" height="20">';
        //var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="20">';

        $('.color-square').each(function() {
            var color = $(this).css('background-color');

            /**
             * Calculate the x position based on the square's index.
             *
             * @type {number}
             */
            var x = $(this).index() * 20; // Adjust this based on your design.

            svg += '<rect x="' + x + '" width="20" height="20" fill="' + color + '" />';
        });

        svg += '</svg>';

        /**
         * Get the post slug from the localized script data.
         *
         * @type {string}
         */
        var postSlug = svgDownload.postSlug;

        /**
         * Create a Blob from the SVG content.
         *
         * @type {Blob}
         */
        var blob = new Blob([svg], { type: 'image/svg+xml' });

        /**
         * Create a download link and trigger a click event to initiate the download.
         *
         * @type {HTMLAnchorElement}
         */
        var link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);

        /**
         * Set the filename for the download link.
         *
         * @type {string}
         */
        link.download = postSlug ? postSlug + '-palette.svg' : 'palette.svg'; // Use post slug or default name.

        link.click();
    });
});
