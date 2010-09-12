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

class Taggable_ft extends EE_Fieldtype {
	public $has_array_data = TRUE;
	public $info = array(
		'name' 		=> 'Taggable',
		'version'	=> TAGGABLE_VERSION
	);
	
	/**
	 * Constructor
	 *
	 * @author Jamie Rumbelow
	 */
	public function __construct() {
		parent::EE_Fieldtype();
		$this->EE->lang->loadfile('taggable');
	}
	
	/**
	 * display_field()
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function display_field($data = "") {		
		if (isset($_POST[$this->field_name])) {
			$data = $_POST[$this->field_name];
		}
		
		$this->EE->load->model('taggable_tag_model', 'tags');
		
		$tags = $this->_get_ids($data);
		$tags = implode(',', $tags).',';
		$data = $tags;
		$value = '';
		$hash = sha1(microtime(TRUE).rand(0,1000));
		
		// What theme are we using?
		$theme = "taggable-light";
		
		// Setup the JavaScript
		$this->_setup_javascript();
		
		// Include the theme JS and CSS
		$this->_insert_theme_js('jquery-ui-1.8.4.custom.min.js');
		$this->_insert_theme_css("$theme/$theme.css");
				
		if ($data) { 
			$ids 	= explode(',', $data);
			$values = array();
			
			foreach ($ids as $id) {
				if (!empty($id)) {
					if (is_numeric($id)) {
						if ($tg = $this->EE->tags->get($id)) {
							$values[] = "$id,$tg->name";
						}
					}
				}
			}
			
			$value = implode('|', $values);
		}
		
		$pre = "";
		
		if (!isset($this->EE->db->punches['deleted'])) {
			$pre .= '<input type="hidden" name="taggable_tags_delete" id="taggable_tags_delete" value="" />';
			$this->EE->db->punches['deleted'] = TRUE;
		}
		
		$limit = $this->settings['taggable_tag_limit'];
		$attrs = array(
			'name' 				=> (isset($this->cell_name)) ? $this->cell_name : $this->field_name,
			'class' 			=> 'taggable_replace_token_input',
			'value'				=> $value,
			'data-tag-limit'	=> $limit,
			'data-id-hash'		=> $hash
		);
		
		return $pre . form_input($attrs);
	}
	
	/**
	 * replace_tag()
	 *
	 * @param string $data 
	 * @param string $params 
	 * @param string $tagdata 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function replace_tag($data, $params = array(), $tagdata = FALSE) {
		$ids = $this->_get_ids($data);
		
		$return = '';
	    $vars = '';
		
		if ($ids) {
			$tags = $this->EE->db->where_in('id', $ids)->get('taggable_tags')->result();
		
			if ($tags) {
				// Loop through and arrange everything
				foreach ($tags as $tag) {
					$tag_rows[] = array(
						'name'				=> $tag->name,
						'id'				=> $tag->id,
						'description'		=> $tag->description,
						'entry_count'		=> $this->tag_entries($tag->id),
						'url_name'			=> str_replace(' ', $this->settings['taggable_url_separator'], $tag->name)
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
	
	/**
	 * save()
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function save($data) {
		// Are we on a CP request?
		if (REQ == 'CP') {
			if ($data) {
				$tags = explode(',', $data);
				array_pop($tags);
		
				$data = '';
		
				foreach ($tags as $key => $tag) {
					if (!is_numeric($tag)) {
						// Is it in the DB? What's the ID?
						$query = $this->EE->tags->get_by('name', $tag);
				
						if ($query) {
							$tags[$key] = $query->id;
						} else {
							$tags[$key] = $this->EE->tags->insert(array('name' => $tag));
						}
					}
				}
		
				foreach ($tags as $id) {
					$tag_names[$id] = $this->EE->db->where('id', $id)->get('taggable_tags')->row('name');
				}
		
				foreach ($tag_names as $id => $name) {
					$data .= "[".$id."] ".$name." ".str_replace(' ', $this->settings['taggable_url_separator'], $name)."\n";
				}
			}
		} else {
			// Load stuff again
			$this->EE->load->model('taggable_tag_model', 'tags');
			
			// Check for the SAEF
			if (isset($_POST[$this->settings['taggable_saef_field_name']])) {
				$input = $_POST[$this->settings['taggable_saef_field_name']];
				$tags = explode($this->settings['taggable_saef_separator'], $input);
				$taggers = array();
				$data = '';
				
				foreach ($tags as $tag) {
					$tag = trim($tag);
					
					if (!$row = $this->EE->tags->get_by('name', $tag)) {
						$taggers[$tag] = $this->EE->tags->insert(array('name' => $tag));
					} else {
						$taggers[$tag] = (int)$row->id;
					}
				}
				
				foreach ($taggers as $name => $id) {
					$data .= "[".$id."] ".$name." ".str_replace(' ', $this->settings['taggable_url_separator'], $name)."\n";
				}
			}
		}
		
		return $data;
	}
	
	/**
	 * post_save()
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function post_save($data) {
		// Delete tags
		if (isset($_POST['taggable_tags_delete'])) {
			$tags = explode(',', $_POST['taggable_tags_delete']);
			array_pop($tags);
			
			foreach ($tags as $tag) {
				if (is_numeric($tag)) {
					$this->EE->db->where('entry_id', $this->settings['entry_id'])
								 ->where('tag_id', $tag)
								 ->delete('exp_taggable_tags_entries');
				}
			}
		}
		
		// Create tags	
		if (REQ == 'CP') {
			if (!empty($_POST[$this->field_name])) {
				$tags = explode(',', $_POST[$this->field_name]);
				array_pop($tags);
			
				$template = $this->_get_template();

				foreach ($tags as $tag) {
					if (!empty($tag)) {
						if (!is_numeric($tag)) {
							$query = $this->EE->tags->get_by('name', $tag);
					
							if ($query) { 
								$tag = $query->id;
							} else {
								$tag = $this->EE->tags->insert(array('name' => $tag));
							}
						}
			
						$num_rows = $this->EE->db->query("SELECT * FROM exp_taggable_tags_entries WHERE tag_id = $tag AND entry_id = {$this->settings['entry_id']}")->num_rows;
					
						if ($num_rows == 0) {
							$this->EE->db->insert('exp_taggable_tags_entries', array(
								'tag_id' 	=> $tag,
								'entry_id'	=> $this->settings['entry_id'],
								'template'	=> $template
							));
						}
					}
				}
			} else {
				// Load stuff again
				$this->EE->load->model('taggable_tag_model', 'tags');

				// Check for the SAEF
				if (isset($_POST[$this->settings['taggable_saef_field_name']])) {
					$input = $_POST[$this->settings['taggable_saef_field_name']];
					$tags = explode($this->settings['taggable_saef_separator'], $input);
					$ids = array();
					
					foreach ($tags as $tag) {
						$ids[] = $this->EE->tags->get_by('name', trim($tag))->id;
					}
					
					foreach ($ids as $id) {
						$this->EE->db->insert('taggable_tags_entries', array('tag_id' => $id, 'entry_id' => $this->settings['entry_id']));
					}
				}
			}
		}
	}
	
	/**
	 * delete()
	 *
	 * @param string $ids 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function delete($ids) {
		$this->EE->db->where_in('entry_id', $ids);
		$this->EE->db->delete('exp_taggable_tags_entries');
	}
	
	/**
	 * display_settings()
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function display_settings($data) {
		$saef_field_name = (isset($data['taggable_saef_field_name'])) ? $data['taggable_saef_field_name'] : 'tags';
		$saef_separator = (isset($data['taggable_saef_separator'])) ? $data['taggable_saef_separator'] : ',';
		$tag_limit = (isset($data['taggable_tag_limit'])) ? $data['taggable_tag_limit'] : 10;
		$url_separator = (isset($data['taggable_url_separator'])) ? $data['taggable_url_separator'] : '_';
		
		$this->EE->table->add_row(lang('taggable_preference_saef_field_name'), form_input('taggable_saef_field_name', $saef_field_name, 'id="taggable_saef_field_name"'));
		$this->EE->table->add_row(lang('taggable_preference_saef_separator'), form_dropdown('taggable_saef_separator', array(
			',' => 'Comma', ' ' => 'Space', 'newline' => 'New line', '|' => 'Bar' 
		), $saef_separator));
		$this->EE->table->add_row(lang('taggable_preference_maximum_tags_per_entry'), form_input('taggable_tag_limit', $tag_limit));
		$this->EE->table->add_row(lang('taggable_preference_url_separator'), form_input('taggable_url_separator', $url_separator));
		
		$this->EE->javascript->output("$('#field_name').change(function(){ $('#taggable_saef_field_name').val($(this).val()); })");
	}
	
	/**
	 * display_cell_settings()
	 *
	 * MATRIX COMPAT. NOT COMPLETE
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function display_cell_settings($data) {
		$saef_field_name = (isset($data['taggable_saef_field_name'])) ? $data['taggable_saef_field_name'] : 'tags';
		$saef_separator = (isset($data['taggable_saef_separator'])) ? $data['taggable_saef_separator'] : ',';
		$tag_limit = (isset($data['taggable_tag_limit'])) ? $data['taggable_tag_limit'] : 10;
		$url_separator = (isset($data['taggable_url_separator'])) ? $data['taggable_url_separator'] : '_';
		
		return array(
			array(lang('taggable_preference_saef_field_name'), form_input('taggable_saef_field_name', $saef_field_name)),
			array(lang('taggable_preference_saef_separator'), form_dropdown('taggable_saef_separator', array(
				',' => 'Comma', ' ' => 'Space', 'newline' => 'New line', '|' => 'Bar' 
			), $saef_separator)),
			array(lang('taggable_preference_maximum_tags_per_entry'), form_input('taggable_tag_limit', $tag_limit)),
			array(lang('taggable_preference_url_separator'), form_input('taggable_url_separator', $url_separator))
		);
	}
	
	/**
	 * save_settings()
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function save_settings() {
		return array(
			'taggable_saef_field_name' => $this->EE->input->post('taggable_saef_field_name'),
			'taggable_saef_separator' => $this->EE->input->post('taggable_saef_separator'),
			'taggable_tag_limit' => $this->EE->input->post('taggable_tag_limit'),
			'taggable_url_separator' => $this->EE->input->post('taggable_url_separator')
		);
	}
	
	/**
	 * Get the IDs from the exp_channel_data
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	protected function _get_ids($data) {
		$lines = explode("\n", $data);
		$ids = array();
		
		foreach ($lines as $line) {
			$ids[] = (int)preg_replace("/^\[([0-9]+)\]/", "$1", $line);
		}
		
		return $ids;
	}
	
	/**
	 * Sets up JavaScript globals
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _setup_javascript() {		
		$js = array(
			'hintText'		 	 	=> lang('taggable_javascript_hint'),
			'noResultsText'	  		=> lang('taggable_javascript_no_results'),
			'searchingText'	 	 	=> lang('taggable_javascript_searching'),
			'pleasEEnterText' 		=> lang('taggable_javascript_please_enter'),
			'noMoreAllowedText'		=> lang('taggable_javascript_limit'),
			'autotaggingComplete'	=> lang('taggable_javascript_autotagging_complete'),
			'searchUrl'				=> '?D=cp&C=addons_modules&M=show_module_cp&module=taggable&method=ajax_search',
			'createUrl'				=> '?D=cp&C=addons_modules&M=show_module_cp&module=taggable&method=ajax_create'
		);
		
		foreach ($js as $name => $value) {
			$this->EE->javascript->set_global("taggable.$name", $value);
		}
	}
	
	/**
	 * There's no tags, so get the {if no_tags}{/if}
	 * and display it.
	 *
	 * @param string $tagdata 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	protected function _parse_if_no_tags($tagdata) {
		return preg_replace("/{if no_tags}(.*){\/if}/", '$1', $tagdata);
	}
		
	/**
	 * There are tags, so get rid of the {if no_tags}{/if}
	 *
	 * @param string $tagdata 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	protected function _no_parse_if_no_tags($tagdata) {
		return preg_replace("/{if no_tags}(.*){\/if}/", '', $tagdata);
	}
	
	/**
	 * Get the tag's entry count
	 *
	 * @param string $id 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	protected function tag_entries($id) {
		return $this->EE->db->select("COUNT(DISTINCT entry_id) AS total")
							->where("tag_id", $id)
							->get('taggable_tags_entries')
							->row('total');
	}
	
	/**
	 * Get the field_name of the custom field
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _get_template() {
 		return $this->EE->db->select('field_name')
						 	->where('field_id', $this->field_id)
							->get('exp_channel_fields')
							->row('field_name');
	}
	
	/**
	 * Get the theme folder URL
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _theme_url() {
		if (! isset($this->cache['theme_url'])) {
			$theme_folder_url = $this->EE->config->item('theme_folder_url');
			if (substr($theme_folder_url, -1) != '/') $theme_folder_url .= '/';
			$this->cache['theme_url'] = $theme_folder_url.'third_party/taggable/';
		}

		return $this->cache['theme_url'];
	}
	
	/**
	 * Insert JS into the CP
	 *
	 * @param string $file 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _insert_theme_js($file) {
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->_theme_url().'javascript/'.$file.'"></script>');
	}
	
	/**
	 * Insert CSS into the CP
	 *
	 * @param string $file 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _insert_theme_css($file) {
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().'css/'.$file.'" />');
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