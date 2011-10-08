<?php
/**
 * Some basic printing functions for the TAH custom post types
 *
 * These can be used in any of a theme's template files to print
 * aspects of the TAH custom post types: Lessons, Podcasts, Primary
 * Source Activities
 *
 * @since 0.8.0
 */

/**
 * Print all of the TAH Lesson custom post fields
 *
 * Call this function anywhere, but makes the most sense called on
 * a custom post template titled: single-lessons.php
 *
 * @since 0.8.0
 */
function tah_lessons_all_sections() {
	global $post;
	$meta = get_post_custom(); ?>
		
	<p class="vcard"><strong><?php _e('Author: '); ?></strong><a href="<?php // link to all author's lessons ?>" class="fn"><span property="dc:creator"><?php echo wptexturize( $meta['tah_lesson_author'][0] ); ?></span></a></p>
	<p><strong><?php _e('School: '); ?></strong><?php echo wptexturize( $meta['tah_school'][0] ); ?></span></p>
	<p><strong><?php _e('Grade Level(s): '); ?></strong><?php echo get_the_term_list($post->ID, 'gradelevel', '', ', ', ''); ?></p>
	<p><strong><?php _e('Time Period(s): '); ?></strong><?php echo wptexturize( get_the_term_list($post->ID, 'timeperiod', '', ', ', '') ); ?></p>
	<p><strong><?php _e('Duration: '); ?></strong><?php echo wptexturize( $meta['tah_duration'][0] ); ?> minutes</p>
	<p><strong><?php _e('Unit: '); ?></strong><?php echo wptexturize( $meta['tah_unit'][0] ); ?></p>
	
	<?php 
	if ( ! empty( $meta['tah_background'][0] ) ) {
		echo wpautop( $meta['tah_background'][0] );
	}
	
	if ( ! empty( $meta['tah_objective'][0] ) ) { ?>
		<h3><?php _e('Lesson Objective'); ?></h3>
		<?php echo wpautop( $meta['tah_objective'][0] );
	}
	
	if ( ! empty( $meta['tah_materials'][0] ) ) { ?>
		<h3><?php _e('Materials'); ?></h3>
		<?php echo tahautolist( $meta['tah_materials'][0] );	
	}
	
	if ( ! empty( $meta['tah_procedure'][0] ) ) { ?>
		<h3><?php _e('Procedure'); ?></h3>
		<?php echo tahautolist( $meta['tah_procedure'][0], 0 );
	}
	
	if ( ! empty( $meta['tah_assessment'][0] ) ) { ?>
		<h3><?php _e('Assessment'); ?></h3>
		<?php echo wpautop( $meta['tah_assessment'][0] );	
	}
	
	if ( ! empty( $meta['tah_references'][0] ) ) { ?>
		<h3><?php _e('References'); ?></h3>
		<?php echo wpautop( $meta['tah_references'][0] );
	}
}

/**
 * Print the Overview
 *
 * @since 0.8.2
 */
function tah_lessons_the_overview( $excerpt = null ) {
	global $post;
	if ( ! empty( $post->post_content ) ) {
		if ( $excerpt == '' ) {
			the_content();
		} else {
			echo tah_lessons_get_the_excerpt( $text );
		}
	}
}

/**
 * Custom excerpt for TAH Post Types
 *
 * Mostly just a raw copy of wp_trim_excerpt($text) from 
 * wp-includes/formatting.php with our own length added. Needed
 * to hard-code this into the plugin because built-in function
 * was adding jibberish characters at the end of the content string
 * for unknown reason.
 *
 * @since 0.8.2
 *
 * @param string $text The excerpt. If set to empty an excerpt is generated.
 * @return string The excerpt.
 */
function tah_lessons_get_the_excerpt( $text ) {
	global $post;
	
	$raw_excerpt = $text;
	
	if ( '' == $text ) {
		$text = get_the_content('');
		
		$text = strip_shortcodes( $text );

		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = apply_filters('excerpt_length', 25);
		$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
		$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
		if ( count($words) > $excerpt_length ) {
			array_pop($words);
			$text = implode(' ', $words);
			$text = $text . $excerpt_more;
		} else {
			$text = implode(' ', $words);
		}
	}	
	return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
}

/**
 * Print only the marked-up author
 *
 * @since 0.6.0
 */
function tah_lessons_the_author() {
	$meta = get_post_custom();
	
	$author = '<span class="meta-prep meta-prep-author">' . _( 'By ' ) . '</span>';
	$author .= '<span class="author vcard">';
	$author .= '<span class="fn n">' . wptexturize( $meta['tah_lesson_author'][0] );
	$author .= '</span></span>';
	
	return apply_filters( 'tah_lessons_the_author', $author );
}

