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
	private $hooks = array(
        array(
        	'class'        => "Taggable_ext",
        	'method'       => "parse_standalone_submission",
        	'hook'         => "entry_submission_redirect",
          	'settings'     => "",
          	'priority'     => 10,
          	'enabled'      => "y"
        )
	);
	
	public function __construct($settings = "") {
		$this->settings = $settings;
		$this->ee		=& get_instance();
	}
	
	public function parse_standalone_submission($entry_id, $data, $fields, $cp) {
		if (!$cp) {
			$this->ee->load->model('taggable_preferences_model', 'preferences');	
			
			// Get preferences
			$field_name = $this->ee->preferences->get_by('preference_key', 'saef_field_name')->preference_value;
			$separator 	= trim($this->ee->preferences->get_by('preference_key', 'saef_separator')->preference_value);
			$separator 	= ($separator == 'newline') ? "\n" : $separator ;
		
			// Get tags
			$tags = $fields[$field_name];
			$tags = explode($separator, $tags);
		
			// Insert tags!
			foreach ($tags as $tag) {
				// Does the tag exist? Get its ID
				$query = $this->ee->db->select('tag_id')->where('tag_name', $tag)->where('exp_tags.site_id', $this->site_id)->get('tags');
			
				if ($query->num_rows == 0) {
					$this->ee->db->insert("exp_tags", array("tag_name" => $tag, 'site_id' => $this->ee->config->item('site_id')));
					$id = $this->ee->db->insert_id();
				} else {
					$id = $query->row('tag_id');
				}
			
				// Insert it under this entry
				if ($this->ee->db->query("SELECT * FROM exp_tags_entries WHERE tag_id = $id AND entry_id = $entry_id")->num_rows == 0) {
					$this->ee->db->insert('exp_tags_entries', array('tag_id' => $id, 'entry_id' => $entry_id));
				}
			
				// Awesome
			}
		
			// Oh look, that was easy
		} else {
			return BASE.AMP.'D=cp'.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP."channel_id=".$data['channel_id'].AMP."entry_id=".$entry_id;
		}
	}
	
	protected function tag_entries($id) {
		return $this->ee->db->select("COUNT(DISTINCT exp_tags_entries.entry_id) AS total")
						->from("exp_tags, exp_tags_entries")
						->where("exp_tags_entries.tag_id", $id)
						->get()
						->row('total');
	}
		
	public function activate_extension() {
		// Hooks
		foreach ($this->hooks as $hook) {
			$hook['version'] = $this->version;
			$this->ee->db->insert('exp_extensions', $hook);
		}
		
		return TRUE;
	}
	
	public function update_extension($current = '') {
		if ($current < 1.2) {
			$this->ee->db->where('method', 'parse_tags_tag')->where('class', 'Taggable_ext')->delete('exp_extensions');
		}
		
		return TRUE;
	}
	
	public function disable_extension() {
		// I do hate to see you leave me
		$this->ee->db->where('class', 'Taggable_ext')->delete('exp_extensions');
		
		return TRUE;
	}
	
}