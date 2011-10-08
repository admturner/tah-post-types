<?php
/*
Plugin Name: TAH Post Types
Plugin URI: http://adamturner.org/
Description: Three custom post types for the CHNM TAH websites. Customized posts for Lessons, Podcasts, and Primary Source Activities.
Author: Adam Turner
Version: 0.8.2
Author URI: http://adamturner.org/
*/

/**
 * Credits: Adapted from the excellent tutorial by Konstantin Kovshenin
 *
 */

include_once 'tah-post-filters.php';
include_once 'tah-post-types-public.php';

class tah_lessons {
	var $meta_fields = array(
		'tah_lesson_author',
		'tah_author_email',
		'tah_school',
		'tah_duration',
		'tah_unit',
		'tah_background',
		'tah_objective',
		'tah_materials',
		'tah_procedure',
		'tah_homework',
		'tah_assessment',
		'tah_differentiate',
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
			'capability_type' => 'page',
			'hierarchical' => false,
			'rewrite' => array('slug' => 'lessons'),
			'supports' => array('title','author', 'editor', 'thumbnail', 'comments', 'revisions', 'page-attributes'),
			'taxonomies' => array('post_tag', 'category', 'gradelevel', 'timeperiod')
		));
		
		// Add posts table columns, make them sortable, then add action for table contents
		add_filter( 'manage_edit-lessons_columns', array( &$this, 'lessons_columns_title_fn' ) );
		add_filter( 'manage_edit-lessons_sortable_columns', array( &$this, 'lessons_columns_title_sort_fn' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'lessons_custom_columns_control_fn' ) );
		
		// Register custom taxonomies
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
					if ( $time_diff > 0 ) {
						echo '<strong class="attention">' . __( 'Missed schedule' ) . '</strong>';
					} else {
						_e( 'Scheduled' );
					}
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
		if ( $post->post_type == 'lessons' ) {
			// Loop through the POST data
			foreach ( $this->meta_fields as $key ) {
				$value = @$_POST[$key];
				
				if ( defined('DOING_AJAX') ) {
					return;
				}
			
				// verify nonce for security; will die and return defaul error if fails
				if ( !empty($_POST) && check_admin_referer( 'tahlessonnonce', 'tahlessonnoncespace' ) ) {
					// Process form data	
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
				} // end security check
			} // end foreach
		}
	}
	
	function tah_lessons_meta_box_fn() {
		// Custom meta boxes for the Add/Edit Lessons page
		add_meta_box( 'tah-lessons-meta', 'Lesson Meta', array( &$this, 'lessons_meta_control_fn' ), 'lessons', 'side', 'high' );
		add_meta_box( 'tah-lessons-background', 'Historical Background', array( &$this, 'lessons_background_control_fn' ), 'lessons', 'normal', 'high' );
		add_meta_box( 'tah-lessons-objective', 'Lesson Objective', array( &$this, 'lessons_objective_control_fn' ), 'lessons', 'normal', 'high' );
		add_meta_box( 'tah-lessons-materials', 'Lesson Materials', array( &$this, 'lessons_materials_control_fn' ), 'lessons', 'normal', 'high' );
		add_meta_box( 'tah-lessons-procedure', 'Lesson Procedure', array( &$this, 'lessons_procedure_control_fn' ), 'lessons', 'normal', 'high' );
		add_meta_box( 'tah-lessons-homework', 'Lesson Homework', array( &$this, 'lessons_homework_control_fn' ), 'lessons', 'normal', 'high' );		
		add_meta_box( 'tah-lessons-assessment', 'Lesson Assessment', array( &$this, 'lessons_assessment_control_fn' ), 'lessons', 'normal', 'high' );
		add_meta_box( 'tah-lessons-differentiate', 'Lesson Differentiation', array( &$this, 'lessons_differentiate_control_fn' ), 'lessons', 'normal', 'high' );
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
		wp_nonce_field( 'tahlessonnonce', 'tahlessonnoncespace' );
		?>
		<p>
			<label id="tah_lesson_author_prompt_text" for="tah_lesson_author"><strong><?php _e('Author'); ?></strong></label>
			<input name="tah_lesson_author" id="tah_lesson_author" type="text" size="35" value="<?php echo $author = $custom['tah_lesson_author'][0]; ?>" />
		</p>
		<p>
			<label id="tah_author_email_prompt_text" for="tah_author_email"><strong><?php _e('Email'); ?></strong></label>
			<input name="tah_author_email" id="tah_author_email" type="text" size="36" value="<?php echo $author = $custom['tah_author_email'][0]; ?>" />
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
	<?php
	}

	function lessons_background_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		wp_nonce_field( 'tahlessonnonce', 'tahlessonnoncespace' );
		the_editor( $custom['tah_background'][0], 'tah_background' );
	}
	function lessons_objective_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		wp_nonce_field( 'tahlessonnonce', 'tahlessonnoncespace' );
		the_editor( $custom['tah_objective'][0], 'tah_objective' );
	}
	function lessons_materials_control_fn() {
		// @todo it would be neat if this was a single-line text box with option to add more as needed in an AJAX-y way
		global $post;
		$custom = get_post_custom($post->ID);
		wp_nonce_field( 'tahlessonnonce', 'tahlessonnoncespace' );
		echo '<p><span class="description">Press Enter/Return twice after each item.</span></p>';
		the_editor( $custom['tah_materials'][0], 'tah_materials' );
	}
	function lessons_procedure_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		wp_nonce_field( 'tahlessonnonce', 'tahlessonnoncespace' );
		the_editor( $custom['tah_procedure'][0], 'tah_procedure' );
	}
	function lessons_homework_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		wp_nonce_field( 'tahlessonnonce', 'tahlessonnoncespace' );
		the_editor( $custom['tah_homework'][0], 'tah_homework' );
	}
	function lessons_assessment_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		wp_nonce_field( 'tahlessonnonce', 'tahlessonnoncespace' );
		the_editor( $custom['tah_assessment'][0], 'tah_assessment' );
	}
	function lessons_differentiate_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		wp_nonce_field( 'tahlessonnonce', 'tahlessonnoncespace' );
		the_editor( $custom['tah_differentiate'][0], 'tah_differentiate' );
	}
	
	function lessons_references_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		wp_nonce_field( 'tahlessonnonce', 'tahlessonnoncespace' );
		the_editor( $custom['tah_references'][0], 'tah_references' );
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

