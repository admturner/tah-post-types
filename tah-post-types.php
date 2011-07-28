<?php
/*
Plugin Name: TAH Post Types
Plugin URI: http://adamturner.org/
Description: Three custom post types for the CHNM TAH websites. Customized posts for Lessons, Podcasts, and Primary Source Activities.
Author: Adam Turner
Version: 0.5.0
Author URI: http://adamturner.org/
*/

/**
 * Credits: Adapted from the excellent tutorial by Konstantin Kovshenin
 *
 * @todo [might be fixed now] Bug fix: Sortable columns function and/or filter causing posts table inline editing to fail on return to table after clicking save changes. Changes are saved, but doesn't return to table.
 */

include_once 'tah-post-filters.php';
include_once 'tah-post-types-public.php';

class tah_lessons {
	var $meta_fields = array(
		'tah_lesson_author',
		'tah_school',
		'tah_duration',
		'tah_unit',
		'tah_overview',
		'tah_objective',
		'tah_materials',
		'tah_procedure',
		'tah_assessment',
		'tah_references'
		);
	
	function tah_lessons() {
		register_post_type('lessons', array(
			'labels' => array(
				'name' => __('Lessons'),
				'singular_name' => __('Lesson'),
				'all_items' => __('All Lessons'),
				'add_new_item' => __('Add New Lesson'),
				'edit_item' => __('Edit Lesson'),
				'new_item' => __('New Lesson'),
				'view_item' => __('View Lesson'),
				'search_items' => __('Search Lessons'),
				'not_found' => __('No lessons found'),
				'not_found_in_trash' => __('No lessons found in trash')
			),
			'public' => true,
			'hierarchical' => false,
			'rewrite' => array('slug' => 'lessons'),
			'supports' => array('title','author', 'editor', 'thumbnail', 'trackbacks', 'comments', 'revisions', 'page-attributes' /*,'custom-fields'*/)
		));
		
		// Add for posts table columns, make them sortable, then add action for table contents
		add_filter( 'manage_edit-lessons_columns', array( &$this, 'lessons_columns_title_fn' ) );
		add_filter( 'manage_edit-lessons_sortable_columns', array( &$this, 'lessons_columns_title_sort_fn' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'lessons_custom_columns_control_fn' ) );
		
		// Register custom taxonomies (second array lists the custom posts types these will apply to
		register_taxonomy( 'gradelevel', array('lessons'), array(
			'hierarchical' => true, 
			'labels' => array('name' => _x('Grade Levels', 'grade levels general name'), 'singular_name' => _x('Grade Level', 'grade level singular name'), 'search_items' => __('Search Grade Levels'), 'parent_item' => __('Parent Grade'), 'edit_item' => __('Edit Grade Level Category'), 'add_new_item' => __('Add New Grade Level'), 'separate_items_with_commas' => __('Separate grades with commas')),
			'rewrite' => true) );
		register_taxonomy( 'timeperiod', array('lessons'), array(
			'hierarchical' => true,
			'labels' => array('name' => _x('Time Periods', 'time periods general name'), 'singular_name' => _x('Time Period', 'time period singular name'), 'search_items' => __('Search Time Periods'), 'parent_item' => __('Parent Period'), 'edit_item' => __('Edit Time Period Category'), 'add_new_item' => __('Add New Time Period'), 'separate_items_with_commas' => __('Separate times with commas')),
			'rewrite' => true) );

		// Admin interface init
		add_action( 'add_meta_boxes', array( &$this, 'tah_lessons_meta_box_fn') );
		// make backwards compatible
		add_action( 'admin_init', array( &$this, 'tah_lessons_meta_box_fn'), 1 );
		add_action('admin_print_scripts', array( &$this, 'tah_lessons_admin_script_fn' ) );
		add_action('admin_print_styles', array( &$this, 'tah_lessons_admin_style_fn' ) );
		
		// Insert post hook
		add_action( 'wp_insert_post', array( &$this, 'wp_insert_post' ), 10, 2 );
	}
	