/**
 * Print only the meta fields
 */
function tah_lessons_the_meta() {
	$meta = get_post_custom(); ?>
	
	<p><strong><?php _e('Author: '); ?></strong><span property="dc:creator"><?php echo wptexturize( $meta['tah_lesson_author'][0] ); ?></span></p>
	<p><strong><?php _e('School: '); ?></strong><?php echo wptexturize( $meta['tah_school'][0] ); ?></span></p>
	<p><strong><?php _e('Grade Level(s): '); ?></strong><?php echo get_the_term_list($post->ID, 'gradelevel', '', ', ', ''); ?></p>
	<p><strong><?php _e('Time Period(s): '); ?></strong><?php echo wptexturize( get_the_term_list($post->ID, 'timeperiod', '', ', ', '') ); ?></p>
	<p><strong><?php _e('Duration: '); ?></strong><?php echo wptexturize( $meta['tah_duration'][0] ); ?> minutes</p>
	<p><strong><?php _e('Unit: '); ?></strong><?php echo wptexturize( $meta['tah_unit'][0] ); ?></p>
	<?php 
}

/**
 * Print the main sections only
 * 
 * @since 0.5.0
 */
function tah_lessons_the_main_sections() {
	global $post;
	$meta = get_post_custom();
	
	if ( ! empty( $meta['tah_background'][0] ) ) { ?>
		<h3><?php _e('Historical Background'); ?></h3>
		<?php echo wpautop( $meta['tah_background'][0] );
	} 
	
	if ( ! empty( $meta['tah_objective'][0] ) ) { ?>
		<h3><?php _e('Lesson Objective'); ?></h3>
		<?php echo wpautop( wptexturize( $meta['tah_objective'][0] ) );
	}
	
	if ( ! empty( $meta['tah_materials'][0] ) ) { ?>
		<h3><?php _e('Materials'); ?></h3> 
		<?php // using wptexturize() with tahautolist(): run texturize on autolisted content
			echo wptexturize( tahautolist( $meta['tah_materials'][0] ) );
	}
	
	if ( ! empty( $meta['tah_procedure'][0] ) ) { ?>
		<h3><?php _e('Procedure'); ?></h3>
		<?php echo wptexturize( tahautolist( $meta['tah_procedure'][0], 0 ) );
	}
	
	if ( ! empty( $meta['tah_homework'][0] ) ) { ?>
		<h3><?php _e('Homework'); ?></h3>
		<?php echo wpautop( wptexturize( $meta['tah_homework'][0] ) );
	}
	
	if ( ! empty( $meta['tah_assessment'][0] ) ) { ?>
		<h3><?php _e('Assessment'); ?></h3>
		<?php echo wpautop( wptexturize( $meta['tah_assessment'][0] ) );	
	}
	
	if ( ! empty( $meta['tah_differentiate'][0] ) ) { ?>
		<h3><?php _e('Differentiation'); ?></h3>
		<?php echo wpautop( wptexturize( $meta['tah_differentiate'][0] ) );
	}
	
	if ( ! empty( $meta['tah_references'][0] ) ) { ?>
		<h3><?php _e('References'); ?></h3>
		<?php echo wpautop( wptexturize( $meta['tah_references'][0] ) );
	}
}

/**
 * Print the TAH Lesson custom post archive lists
 *
 * Call this function anywhere, but makes the most sense called on
 * a custom post template titled: archive-lessons.php
 *
 * @todo Clean this up
 * @todo Check on AND vs OR vs noneexistent 'relation' operator to allow for single-taxonomy searching
 *
 * @since 0.5.0
 */
