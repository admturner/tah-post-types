jQuery(document).ready(function($) {
   // place meta box before standard post edit field
   if( document.getElementById('postdiv') ) {
       jQuery('#tah-lessons-overview').insertBefore('#postdiv');
       jQuery('<h3>Historical Background</h3><span class="description"> Use textarea below for historical background.</span>').insertAfter('#tah-lessons-overview');
   } else if( document.getElementById('postdivrich') ) {
       jQuery('#tah-lessons-overview').insertBefore('#postdivrich');
       jQuery('<h3>Historical Background</h3><span class="description"> Use textarea below for historical background.</span>').insertAfter('#tah-lessons-overview');
   }
});