	function lessons_columns_title_fn( $columns ) {
		$columns = array(
			'cb' => '<input type=\'checkbox\' />',
			'title' => _x( 'Lesson Title', 'lessons column name' ),
			'lesson_author' => __( 'Lesson Author' ),
			'lesson_grade' => __( 'Grade Level(s)' ),
			'lesson_period' => __( 'Time Period(s)' ),
			'lesson_date' => __( 'Date' )
		);		
		return $columns;
	}

	function lessons_custom_columns_control_fn( $column ) {
		global $post;
		switch ( $column ) {
			case 'lesson_author' :
				$custom = get_post_custom($post->ID);
				echo $custom["tah_lesson_author"][0];
				break;
			case 'lesson_grade' :
				echo get_the_term_list($post->ID, 'gradelevel', '', ', ', '');
				break;
			case 'lesson_period' :
				echo get_the_term_list($post->ID, 'timeperiod', '', ', ', '');
				break;
			case 'lesson_date' :
				echo get_the_date( __( 'Y/m/d' ) ) . '<br />';
				if ( 'publish' == $post->post_status ) {
					_e( 'Published' );
				} elseif ( 'future' == $post->post_status ) {
					if ( $time_diff > 0 )
					echo '<strong class="attention">' . __( 'Missed schedule' ) . '</strong>';
				else
					_e( 'Scheduled' );
				} else {
					_e( 'Last Modified' );
				}
				break;
		}
	}
	
	function lessons_columns_title_sort_fn( $sort_columns ) {
		$sortables = array(
			'lesson_author' => 'lesson_author',
			'lesson_grade'  => 'lesson_grade',
			'lesson_period' => 'lesson_period'
		);
		return wp_parse_args( $sortables, $sort_columns );
	}
	
	// When a post is added or updated
	function wp_insert_post($post_id, $post = null) {
		// verify nonce field for security
		if ( !empty($_POST) && check_admin_referer( 'tah_lessons_meta_submit', 'lessons_meta_nonce_field' ) ) {
			if ( $post->post_type == 'lessons' ) {
				// Loop through the POST data
				foreach ( $this->meta_fields as $key ) {
					$value = @$_POST[$key];
					if ( empty($value) ) {
						delete_post_meta($post_id, $key);
						continue;
					}
					// If value is a string it should be unique
					if ( !is_array($value) ) {
						// Update meta
						if ( !update_post_meta($post_id, $key, $value) ) {
							// Or add the meta data
							add_post_meta( $post_id, $key, $value );
						}
					} else {
						// If passed along is an array, we should remove all previous data
						delete_post_meta( $post_id, $key );
						
						// Loop through the array adding new values to the post meta as different entries with the same name
						foreach ( $value as $entry )
							update_post_meta( $post_id, $key, $entry );
					}
				}
			}
		}
	}
	
	function tah_lessons_meta_box_fn() {
		// Custom meta boxes for the Add/Edit Lessons page
		add_meta_box( 'tah-lessons-meta', 'Lesson Meta', array( &$this, 'lessons_meta_control_fn' ), 'lessons', 'side', 'high' );
		add_meta_box( 'tah-lessons-overview', 'Lesson Overview', array( &$this, 'lessons_overview_control_fn' ), 'lessons', 'normal', 'high' );
		add_meta_box( 'tah-lessons-objective', 'Lesson Objective', array( &$this, 'lessons_objective_control_fn' ), 'lessons', 'normal', 'high' );
		add_meta_box( 'tah-lessons-materials', 'Lesson Materials', array( &$this, 'lessons_materials_control_fn' ), 'lessons', 'normal', 'high' );
		add_meta_box( 'tah-lessons-procedure', 'Lesson Procedure', array( &$this, 'lessons_procedure_control_fn' ), 'lessons', 'normal', 'high' );
		add_meta_box( 'tah-lessons-assessment', 'Lesson Assessment', array( &$this, 'lessons_assessment_control_fn' ), 'lessons', 'normal', 'high' );
		add_meta_box( 'tah-lessons-references', 'Lesson References', array( &$this, 'lessons_references_control_fn' ), 'lessons', 'normal', 'high' );
		
		// While we're at it, lets register some stuff
		wp_register_script( 'tah-lesson-script', plugins_url('/lib/tah-post-types.js', __FILE__), array('jquery'), '', true );
		wp_register_style( 'tah-lesson-style', plugins_url('/lib/tah-post-types.css', __FILE__) );
	}
	
