$(document).ready(function(){
    // Chrome?
    var is_chrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
    
    if (is_chrome) {
        $('body').html('<div id="unsupported-browser"><h2>Sorry!</h2> <p>Chrome isn\'t supported in the Taggable local docs yet. Please visit the remote docs to view documentation in Chrome, or switch to Firefox.</p></div>');
    }
    
    $('.doc-links li a').click(function(){
        $('#documentation-current-page').load($(this).attr('href'));
        
        return false;
    });
});