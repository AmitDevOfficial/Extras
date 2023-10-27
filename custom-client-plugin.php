        <?php
        /*
        Plugin Name: Custom Client Plugin
        Description: Custom client page template with JavaScript features.
        Version: 1.0
        Author: < @  @ > 
        */

        function enqueue_custom_style() {
        wp_enqueue_style('custom-style', get_template_directory_uri() . '.style.css', array(), '1.0', 'all');
         }
        add_action('wp_enqueue_scripts', 'enqueue_custom_style');

        
        
    

        function custom_post_type() {
            $labels = array(
                'name' => 'Custom Clients Tabs',
                'singular_name' => 'Custom Post',
            );

            $args = array(
                'labels' => $labels,
                'public' => true,
                'has_archive' => true,
                'rewrite' => array('slug' => 'custom-posts'),
                'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
                'taxonomies' => array('custom_category'),
            );

            register_post_type('custom_post', $args);
        }
        add_action('init', 'custom_post_type');

        

        function custom_taxonomy() {
            $labels = array(
                'name' => 'Custom Categories',
                'singular_name' => 'Custom Category',
                'search_items' => 'Search Custom Categories',
                'all_items' => 'All Custom Categories',
                'parent_item' => 'Parent Custom Category',
                'parent_item_colon' => 'Parent Custom Category:',
                'edit_item' => 'Edit Custom Category',
                'update_item' => 'Update Custom Category',
                'add_new_item' => 'Add New Custom Category',
                'new_item_name' => 'New Custom Category Name',
                'menu_name' => 'Custom Categories',
            );

            $args = array(
                'hierarchical' => true, // Set to true if you want a category hierarchy.
                'labels' => $labels,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'custom-category'),
            );

            register_taxonomy('custom_category', 'custom_post', $args);
        }
        add_action('init', 'custom_taxonomy');

        
// ------------------------Add category and display---------------------------------