	// Add the script
	function tah_lessons_admin_script_fn() {
		wp_enqueue_script( 'tah-lesson-script' );
	}
	function tah_lessons_admin_style_fn() {
		wp_enqueue_style( 'tah-lesson-style' );
	}
	
	// Control for the meta boxes on the Add/Edit Lessons page
	function lessons_meta_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		?>
		<p>
			<label id="tah_lesson_author_prompt_text" for="tah_lesson_author"><strong><?php _e('Author'); ?></strong></label>
			<input name="tah_lesson_author" id="tah_lesson_author" type="text" size="35" value="<?php echo $author = $custom['tah_lesson_author'][0]; ?>" />
		</p>
		<p>
			<label id="tah_school_prompt_text" for="tah_school"><strong><?php _e('School'); ?></strong></label>
			<input name="tah_school" id="tah_school" type="text" size="35" value="<?php echo $custom['tah_school'][0]; ?>" />
		</p>
		<p>
			<label id="tah_duration_prompt_text" for="tah_duration"><strong><?php _e('Duration'); ?></strong></label>
			<input name="tah_duration" id="tah_duration" type="text" size="18" value="<?php echo $custom['tah_duration'][0]; ?>" />
			<span class="description">In minutes</span>
		</p>
		<p>
			<label id="tah_unit_prompt_text" for="tah_unit"><strong><?php _e('Unit'); ?></strong></label>
			<input name="tah_unit" id="tah_unit" type="text" size="38" value="<?php echo $custom['tah_unit'][0]; ?>" />
		</p>
		
		<?php wp_nonce_field( 'tah_lessons_meta_submit', 'lessons_meta_nonce_field' ); ?>
	<?php
	}
	
	function lessons_overview_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		?>
<textarea name="tah_overview" id="tah_overview" class="mceEditor tah-lesson-textarea" rows="10" cols="40">
<?php echo $custom['tah_overview'][0]; ?>
</textarea>
		<?php wp_nonce_field( 'tah_lessons_meta_submit', 'lessons_meta_nonce_field' );
	}
	
	function lessons_objective_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		?>
<textarea name="tah_objective" id="tah_objective" class="mceEditor tah-lesson-textarea" rows="10" cols="40">
<?php echo $custom['tah_objective'][0]; ?>
</textarea>
		<?php wp_nonce_field( 'tah_lessons_meta_submit', 'lessons_meta_nonce_field' );		
	}
	
	function lessons_materials_control_fn() {
		// it would be neat if this was a single-line text box with option to add more as needed in an AJAX-y way
		global $post;
		$custom = get_post_custom($post->ID);
		?>
		<p><span class="description">Press Enter/Return twice after each item.</span></p>
<textarea name="tah_materials" id="tah_materials" class="mceEditor tah-lesson-textarea" rows="10" cols="40">
<?php echo $custom['tah_materials'][0]; ?>
</textarea>
		<?php wp_nonce_field( 'tah_lessons_meta_submit', 'lessons_meta_nonce_field' );
	}
	
	function lessons_procedure_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		?>
		<p><span class="description">Return twice to separate steps into ordered list.</span></p>
