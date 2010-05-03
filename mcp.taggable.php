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
		$this->data['license_key'] = $this->ee->preferences->get_by('preference_key', 'license_key')->preference_value;
		
		// MSM
		$this->site_id 				= $this->ee->config->item('site_id');
		$this->ee->tags->site_id	= $this->site_id;
		
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
		
		// debug		// 
				// $this->ee->db->save_queries = TRUE;
				// $this->ee->output->enable_profiler(TRUE);
	}
	
	public function index() {
		$this->data['tags_alphabetically'] = $this->ee->tags->get_alphabet_list();
		$this->data['stats']			   = $this->ee->tags->stats();
		
		$this->_title("taggable_module_name");
		return $this->_view('cp/index');
	}
	
	// @todo Put all the queries and such in the model
	public function tags() {
		$this->data['order'] 			 = 'tag_id';
		$this->data['text_search_order'] = 'sw';
		$this->data['text_search'] 		 = '';
		$this->data['entry_count_order'] = 'mt';
		$this->data['entry_count'] 		 = '';
		
		$this->ee->db->select('exp_tags.tag_id, exp_tags.tag_name, exp_tags.tag_description');
		$this->ee->db->where('exp_tags.site_id', $this->site_id);
		$this->ee->db->from('exp_tags');
		$this->ee->db->group_by('exp_tags.tag_id');
		
		if ($this->ee->input->get('order')) {
			if ($this->ee->input->get('order') == "entries") {
				$this->ee->tags->order_by_entries();
			} else {
				$this->ee->tags->order_by($this->ee->input->get('order'));
			}
			
			$this->data['order'] = $this->ee->input->get('order');
		}
		
		if ($term = $this->ee->input->get('text_search')) {
			switch ($this->ee->input->get('text_search_order')) {
				case 'sw':
					$this->ee->db->where("exp_tags.tag_name LIKE ", $term."%");
					break;
				
				case 'co':
					$this->ee->db->where("exp_tags.tag_name LIKE ", "%".$term."%");
					break;
				
				case 'ew':
					$this->ee->db->where("exp_tags.tag_name LIKE ", "%".$term);
					break;
			}
			
			$this->data['text_search_order'] = $this->ee->input->get('text_search_order');
			$this->data['text_search']		 = $this->ee->input->get('text_search');
		}
		
		if ($count = $this->ee->input->get('entry_count')) {
			$this->ee->db->select('COUNT(DISTINCT exp_tags_entries.entry_id) AS entry_count');
			$this->ee->db->join('exp_tags_entries', 'exp_tags_entries.tag_id = exp_tags.tag_id', 'left');
			
			switch ($this->ee->input->get('entry_count_order')) {
				case 'mt':
					$this->ee->db->having('entry_count > ', $this->ee->input->get('entry_count'));
					break;
					
				case 'lt':
					$this->ee->db->having('entry_count < ', $this->ee->input->get('entry_count'));
					break;
					
				case 'et':
					$this->ee->db->having('entry_count = ', $this->ee->input->get('entry_count'));
					break;
			}
			
			$this->data['entry_count'] 			= $this->ee->input->get('entry_count');
			$this->data['entry_count_order']	= $this->ee->input->get('entry_count_order');
		}

		$this->data['tags'] = $this->ee->db->get()->result();		
		$this->data['tags_alphabetically'] = $this->ee->tags->get_alphabet_list();
		$this->data['errors'] = ($this->ee->session->flashdata('create_validate') == 'yes') ? TRUE : FALSE;
		
		$this->_title("taggable_tags_title");
		return $this->_view('cp/tags');
	}
	
	public function tag_entries() {
		$tag = $this->ee->input->get('tag_id');
		
		$this->data['tag'] 		= $this->ee->tags->get($tag);
		$this->data['errors'] 	= ($this->ee->session->flashdata('edit_validate') == 'yes') ? TRUE : FALSE;
		$this->data['entries']	= $this->ee->db->select('DISTINCT exp_tags_entries.entry_id, exp_channel_titles.title, exp_channel_titles.url_title, exp_channel_titles.channel_id')
											   ->where('exp_tags_entries.tag_id', $tag)
											   ->where('exp_channel_titles.entry_id = exp_tags_entries.entry_id')
											   ->get('exp_tags_entries, exp_channel_titles')
											   ->result();
		
		$this->_title("taggable_edit_tag");
		return $this->_view('cp/entries');
	}
	
	public function create_tag() {
		if (empty($_POST['tag']['tag_name'])) {
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
		
		foreach ($tags as $tag) {
			$this->ee->db->where('tag_id', $tag)->delete('exp_tags_entries');
		}
		
		$this->ee->session->set_flashdata('message_success', "Tag(s) deleted");
		$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=tags");
	}
	
	public function update_tag() {
		if (empty($_POST['tag']['tag_name'])) {
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
			if ($this->ee->db->where('tag_name', $tag->name)->get('tags')->num_rows == 0) {
				// ...and insert!
				$this->ee->db->insert('tags', array('tag_name' => $tag->name, 'tag_description' => $tag->description));
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
			if ($this->ee->db->where('tag_name', $tag->name)->get('tags')->num_rows == 0) {
				// ...and insert!
				$this->ee->db->insert('tags', array('tag_name' => $tag->name, 'tag_description' => $ta[$tag->term_id]['description']));
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
			if ($this->ee->db->where('tag_name', $tag->tag_name)->get('tags')->num_rows == 0) {
				// ...and insert!
				$this->ee->db->insert('tags', array('tag_name' => $tag->tag_name));
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
			if ($this->ee->db->where('tag_name', $tag->tag_name)->get('tags')->num_rows == 0) {
				// ...and insert!
				$this->ee->db->insert('tags', array('tag_name' => $tag->tag_name));
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
		
		$this->_title('taggable_diagnostics_title');
		return $this->_view('cp/diagnostics');
	}
	
	public function export() {
		$tags = $this->ee->db->get('tags');
		$output = array();
		
		foreach ($tags->result() as $tag) {
			$output[] = array(
				'name' => $tag->tag_name,
				'description' => $tag->tag_description
			);
		}
		
		$content = json_encode($output);
		
		$this->ee->load->helper('download');
		force_download('taggable_export_'.time().'.json', $content);
	}
	
	public function preferences() {
		if ($this->ee->input->post('save_preferences')) {
			// Save preference
			$preferences = $this->ee->input->post('preferences');
			
			foreach ($preferences as $key => $p): 
				$this->ee->preferences->update($key, array('preference_value' => $p['preference_value'], 'site_id' => $this->site_id)); 
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
		$query = $this->ee->db->where('tag_name LIKE ', "%$term%")->where('site_id', $this->site_id)->get('exp_tags')->result();
		$tags = array();
		
		foreach ($query as $tag) {
			$tags[] = array(
				'id' 	=> $tag->tag_id,
				'name'	=> $tag->tag_name
			);
		}
		
		die(json_encode($tags));
	}
	
	public function ajax_create() {
		$name = $this->ee->input->get('tag_name');
		$id   = $this->ee->tags->insert(array('tag_name' => $name, 'site_id' => $this->ee->config->item('site_id')));
		
		if ($id) {
			die(json_encode(array(
				'success'	=> TRUE,
				'tag_id' 	=> $id,
				'tag_name'	=> $name
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
		return preg_match("/^([A-Z]{4}\-[0-9]{4}\-[A-Z]{4}\-[0-9]{4})$/", $key);
	}
	
	private function _test($value, $exp) {
		return array(
			'value' => $value,
			'success' => (bool)$exp
		);
	}
}