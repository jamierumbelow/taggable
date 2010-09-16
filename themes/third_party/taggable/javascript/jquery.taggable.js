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
    jQuery.prototype.taggableAutocomplete = function() {
        $(this).tokenInput();
    };
    
    jQuery.prototype.matrixNameAutocomplete = function() {
        var name = $(this).attr('name');
        var col = name.match(/matrix\[cols\]\[([a-z0-9_]+)\]\[name\]/);
        var col = col[1];
        
        $("input.taggable_saef_field_name[name*=" + col + "]").val($("#field_name").val() + "_" + $(this).val());
    };
})(jQuery);