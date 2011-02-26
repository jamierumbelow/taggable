/**
 * Taggable
 *
 * A powerful, easy to use folksonomy
 * engine for ExpressionEngine 2.0.
 *
 * @author Jamie Rumbelow <http://jamierumbelow.net>
 * @copyright Copyright (c)2010 Jamie Rumbelow
 * @license http://getsparkplugs.com/taggable/docs#license
 * @version 1.4.1
 **/

/**
 * Load the documentation page
 */
function docs_load_page(page) {
    // Slide down the content and set the window hash
    $("#documentation-current-page").slideUp('slow', function(){
        window.location.hash = page;
        var url = 'pages/'+page+'.html';

        // Slide up the subnav
        $('.doc-links').find('.subnav.active').slideUp('slow').removeClass('active');

        // Load the page
        $('#documentation-current-page').load(url, function(){
            $("#documentation-current-page").slideDown('slow');
        });
    });
}

/**
 * Do it
 */
$(document).ready(function(){
    // Chrome?
    var is_chrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
    
    if (is_chrome) {
        $('body').html('<div id="unsupported-browser"><h2>Sorry!</h2> <p>Chrome isn\'t supported in the Taggable local docs yet. Please visit the remote docs to view documentation in Chrome, or switch to Firefox.</p></div>');
    }
    
    // Is there a hash on load?
    if (window.location.hash) {
        var page = window.location.hash.substr(1);
        docs_load_page(page);
    } else {
        // Load the introduction
        docs_load_page('introduction');
    }
    
    // Link click
    $('.doc-links li a').click(function(){
        docs_load_page($(this).attr('data-page'));
        
        return false;
    });
});