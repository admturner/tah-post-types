<?php 
/**
 * Replace double line-breaks with list elements
 * 
 * Modeled on wpautop. A group of regex replacements identify
 * text formatted with newlines and replace all double line-
 * breaks with HTML ul tags.
 *
 * @since 0.5.0
 *
 * @param string $eats The text to be formatted
 * @param int|bool $ul Optional. If set, will create ordered list rather than unordered. Default true (unordered).
 * @return string Text that has been converted with correct tags
 */
function tahautolist( $eats, $ul = 1 ) {
	if ( trim($eats) === '' )
		return '';
	$eats = $eats . "\n"; // pad the end a little
	$eats = preg_replace('|<br />\s*<br />|', "\n\n", $eats);
	$eats = str_replace(array("\r\n", "\r"), "\n", $eats); // cross-platform newlines
	$eats = preg_replace("/\n\n+/", "\n\n", $eats); // take care of duplicates
	$meal = preg_split('/\n\s*\n/', $eats, -1, PREG_SPLIT_NO_EMPTY);
	$eats = '';
	foreach ( $meal as $bite )
		$eats .= '<li>' . trim($bite, "\n") . "</li>\n";	
	$eats = preg_replace('|<li>\s*</li>|', '', $eats); // sometimes might create empty tags	
	if ($ul) {
		$eats = '<ul>' . $eats . '</ul>';
	} else {
		$eats = '<ol>' . $eats . '</ol>';
	}
	
	return $eats;
}



/* Maybe creating a search filtering system */


?>