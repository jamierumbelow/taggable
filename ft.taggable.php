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

class Taggable_ft extends EE_Fieldtype {
	
	public $info = array(
		'name' 		=> 'Tag',
		'version'	=> TAGGABLE_VERSION
	);
	
	public function __construct() {
		parent::EE_Fieldtype();
	}
	
	public function display_field($data = "") {
		if (isset($_POST[$this->field_name])) {
			$data = $_POST[$this->field_name];
		}
		
		$this->EE->load->library('model');
		$this->EE->load->model('preferences_model', 'preferences');
		
		$this->_javascript($this->field_name, $data);
		$this->_stylesheet();
		
		$pre = '';
		
		if (!isset($this->EE->preferences->punches['deleted'])) {
			$pre = '<input type="hidden" name="taggable_tags_delete" id="taggable_tags_delete" value="" />';
			$this->EE->preferences->punches['deleted'] = TRUE;
		}
		
		return $pre . form_input(array(
			'name' 	=> $this->field_name,
			'id'	=> $this->field_name,
			'value'	=> ''
		));
	}
	
	public function save($str) {
		return $str;
	}
	
	public function post_save($data) {	
		// Delete tags
		if (isset($_POST['taggable_tags_delete'])) {
			$tags = explode(',', $_POST['taggable_tags_delete']);
			array_pop($tags);
			
			foreach ($tags as $tag) {
				if (is_numeric($tag)) {
					$this->EE->db->where('entry_id', $this->settings['entry_id'])
								 ->where('tag_id', $tag)
								 ->delete('exp_tags_entries');
				}
			}
		}
		
		// Create tags	
		if (!empty($_POST[$this->field_name])) {
			$tags = explode(',', $_POST[$this->field_name]);
			array_pop($tags);
			
			$template = $this->_get_template();

			foreach ($tags as $tag) {
				if (!empty($tag)) {
					if (!is_numeric($tag)) {
						$query = $this->EE->db->query("SELECT * FROM exp_tags WHERE tag_name = '$tag' AND site_id = ".$this->EE->config->item('site_id'));
					
						if ($query->num_rows == 0) { 
							$tag = $this->EE->tags->insert(array('tag_name' => $tag, 'site_id' => $this->EE->config->item('site_id')));
						} else {
							$tag = $query->row('tag_id');
						}
					}
			
					$num_rows = $this->EE->db->query("SELECT * FROM exp_tags_entries WHERE tag_id = $tag AND entry_id = {$this->settings['entry_id']}")->num_rows;
				
					if ($num_rows == 0) {
						$this->EE->db->insert('exp_tags_entries', array(
							'tag_id' 	=> $tag,
							'entry_id'	=> $this->settings['entry_id'],
							'template'	=> $template
						));
					}
				}
			}
		}
	}
	
	public function delete($ids) {
		$this->ee->db->where_in('entry_id', $ids);
		$this->ee->db->delete('exp_tags_entries');
	}
	
	private function _get_template() {
		if (substr($this->field_id, 0, 10) == "taggable__") {
			$f = str_replace('taggable__', '', $this->field_id);
		} else {
			$f = $this->field_id;
		}
		
		$a = $this->EE->preferences->get_by('preference_key', 'default_tag_name')->preference_value;
		
		if ($f !== $a) {
			$q = $this->EE->db->select('field_name')
									 ->where('field_id', $this->field_id)
									 ->get('exp_channel_fields')
									 ->row('field_name');
		} else {
			$q = $a;
		}
		
		return $q;
	}
	
	private function _javascript($field_name, $data = "") {		
		$js = array(
			'hintText'		 	 	=> lang('taggable_javascript_hint'),
			'noResultsText'	  		=> lang('taggable_javascript_no_results'),
			'searchingText'	 	 	=> lang('taggable_javascript_searching'),
			'pleaseEnterText' 		=> lang('taggable_javascript_please_enter'),
			'noMoreAllowedText'		=> lang('taggable_javascript_limit'),
			'autotaggingComplete'	=> lang('taggable_javascript_autotagging_complete'),
			'searchUrl'				=> '?D=cp&C=addons_modules&M=show_module_cp&module=taggable&method=ajax_search',
			'createUrl'				=> '?D=cp&C=addons_modules&M=show_module_cp&module=taggable&method=ajax_create',
			'autotagUrl'			=> '?D=cp&C=addons_modules&M=show_module_cp&module=taggable&method=ajax_autotag'			
		);
		
		foreach ($js as $name => $value) {
			$this->EE->javascript->set_global("tag.$name", $value);
		}
		
		$this->EE->cp->load_package_js('jquery.autocomplete');
		
		$js = '$("#'.$field_name.'").tokenInput(EE.tag.searchUrl, EE.tag.createUrl, { autotagUrl: EE.tag.autotagUrl, lang: EE.tag,';
		
		if ($data) { 
			$ids 	= explode(',', $data);
			$datar 	= array();
			
			foreach ($ids as $id) {
				if (!empty($id)) {
					$name = $this->EE->db->select('tag_name')->where('tag_id', $id)->get('exp_tags')->row('tag_name');
				
					$datar[] = array(
						'id' 	=> $id,
						'name'	=> $name
					);
				}
			}

			$js .= 'prePopulate: '.json_encode($datar).',';
		}
		
		$pref = $this->EE->preferences->get_by('preference_key', 'maximum_tags_per_entry');
		
		if (!(int)$pref->preference_value === 0) {
			$js .= 'tokenLimit: '.$pref->preference_value.',';
		}
		
		$js .= 'a:{}});';
		
		if ($this->EE->preferences->get_by('preference_key', 'enable_autotagging')->preference_value == 'y' && !isset($this->EE->preferences->punches['autotagged'])) {
			$js .= '
			
				var element = $("<a href=\"\" />");
				element.attr("id", "autotagging");
				element.attr("class", "submit");
				element.attr("style", "color: white; text-decoration: none");
				element.text("Autotag");
				
				$("#sub_hold_field_taggable__taggable_autotag p").append(element);
			';	
			
			$this->EE->preferences->punches['autotagged'] = TRUE;
		}
		
		$this->EE->javascript->output($js);
	}
	
	private function _stylesheet() {
		$this->EE->cp->load_package_css('autocomplete');
	}
}