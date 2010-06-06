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

class Taggable_ft extends EE_Fieldtype {
	public $has_array_data = TRUE;
	public $info = array(
		'name' 		=> 'Taggable',
		'version'	=> TAGGABLE_VERSION
	);
	
	public function __construct() {
		parent::EE_Fieldtype();
		$this->EE->lang->loadfile('taggable');
	}
	
	public function display_field($data = "") {
		if (isset($_POST[$this->field_name])) {
			$data = $_POST[$this->field_name];
		}
		
		$this->EE->load->library('model');
		$this->EE->load->model('taggable_preferences_model', 'preferences');
		
		$tags = explode(',', $data);
		array_pop($tags);		
		$tags = implode(',', $tags).',';
		$data = $tags;
		
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
	
	public function replace_tag($data, $params = array(), $tagdata = FALSE) {
		$ids = explode(',', $data);
		array_pop($ids);
		$return = '';
	    $vars = '';
		
		if ($ids) {
			$tags = $this->EE->db->where_in('tag_id', $ids)->get('tags')->result();
		
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
				}
			
				$vars = $tag_rows;			
				$return = $this->_no_parse_if_no_tags($tagdata);
			}
			
			// parse
			$return = $this->EE->TMPL->parse_variables($return, $vars);
			
			// Backspace
			if (isset($params['backspace'])) {
				$return = substr($return, 0, -$params['backspace']);	
			}
		} else {
			$return = "";
		}
		
		// done!
		return $return;
	}
	
	public function save($data) {
		$tags = explode(',', $data);
		array_pop($tags);
		
		foreach ($tags as $key => $tag) {
			if (!is_numeric($tag)) {
				// Is it in the DB? What's the ID?
				$query = $this->EE->db->where('tag_name', $tag)->get('tags');
				
				if ($query->num_rows > 0) {
					$tags[$key] = $query->row('tag_id');
				} else {
					$this->EE->db->insert('tags', array('tag_name' => $tag));
					$tags[$key] = $this->EE->db->insert_id();
				}
			}
		}
		
		$data = $data . ',';
		$data = implode(',', $tags);
		
		return $data;
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
							$tag = $this->EE->db->insert('tags', array('tag_name' => $tag, 'site_id' => $this->EE->config->item('site_id')));
							$tag = $this->EE->db->insert_id();
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
		$this->EE->db->where_in('entry_id', $ids);
		$this->EE->db->delete('exp_tags_entries');
	}
	
	protected function _parse_if_no_tags($tagdata) {
		return preg_replace("/{if no_tags}(.*){\/if}/", '$1', $tagdata);
	}
		
	protected function _no_parse_if_no_tags($tagdata) {
		return preg_replace("/{if no_tags}(.*){\/if}/", '', $tagdata);
	}
	
	protected function tag_entries($id) {
		return $this->EE->db->select("COUNT(DISTINCT entry_id) AS total")
							->where("tag_id", $id)
							->get('tags_entries')
							->row('total');
	}
	
	private function _get_template() {
 		return $this->EE->db->select('field_name')
						 	->where('field_id', $this->field_id)
							->get('exp_channel_fields')
							->row('field_name');
	}
	
	private function _javascript($field_name, $data = "") {		
		$js = array(
			'hintText'		 	 	=> lang('taggable_javascript_hint'),
			'noResultsText'	  		=> lang('taggable_javascript_no_results'),
			'searchingText'	 	 	=> lang('taggable_javascript_searching'),
			'pleasEEnterText' 		=> lang('taggable_javascript_please_enter'),
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
		
		$this->EE->javascript->output($js);
	}
	
	private function _stylesheet() {
		$this->EE->cp->load_package_css('autocomplete');
	}
	
	/**
	 * A pretty sexy method to generically parse a parameter
	 * that can contain multiple values, with support for "not",
	 * and then call the correct database methods on it.
	 *
	 * Also supports passing through an additional lookup column/table
	 *
	 * @param string $string 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function parse_multiple_params($id_col, $string, $lookup_table = '', $lookup_col = '', $lookup_id = '') {
		if (strpos($string, "not ") !== FALSE) {
			// It's a "not" query
			if (strpos($string, "|")) {
				// multiple nots
				$string = str_replace("not ", "", $string);
				$string = str_replace(" ", "", $string);
				
				$vals = explode('|', $string);
				
				// Lookup?
				if ($lookup_table) {
					$new_vals = array();
					
					foreach ($vals as $key => $val) {
						$v = $this->EE->db->where($lookup_col, $val)->get($lookup_table);
						
						if ($v->num_rows > 0) {
							$new_vals[] = $v->row($lookup_id);
						} else {
							$new_vals[] = $val;
						}
					}
				} else {
					$new_vals = $vals;
				}
				
				$this->EE->db->where_not_in($id_col, $new_vals);
			} else {
				// one not
				$string = str_replace("not ", "", $string);
				$string = trim($string);
				
				// Lookup?
				if ($lookup_table) {
					$new_val = array();
					$v = $this->EE->db->where($lookup_col, $string)->get($lookup_table);
						
					if ($v->num_rows > 0) {
						$new_val = $v->row($lookup_id);
					} else {
						$new_val = $string;
					}
				} else {
					$new_val = $string;
				}
				
				$this->EE->db->where($id_col.' !=', $new_val);
			}
		} else {
			if (strpos('|', $string)) {
				// multiple vals
				$string = str_replace(" ", "", $string);
				$vals = explode('|', $string);
				
				// Lookup?
				if ($lookup_table) {
					$new_vals = array();
					
					foreach ($vals as $key => $val) {
						$v = $this->EE->db->where($lookup_col, $val)->get($lookup_table);
						
						if ($v->num_rows > 0) {
							$new_vals[] = $v->row($lookup_id);
						} else {
							$new_vals[] = $val;
						}
					}
				} else {
					$new_vals = $vals;
				}
				
				$this->EE->db->where_in($id_col, $new_vals);
			} else {
				// single value
				$string = str_replace("not ", "", $string);
				$string = trim($string);
				
				// Lookup?
				if ($lookup_table) {
					$new_val = array();
					$v = $this->EE->db->where($lookup_col, $string)->get($lookup_table);
						
					if ($v->num_rows > 0) {
						$new_val = $v->row($lookup_id);
					} else {
						$new_val = $string;
					}
				} else {
					$new_val = $string;
				}
				
				$this->EE->db->where($id_col, $new_val);
			}
		}
	}
}