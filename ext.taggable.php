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

require_once PATH_THIRD."taggable/libraries/Model.php";

if (!defined("TAGGABLE_VERSION")) {
	require PATH_THIRD.'taggable/config.php';
	define('TAGGABLE_VERSION', $config['version']);
}

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
        	'method'       => "parse_tags_tag",
        	'hook'         => "channel_entries_tagdata",
          	'settings'     => "",
          	'priority'     => 10,
          	'enabled'      => "y"
        ),
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
	
	public function parse_tags_tag($tagdata, $row) {
		$this->ee->load->model('taggable_preferences_model', 'preferences');	
		
		$disable = $this->ee->TMPL->fetch_param('disable');
		$disable = explode('|', $disable);
		$disable = array_flip($disable);
		
		if (!isset($disable['tags'])) { 
			// taggable_ext_tagdata
			if ($this->ee->extensions->active_hook('taggable_ext_tagdata')) {
				$tagdata = $this->ee->extensions->call('taggable_ext_tagdata', $tagdata);
				if ($this->ee->extensions->end_script === TRUE) return $tagdata;
			}
		
			$vars = array();
							 	 
			// Parse the template tag... to a certain extent
			$chunks = array();
			
			if (strpos($this->ee->TMPL->tagdata, LD.'/tags'.RD) !== FALSE) {
				if (preg_match_all("/".LD."tags(.*?)".RD."(.*?)".LD.'\/'.'tags'.RD."/s", $this->ee->TMPL->tagdata, $matches)) {
					for ($j = 0; $j < count($matches[0]); $j++) {
						$chunks[] = array($matches[2][$j], $this->ee->functions->assign_parameters($matches[1][$j]), $matches[0][$j]);
					}
		  		}
			}
			
			$return = "";
			
			// taggable_ext_chunks
			if ($this->ee->extensions->active_hook('taggable_ext_chunks')) {
				$tagdata = $this->ee->extensions->call('taggable_ext_chunks', $chunks, $tagdata);
				if ($this->ee->extensions->end_script === TRUE) return $tagdata;
			}
			
			// Loop through the occurances
			foreach($chunks as $chunk) {
				// Params
				$params = (is_array($chunk[1])) ? $chunk[1] : array();
				
				// taggable_ext_custom_find
				$this->ee->extensions->call('taggable_ext_custom_find', $params, $chunk);
				
				// Order & sort
				$orderstring = "";
				
				if (isset($params['orderby'])) {
					$orderstring = 'exp_tags.' . $params['orderby'];
				} else {
					$orderstring = 'exp_tags.tag_name';
				}
				
				if (isset($params['sort'])) {
					$orderstring .= ' '.strtoupper($params['sort']);
				} else {
					$orderstring .= ' ASC';
				}
				
				$this->ee->db->order_by($orderstring);
				
				// Tag IDs
				if (isset($params['tag_id'])) {
					// @todo Modelfy
					if (is_numeric($params['tag_id'])) {
						// It's just the one ID, so get it straight from the value
						$entries = $this->ee->db->where('tag_id', $tag_ids);
					} else {
						$tag_ids = $params['tag_id'];
					
						if (strpos($tag_ids, "not ") !== FALSE) {
							// It's a "not" query
							if (strpos($tag_ids, "|")) {
								// multiple nots
								$tag_ids = str_replace("not ", "", $tag_ids);
								$tag_ids = str_replace(" ", "", $tag_ids);
								
								$tag_ids = explode('|', $tag_ids);
								$this->ee->db->where_not_in('exp_tags.tag_id', $tag_ids);
							} else {
								// one not
								$tag_ids = str_replace("not ", "", $tag_ids);
								$this->ee->db->where('exp_tags.tag_id !=', $tag_ids);
							}
						} else {
							if (!strpos('not ', $tag_ids)) {
								// multiple ids
								$tag_ids = str_replace(" ", "", $tag_ids);
							
								$tag_ids = explode('|', $tag_ids);
								$this->ee->db->where_in('exp_tags.tag_id', $tag_ids);
							} else {
								// multiple not ids
								$tag_ids = str_replace('not ', '', $tag_ids);
								$tag_ids = str_replace(' ', '', $tag_ids);
								
								$tag_ids = explode('|', $tag_ids);
								$this->ee->db->where_not_in('exp_tags.tag_id', $tag_ids);
							}
						}
					}
				}
				
				// Limit to this entry
				$this->ee->db->where('exp_tags.tag_id = exp_tags_entries.tag_id')->where('exp_tags.site_id', $this->ee->config->item('site_id'));
				$this->ee->db->where('exp_tags_entries.entry_id', $row['entry_id']);
				
				// Get the tags
				$tags 		= $this->ee->db->get('tags, tags_entries')->result();
				$tag_rows	= array();
	
				// taggable_ext_pre_vars
				$this->ee->extensions->call('taggable_ext_pre_vars', $tags);
	
				if ($tags) {
					// Loop through and arrange everything
					foreach ($tags as $tag) {
						$tag_rows[] = array(
							'tag_name'			=> $tag->tag_name,
							'tag_id'			=> $tag->tag_id,
							'tag_description'	=> $tag->tag_description,
							'entry_count'		=> $this->tag_entries($tag->tag_id),
							'tag_url_name'		=> str_replace(' ', "_", $tag->tag_name),
							'tag_pretty_name'	=> $tag->tag_name
						);
					
						$vars = array('tags' => $tag_rows);
					}
					
					$return = $this->_no_parse_if_no_tags($chunk[2]);
				} else {
					$vars = array('tags' => array(array('tag_name' => '','tag_id' => '','tag_description' => '','entry_count' => '','tag_url_name' => '','tag_pretty_name' => '')));
					$return = $this->_parse_if_no_tags($chunk[2]);
				}
				
				// taggable_ext_post_vars
				$this->ee->extensions->call('taggable_ext_post_vars', $vars);
				
				// parse {tags}{/tags}!
				$return = $this->ee->TMPL->parse_variables($return, array($vars));
				
				// Backspace
				if (isset($params['backspace'])) {
					$return = substr($return, 0, -$params['backspace']);	
				}
				
				// taggable_ext_chunk_done
				if ($this->ee->extensions->active_hook('taggable_ext_chunk_done')) {
					$tagdata = $this->ee->extensions->call('taggable_ext_chunk_done', $tagdata);
				}
				
				// Merge
				$tagdata = str_replace($chunk[2], $return, $tagdata);
				
			}
			
			// taggable_ext_end
			if ($this->ee->extensions->active_hook('taggable_ext_end')) {
				$tagdata = $this->ee->extensions->call('taggable_ext_end', $tagdata);	
			}
	
			// done!
			return $tagdata;
		} else {
			// return the tagdata, ignoring all processing
			return $tagdata;
		}
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
	
	protected function _parse_if_no_tags($tagdata) {
		return preg_replace("/{if no_tags}(.*){\/if}/", '$1', $tagdata);
	}
	
	protected function _no_parse_if_no_tags($tagdata) {
		return preg_replace("/{if no_tags}(.*){\/if}/", '', $tagdata);
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
		return TRUE;
	}
	
	public function disable_extension() {
		// I do hate to see you leave me
		$this->ee->db->where('class', 'Taggable_ext')->delete('exp_extensions');
		
		return TRUE;
	}
	
}