class tah_psas {
	var $meta_fields = array(
		'tah_psa_author',
		'tah_psa_author_email',
		'tah_psa_author_about',
		'tah_psa_analysis',
		'tah_psa_discussion',
		'tah_background',
		'tah_conclusions',
		'tah_classroom'
	);
	
	function tah_psas() {
		register_post_type('psas', array(
			'labels' => array(
				'name' => __('PSAs'),
				'singular_name' => __('PSA'),
				'all_items' => __('All Activities'),
				'add_new_item' => __('Add New Activity'),
				'edit_item' => __('Edit Activity'),
				'new_item' => __('New Activity'),
				'view_item' => __('View Activity'),
				'search_items' => __('Search Activity'),
				'not_found' => __('No activities found'),
				'not_found_in_trash' => __('No activities found in trash')
			),
			'public' => true,
			'capability_type' => 'page',
			'hierarchical' => false,
			'rewrite' => array('slug' => 'psas'),
			'supports' => array('title','author', 'editor', 'thumbnail', 'trackbacks', 'comments', 'revisions', 'page-attributes')
		));
		
		// Add posts table columns, make them sortable, then add action for table contents
		add_filter( 'manage_edit-psas_columns', array( &$this, 'psas_columns_title_fn' ) );
		add_filter( 'manage_edit-psas_sortable_columns', array( &$this, 'psas_columns_title_sort_fn' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'psas_custom_columns_control_fn' ) );
		
		// Register custom taxonomies
//		register_taxonomy( 'gradelevel', array('psas'), array(
//			'hierarchical' => true, 
//			'labels' => array('name' => _x('Grade Levels', 'grade levels general name'), 'singular_name' => _x('Grade Level', 'grade level singular name'), 'search_items' => __('Search Grade Levels'), 'parent_item' => __('Parent Grade'), 'edit_item' => __('Edit Grade Level Category'), 'add_new_item' => __('Add New Grade Level'), 'separate_items_with_commas' => __('Separate grades with commas')),
//			'rewrite' => true) );

		// Admin interface init
		add_action( 'add_meta_boxes', array( &$this, 'tah_psas_meta_box_fn') );
		// make backwards compatible
		add_action( 'admin_init', array( &$this, 'tah_psas_meta_box_fn'), 1 );
		
		// Insert post hook
		add_action( 'wp_insert_post', array( &$this, 'wp_insert_post' ), 10, 2 );
	}
	
	function psas_columns_title_fn( $columns ) {
		$columns = array(
			'cb' => '<input type=\'checkbox\' />',
			'title' => _x( 'PSA Title', 'psas column name' ),
			'tah_psa_author' => __( 'PSA Author' ),
//			'psa_taxonomy' => __( 'Grade Level(s)' ),
			'psa_date' => __( 'Date' )
		);
		return $columns;
	}