// ----------------------------------End Categeroy------------------------------------

        function add_custom_meta_box() {
            add_meta_box(
                'custom-meta-box',
                'Multiple Meta Box Fields',
                'render_custom_meta_box',
                'custom_post', // Change to your custom post type name
                'normal',
                'default'
            );
        }
        add_action('add_meta_boxes', 'add_custom_meta_box');

    function render_custom_meta_box($post) {
    $custom_fields = get_post_meta($post->ID, '_custom_fields', true);
    ?>

    <div class="custom-meta-box">
        <ul id="meta-fields">
            <?php
            $field_counter = 0;

            if ($custom_fields) {
                foreach ($custom_fields as $field) {
                    if (!empty($field['label']) || !empty($field['text']) || !empty($field['image']) || !empty($field['number'])) {
                        $field_counter++;
                        ?>
                        <li>
                            <input type="text" name="custom_label[]" value="<?php echo esc_attr($field['label']); ?>" placeholder="Label">
                            <input type="text" name="custom_text[]" value="<?php echo esc_attr($field['text']); ?>" placeholder="Text">
                            <input type="text" class="custom_image" name="custom_image[]" value="<?php echo esc_url($field['image']); ?>" placeholder="Image URL">
                            <button class="upload-image-button">Upload Image</button>
                            <input type="number" name="custom_number[]" value="<?php echo esc_attr($field['number']); ?>" placeholder="Number">
                            <button class="remove-meta-field">Remove</button>
                        </li>
                        <?php
                    }
                }
            }
            ?>
        </ul>
        <button id="add-meta-field">Add Meta Field</button>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Handle the image upload button
            $('.custom-meta-box').on('click', '.upload-image-button', function(e) {
                e.preventDefault();

                var imageInput = $(this).prev('.custom_image');
                var imageFrame;

                if (imageFrame) {
                    imageFrame.open();
                    return;
                }

                imageFrame = wp.media({
                    title: 'Choose or Upload an Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });

                imageFrame.on('select', function() {
                    var attachment = imageFrame.state().get('selection').first().toJSON();
                    imageInput.val(attachment.url);
                });

                imageFrame.open();
            });

            $('#add-meta-field').click(function() {
                $('#meta-fields').append(`
                    <li>
                        <input type="text" name="custom_label[]" placeholder="Label">
                        <input type="text" name="custom_text[]" placeholder="Text">
                        <input type="text" class="custom_image" name="custom_image[]" placeholder="Image URL">
                        <button class="upload-image-button">Upload Image</button>
                        <input type="number" name="custom_number[]" placeholder="Number">
                        <button class="remove-meta-field">Remove</button>
                    </li>
                `);
                return false;
            });

            $('#meta-fields').on('click', '.remove-meta-field', function() {
                $(this).parent().remove();
            });
        });
    </script>
    <?php
}

        
function save_custom_meta_box($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $custom_fields = array();

    // Check if the fields have been submitted
    if (isset($_POST['custom_text'])) {
        $text_values = $_POST['custom_text'];
        $image_values = $_POST['custom_image'];
        $number_values = $_POST['custom_number'];
        $label_values = $_POST['custom_label'];

        // Initialize the new custom fields array
        $new_fields = array();

        foreach ($text_values as $key => $text) {
            if (!empty($text) || !empty($image_values[$key]) || !empty($number_values[$key]) || !empty($label_values[$key])) {
                $new_fields[] = array(
                    'text' => sanitize_text_field($text),
                    'image' => esc_url($image_values[$key]),
                    'label' => sanitize_text_field($label_values[$key]),
                    'number' => intval($number_values[$key]),
                );
            }
        }

        $custom_fields = $new_fields;
    }

    // Update the post meta with the new custom fields
    update_post_meta($post_id, '_custom_fields', $custom_fields);
}
add_action('save_post', 'save_custom_meta_box');





       function custom_clients_tabs_shortcode($atts) {
        ob_start(); // Start an output buffer to capture the shortcode content.

        $args = shortcode_atts(array(
            'posts_per_page' => -1, // Change this to control the number of posts to display.
        ), $atts);

        $custom_query = new WP_Query(array(
            'post_type' => 'custom_post', // Change to your custom post type name.
            'posts_per_page' => $args['posts_per_page'],
        ));

        if ($custom_query->have_posts()) {
            while ($custom_query->have_posts()) {
                $custom_query->the_post();


               $post_categories = get_the_terms(get_the_ID(), 'custom_category');

            if ($post_categories && !is_wp_error($post_categories)) {
                echo '<div class="post-categories">';
                foreach ($post_categories as $category) {
                    $category_link = get_term_link($category);
                    echo '<a href="' . esc_url($category_link) . '">' . esc_html($category->name) . '</a>';
                }
                echo '</div>';
            }

           



                echo '<center><h2>' . get_the_title() . '</h2>' . '<div>' . get_the_content() . '</div>' . '<div>' . get_the_post_thumbnail() . '</div><center>';

                // Retrieve and display meta box field values
                $custom_fields = get_post_meta(get_the_ID(), '_custom_fields', true); // Use '_custom_fields' consistently
    if (!empty($custom_fields)) {
        // Calculate the progress value based on a specific field (e.g., 'number' field)
        $progress_value = 0;
        foreach ($custom_fields as $field) {
            if (!empty($field['number'])) {
                $progress_value += intval($field['number']);
            }
        }

        // Ensure the maximum value is reasonable (e.g., 100)
        $max_value = 100;

        // Calculate the percentage
        $percentage = ($progress_value / $max_value) * 100;

        // Display the progress bar
echo '<progress id="file" value="' . $progress_value . '" max="' . $max_value . '"> ' . $percentage . '% </progress>';  

        // Display other field values if needed
        foreach ($custom_fields as $field) {
            if (!empty($field['label']) || !empty($field['text']) || !empty($field['image']) || !empty($field['number'])) {
                echo '<div>';
                echo '<br>'; // Display the label
                if (!empty($field['label']) && !empty($field['text'])) {
                    echo '<strong>' . esc_html($field['label']) . ':</strong> ' . esc_html($field['text']) . '<br>';
                }
                if (!empty($field['label']) && !empty($field['number'])) {
                    echo '<strong>' . esc_html($field['label']) . ':</strong> ' . esc_html($field['number']) . '<br>';
                }
                if (!empty($field['image'])) {
                    echo '<img src="' . esc_url($field['image']) . '" alt="Custom Image"><br>'; // Display the image
                }
                echo '</div>';
            }
        }
    }

            }
        } else {
            echo 'No custom clients tabs found.';
        }

        wp_reset_postdata();
        return ob_get_clean(); // Return the captured output.
    }
    add_shortcode('custom_clients_tabs', 'custom_clients_tabs_shortcode');


            
        ?>




