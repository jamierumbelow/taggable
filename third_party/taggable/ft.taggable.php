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
 * @version 1.4.5
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
		
		if (is_string($data) || $data === FALSE) {
			$this->EE->load->model('taggable_tag_model', 'tags');
			
			// Are we in versioning?
			if ($this->EE->input->get('version_id')) {
				$tags = $this->_format_for_field_from_versioning($data);
			} else {
				// Get the names
				$tags = $this->_get_ids_and_names($data);
				$tags = $this->_format_for_field($tags);
			}
			
			// What theme are we using?
			$default_theme = $this->EE->config->item('taggable_default_theme') ? $this->EE->config->item('taggable_default_theme') : "taggable-tokens";
			$theme = (isset($this->settings['taggable_theme'])) ? $this->settings['taggable_theme'] : $default_theme;
		
			// Setup the JavaScript
			$this->_setup_javascript();
		
			// Include the theme JS and CSS
			$this->_insert_theme_js('jquery.autocomplete.js');
			$this->_insert_theme_js('jquery.taggable.js');
			$this->_insert_theme_css("$theme/$theme.css");
		
			// Setup the input
			$limit = $this->settings['taggable_tag_limit'];
			$attrs = array(
				'name' 				=> (isset($this->cell_name)) ? $this->cell_name : $this->field_name,
				'class' 			=> (isset($this->cell_name)) ? 'taggable_replace_token_input taggable_matrix' : 'taggable_replace_token_input',
				'value'				=> $tags,
				'data-tag-limit'	=> $limit
			);
		
			// Wrap in theme container
			$html = (isset($this->cell_name)) ? "<div class='$theme matrix'>" : "<div class='$theme'>";
			
			// Output the input - teeheehee
			$html .= form_input($attrs);
			
			// Wrap it up
			$html .= "</div>";
			
			// taggable_ft_display_field
			if ($this->EE->extensions->active_hook('taggable_ft_display_field')) {
				$html = $this->EE->extensions->call('taggable_ft_display_field', $html);
			}
		
			// And we're done!
			return $html;
		}
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
		
		// taggable_ft_tag_ids
		if ($this->EE->extensions->active_hook('taggable_ft_tag_ids')) {
			$ids = $this->EE->extensions->call('taggable_ft_tag_ids', $ids);
			if ($this->EE->extensions->end_script === TRUE) return $tagdata;
		}
		
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
				
				// Obey the limit="" parameter
				$limit = (isset($params['limit'])) ? $params['limit'] : count($tag_rows);
				$tag_rows = array_slice($tag_rows, 0, $limit);
				
				$vars = $tag_rows;			
				$return = $this->_no_parse_if_no_tags($tagdata);
			}
			
			// taggable_ft_tag_vars
			if ($this->EE->extensions->active_hook('taggable_ft_tag_vars')) {
				$vars = $this->EE->extensions->call('taggable_ft_tag_vars', $vars, $return);
				if ($this->EE->extensions->end_script === TRUE) return $return;
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
		
		// taggable_ft_tag_end
		if ($this->EE->extensions->active_hook('taggable_ft_tag_end')) {
			$return = $this->EE->extensions->call('taggable_ft_tag_end', $return);
			if ($this->EE->extensions->end_script === TRUE) return $return;
		}
		
		// done!
		return $return;
	}
	
	/**
	 * {tags:ul}
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function replace_ul($data, $params = array(), $tagdata = FALSE) {
		$html = '<ul>'.$this->_generate_list($data).'</ul>';
		return $html;
	}
	
	/**
	 * {tags:ol}
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function replace_ol($data, $params = array(), $tagdata = FALSE) {
		$html = '<ol>'.$this->_generate_list($data).'</ol>';
		return $html;
	}
	
	/**
	 * save()
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function save($data) {
		// Load stuff again
		$this->EE->load->model('taggable_tag_model', 'tags');
		$name = (isset($this->cell_name)) ? $this->cell_name : $this->field_name;
		
		// SafeCracker Settings
		if (isset($this->EE->safecracker)) {
			$this->settings = array_merge($this->settings, unserialize(base64_decode($this->settings['field_settings'])));
		}
		
		// Are we on a CP request?
		if (REQ == 'CP') {
			if ($data) {
				// taggable_ft_save_cp
				if ($this->EE->extensions->active_hook('taggable_ft_save_cp')) {
					$data = $this->EE->extensions->call('taggable_ft_save_cp', $data);
					if ($this->EE->extensions->end_script === TRUE) return $data;
				}
				
				$tags = array_filter(explode(',', $data));
				$newt = array();
				$data = '';
				
				foreach ($tags as $tag) {
					if ($tag) {
						if (is_numeric($tag)) {
							// Is it in the DB? What's the name?
							$query = $this->EE->tags->get($tag);
						
							if ($query) {
								$newt[$tag] = $query->name;
							}
						} else {
							// Just do a dupe check
							$dupe = $this->EE->tags->get_by('name', $tag);
							
							if ($dupe == array()) {
								$new_tag = $this->EE->tags->insert(array('name' => $tag));
								$newt[$new_tag] = $tag;
							} else {
								$newt[$dupe->id] = $dupe->name;
							}
						}
					}
				}
				
				foreach ($newt as $id => $name) {
					$data .= "[".$id."] ".$name." ".str_replace(' ', $this->settings['taggable_url_separator'], $name)."\n";
				}
				
				return $data;
			}
		} elseif (isset($this->EE->safecracker)) {
			if ($data) {
				$tags = array_filter(explode(',', $data));
				$newt = array();
				$data = '';
				
				foreach ($tags as $tag) {
					if ($tag) {
						if (is_numeric($tag)) {
							// Is it in the DB? What's the name?
							$query = $this->EE->tags->get($tag);
						
							if ($query) {
								$newt[$tag] = $query->name;
							}
						} else {
							$dupe = $this->EE->tags->get_by('name', $tag);
							
							if ($dupe == array()) {
								$new_tag = $this->EE->tags->insert(array('name' => $tag));
								$newt[$new_tag] = $tag;
							} else {
								$newt[$dupe->id] = $dupe->name;
							}
						}
					}
				}
				
				foreach ($newt as $id => $name) {
					$data .= "[".$id."] ".$name." ".str_replace(' ', $this->settings['taggable_url_separator'], $name)."\n";
				}
				
				return $data;
			}
		}
	}
	
	/**
	 * post_save()
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function post_save($data) {
		// Get the tags
		$ids = $this->_get_ids($data);
		$template = $this->_get_template();
		$old = $this->EE->db->where(array('entry_id' => $this->settings['entry_id'], 'template' => $template))->get('taggable_tags_entries')->result();
		$delete = array();
		
		// Check for deleted tags
		foreach ($old as $row) {
			if (!in_array($row->tag_id, $ids)) {
				$delete[] = $row->tag_id;
			}
		}
		
		// Delete any that shouldn't be saved
		if ($delete) {
			$this->EE->db->where('entry_id', $this->settings['entry_id'])->where_in('tag_id', $delete)->delete('taggable_tags_entries');
		}
		
		// Loop through and insert new ones
		foreach ($ids as $id) {
			if ($this->EE->db->where(array('tag_id' => $id, 'entry_id' => $this->settings['entry_id'], 'template' => $template))->get('taggable_tags_entries')->num_rows == 0) {
				$this->EE->db->insert('taggable_tags_entries', array('tag_id' => $id, 'entry_id' => $this->settings['entry_id'], 'template' => $template));
			}
		}
		
		// taggable_ft_post_save
		if ($this->EE->extensions->active_hook('taggable_ft_post_save')) {
			$this->EE->extensions->call('taggable_ft_post_save', $ids, $this);
		}
		
		// Cool!
		return $data;
	}
	
	/**
	 * delete()
	 *
	 * @param string $ids 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function delete($ids) {
		// taggable_ft_delete
		if ($this->EE->extensions->active_hook('taggable_ft_delete')) {
			$ids = $this->EE->extensions->call('taggable_ft_delete', $ids);
			if ($this->EE->extensions->end_script === TRUE) return $data;
		}
		
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
	public function display_settings($data, $ret = FALSE) {
		// Sensible defaults
		$tag_limit = (isset($data['taggable_tag_limit'])) ? $data['taggable_tag_limit'] : 10;
		$default_theme = $this->EE->config->item('taggable_default_theme') ? $this->EE->config->item('taggable_default_theme') : "taggable-tokens";
		$theme = (isset($data['taggable_theme'])) ? $data['taggable_theme'] : $default_theme;
		$url_separator = (isset($data['taggable_url_separator'])) ? $data['taggable_url_separator'] : '-';
				
		// Build up the settings array?
		$settings = array(
			array(lang('taggable_preference_maximum_tags_per_entry'), form_input('taggable_tag_limit', $tag_limit, 'class="taggable-field"')),
			array(lang('taggable_preference_theme'), form_dropdown('taggable_theme', $this->_get_themes(), $theme)),
			array(lang('taggable_preference_url_separator'), form_input('taggable_url_separator', $url_separator, 'class="taggable-field"'))
		);
		
		// taggable_ft_settings
		if ($this->EE->extensions->active_hook('taggable_ft_settings')) {
			$settings = $this->EE->extensions->call('taggable_ft_settings', $settings);
		}
		
		// Do we return it or output it as a table?
		if ($ret) {
			return $settings;
		} else {
			// Output the searchable JavaScript
			$this->EE->javascript->output('
				var oldShow = $.fn.show; 
				$.fn.show = function(s,c){
					if($(this).attr("id")=="ft_taggable") {
						$("#field_search_y").attr("checked","checked");
					}
					
					return oldShow.apply($(this),[s, c]);
				};
			');
			
			foreach ($settings as $setting) {
				$this->EE->table->add_row($setting[0], $setting[1]);
			}
		}
	}
	
	/**
	 * save_settings()
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function save_settings($data) {
		$settings = array(
			'taggable_tag_limit' => (isset($data['taggable_tag_limit'])) ? $data['taggable_tag_limit'] : $_POST['taggable_tag_limit'],
			'taggable_theme' => (isset($data['taggable_theme'])) ? $data['taggable_theme'] : $_POST['taggable_theme'],
			'taggable_url_separator' => (isset($data['taggable_url_separator'])) ? $data['taggable_url_separator'] : $_POST['taggable_url_separator']
		);
		
		// taggable_ft_save_settings
		if ($this->EE->extensions->active_hook('taggable_ft_save_settings')) {
			$settings = $this->EE->extensions->call('taggable_ft_save_settings', $settings);
		}
		
		// Done
		return $settings;
	}
	
	// ------------------------
	// P&T MATRIX SUPPORT
	// ------------------------
	
	/**
	 * Display Matrix field
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function display_cell($data) {
		$old = $this->EE->load->_ci_view_path;
		$this->EE->load->_ci_view_path = str_replace('matrix', 'taggable', $this->EE->load->_ci_view_path);
		$this->EE->load->add_package_path(PATH_THIRD.'taggable/');
		
		$html = $this->display_field($data);
		
		$this->EE->load->_ci_view_path = $old;
		
		return $html;
	}
	
	/**
	 * Save Matrix field
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function save_cell($data) {
		$old = $this->EE->load->_ci_view_path;
		$this->EE->load->_ci_view_path = str_replace('matrix', 'taggable', $this->EE->load->_ci_view_path);
		$this->EE->load->add_package_path(PATH_THIRD.'taggable/');
		
		$return = $this->save($data);
		
		$this->EE->load->_ci_view_path = $old;
		
		return $return;
	}
	
	/**
	 * Post save Matrix field
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function post_save_cell($data) {
		$old = $this->EE->load->_ci_view_path;
		$this->EE->load->_ci_view_path = str_replace('matrix', 'taggable', $this->EE->load->_ci_view_path);
		$this->EE->load->add_package_path(PATH_THIRD.'taggable/');
		
		$return = $this->post_save($data);
		
		$this->EE->load->_ci_view_path = $old;
		
		return $return;
	}
	
	/**
	 * Display Matrix settings
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function display_cell_settings($data) {
		// Output the autocomplete JavaScript
		$this->_insert_theme_js('jquery.taggable.js');
		$this->EE->cp->add_to_foot("<style type='text/css'>.matrix .taggable-field { border:0; }</style>");
		
		return $this->display_settings($data, TRUE); 
	}
	
	/**
	 * Save Matrix settings
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function save_cell_settings($data) {
		return $this->save_settings($data);
	}
	
	// ------------------------
	// LOW VARIABLES SUPPORT
	// ------------------------
	
	/**
	 * Display Low Variables field
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function display_var_field($data) {
		$old = $this->EE->load->_ci_view_path;
		$this->EE->load->_ci_view_path = str_replace('low_variables', 'taggable', $this->EE->load->_ci_view_path);
		$this->EE->load->add_package_path(PATH_THIRD.'taggable/');

		$html = $this->display_field($data);
		$this->EE->load->_ci_view_path = $old;
		
		return $html;
	}
	
	/**
	 * Save Low Variables field
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function save_var_field($data) {
		$old = $this->EE->load->_ci_view_path;
		$this->EE->load->_ci_view_path = str_replace('low_variables', 'taggable', $this->EE->load->_ci_view_path);
		$this->EE->load->add_package_path(PATH_THIRD.'taggable/');

		$return = $this->save($data);
		$this->EE->load->_ci_view_path = $old;
		
		return $return;
	}
	
	/**
	 * Display Low Variables tag
	 *
	 * @param string $data 
	 * @param string $params 
	 * @param string $tagdata 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function display_var_tag($data, $params, $tagdata) {
		$old = $this->EE->load->_ci_view_path;
		$this->EE->load->_ci_view_path = str_replace('low_variables', 'taggable', $this->EE->load->_ci_view_path);
		$this->EE->load->add_package_path(PATH_THIRD.'taggable/');
		
		// Reset LV settings
		foreach ($params as $key => $value) {
			$this->settings[$key] = $value;
		}
		
		// Remove {tags}
		$html = str_replace(LD.$params['var'].RD, '', $tagdata);
		$html = str_replace(LD.'/'.$params['var'].RD, '', $html);
		
		// Run replace_tag()
		$html = $this->replace_tag($data, $params, $html);
		$this->EE->load->_ci_view_path = $old;
		
		return $html;
	}
	
	/**
	 * Display Low Variables Settings
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function display_var_settings($data) {
		return $this->display_settings($data, TRUE);
	}
	
	/**
	 * Save Low Variables Settings
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function save_var_settings($data) {
		return $this->save_settings($data);
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
			if ($line) {
				$ids[] = (int)preg_replace("/^\[([0-9]+)\]/", "$1", $line);
			}
		}
		
		return $ids;
	}
	
	/**
	 * Get the names from the exp_channel_data
	 *
	 * @param string $data 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	protected function _get_names($data) {
		$lines = explode("\n", $data);
		$names = array();
		
		foreach ($lines as $line) {
			if ($line) {
				$names[] = preg_replace('/^\[([0-9]+)\] (.+) ([^\s]+)/', "$2", $line);
			}
		}
		
		return $names;
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
	 * Generate a HTML list
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	protected function _generate_list($data) {
		$ids = $this->_get_ids($data);
		$html = "";
		
		if ($ids) {
			$tags = $this->EE->db->select('id, name')->where_in('id', $ids)->get('taggable_tags')->result();
			
			foreach ($tags as $tag) {
				$html .= '<li data-id="'.$tag->id.'">'.$tag->name.'</li>';
			}
		}
		
		return $html;
	}
	
	/**
	 * Format the tags for tag fields from versioning data
	 *
	 * @param string $tags 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	protected function _format_for_field_from_versioning($tags) {
		$ids = array_filter(explode(',', $tags));
		$result = $this->EE->db->select('id, name')->where_in('id', $ids)->get('taggable_tags')->result();
		$tags = array();
		
		foreach ($result as $row) {
			$tags[$row->id] = $row->name;
		}
		
		return $this->_format_for_field($tags);
	}
	
	/**
	 * Format the tags for tag fields
	 *
	 * @param string $tags 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	protected function _format_for_field($tags) {
		if ($tags) { 
			foreach($tags as $id => $tag) { 
				$new_tags[] = "$id,$tag"; 
			} 
			
			$tags = implode("|", $new_tags);
		} else {
			$tags = ''; 
		}
		
		return $tags;
	}
	
	/**
	 * Sets up JavaScript globals
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _setup_javascript() {		
		// Set lang globals
		if (!isset($this->EE->session->cache['taggable']['js_globals'])) {
			$js = array(
				'hintText'		 	 	=> lang('taggable_javascript_hint'),
				'noResultsText'	  		=> lang('taggable_javascript_no_results'),
				'searchingText'	 	 	=> lang('taggable_javascript_searching'),
				'pleasEEnterText' 		=> lang('taggable_javascript_please_enter'),
				'noMoreAllowedText'		=> lang('taggable_javascript_limit'),
				'autotaggingComplete'	=> lang('taggable_javascript_autotagging_complete'),
				'searchUrl'				=> '?D=cp&C=addons_modules&M=show_module_cp&module=taggable&method=ajax_search',
				'createUrl'				=> '?D=cp&C=addons_modules&M=show_module_cp&module=taggable&method=ajax_create',
				'tags'					=> $this->_get_all_tags_json()
			);
			
			// Set and output the JS
			$json = $this->EE->javascript->generate_json($js);
			$output = '<script type="text/javascript">if (typeof EE == "undefined" || ! EE) {'."\n".'var EE = {"taggable": '.$json;
			$output .= '};} else { EE.taggable = ' . $json . ' }</script>';
			$this->EE->cp->add_to_foot($output);
			
			// Make sure we only bother once
			$this->EE->session->cache['taggable']['js_globals'] = TRUE;
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
		if ($this->field_id) {
	 		return $this->EE->db->select('field_name')
							 	->where('field_id', $this->field_id)
								->get('exp_channel_fields')
								->row('field_name');
		} else {
			return "matrix_".$this->settings['field_name']."_".$this->settings['row_name']."_".$this->settings['col_name'];
		}
	}
	
	/**
	 * Get the theme folder URL
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _theme_url() {
		if (!isset($this->EE->session->cache['taggable']['theme_url'])) {
			$theme_folder_url = $this->EE->config->item('theme_folder_url');
			if (substr($theme_folder_url, -1) != '/') $theme_folder_url .= '/';
			$this->EE->session->cache['taggable']['theme_url'] = $theme_folder_url.'third_party/taggable/';
		}

		return $this->EE->session->cache['taggable']['theme_url'];
	}
	
	/**
	 * Insert JS into the CP
	 *
	 * @param string $file 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _insert_theme_js($file) {
		if (!isset($this->EE->session->cache['taggable']['javascripts'][$file])) {
			$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->_theme_url().'javascript/'.$file.'"></script>');
			$this->EE->session->cache['taggable']['javascripts'][$file] = TRUE;
		}
	}
	
	/**
	 * Insert CSS into the CP
	 *
	 * @param string $file 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _insert_theme_css($file) {
		if (!isset($this->EE->session->cache['taggable']['stylesheets'][$file])) {
			$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().'css/'.$file.'" />');
			$this->EE->session->cache['taggable']['stylesheets'][$file] = TRUE;
		}
	}
	
	/**
	 * Get UI themes
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _get_themes() {
		if (!isset($this->EE->session->cache['taggable']['themes'])) {
			$theme_folder_path = $this->EE->config->item('theme_folder_path');
			if (substr($theme_folder_path, -1) != '/') $theme_folder_path .= '/';
			
			$themes = array();
			$dir = @scandir($theme_folder_path . 'third_party/taggable/css/');
			
			foreach ($dir as $file) {
				if ($file[0] !== '.') {
					$themes[$file] = ucwords(str_replace('-', ' ', $file));
				}
			}
			
			$this->EE->session->cache['taggable']['themes'] = $themes;
		}
		
		return $this->EE->session->cache['taggable']['themes'];
	}
	
	/**
	 * Return a JSONifiable object of tags
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _get_all_tags_json() {
		$tags = $this->EE->db->where('site_id', $this->EE->config->item('site_id'))->get('taggable_tags')->result();
		$json = array();
		
		foreach ($tags as $tag) {
			$json[] = array(
				'id' => $tag->id,
				'name' => $tag->name
			);
		}
		
		return $json;
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
	
	/**
	 * Install the fieldtype
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function install() {
		$this->EE->load->dbforge();
		
		// exp_taggable_tags
		$tags = array(
			'id' 			=> array('type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'name'			=> array('type' => 'VARCHAR', 'constraint' => 100),
			'description'	=> array('type' => 'TEXT'),
			'site_id'		=> array('type' => 'INT', 'default' => $this->EE->config->item('site_id'))
		);
		
		$this->EE->dbforge->add_field($tags);
		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->create_table('taggable_tags');
		
		// exp_taggable_tags_entries
		$tags_entries = array(
			'tag_id' 	=> array('type' => 'INT'),
			'entry_id'	=> array('type' => 'INT'),
			'site_id'	=> array('type' => 'INT'),
			'template'  => array('type' => 'VARCHAR', 'constraint' => 250, 'default' => 'tags')
		);
		
		$this->EE->dbforge->add_field($tags_entries);
		$this->EE->dbforge->add_key('tag_id');
		$this->EE->dbforge->add_key('entry_id');
		$this->EE->dbforge->create_table('taggable_tags_entries');
		
		// Add license key to config file
		if (!$this->EE->config->item('taggable_license_key')) {
			$this->EE->config->_update_config(array('taggable_license_key' => 'ENTER YOUR LICENSE KEY HERE'));
		}
		
		if (!$this->EE->config->item('taggable_default_theme')) {
			$this->EE->config->_update_config(array('taggable_default_theme' => 'taggable-tokens'));
		}
	}
	
	/**
	 * Uninstall the fieldtype
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function uninstall() {
		$this->EE->load->dbforge();
		
		$this->EE->dbforge->drop_table('taggable_tags');
		$this->EE->dbforge->drop_table('taggable_tags_entries');
		$this->EE->config->_update_config(array(), array('taggable_license_key'));
	}
}