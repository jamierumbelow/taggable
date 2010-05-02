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
		
		$this->_title("taggable_tags_title");
		return $this->_view('cp/tags');
	}
	
	public function tag_entries() {
		$tag = $this->ee->input->get('tag_id');
		
		$this->data['tag'] 		= $this->ee->tags->get($tag);
		$this->data['entries']	= $this->ee->db->select('DISTINCT exp_tags_entries.entry_id, exp_channel_titles.title, exp_channel_titles.url_title, exp_channel_titles.channel_id')
											   ->where('exp_tags_entries.tag_id', $tag)
											   ->where('exp_channel_titles.entry_id = exp_tags_entries.entry_id')
											   ->get('exp_tags_entries, exp_channel_titles')
											   ->result();
		
		$this->_title("taggable_edit_tag");
		return $this->_view('cp/entries');
	}
	
	public function create_tag() {
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
	
	public function preferences() {
		if ($this->ee->input->post('save_preferences')) {
			// Save preference
			$preferences = $this->ee->input->post('preferences');
			
			foreach ($preferences as $key => $p): 
				$this->ee->preferences->update($key, array('preference_value' => $p['preference_value'])); 
			endforeach;
			
			// Show alert and redirect
			$this->ee->session->set_flashdata('message_success', lang('taggable_preferences_saved'));
			$this->ee->functions->redirect(TAGGABLE_URL.AMP."method=preferences");
		}
		
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
}