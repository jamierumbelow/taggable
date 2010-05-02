<?php if (!defined("BASEPATH")) die("No direct script access allowed");
/**
 * Taggable
 *
 * A powerful, easy to use folksonomy
 * engine for ExpressionEngine 2.0.
 *
 * @author Jamie Rumbelow <http://jamierumbelow.net>
 * @copyright Copyright (c)2010 Jamie Rumbelow
 * @license http://gettaggable.com/docs/introduction#license
 * @version 1.1.0
 **/

$config['convert_to_lowercase'] 	= array('type' => 'boolean', 'value' => 'y');
$config['maximum_tags_per_entry']	= array('type' => 'integer', 'value' => 0);

$config['saef_separator']			= array('type' => 'select', 'options' => array( 
	',' => 'Comma', ' ' => 'Space', '', 'newline' => 'New line', '|' => 'Bar' 
), 'value' => ',');
$config['saef_field_name']			= array('type' => 'string', 'value' => 'tags');

$config['license_key']				= array('type' => 'string', 'value' => "");