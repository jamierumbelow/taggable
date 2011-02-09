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
 */
 
/**
 *  Secure Hash Algorithm (SHA1)
 *  http://www.webtoolkit.info/
 **/
function SHA1(c){function l(m,g){return m<<g|m>>>32-g}function p(m){var g="",n,h;for(n=7;n>=0;n--){h=m>>>n*4&15;g+=h.toString(16)}return g}var a,d,i=Array(80),q=1732584193,r=4023233417,s=2562383102,t=271733878,u=3285377520,b,e,f,j,k;c=function(m){m=m.replace(/\r\n/g,"\n");for(var g="",n=0;n<m.length;n++){var h=m.charCodeAt(n);if(h<128)g+=String.fromCharCode(h);else{if(h>127&&h<2048)g+=String.fromCharCode(h>>6|192);else{g+=String.fromCharCode(h>>12|224);g+=String.fromCharCode(h>>6&63|128)}g+=String.fromCharCode(h&
63|128)}}return g}(c);b=c.length;var o=[];for(a=0;a<b-3;a+=4){d=c.charCodeAt(a)<<24|c.charCodeAt(a+1)<<16|c.charCodeAt(a+2)<<8|c.charCodeAt(a+3);o.push(d)}switch(b%4){case 0:a=2147483648;break;case 1:a=c.charCodeAt(b-1)<<24|8388608;break;case 2:a=c.charCodeAt(b-2)<<24|c.charCodeAt(b-1)<<16|32768;break;case 3:a=c.charCodeAt(b-3)<<24|c.charCodeAt(b-2)<<16|c.charCodeAt(b-1)<<8|128;break}for(o.push(a);o.length%16!=14;)o.push(0);o.push(b>>>29);o.push(b<<3&4294967295);for(c=0;c<o.length;c+=16){for(a=0;a<
16;a++)i[a]=o[c+a];for(a=16;a<=79;a++)i[a]=l(i[a-3]^i[a-8]^i[a-14]^i[a-16],1);d=q;b=r;e=s;f=t;j=u;for(a=0;a<=19;a++){k=l(d,5)+(b&e|~b&f)+j+i[a]+1518500249&4294967295;j=f;f=e;e=l(b,30);b=d;d=k}for(a=20;a<=39;a++){k=l(d,5)+(b^e^f)+j+i[a]+1859775393&4294967295;j=f;f=e;e=l(b,30);b=d;d=k}for(a=40;a<=59;a++){k=l(d,5)+(b&e|b&f|e&f)+j+i[a]+2400959708&4294967295;j=f;f=e;e=l(b,30);b=d;d=k}for(a=60;a<=79;a++){k=l(d,5)+(b^e^f)+j+i[a]+3395469782&4294967295;j=f;f=e;e=l(b,30);b=d;d=k}q=q+d&4294967295;r=r+b&4294967295;
s=s+e&4294967295;t=t+f&4294967295;u=u+j&4294967295}k=p(q)+p(r)+p(s)+p(t)+p(u);return k.toLowerCase()};
 
(function($){
    /**
     * Standardised Taggable Autocomplete function
     * in case I ever need to add in extra options
     * or change things globally, etc
     */
    jQuery.prototype.taggableAutocomplete = function() {
        $(this).tokenInput();
    };
    
    /**
     * Matrix SAEF name autoguess
     */
    jQuery.prototype.matrixNameAutocomplete = function() {
        var name = $(this).attr('name');
        var col = name.match(/matrix\[cols\]\[([a-z0-9_]+)\]\[name\]/);
        var col = col[1];
        
        $("input.taggable_saef_field_name[name*=" + col + "]").val($("#field_name").val() + "_" + $(this).val());
    };
    
    jQuery(function(){
        
        /**
         * Create Taggable field on row create.
         * Change the random ID hash, which must be unique.
         */
        if (typeof Matrix !== 'undefined') {
            Matrix.bind('taggable', 'display', function(cell){
                cell.dom.$inputs.attr('data-id-hash', SHA1(Date()+Math.random(0, 1000)));
                cell.dom.$inputs.taggableAutocomplete()
            });
        }
    });
    
    
})(jQuery);