	function psas_custom_columns_control_fn( $column ) {
		global $post;
		switch ( $column ) {
			case 'tah_psa_author' :
				$custom = get_post_custom($post->ID);
				echo $custom["tah_psa_author"][0];
				break;
//			case 'psa_taxonomy' :
//				echo get_the_term_list($post->ID, 'taxonomy', '', ', ', '');
//				break;
			case 'psa_date' :
				echo get_the_date( __( 'Y/m/d' ) ) . '<br />';
				if ( 'publish' == $post->post_status ) {
					_e( 'Published' );
				} elseif ( 'future' == $post->post_status ) {
					if ( $time_diff > 0 ) {
						echo '<strong class="attention">' . __( 'Missed schedule' ) . '</strong>';
					} else {
						_e( 'Scheduled' );
					}
				} else {
					_e( 'Last Modified' );
				}
				break;
		}
	}
	
	function psas_columns_title_sort_fn( $sort_columns ) {
		$sortables = array(
			'tah_psa_author' => 'tah_psa_author'
//			'lesson_grade'  => 'lesson_grade'
		);
		return wp_parse_args( $sortables, $sort_columns );
	}
	
	// When a post is added or updated
	function wp_insert_post($post_id, $post = null) {		
		if ( $post->post_type == 'psas' ) {
			// Loop through the POST data
			foreach ( $this->meta_fields as $key ) {
				$value = @$_POST[$key];
				
				if ( defined('DOING_AJAX') ) {
					return;
				}
				
				if ( !empty($_POST) && check_admin_referer( 'tahpsanonce', 'tahpsanoncespace' ) ) {
					// Process form data	
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
						foreach ( $value as $entry ) {
							update_post_meta( $post_id, $key, $entry );
						}
					}
				} // end nonce check
			} // end foreach
		} // end post_type check
	}
	
	function tah_psas_meta_box_fn() {
		// Custom meta boxes for the Add/Edit PSAs page
		add_meta_box( 'tah-psas-meta', 'PSA Meta', array( &$this, 'psas_meta_control_fn' ), 'psas', 'side', 'high' );
		add_meta_box( 'tah_psa_analysis', 'Source Analysis', array( &$this, 'psas_analysis_control_fn' ), 'psas', 'normal', 'high' );
		add_meta_box( 'tah_psa_discussion', 'Group Discussion', array( &$this, 'psas_discussion_control_fn' ), 'psas', 'normal', 'high' );
		add_meta_box( 'tah_background', 'Historical Background', array( &$this, 'psas_background_control_fn' ), 'psas', 'normal', 'high' );
		add_meta_box( 'tah_conclusions', 'Conclusions', array( &$this, 'psas_conclusions_control_fn' ), 'psas', 'normal', 'high' );
		add_meta_box( 'tah_classroom', 'Classroom Applications', array( &$this, 'psas_classroom_control_fn' ), 'psas', 'normal', 'high' );
	}
	
	// Control for the meta boxes on the Add/Edit psas page
	function psas_meta_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		
		wp_nonce_field( 'tahpsanonce', 'tahpsanoncespace' ); ?>
		<p>
			<label id="tah_psa_author_prompt_text" for="tah_psa_author"><strong><?php _e( 'Author' ); ?></strong></label>
			<input name="tah_psa_author" id="tah_psa_author" type="text" size="35" value="<?php echo $author = $custom['tah_psa_author'][0]; ?>" />
		</p>
		<p>
			<label id="tah_psa_author_email_prompt_text" for="tah_psa_author_email"><strong><?php _e( 'Email' ); ?></strong></label>
			<input name="tah_psa_author_email" id="tah_psa_author_email" type="text" size="35" value="<?php echo $custom['tah_psa_author_email'][0]; ?>" />
		</p>
		<p>
			<label id="tah_psa_author_about_prompt_text" for="tah_psa_author_about"><strong><?php _e( 'About the Author' ); ?></strong></label>
<textarea name="tah_psa_author_about" id="tah_psa_author_about" class="mceEditor tah-lesson-textarea" rows="4" cols="35">
<?php echo $custom['tah_psa_author_about'][0]; ?>
</textarea>
		</p><?php
	}
	
	function psas_analysis_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		the_editor( $custom['tah_psa_analysis'][0], 'tah_psa_analysis' );
		wp_nonce_field( 'tahpsanonce', 'tahpsanoncespace' );
	}
	
	function psas_discussion_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		the_editor( $custom['tah_psa_discussion'][0], 'tah_psa_discussion' );
		wp_nonce_field( 'tahpsanonce', 'tahpsanoncespace' );
	}
	
	function psas_background_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		the_editor( $custom['tah_background'][0], 'tah_background' );
		wp_nonce_field( 'tahpsanonce', 'tahpsanoncespace' );
	}
	
	function psas_conclusions_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		the_editor( $custom['tah_conclusions'][0], 'tah_conclusions' );
		wp_nonce_field( 'tahpsanonce', 'tahpsanoncespace' );
	}
	
	function psas_classroom_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		the_editor( $custom['tah_classroom'][0], 'tah_classroom' );
		wp_nonce_field( 'tahpsanonce', 'tahpsanoncespace' );
	}
	
} // end tah_psas class







