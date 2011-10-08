jQuery(document).ready(function($) {
   // place meta box before standard post edit field
   if ( document.getElementById('tah-lessons-background') ) {
       jQuery('<h3>Lesson Overview</h3><span class="description"> Use textarea below for lesson overview.</span>').insertAfter('#titlediv');
   }
   if ( document.getElementById('tah_psa_analysis') ) {
       jQuery('<h3>PSA Overview</h3><span class="description"> Use textarea below for primary source activity overview.</span>').insertAfter('#titlediv');
   }
});