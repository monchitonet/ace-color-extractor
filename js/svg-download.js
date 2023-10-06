jQuery(document).ready(function($) {
    $('.svg-download-link').on('click', function(e) {
        e.preventDefault();

        // Create a new SVG element containing the color palette.
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="20">';
        $('.color-square').each(function() {
            var color = $(this).css('background-color');
            var x = $(this).index() * 20; // Adjust this based on your design.
            svg += '<rect x="' + x + '" width="20" height="20" fill="' + color + '" />';
        });
        svg += '</svg>';

        // Get the post title and convert it to lowercase with hyphens instead of spaces.
        var postTitle = $('h1.entry-title').text().trim().toLowerCase().replace(/\s+/g, '-');

        // Create a Blob from the SVG content.
        var blob = new Blob([svg], { type: 'image/svg+xml' });

        // Create a download link and trigger a click event to initiate the download.
        var link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);

        // Set the filename for the download link.
        link.download = postTitle ? postTitle + '-palette.svg' : 'palette.svg'; // Use post title or default name.

        link.click();
    });
});

