/**
 * Taggable
 *
 * A powerful, easy to use folksonomy
 * engine for ExpressionEngine 2.0.
 *
 * @author Jamie Rumbelow <http://jamierumbelow.net>
 * @copyright Copyright (c)2010 Jamie Rumbelow
 * @license http://getsparkplugs.com/taggable/docs#license
 * @version 1.4.6
 */
 
(function($){
	
	/**
	 * Standardised Taggable Autocomplete function
	 * in case I ever need to add in extra options
	 * or change things globally, etc
	 */
	jQuery.prototype.taggableAutocomplete = function() {
		$(this).tokenInput();
	};
	
	jQuery(function(){
		
		/**
		 * Replace all the Taggable fields with the
		 * autocomplete dropdown
		 */
		$("input.taggable_replace_token_input:not(.taggable_matrix)").taggableAutocomplete();
		
		/**
		 * Create Taggable field on row create.
		 * Change the random ID hash, which must be unique.
		 */
		if (typeof Matrix !== 'undefined') {
			Matrix.bind('taggable', 'display', function(cell){
				cell.dom.$inputs.taggableAutocomplete()
			});
		}
	});
	
	
})(jQuery);