<textarea name="tah_procedure" id="tah_procedure" class="mceEditor tah-lesson-textarea" rows="20" cols="40">
<?php echo $custom['tah_procedure'][0]; ?>
</textarea>
		<?php wp_nonce_field( 'tah_lessons_meta_submit', 'lessons_meta_nonce_field' );
	}
	
	function lessons_assessment_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		?>
<textarea name="tah_assessment" id="tah_assessment" class="mceEditor tah-lesson-textarea" rows="10" cols="40">
<?php echo $custom['tah_assessment'][0]; ?>
</textarea>
		<?php wp_nonce_field( 'tah_lessons_meta_submit', 'lessons_meta_nonce_field' );
	}
	
	function lessons_references_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		?>
<textarea name="tah_references" id="tah_references" class="mceEditor tah-lesson-textarea" rows="10" cols="40">
<?php echo $custom['tah_references'][0]; ?>
</textarea>
		<?php wp_nonce_field( 'tah_lessons_meta_submit', 'lessons_meta_nonce_field' );
	}
	
	/*
	 * To do once everything is working
	 *
	 */
	 /*
	//add filter to ensure the text Book, or book, is displayed when user updates a book 
	add_filter('post_updated_messages', 'codex_book_updated_messages');
	function codex_book_updated_messages( $messages ) {
		global $post, $post_ID;
		
		$messages['book'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __('Book updated. <a href="%s">View book</a>'), esc_url( get_permalink($post_ID) ) ),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __('Book updated.'),
			// translators: %s: date and time of the revision
			5 => isset($_GET['revision']) ? sprintf( __('Book restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Book published. <a href="%s">View book</a>'), esc_url( get_permalink($post_ID) ) ),
			7 => __('Book saved.'),
			8 => sprintf( __('Book submitted. <a target="_blank" href="%s">Preview book</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __('Book scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview book</a>'),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __('Book draft updated. <a target="_blank" href="%s">Preview book</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);

		return $messages;
	}
	
	//display contextual help for Books
	add_action( 'contextual_help', 'codex_add_help_text_fn', 10, 3 );
	
	function codex_add_help_text_fn($contextual_help, $screen_id, $screen) { 
		//$contextual_help .= var_dump($screen); // use this to help determine $screen->id
		if ('book' == $screen->id ) {
			$contextual_help =
			'<p>' . __('Things to remember when adding or editing a book:') . '</p>' .
			'<ul>' .
			'<li>' . __('Specify the correct genre such as Mystery, or Historic.') . '</li>' .
			'<li>' . __('Specify the correct writer of the book.  Remember that the Author module refers to you, the author of this book review.') . '</li>' .
			'</ul>' .
			'<p>' . __('If you want to schedule the book review to be published in the future:') . '</p>' .
			'<ul>' .
			'<li>' . __('Under the Publish module, click on the Edit link next to Publish.') . '</li>' .
			'<li>' . __('Change the date to the date to actual publish this article, then click on Ok.') . '</li>' .
			'</ul>' .
			'<p><strong>' . __('For more information:') . '</strong></p>' .
			'<p>' . __('<a href="http://codex.wordpress.org/Posts_Edit_SubPanel" target="_blank">Edit Posts Documentation</a>') . '</p>' .
			'<p>' . __('<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>' ;
		} elseif ( 'edit-book' == $screen->id ) {
			$contextual_help = 
			'<p>' . __('This is the help screen displaying the table of books blah blah blah.') . '</p>' ;
		}
		return $contextual_help;
	}
	
	// end things to do later
	*/	
} // end tah_lessons class

/* 
Might need this if getting 'post not found' error on new installation
register_activation_hook( __FILE__, 'tah_post_types_activate' );
function tah_post_types_activate() {
	flush_rewrite_rules();
}
*/

// Initiate the plugin
add_action("init", "tah_lessons_init");
function tah_lessons_init() { 
	global $tahlessons;
	$tahlessons = new tah_lessons();
	flush_rewrite_rules();
}
?>