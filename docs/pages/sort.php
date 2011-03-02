<?php
/**
 * Taggable
 *
 * A powerful, easy to use folksonomy
 * engine for ExpressionEngine 2.0.
 *
 * @author Jamie Rumbelow <http://jamierumbelow.net>
 * @copyright Copyright (c)2010 Jamie Rumbelow
 * @license http://getsparkplugs.com/taggable/docs#license
 * @version 1.4.2
 **/

function sparkplugs_doc_sort($a, $b) {
	$order = array(
		'introduction.html', 'fieldtype.html', 'module.html', 'developers.html'
	);
	
	return (array_search($a, $order) > array_search($b, $order)) ? 1 : -1;
}