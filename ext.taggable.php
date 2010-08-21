<?php if (!defined("BASEPATH")) die("No direct script access allowed");
/**
 * Taggable
 *
 * A powerful, easy to use folksonomy
 * engine for ExpressionEngine 2.0.
 *
 * @author Jamie Rumbelow <http://jamierumbelow.net>
 * @copyright Copyright (c)2010 Jamie Rumbelow
 * @license http://getsparkplugs.com/taggable/docs#license
 * @version 1.2.1
 **/

require_once PATH_THIRD."taggable/libraries/Model.php";
require_once PATH_THIRD."taggable/config.php";

class Taggable_ext {
	public $settings        = array();

    public $name            = 'Taggable';
    public $version         = TAGGABLE_VERSION;
    public $description     = 'A powerful, easy to use folksonomy engine for ExpressionEngine 2.0.';
    public $settings_exist  = 'n';
    public $docs_url        = 'http://gettaggable.com/docs/';
    public $versions_xml 	= "http://gettaggable.com/docs/releases_xml";
	
	private $ee;
	
	public function __construct($settings = "") {
		$this->settings = $settings;
		$this->ee		=& get_instance();
	}
	
	public function entry_submission_redirect($entry, $data, $fields, $cp) {
		$this->ee->load->model('taggable_preferences_model', 'preferences');
		$this->ee->load->model('taggable_tags_model', 'tags');
		die(var_dump($data));
		if (!$cp) {
			
		} else {
			return BASE.AMP.'D=cp'.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP."channel_id=".$data['channel_id'].AMP."entry_id=".$entry_id;
		}
	}
		
	public function activate_extension() {
		// Extension installing is handled in the module
		return TRUE;
	}
	
	public function update_extension($current = '') {
		// Extension updates are handled in the module
		return TRUE;
	}
	
	public function disable_extension() {
		// Extension disbaling is handled in the module
		return TRUE;
	}
	
}