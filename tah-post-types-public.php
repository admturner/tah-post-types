<?php
/**
 * Some basic printing functions for the TAH custom post types
 *
 * These can be used in any of a theme's template files to print
 * aspects of the TAH custom post types: Lessons, Podcasts, Primary
 * Source Activities
 *
 * @since 0.5.0
 */

/**
 * Print all of the TAH Lesson custom post fields
 *
 * Call this function anywhere, but makes the most sense called on
 * a custom post template titled: single-lessons.php
 *
 * @since 0.5.0
 */
function tah_lessons_all_sections() {
	
	$meta = get_post_custom(); ?>
	
	<blockquote>
		<h5><?php _e('Lesson Overview'); ?></h5>
		<p><?php echo $meta['tah_overview'][0]; ?><p>
	</blockquote>
	
	<p><strong><?php _e('Author: '); ?></strong><span property="dc:creator"><?php echo $meta['tah_lesson_author'][0]; ?></span></p>
	<p><strong><?php _e('School: '); ?></strong><span><?php echo $meta['tah_school'][0]; ?></span></p>
	<p><strong><?php _e('Grade Level(s): '); ?></strong><span><?php echo get_the_term_list($post->ID, 'gradelevel', '', ', ', ''); ?></span></p>
	<p><strong><?php _e('Time Period(s): '); ?></strong><span><?php echo get_the_term_list($post->ID, 'timeperiod', '', ', ', ''); ?></span></p>
	<p><strong><?php _e('Duration: '); ?></strong><span><?php echo $meta['tah_duration'][0]; ?> minutes</span></p>
	<p><strong><?php _e('Unit: '); ?></strong><span><?php echo $meta['tah_unit'][0]; ?></span></p>
	
	<h3><?php _e('Historical Background'); ?></h3>
	<?php the_content(); ?>
	
	<h3><?php _e('Lesson Objective'); ?></h3>
	<?php echo wpautop( $meta['tah_objective'][0] ); ?>
	
	<h3><?php _e('Materials'); ?></h3>
	<?php echo tahautolist( $meta['tah_materials'][0] ); ?>
	
	<h3><?php _e('Procedure'); ?></h3>
	<?php echo tahautolist( $meta['tah_procedure'][0], 0 ); ?>
	
	<h3><?php _e('Assessment'); ?></h3>
	<?php echo wpautop( $meta['tah_assessment'][0] ); ?>
	
	<h3><?php _e('References'); ?></h3>
	<?php echo wpautop( $meta['tah_references'][0] );
}

/**
 * Print the TAH Lesson custom post archive lists
 *
 * Call this function anywhere, but makes the most sense called on
 * a custom post template titled: archive-lessons.php
 *
 * @since 0.5.0
 */
?>