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
 * @version 1.2.0b
 **/

require_once PATH_THIRD."taggable/libraries/Model.php";

class Taggable_preferences_model extends Model {
	public $primary_key = 'preference_id';
	public $_table 		= 'taggable_preferences';
	
	// @todo Clean this up - chucking it here for now for sanity's sake
	public $options = array( 
		',' 	  => 'Comma', 
		' ' 	  => 'Space', 
		'newline' => 'New line', 
		'|' 	  => 'Bar' 
	);
	
	public function __construct() {
		parent::__construct();
	}
	
	public function format_field($name, $type, $value = "") {
		$this->load->helper('form');
		
		// @todo Pad this out more, add more types and validation
		switch ($type) {
			case 'boolean':
				return form_dropdown($name, array('y' => 'Yes', 'n' => 'No'), $value);
				break;
			
			case 'integer':
				return form_input($name, $value);
				break;
				
			case 'string':
				return form_input($name, $value);
				break;
				
			case 'select':
				return form_dropdown($name, $this->options, $value);
			
			default:
				return form_input($name, $value);
				break;
		}
	}
}