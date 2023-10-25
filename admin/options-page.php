<?php
/**
 * ACE Color Extractor Options Page
 *
 * @since 1.2.0
 */

// Ensure this file is not accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define the options page.
 *
 * This function sets up the HTML structure and content for the options page.
 *
 * @since 1.2.0
 */
function ace_color_extractor_options_page() {

     // Define the URL of your custom stylesheet
     $stylesheet_url = plugins_url('css/admin-style.css', __FILE__); // Path to the stylesheet in the 'css' folder within the 'admin' folder

     // Enqueue the stylesheet
     wp_enqueue_style('ace-color-extractor-admin-style', $stylesheet_url, array(), '1.0.0');

    // Check if the form has been submitted
    if (isset($_POST['submit'])) {
        // Form was submitted, process the data
        if (isset($_POST['max_colors'])) {
            // Update the 'max_colors' option
            update_option('max_colors', intval($_POST['max_colors']));

            // Validate and sanitize 'color_similarity_threshold' value
            $color_similarity_threshold = intval($_POST['color_similarity_threshold']);
            $color_similarity_threshold = max(0, min(100, $color_similarity_threshold)); // Ensure it's between 0 and 100

            // Update the 'color_similarity_threshold' option
            update_option('color_similarity_threshold', $color_similarity_threshold);

            // Set an admin notice transient
            set_transient('ace_color_extractor_admin_notice', 'Options updated!', 5); // Set the transient for 5 seconds

        }
    }
    ?>
    <div class="wrap">
        <h2><?php esc_html_e('ACE Color Extractor Options', 'ace-color-extractor'); ?></h2>
        <form method="post" action="">
            <?php settings_fields('ace-color-extractor-settings-group'); ?>
            <?php do_settings_sections('ace-color-extractor-settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="max_colors"><?php esc_html_e('Palette maximum colors', 'ace-color-extractor'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="max_colors" name="max_colors" value="<?php echo esc_attr(get_option('max_colors', 5)); ?>" />
                        <p><?php esc_html_e('Set the maximum number of colors to extract from the image (e.g., 5).', 'ace-color-extractor'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="color_similarity_threshold"><?php esc_html_e('Color Similarity Threshold', 'ace-color-extractor'); ?></label>
                    </th>
                    <td>
                        <div class="similarity-slider">
                             <input type="range" id="color_similarity_threshold" name="color_similarity_threshold" value="<?php echo esc_attr(get_option('color_similarity_threshold', 60)); ?>" />
                             <span id="slider-value">0%</span>
                        </div>
                        <p><?php esc_html_e('Set the similarity threshold for colors (0% to 100%) when extracting representative colors from the image.', 'ace-color-extractor'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button( __('Save Changes', 'ace-color-extractor'), 'primary', 'submit' ); ?>
        </form>
    </div>
    <script>
    // Display the current value of the dynamic range slider
    document.addEventListener('DOMContentLoaded', function () {
         var slider = document.getElementById('color_similarity_threshold');
         var sliderValue = document.getElementById('slider-value');

         // Initialize the value display with the initial slider value
         sliderValue.textContent = slider.value + '%';

         // Update the value display as the slider value changes
         slider.addEventListener('input', function () {
             sliderValue.textContent = this.value + '%';
         });
     });
    </script>
    <?php
    // Display the admin notice if the transient is set
    if ($notice = get_transient('ace_color_extractor_admin_notice')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($notice); ?></p>
        </div>
        <?php
        // Clear the transient to ensure the notice is not displayed again
        delete_transient('ace_color_extractor_admin_notice');
    }
}

// Register the setting to validate and sanitize 'color_similarity_threshold'
function ace_color_extractor_register_settings() {
    register_setting('ace-color-extractor-settings-group', 'color_similarity_threshold', 'sanitize_color_similarity_threshold');
}

// Hook the setting registration
add_action('admin_init', 'ace_color_extractor_register_settings');

// Validation and sanitization function for 'color_similarity_threshold'
function sanitize_color_similarity_threshold($input) {
    $input = intval($input);
    $input = max(0, min(100, $input)); // Ensure it's between 0 and 100
    return $input;
}


/**
 * Add the ACE Color Extractor options page to the admin menu.
 *
 * This function hooks the options page into the WordPress admin menu under the "Tools" section.
 *
 * @since 1.2.0
 */
function ace_color_extractor_menu() {
    add_options_page(
        esc_html__('ACE Color Extractor', 'ace-color-extractor'), // Page title
        esc_html__('ACE Color Extractor', 'ace-color-extractor'), // Menu title
        'manage_options',                     // Capability
        'ace-color-extractor-options',        // Menu slug
        'ace_color_extractor_options_page'    // Callback function
    );
}

// Hook the menu creation function
add_action('admin_menu', 'ace_color_extractor_menu');
