<?php
/*
Plugin Name: WP Scheduled Links
Plugin URL: http://fahmiadib.com/plugins/wp-scheduled-links/
Description: Schedule when link(s) should appear on your post/page
Version: 1.02
Author: Fahmi Adib
Author URI: http://fahmiadib.com
Contributors: fahmiadib
*/

/**
 * Process the content
 */
function wpsl_filter_content( $content ) {
    global $post;
    $show_date = get_post_meta( $post->ID, '_wpsl', true );
    if ( $show_date && is_main_query() ) {
        if ( time() < strtotime( $show_date ) ) {
            $content = preg_replace( "|<a(.*?)[^>]*>|", "", $content );
        }
    }
	return $content;
}
add_filter( 'the_content', 'wpsl_filter_content' );

/**
 * Adds a box to the main column on the Post and Page edit screens.
 */
function wpsl_add_meta_box() {
	$screens = array( 'post', 'page' );
	foreach ( $screens as $screen ) {
		add_meta_box(
			'wpsl_sectionid',
			__( 'WP Scheduled Links', 'wpsl_textdomain' ),
			'wpsl_meta_box_callback',
			$screen,
            'side',
            'high'
		);
	}

    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui-style', plugins_url('css/jquery-ui.css', __FILE__) );
}
add_action( 'add_meta_boxes', 'wpsl_add_meta_box' );

/**
 * Prints the box content.
 * 
 * @param WP_Post $post The object for the current post/page.
 */
function wpsl_meta_box_callback( $post ) {

	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'wpsl_meta_box', 'wpsl_meta_box_nonce' );

	/*
	 * Use get_post_meta() to retrieve an existing value
	 * from the database and use the value for the form.
	 */
	$value = get_post_meta( $post->ID, '_wpsl', true );
    if ( !$value ) {
        $value = date('n/j/Y');
    }

	echo '<label for="wpsl_date">';
	_e( 'Show the link(s) on & after:', 'wpsl_textdomain' );
	echo '</label> ';
	echo '<input type="text" id="wpsl_new_field" name="wpsl_date" value="' . esc_attr( $value ) . '" size="25" />';
    echo '
        <script>
        jQuery().ready(function(){
            jQuery(\'#wpsl_new_field\').datepicker({
                dateFormat : \'m/d/yy\'
            });
        });
        </script>
    ';
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function wpsl_save_meta_box_data( $post_id ) {

	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */

	// Check if our nonce is set.
	if ( ! isset( $_POST['wpsl_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['wpsl_meta_box_nonce'], 'wpsl_meta_box' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	/* OK, its safe for us to save the data now. */
	
	// Make sure that it is set.
	if ( ! isset( $_POST['wpsl_date'] ) ) {
		return;
	}

	// Sanitize user input.
	$my_data = sanitize_text_field( $_POST['wpsl_date'] );

    // Make sure at least it has 2 '/'
    $exp_my_data = explode( '/', $my_data );
    if ( $exp_my_data && count( $exp_my_data ) < 3 ) {
        return;
    }

	// Update the meta field in the database.
	update_post_meta( $post_id, '_wpsl', $my_data );
}
add_action( 'save_post', 'wpsl_save_meta_box_data' );
