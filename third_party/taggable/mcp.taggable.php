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
 * @version 1.3.4
 **/

require_once PATH_THIRD."taggable/libraries/Model.php";
require_once PATH_THIRD."taggable/config.php";
require_once PATH_THIRD."taggable/libraries/eh_compat.php";

define('TAGGABLE_URL', BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taggable');
define('TAGGABLE_PATH', 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taggable');

class Taggable_mcp {
	private $ee;
	
	public $data 		= array();
	public $docs_url	= "http://getsparkplugs.com/taggable/docs/";
	
	/**
	 * Constructor
	 *
	 * @author Jamie Rumbelow
	 */
	public function __construct() {
		$this->ee =& get_instance();
		
		// Load libraries
		$this->ee->load->library('model');
		$this->ee->load->library('form_validation');
		
		// Load models and helpers
		$this->ee->load->model('taggable_tag_model', 'tags');
		$this->ee->load->helper('language');
		$this->ee->load->helper('string');
		
		// Add nav globally
		$this->ee->cp->set_right_nav(array(
			'taggable_module_name' 			=> TAGGABLE_URL,
			'taggable_tags_title' 			=> TAGGABLE_URL.AMP.'method=tags',
			'taggable_preferences_title' 	=> TAGGABLE_URL.AMP.'method=preferences',
			'taggable_tools_title' 			=> TAGGABLE_URL.AMP.'method=tools',
			'taggable_doc_title' 			=> $this->docs_url
		));
		
		// Global data
		$this->data['license_key'] = $this->ee->config->item('taggable_license_key');
		
		// MSM
		$this->site_id = $this->ee->config->item('site_id');
		
		// Theme URL
		define('TAGGABLE_THEME_URL', $this->_theme_url());
				
		// License key check
		if (empty($this->data['license_key']) || !$this->_valid($this->data['license_key'])) { 
			$this->ee->cp->add_to_head("
				<script type=\"text/javascript\">
					jQuery(function(){
						$.ee_notice(\"".lang('taggable_no_license_key')."\", {type: 'error'});
					});
				</script>
			");
		}
	}
	
	/**
	 * MCP Dashboard
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function index() {
		$this->data['tags_alphabetically'] = $this->ee->tags->get_alphabet_list();
		$this->data['stats']			   = $this->ee->tags->stats();
		
		$this->_title("taggable_module_name");
		return $this->_view('cp/index');
	}
	
	/**
	 * Tags list, search and filtering
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function tags() {
		// Reset filters and start building the query
		$this->ee->tags->reset_filters();
		$this->ee->tags->grouped_tags_lookup();
		
		// Order by
		if ($this->ee->input->get('order')) {
			$this->ee->tags->order_by($this->ee->input->get('order'));
		}
		
		// Text searching
		if ($term = $this->ee->input->get('text_search')) {
			$this->ee->tags->where_tag_name_based_on_text_search_term($this->ee->input->get('text_search'), $term);
		}
		
		// Entry count
		if ($count = $this->ee->input->get('entry_count')) {
			$this->ee->tags->where_entry_count_based_on_entry_count_order($this->ee->input->get('entry_count_order'), $count);
		}

		// Get the tags
		$this->data = $this->ee->tags->filters;
		$this->data['tags'] = $this->ee->tags->get_all();		
		$this->data['tags_alphabetically'] = $this->ee->tags->get_alphabet_list();
		$this->data['errors'] = ($this->ee->session->flashdata('create_validate') == 'yes') ? TRUE : FALSE;
		
		// Show the view
		$this->_title("taggable_tags_title");
		return $this->_view('cp/tags');
	}
	
	/**
	 * Entries tagged with tag
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function tag_entries() {
		$tag = $this->ee->input->get('tag_id');
		
		$this->data['tag'] 		= $this->ee->tags->get($tag);
		$this->data['errors'] 	= ($this->ee->session->flashdata('edit_validate') == 'yes') ? TRUE : FALSE;
		$this->data['entries']	= $this->ee->tags->tag_entries($tag);
		
		$this->_title("taggable_edit_tag");
		return $this->_view('cp/entries');
	}
	
	/**
	 * Create a new tag
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function create_tag() {
		if (empty($_POST['tag']['name'])) {
			$this->ee->session->set_flashdata('create_validate', 'yes');
			$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=tags");
		}
				
		$tag = $this->ee->input->post('tag');
		$tag['site_id'] = $this->ee->config->item('site_id');
		$this->ee->tags->insert($tag);
		
		$this->ee->session->set_flashdata('message_success', "Tag Created");
		$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=tags");
	}
	
	/**
	 * Delete a tag
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function delete_tags() {
		$tags = $this->ee->input->post('delete_tags');
		
		$this->ee->tags->delete_many($tags);
		$this->ee->tags->delete_from_channel_data($tags);
		$this->ee->tags->delete_entries($tags);
		
		$this->ee->session->set_flashdata('message_success', "Tag(s) deleted");
		$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=tags");
	}
	
	/**
	 * Update/edit a tag
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function update_tag() {
		if (empty($_POST['tag']['name'])) {
			$this->ee->session->set_flashdata('edit_validate', 'yes');
			$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=tag_entries".AMP.'tag_id='.$this->ee->input->post('tag_id'));
		}
		
		$tag_id = $this->ee->input->post('tag_id');
		$tag	= $this->ee->input->post('tag');
		
		$this->ee->tags->update($tag_id, $tag);
		
		$this->ee->session->set_flashdata('message_success', lang('taggable_tag_updated'));
		$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=tag_entries".AMP."tag_id=".$tag_id);
	}
	
	/**
	 * Tools page
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function tools() {
		$this->data['import'] = array(
			'taggable' => 'Taggable',
			'wordpress' => 'WordPress',
			'solspace' => 'Solspace Tag',
			'tagger' => 'Tagger Lite'
		);
		
		$this->_title('taggable_tools_title');
		return $this->_view('cp/tools');
	}
	
	/**
	 * Import from...
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function import() {
		$from = $this->ee->input->get_post('from');
		$this->data['errors'] 	= ($this->ee->session->flashdata('error') == 'yes') ? TRUE : FALSE;
		
		$this->_title('taggable_import_'.$from);
		return $this->_view('other/import/'.$from);
	}
	
	/**
	 * Import from Taggable
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function import_taggable() {
		// We won't use CI's uploader because we don't want
		// to move the file anywhere, we just want to store it
		// in memory.
		if (isset($_FILES['file'])) {
			if (is_uploaded_file($_FILES['file']['tmp_name'])) {
				$file = file_get_contents($_FILES['file']['tmp_name']);
			} else {
				$this->ee->session->set_flashdata('error', 'yes');
				$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=import".AMP."from=taggable");
			}
		} else {
			$this->ee->session->set_flashdata('error', 'yes');
			$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=import".AMP."from=taggable");
		}
		
		// Parse the JSON
		$tags = json_decode($file);
		
		// Loop through the tags
		foreach ($tags as $tag) {
			if (!$this->ee->tags->get_by('name', $tag->name)) {
				// ...and insert!
				$this->ee->tags->insert(array('name' => $tag->name, 'description' => $tag->description));
			}
		}
		
		// We're done!
		$this->ee->functions->redirect(TAGGABLE_URL.AMP.'method=import_success');
	}
	
	/**
	 * Import from WordPress
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function import_wordpress() {
		// Gather the database details, and make a DNS string
		$con = $this->ee->input->post('wordpress');
		$dns = "mysql://".$con['user'].":".$con['pass']."@".$con['host']."/".$con['name'];
		
		// Try to connect
		$db = DB($dns);
		$db->dbprefix = $con['prefix'];
		
		// Are we connected?
		if (!$db->conn_id) {
			$this->ee->session->set_flashdata('error', 'yes');
			$this->ee->functions->redirect(TAGGABLE_URL.AMP.'method=import'.AMP.'from=wordpress');
		}
		
		// Okay, WP's database is retarded. So we need to get
		// all the tag IDs and descriptions first, then the names :/
		$ids = $db->select('term_id, description')->where('taxonomy', 'post_tag')->get('term_taxonomy')->result();
		$ta = array();
		
		foreach ($ids as $id) {
			$ta[$id->term_id] = array('id' => $id->term_id, 'description' => $id->description);
		}
		
		// See? Really fucked up!
		$tags = $db->where_in('term_id', array_keys($ta))->get('terms')->result();
		
		// Okay, we've got the tags now, let's insert them
		foreach ($tags as $tag) {
			if (!$this->ee->tags->get_by('name', $tag->name)) {
				// ...and insert!
				$this->ee->tags->insert(array('name' => $tag->name, 'description' => $tag->description));
			}
		}
		
		// We're done!
		$this->ee->functions->redirect(TAGGABLE_URL.AMP.'method=import_success');
	}
	
	/**
	 * Import from Solspace
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function import_solspace() {
		// Gather the database details, and make a DNS string
		$con = $this->ee->input->post('solspace');
		$dns = "mysql://".$con['user'].":".$con['pass']."@".$con['host']."/".$con['name'];
		
		// Try to connect
		$db = DB($dns);
		$db->dbprefix = $con['prefix'];
		
		// Are we connected?
		if (!$db->conn_id) {
			$this->ee->session->set_flashdata('error', 'yes');
			$this->ee->functions->redirect(TAGGABLE_URL.AMP.'method=import'.AMP.'from=solspace');
		}
		
		// Get the tags from Tag
		$tags = $db->get('tag_tags')->result();
		
		// Okay, we've got the tags now, let's insert them
		foreach ($tags as $tag) {
			if (!$this->ee->tags->get_by('name', $tag->name)) {
				// ...and insert!
				$this->ee->tags->insert(array('name' => $tag->name, 'description' => $tag->description));
			}
		}
		
		// We're done!
		$this->ee->functions->redirect(TAGGABLE_URL.AMP.'method=import_success');
	}
	
	/**
	 * Import from Tagger Lite / Tagger
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function import_tagger() {
		// Gather the database details, and make a DNS string
		$con = $this->ee->input->post('tagger');
		$dns = "mysql://".$con['user'].":".$con['pass']."@".$con['host']."/".$con['name'];
		
		// Try to connect
		$db = DB($dns);
		$db->dbprefix = $con['prefix'];
		
		// Are we connected?
		if (!$db->conn_id) {
			$this->ee->session->set_flashdata('error', 'yes');
			$this->ee->functions->redirect(TAGGABLE_URL.AMP.'method=import'.AMP.'from=tagger');
		}
		
		// Get the tags from Tagger
		$tags = $db->get('tagger')->result();
		
		// Okay, we've got the tags now, let's insert them
		foreach ($tags as $tag) {
			if (!$this->ee->tags->get_by('name', $tag->name)) {
				// ...and insert!
				$this->ee->tags->insert(array('name' => $tag->name, 'description' => $tag->description));
			}
		}
		
		// We're done!
		$this->ee->functions->redirect(TAGGABLE_URL.AMP.'method=import_success');
	}
	
	/**
	 * Successful import!
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function import_success() {
		$this->_title('taggable_import_success');
		return $this->_view('other/import/success');
	}
		
	/**
	 * Export
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function export() {
		$tags = $this->ee->tags->get_all();
		$output = array();
		
		foreach ($tags as $tag) {
			$output[] = array(
				'name' => $tag->name,
				'description' => $tag->description
			);
		}
		
		$content = json_encode($output);
		
		$this->ee->load->helper('download');
		force_download('taggable_export_'.time().'.json', $content);
	}
	
	/**
	 * Merge tags
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function merge_tags() {
		// Get all the tags
		$this->data['tags'] = $this->ee->db->order_by('name ASC')->get('taggable_tags')->result();
		
		// Load the view
		$this->_title('taggable_merge_tags');
		return $this->_view('cp/merge');
	}
	
	/**
	 * Process the merge tags
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function process_merge_tags() {
		// Get the inputs
		$master_tag = $this->ee->tags->get($this->ee->tags->input->post('master_tag'));
		$merge_tags = $this->ee->input->post('merge_tags');
		
		// Okay, go and get each entry
		foreach ($merge_tags as $tag) {
			$tag = $this->ee->db->where('id', $tag)->get('taggable_tags')->row();
			
			// Get the entries
			$entries = $this->ee->db->where('tag_id', $tag->id)->get('taggable_tags_entries')->result();
			foreach ($entries as $entry): $nen[] = $entry->entry_id; endforeach;
						
			// Loop through the entries and remove from field data
			foreach ($entries as $entry) {
				// Let's not support Matrix yet...
				if (!preg_match("/^matrix_field_id_(\d+)_row_id_(\d+)_col_id_(\d+)$/", $entry->template)) {
					if ($entry->entry_id) {
						// Get the field ID from the template
						$field = $this->_fetch_field_id_and_field_settings($entry->entry_id, $entry->template);
						
						$field_id = $field->field_id;
						$field_settings = unserialize(base64_decode($field->field_settings));
						
						// Get the field data from the DB
						$data = $this->ee->db->select('field_id_'.$field_id)->where('entry_id', $entry->entry_id)->get('channel_data')->row('field_id_'.$field_id);
						
						// Split the data into IDs and remove the tag
						$current_data = $this->_get_ids_and_names($data);
						unset($current_data[$tag->id]);
						$this->ee->db->where('tag_id', $tag->id)->where('entry_id', $entry->entry_id)->delete('taggable_tags_entries');
						
						// Rewrite the data
						$new_data = "";
						
						foreach ($current_data as $id => $name) {
							if ($id !== $master_tag->id) {
								$new_data .= "[".$id."] ".$name." ".str_replace(' ', $field_settings['taggable_url_separator'], $name)."\n";
							}
						}
						
						// Add the master tag in
						$new_data .= "[".$master_tag->id."] ".$master_tag->name." ".str_replace(' ', $field_settings['taggable_url_separator'], $master_tag->name)."\n";
						
						// Link the entry
						if ($this->ee->db->where(array('entry_id' => $entry->entry_id, 'tag_id' => $master_tag->id, 'template' => $entry->template))->get('taggable_tags_entries')->num_rows == 0) {
							$this->ee->db->insert('taggable_tags_entries', array('entry_id' => $entry->entry_id, 'tag_id' => $master_tag->id, 'template' => $entry->template));
						}
						
						// Store it back
						$this->ee->db->where('entry_id', $entry->entry_id)->set('field_id_'.$field_id, $new_data)->update('channel_data');
					}
				}
			}
			
			// Delete the tag!
			$this->ee->db->where('id', $tag->id)->delete('taggable_tags');
		}
	}
	
	/**
	 * Tag indexing tool
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function index_tags() {
		// Let's first get the IDs of every Taggable field
		$ids = $this->ee->db->select('field_id, field_name')->where('field_type', 'taggable')->get('channel_fields')->result();
		$entry_data = array();
		
		// Now, let's go and get every entry with tags. Get the channel_data!
		foreach ($ids as $id) {
			$id = $id->field_id;
			
			// Unfortunately, we don't know how many fields there are,
			// so we have to SELECT *.
			$query = $this->ee->db->where("field_id_$id != ''")->get('channel_data');
			
			foreach ($query->result() as $row) {
				// Check for dupes!
				if (!in_array($row, $entry_data)) {
					$entry_data[] = $row;
				}
			}
		}
		
		// Now we've got the entries, get the tag data!
		foreach ($ids as $ar_id) {
			$id = $ar_id->field_id;
			$template = $ar_id->field_name;
			
			foreach ($entry_data as $entry) {
				$key = "field_id_$id";
				
				if ($entry->$key) {
					$tags = $this->_index_tags_parse($entry->$key);
					
					// Are the tags in the taggable_tags_entries table?
					foreach ($tags as $tag) {
						$params = array('tag_id' => $tag, 'entry_id' => $entry->entry_id, 'template' => $template);
						
						if ($this->ee->db->where($params)->get('taggable_tags_entries')->num_rows == 0) {
							$this->ee->db->insert('taggable_tags_entries', $params);
						}
					}
				}
			}
		}
		
		// That went well! Redirect back!
		$this->ee->session->set_flashdata('message_success', lang('taggable_tags_indexed'));
		$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=tools");
	}
	
	/**
	 * Parse the tags from exp_channel_data
	 *
	 * @param string $string 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function _index_tags_parse($string) {
		$lines = explode("\n", $string);
		$tags = array();
		
		foreach ($lines as $line) {
			$id = (int)preg_replace("/^\[([0-9]+)\]/", "$1", $line);
			
			if ($id > 0) {
				$tags[] = $id;
			}
		}
		
		return $tags;
	}
	
	/**
	 * Get an assoc array of IDs and names
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	protected function _get_ids_and_names($data) {
		$lines = explode("\n", $data);
		$names = array();
		
		foreach ($lines as $line) {
			if ($line) {
				$id = preg_replace('/^\[([0-9]+)\] (.+) ([^\s]+)/', "$1", $line);
				$name = preg_replace('/^\[([0-9]+)\] (.+) ([^\s]+)/', "$2", $line);
				
				$names[$id] = $name;
			}
		}
		
		return $names;
	}
		
	/**
	 * Preferences page...
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function preferences() {
		if ($this->ee->input->post('taggable_license_key')) {
			// Save license key & theme
			$key = $this->ee->input->post('taggable_license_key');
			$default_theme = $this->ee->input->post('taggable_default_theme');
			
			$this->ee->config->_update_config(array('taggable_license_key' => $key, 'taggable_default_theme' => $default_theme)); 
			
			// Show alert and redirect
			$this->ee->session->set_flashdata('message_success', lang('taggable_preferences_saved'));
			$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=preferences");
		}
		
		$this->data['themes'] = $this->_get_themes();
		$this->data['default_theme'] = $this->ee->config->item('taggable_default_theme');
		$this->data['api_endpoint'] = base_url() . "?ACT=" . $this->ee->cp->fetch_action_id('Taggable', 'api_entries');
		
		$this->_title("taggable_preferences_title");
		return $this->_view('cp/preferences');
	}
	
	// ----------
	// AJAX stuff
	// ----------
	
	/**
	 * Search the tag database!
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function ajax_search() {
		$term = $this->ee->input->get('q');
		$query = $this->ee->db->where('name LIKE ', "%$term%")->where('site_id', $this->site_id)->get('exp_taggable_tags')->result();
		$tags = array();
		
		foreach ($query as $tag) {
			$tags[] = array(
				'id' 	=> $tag->id,
				'name'	=> $tag->name,
				'value' => $tag->name
			);
		}
		
		die(json_encode($tags));
	}
	
	/**
	 * Create a new tag via AJAX
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function ajax_create() {
		$name = htmlentities($this->ee->input->get('tag_name'));
		$id   = $this->ee->tags->insert(array('tag_name' => $name, 'site_id' => $this->ee->config->item('site_id')));
		
		if ($id) {
			die(json_encode(array(
				'success'	=> TRUE,
				'tag_id' 	=> $id,
				'tag_name'	=> html_entity_decode($name)
			)));
		} else {
			die(json_encode(array(
				'success'	=> FALSE
			)));
		}
	}
	
	// --------------
	// Layout helpers
	// --------------
	
	/**
	 * Set page titles and breadcrumb
	 *
	 * @param string $title 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _title($title) {
		$this->data['title'] = lang($title);
		
		$this->ee->cp->set_variable('cp_page_title', lang($title)); 
		$this->ee->cp->set_breadcrumb(TAGGABLE_URL, "Taggable");
	}
	
	/**
	 * Load the view through $this->data
	 *
	 * @param string $view 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _view($view) {
		return $this->ee->load->view($view, $this->data, TRUE);
	}
	
	/**
	 * Get the theme folder URL
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _theme_url() {
		if (!isset($this->ee->session->cache['taggable']['theme_url'])) {
			$theme_folder_url = $this->ee->config->item('theme_folder_url');
			if (substr($theme_folder_url, -1) != '/') $theme_folder_url .= '/';
			$this->ee->session->cache['taggable']['theme_url'] = $theme_folder_url.'third_party/taggable/';
		}

		return $this->ee->session->cache['taggable']['theme_url'];
	}
	
	// ------
	// Misc
	// ------
	
	/**
	 * Is the license key valid?
	 *
	 * @param string $key 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _valid($key) {
		return preg_match("/^([A-Z0-9a-z]{8}\-[A-Z0-9a-z]{4}\-[A-Z0-9a-z]{4}\-[A-Z0-9a-z]{4}\-[A-Z0-9a-z]{12})$/", $key);
	}
	
	/**
	 * Get UI themes
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _get_themes() {
		if (!isset($this->ee->session->cache['taggable']['themes'])) {
			$theme_folder_path = $this->ee->config->item('theme_folder_path');
			if (substr($theme_folder_path, -1) != '/') $theme_folder_path .= '/';
			
			$themes = array();
			$dir = @scandir($theme_folder_path . 'third_party/taggable/css/');
			
			foreach ($dir as $file) {
				if ($file[0] !== '.') {
					$themes[$file] = ucwords(str_replace('-', ' ', $file));
				}
			}
			
			$this->ee->session->cache['taggable']['themes'] = $themes;
		}
		
		return $this->ee->session->cache['taggable']['themes'];
	}
	
	/**
	 * Fetch the field ID via the name and entry
	 *
	 * @param string $id 
	 * @param string $template 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _fetch_field_id_and_field_settings($id, $template) {
		return $this->ee->db->select('exp_channel_fields.field_id, exp_channel_fields.field_settings')
						 	->where('exp_channels.channel_id = exp_channel_titles.channel_id')
							->where('exp_channels.field_group = exp_field_groups.group_id')
							->where('exp_field_groups.group_id = exp_channel_fields.group_id')
							->where('exp_channel_titles.entry_id', $id)
							->where('exp_channel_fields.field_name', $template)
							->get('exp_channel_fields, exp_channel_titles, exp_field_groups, exp_channels')
							->row();
	}
}