class tah_podcasts {
	var $meta_fields = array(
		'tah_pod_speaker',
		'tah_pod_speaker_title',
		'tah_pod_speaker_about',
		'tah_pod_duration',
		'tah_pod_source'
	);
	
	function tah_podcasts() {
		register_post_type('podcasts', array(
			'labels' => array(
				'name' => __('Podcasts'),
				'singular_name' => __('Podcast'),
				'all_items' => __('All Podcasts'),
				'add_new_item' => __('Add New Podcast'),
				'edit_item' => __('Edit Podcast'),
				'new_item' => __('New Podcast'),
				'view_item' => __('View Podcast'),
				'search_items' => __('Search Podcasts'),
				'not_found' => __('No podcasts found'),
				'not_found_in_trash' => __('No podcasts found in trash')
			),
			'public' => true,
			'capability_type' => 'page',
			'hierarchical' => false,
			'rewrite' => array('slug' => 'podcasts'),
			'supports' => array('title','author', 'editor', 'thumbnail', 'trackbacks', 'comments', 'revisions', 'page-attributes')
		));
		
		// Add posts table columns, make them sortable, then add action for table contents
		add_filter( 'manage_edit-podcasts_columns', array( &$this, 'podcasts_columns_title_fn' ) );
		add_filter( 'manage_edit-podcasts_sortable_columns', array( &$this, 'podcasts_columns_title_sort_fn' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'podcasts_custom_columns_control_fn' ) );
		
		// Register custom taxonomies
//		register_taxonomy( 'gradelevel', array('podcasts'), array(
//			'hierarchical' => true, 
//			'labels' => array('name' => _x('Grade Levels', 'grade levels general name'), 'singular_name' => _x('Grade Level', 'grade level singular name'), 'search_items' => __('Search Grade Levels'), 'parent_item' => __('Parent Grade'), 'edit_item' => __('Edit Grade Level Category'), 'add_new_item' => __('Add New Grade Level'), 'separate_items_with_commas' => __('Separate grades with commas')),
//			'rewrite' => true) );

		// Admin interface init
		add_action( 'add_meta_boxes', array( &$this, 'tah_podcasts_meta_box_fn') );
		// make backwards compatible
		add_action( 'admin_init', array( &$this, 'tah_podcasts_meta_box_fn'), 1 );
		
		// Insert post hook
		add_action( 'wp_insert_post', array( &$this, 'wp_insert_post' ), 10, 2 );
	}
	
	function podcasts_columns_title_fn( $columns ) {
		$columns = array(
			'cb' => '<input type=\'checkbox\' />',
			'title' => _x( 'Podcast Title', 'podcasts column name' ),
			'tah_pod_speaker' => __( 'Podcast Speaker' ),
//			'pod_taxonomy' => __( 'Grade Level(s)' ),
			'pod_date' => __( 'Date' )
		);
		return $columns;
	}

	function podcasts_custom_columns_control_fn( $column ) {
		global $post;
		switch ( $column ) {
			case 'tah_pod_speaker' :
				$custom = get_post_custom($post->ID);
				echo $custom["tah_pod_speaker"][0];
				break;
//			case 'pod_taxonomy' :
//				echo get_the_term_list($post->ID, 'taxonomy', '', ', ', '');
//				break;
			case 'pod_date' :
				echo get_the_date( __( 'Y/m/d' ) ) . '<br />';
				if ( 'publish' == $post->post_status ) {
					_e( 'Published' );
				} elseif ( 'future' == $post->post_status ) {
					if ( $time_diff > 0 ) {
						echo '<strong class="attention">' . __( 'Missed schedule' ) . '</strong>';
					} else {
						_e( 'Scheduled' );
					}
				} else {
					_e( 'Last Modified' );
				}
				break;
		}
	}
	
	function podcasts_columns_title_sort_fn( $sort_columns ) {
		$sortables = array(
			'tah_pod_speaker' => 'tah_pod_speaker'
//			'lesson_grade'  => 'lesson_grade'
		);
		return wp_parse_args( $sortables, $sort_columns );
	}
	
