/**
 * Taggable
 *
 * A powerful, easy to use folksonomy
 * engine for ExpressionEngine 2.0.
 *
 * @author Jamie Rumbelow <http://jamierumbelow.net>
 * @copyright Copyright (c)2010 Jamie Rumbelow
 * @license http://getsparkplugs.com/taggable/docs#license
 * @version 1.3.4
 */
 
(function($){
    $.fn.taggableAutocomplete = function() {
        $(this).autocomplete({ 
            source: function(request, response) { 
                $.getJSON(EE.taggable.searchUrl, { 
                    q: request.term.split(/,\s*/).pop() 
                }, response);
            },
            
            search: function() {
                // Make sure at least 1 character is inserted
                if (this.value.split(/,\s*/).pop().length < 1) {
                    return false;
                }
            },
            
            select: function(event, ui) {
    			var terms = this.value.split(/,\s*/);
    			
    			// remove the current input
    			terms.pop();
    			
    			// add the selected item
    			terms.push( ui.item.value );
    			
    			// add placeholder to get the comma-and-space at the end
    			terms.push("");
    			this.value = terms.join(", ");
    			
    			return false;
    		},
            
            focus: function() { 
                // prevent value inserted on focus
                return false; 
            } 
        });
    }
})(jQuery);