function tah_lessons_list_posts( $args ) {
	$defaults = array(
		'gradelevels' => $gradelevels,
		'timeperiods' => $timeperiods,
		'tax_relation' => 'AND'
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	$gradelevels = get_terms( 'gradelevel', array(
			'hide_empty' => 0,
			'fields' => 'names' ) );
	$timeperiods = get_terms( 'timeperiod', array(
			'hide_empty' => 0,
			'fields' => 'names' ) );
	
	?><form name="tah_lessons_search" action="" method="get">
		<?php if ( $gradelevels ) {
			foreach ( $gradelevels as $level ) {
				echo '<input type="checkbox" name="grade" value="'. $level . '">' . $level . '</input><br />';
			}
		} ?>
		
		<?php if ( $timeperiods ) {
			foreach ( $timeperiods as $period ) {
				echo '<input type="checkbox" name="era" value="'. $period . '">' . $period . '</input><br />';
			}
		} ?>
		</select>
		<input type="submit" value="search" />
	</form><?php 
	
	$currentSearch = esc_url( $_SERVER['REQUEST_URI'] );
	
	$currentSearch = str_replace( '/testing-page/?', '', $currentSearch);
	
	$searchArray = array();
	$searchArray = explode('&', $currentSearch );
	
	$gradelevels = array();
	$timeperiods = array();
	
	foreach ( $searchArray as $item ) {
		if ( strpos( $item, "grade=" ) !== false ) {
			// strip 'grade=' and add value to grades variable string
			$n = array( 'grade=', '+', '#038;' );
			$h = array( '',       ' ', ''      );
			$item = str_replace($n, $h, $item);
			$gradelevels[] = $item;
		}
		if ( strpos( $item, "era=" ) !== false ) {
			// strip 'era=' and add value to periods variable string
			$n = array( 'era=', '+', '#038;', '%26' );
			$h = array( '',       ' ', '',    ''    );
			$item = str_replace($n, $h, $item);
			$timeperiods[] = $item;
		}
	}
	
	$grade_count = count($gradelevels);
	$period_count = count($timeperiods);
	
	echo '<p>Grades queried: ' . $grade_count . '</p>';
	echo '<p>Times queried: ' . $period_count . '</p>';
	
	$args = array(
		'posts_per_page' => -1,
		'tax_query' => array(
				'relation' => $tax_relation,
				array(
						'taxonomy' => 'gradelevel',
						'field' => 'slug',
						'terms' => $gradelevels
				),
				array(
						'taxonomy' => 'timeperiod',
						'field' => 'slug',
						'terms' => $timeperiods
				)
		)
	);
	$query = new WP_Query( $args );
	
	if ( $query->have_posts() ) :
		while ( $query->have_posts() ) : $query->the_post();
		?>
		
			<h2><?php the_title(); ?></h2>
		
		<?php endwhile; wp_reset_query();
	
	else : ?>
		
		<h2>No posts yet.</h2>
	
	<?php endif;
}

/**
 * A shortcode to display the TAH Lessons list
 *
 * @since 0.5.0
 */
function tah_lessons_list_posts_shortcode( $args ) {
	extract(shortcode_atts(array(
		'gradelevels' => $gradelevels,
		'timeperiods' => $timeperiods,
		'relation' => 'AND'
	), $args));
	
	ob_start();
		tah_lessons_list_all_posts( $args );
		$output_string = ob_get_contents();
	ob_end_clean();
	
	return $output_string;
}
add_shortcode( 'list_tah_lessons', 'tah_lessons_list_posts_shortcode' );





/**
 * TEMPORARY FUNCTION to list all TAH Lesson custom post archive lists
 *
 */
function tah_lessons_list_all_posts( $args ) {
	$defaults = array(
		'gradelevels' => $gradelevels,
		'timeperiods' => $timeperiods,
		'relation' => 'AND'
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	$gradelevels = get_terms( 'gradelevel', array(
			'hide_empty' => 0,
			'fields' => 'names' ) );
	$timeperiods = get_terms( 'timeperiod', array(
			'hide_empty' => 0,
			'fields' => 'names' ) );
		
	$args = array(
		'posts_per_page' => -1,
		'tax_query' => array(
				'relation' => $relation,
				array(
						'taxonomy' => 'gradelevel',
						'field' => 'slug',
						'terms' => $gradelevels
				),
				array(
						'taxonomy' => 'timeperiod',
						'field' => 'slug',
						'terms' => $timeperiods
				)
		)
	);
	$query = new WP_Query( $args );
			
	if ( $query->have_posts() ) :
		while ( $query->have_posts() ) : $query->the_post();
		?>
		
			<h2 class="entry-title"><a href="<?php echo esc_url( get_permalink() ); ?>" title="Permalink to <?php echo esc_attr( get_the_title() ); ?>"><?php the_title(); ?></a></h2>
			<?php echo childtheme_override_postheader_postmeta(); ?>
			<?php echo apply_filters( 'the_excerpt', tah_lessons_the_overview() ); ?>
			<div class="entry-utility">
				<?php echo childtheme_override_postfooter_postcategory(); ?>
			</div>
		
		<?php endwhile; wp_reset_query();
	
	else : ?>
		
		<h2>No posts yet.</h2>
	
	<?php endif;
}
?>