	// When a post is added or updated
	function wp_insert_post($post_id, $post = null) {		
		if ( $post->post_type == 'podcasts' ) {
			// Loop through the POST data
			foreach ( $this->meta_fields as $key ) {
				$value = @$_POST[$key];
				
				if ( defined('DOING_AJAX') ) {
					return;
				}
				
				if ( !empty($_POST) && check_admin_referer( 'tahpodnonce', 'tahpodnoncespace' ) ) {
					// Process form data	
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
						foreach ( $value as $entry ) {
							update_post_meta( $post_id, $key, $entry );
						}
					}
				} // end nonce check
			} // end foreach
		} // end post_type check
	}
	
	function tah_podcasts_meta_box_fn() {
		// Custom meta boxes for the Add/Edit podcasts page
		add_meta_box( 'tah-podcasts-meta', 'Podcast Meta', array( &$this, 'podcasts_meta_control_fn' ), 'podcasts', 'side', 'high' );
		add_meta_box( 'tah_pod_source', 'Podcast Source', array( &$this, 'podcasts_source_control_fn' ), 'podcasts', 'normal', 'high' );
	}
	
	// Control for the meta boxes on the Add/Edit podcasts page
	function podcasts_meta_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID);
		
		wp_nonce_field( 'tahpodnonce', 'tahpodnoncespace' ); ?>
		<p>
			<label id="tah_pod_speaker_prompt_text" for="tah_pod_speaker"><strong><?php _e( 'Speaker' ); ?></strong></label>
			<input name="tah_pod_speaker" id="tah_pod_speaker" type="text" size="35" value="<?php echo $custom['tah_pod_speaker'][0]; ?>" />
		</p>
		<p>
			<label id="tah_pod_speaker_title_prompt_text" for="tah_pod_speaker_title"><strong><?php _e( 'Speader Title' ); ?></strong></label>
			<input name="tah_pod_speaker_title" id="tah_pod_speaker_title" type="text" size="35" value="<?php echo $custom['tah_pod_speaker_title'][0]; ?>" />
		</p>
		<p>
			<label id="tah_pod_duration_prompt_text" for="tah_pod_duration"><strong><?php _e( 'Duration'); ?></strong></label>
			<input name="tah_pod_duration" id="tah_pod_duration" type="text" size="35" value="<?php echo $custom['tah_pod_duration'][0]; ?>" />
			<span class="description">In minutes</span>
		</p>
		<p>
			<label id="tah_pod_speaker_about_prompt_text" for="tah_pod_speaker_about"><strong><?php _e( 'About the Speaker' ); ?></strong></label>
<textarea name="tah_pod_speaker_about" id="tah_pod_speaker_about" class="mceEditor tah-lesson-textarea" rows="4" cols="35">
<?php echo $custom['tah_pod_speaker_about'][0]; ?>
</textarea>
		</p><?php
	}
	
	function podcasts_source_control_fn() {
		global $post;
		$custom = get_post_custom($post->ID); ?>
		<p>
			<label id="tah_pod_source_prompt_text" for="tah_pod_source"><strong><?php _e( 'Podcast Source' ); ?></strong></label>
			<input name="tah_pod_source" id="tah_pod_source" type="text" size="40" value="<?php echo $custom['tah_pod_source'][0]; ?>" />
			<span class="description">Enter URL of podcast audio file.</span>
		</p>
		<?php wp_nonce_field( 'tahpsanonce', 'tahpsanoncespace' );
	}
	
} // end tah_podcasts class

add_filter('admin_head','ShowTinyMCE');
function ShowTinyMCE() {
	wp_enqueue_script( 'common' );
	wp_enqueue_script( 'jquery-color' );
	wp_print_scripts( 'editor' );
	if (function_exists('add_thickbox')) add_thickbox();
	wp_print_scripts('media-upload');
	if (function_exists('wp_tiny_mce')) wp_tiny_mce();
	wp_admin_css();
	wp_enqueue_script('utils');
	do_action("admin_print_styles-post-php");
	do_action('admin_print_styles');
}

// Reset rewrite rules on new activation
// @todo This still isn't working correctly; fix it
function tah_post_types_flush() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}
register_activation_hook( __FILE__, 'tah_post_types_flush' );

// Initiate the plugin
add_action("init", "tah_lessons_init");
function tah_lessons_init() { 
	global $tahlessons;
	global $tahpsas;
	global $tahpodcasts;
	$tahlessons = new tah_lessons();
	$tahpsas = new tah_psas();
	$tahpodcasts = new tah_podcasts();
}
?>