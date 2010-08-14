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

require_once PATH_THIRD."taggable/config.php";

define('TAGGABLE_URL', BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taggable');
define('TAGGABLE_PATH', 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taggable');

class Taggable_mcp {
	private $ee;
	
	public $data 		= array();
	public $docs_url	= "http://gettaggable.com/docs/";
	
	public function __construct() {
		$this->ee =& get_instance();
		
		// Load libraries
		$this->ee->load->library('model');
		$this->ee->load->library('form_validation');
		
		// Load models and helpers
		$this->ee->load->model('taggable_preferences_model', 'preferences');
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
		$this->data['license_key'] = $this->ee->preferences->get_by('preference', 'license_key')->value;
		
		// MSM
		$this->site_id = $this->ee->config->item('site_id');
				
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
	
	public function index() {
		$this->data['tags_alphabetically'] = $this->ee->tags->get_alphabet_list();
		$this->data['stats']			   = $this->ee->tags->stats();
		
		$this->_title("taggable_module_name");
		return $this->_view('cp/index');
	}
	
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
	
	public function tag_entries() {
		$tag = $this->ee->input->get('tag_id');
		
		$this->data['tag'] 		= $this->ee->tags->get($tag);
		$this->data['errors'] 	= ($this->ee->session->flashdata('edit_validate') == 'yes') ? TRUE : FALSE;
		$this->data['entries']	= $this->ee->tags->tag_entries($tag);
		
		$this->_title("taggable_edit_tag");
		return $this->_view('cp/entries');
	}
	
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
	
	public function delete_tags() {
		$tags = $this->ee->input->post('delete_tags');
		
		$this->ee->tags->delete_many($tags);
		$this->ee->tags->delete_entries($tags);
		
		$this->ee->session->set_flashdata('message_success', "Tag(s) deleted");
		$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=tags");
	}
	
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
	
	public function import() {
		$from = $this->ee->input->get_post('from');
		$this->data['errors'] 	= ($this->ee->session->flashdata('error') == 'yes') ? TRUE : FALSE;
		
		$this->_title('taggable_import_'.$from);
		return $this->_view('other/import/'.$from);
	}
	
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
	
	public function import_success() {
		$this->_title('taggable_import_success');
		return $this->_view('other/import/success');
	}
	
	public function diagnostics() {
		$tests = array();
		
		// Generic tests
		$tests['generic']['php_version'] = $this->_test(phpversion(), phpversion() > '5.2');
		$tests['generic']['ee_version'] = $this->_test(APP_VER, APP_VER > '2.0.2');
		$tests['generic']['ee_build'] = $this->_test(APP_BUILD, APP_BUILD >= '201004');
		$tests['generic']['extensions_enabled'] = $this->_test($this->ee->config->item('allow_extensions'), $this->ee->config->item('allow_extensions') == 'y');
		
		// Go
		$this->data['tests'] = $tests;
		
		// Download
		if ($this->ee->input->get('download_report') == 'yes') {
			$this->ee->load->helper('download');
			force_download('taggable_diagnostics_'.time().'.txt', $this->_view('other/diagnostics_report'));
		}
		
		$this->_title('taggable_diagnostics');
		return $this->_view('cp/diagnostics');
	}
	
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
	
	public function upgrade() {
		$channels = $this->ee->api_channel_structure->get_channels()->result_object();
		$c_array = array();
		
		foreach ($channels as $channel) {
			$c_array[$channel->channel_id] = $channel->channel_title;
		}
		
		$this->data['channels'] = $c_array;
		
		$this->_title('taggable_upgrade_utility');
		return $this->_view('cp/upgrade');
	}
	
	public function upgrade_field() {
		$this->ee->load->library('api/api_channel_fields');
		$group = $this->ee->db->select('field_group')->where('channel_id', $this->ee->input->post('channel'))->get('channels')->row('field_group');
		
		$this->ee->db->where('group_id', $group);
		$this->ee->db->where('field_type', 'taggable');
		
		$fields = $this->ee->db->get('channel_fields')->result();
		$f_array = array();
		
		foreach ($fields as $field) {
			$f_array[$field->field_name] = $field->field_label;
		}
		
		$this->data['fields'] = $f_array;
		$this->data['channel'] = $this->ee->input->post('channel');
		
		$this->_title('taggable_upgrade_utility');
		return $this->_view('cp/upgrade_field');
	}
	
	public function upgrade_do() {
		$channel = $this->ee->input->post('channel');
		$field = $this->ee->input->post('field');
		$field_id = $this->ee->db->select('field_id')->where('field_name', $field)->get('channel_fields')->row('field_id');
		
		// select all the entries for this channel
		$entries = $this->ee->db->select('entry_id')->where('channel_id', $channel)->get('channel_titles')->result();
		
		// Loop through each entry and assign the template to that entry's tags
		foreach ($entries as $entry) {
			$this->ee->db->where('entry_id', $entry->entry_id)->set('template', $field)->update('taggable_tags_entries');
			
			// Get all the tags for this entry
			$tags = $this->ee->db->where('entry_id', $entry->entry_id)->get('taggable_tags_entries')->result();
			$tagi = array();
			
			foreach ($tags as $tag) {
				$tagi[] = $tag->tag_id;
			}
			
			// Build the string
			$str = implode(',', $tagi);
			$str = $str . ',';
			
			// Set the exp_channel_data
			$this->ee->db->where('entry_id', $entry->entry_id)
						 ->set('field_id_'.$field_id, $str)
						 ->update('channel_data');
		}
		
		// Done!
		$this->ee->session->set_flashdata('message_success', lang('taggable_entries_upgraded'));
		$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=tools");
	}
	
	public function preferences() {
		if ($this->ee->input->post('save_preferences')) {
			// Save preference
			$preferences = $this->ee->input->post('preferences');
			
			foreach ($preferences as $key => $p): 
				$this->ee->preferences->update($key, array('value' => $p['value'], 'site_id' => $this->site_id)); 
			endforeach;
			
			// Show alert and redirect
			$this->ee->session->set_flashdata('message_success', lang('taggable_preferences_saved'));
			$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=preferences");
		}
		
		$this->ee->db->where('site_id', $this->site_id);
		$this->data['preferences'] = $this->ee->preferences->get_all();
		
		$this->_title("taggable_preferences_title");
		return $this->_view('cp/preferences');
	}
	
	// AJAX stuff
	public function ajax_search() {
		$term = $this->ee->input->get('q');
		$query = $this->ee->db->where('name LIKE ', "%$term%")->where('site_id', $this->site_id)->get('exp_taggable_tags')->result();
		$tags = array();
		
		foreach ($query as $tag) {
			$tags[] = array(
				'id' 	=> $tag->id,
				'name'	=> $tag->name
			);
		}
		
		die(json_encode($tags));
	}
	
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
	
	public function ajax_stylesheet() {
		header("Content-type: text/css");
		die(file_get_contents(PATH_THIRD."taggable/css/autoSuggest.css"));
	}
	
	public function ajax_javascript() {
		header("Content-type: text/javascript");
		die(file_get_contents(PATH_THIRD."taggable/javascript/jquery.autoSuggest.js"));
	}
	
	public function image() {
		header("Content-type: image/png");
		die(file_get_contents(PATH_THIRD."taggable/images/".$_GET['file'].'.png'));
	}
	
	// Layout helpers
	private function _title($title) {
		$this->data['title'] = lang($title);
		
		$this->ee->cp->set_variable('cp_page_title', lang($title)); 
		$this->ee->cp->set_breadcrumb(TAGGABLE_URL, "Taggable");
	}
	
	private function _view($view) {
		return $this->ee->load->view($view, $this->data, TRUE);
	}
	
	// Misc
	private function _valid($key) {
		return preg_match("/^([A-Z0-9a-z]{8}\-[A-Z0-9a-z]{4}\-[A-Z0-9a-z]{4}\-[A-Z0-9a-z]{4}\-[A-Z0-9a-z]{12})$/", $key);
	}
	
	private function _test($value, $exp) {
		return array(
			'value' => $value,
			'success' => (bool)$exp
		);
	}
}