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
 * @version 1.4.0
 **/

require_once BASEPATH.	"core/Model.php";
require_once PATH_THIRD."taggable/libraries/Model.php";
require_once PATH_THIRD."taggable/libraries/eh_compat.php";
require_once PATH_THIRD."taggable/config.php";

class Taggable {
	private $ee;
	
	public $tagdata;
	public $site_id;
	
	/**
	 * {exp:taggable tag_id="1" tag_name="some tag" tag_url_name="some-tag" entry_id="1" entry_url_title="entry" field="tags"
	 * 				 orderby="name" sort="asc" limit="10" url_separator="-" min_size="10" max_size="25" backspace="2"}
	 * 		{name}
	 *		{id}
	 *		{description}
	 *		{entry_count}
	 *		{size}
	 *		{url_name}
	 *		{pretty_name}
	 * {/exp:taggable}
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function Taggable() {
		$this->ee =& get_instance();
		
		$this->ee->load->library('model');	
		$this->ee->load->model('taggable_tag_model', 'tags');
		
		if (REQ !== 'ACTION') {
			$this->tagdata = $this->ee->TMPL->tagdata;
			$this->site_id = $this->ee->config->item('site_id');
		
			// taggable_vanilla_tagdata
			if ($this->ee->extensions->active_hook('taggable_vanilla_tagdata')) {
				$this->tagdata = $this->ee->extensions->call('taggable_vanilla_tagdata', $this->tagdata);
				if ($this->ee->extensions->end_script === TRUE) return $this->tagdata;
			}
	
			// Parameters
			$this->params['tag_id'] 		 = $this->ee->TMPL->fetch_param('tag_id');
			$this->params['tag_name'] 		 = $this->ee->TMPL->fetch_param('tag_name');
			$this->params['tag_url_name'] 	 = $this->ee->TMPL->fetch_param('tag_url_name');
			$this->params['entry_id'] 		 = $this->ee->TMPL->fetch_param('entry_id');
			$this->params['entry_url_title'] = $this->ee->TMPL->fetch_param('entry_url_title');
			$this->params['orderby']	 	 = ($this->ee->TMPL->fetch_param('orderby') && in_array($this->ee->TMPL->fetch_param('orderby'), array('id', 'name', 'entry_count', ''))) ? 
											    strtolower($this->ee->TMPL->fetch_param('orderby')) : 'name';
			$this->params['sort']		 	 = strtolower($this->ee->TMPL->fetch_param('sort'));
			$this->params['backspace'] 		 = $this->ee->TMPL->fetch_param('backspace');
			$this->params['limit']			 = $this->ee->TMPL->fetch_param('limit');
			$this->params['url_separator']	 = ($u = $this->ee->TMPL->fetch_param('url_separator')) ? $u : '-' ;
			$this->params['min_size'] 	   	 = $this->ee->TMPL->fetch_param('min_size');
			$this->params['max_size'] 	   	 = $this->ee->TMPL->fetch_param('max_size');
			$this->params['field']			 = $this->ee->TMPL->fetch_param('field');
			$min_size 						 = ($this->params['min_size']) ? $this->params['min_size'] : 12;
			$max_size 						 = ($this->params['max_size']) ? $this->params['max_size'] : 12;
			$vars				 			 = array();
			
			// Run the lookup
			$tags = $this->_search_tags();
			
			// Get the min and max, then calculate the spread...
			$min_qty = (empty($counts)) ? 0 : min($counts);
			$max_qty = (empty($counts)) ? 0 : max($counts);
			$spread = $max_qty - $min_qty;
		
			if ($spread == 0) {
	                $spread = 1;
	        }
		
			// Figure out each step
	        $step = ($max_qty - $min_qty) / ($spread);
		
			// taggable_tags_pre_loop
			if ($this->ee->extensions->active_hook('taggable_tags_pre_loop')) {
				$tags = $this->ee->extensions->call('taggable_tags_pre_loop', $tags, $this->tagdata);
				if ($this->ee->extensions->end_script === TRUE) return $this->tagdata;
			}
		
			// Set up the tag variables
			if ($tags) {
				foreach ($tags as $tag) {
					$size = round($min_size + (($tag->entry_count - $min_qty) * $step));
					if ($size < $min_size) { $size = $min_size; }
					if ($size > $max_size) { $size = $max_size; }
				
					$vars[] = array(
						'name'			=> $tag->name,
						'id'			=> $tag->id,
						'description'	=> $tag->description,
						'entry_count'	=> $tag->entry_count,
						'size'			=> $size,
						'url_name'		=> str_replace(' ', $this->params['url_separator'], $tag->name),
						'pretty_name'	=> $this->_pretty_tag($tag->name)
					);
				}
			} else {
				$this->return_data = $this->ee->TMPL->no_results();
			}
		
			// taggable_tags_post_loop
			if ($this->ee->extensions->active_hook('taggable_tags_post_loop')) {
				$vars = $this->ee->extensions->call('taggable_tags_post_loop', $vars, $this->tagdata);
				if ($this->ee->extensions->end_script === TRUE) return $this->tagdata;
			}
		
			// Parse it!
			$parsed = $this->ee->TMPL->parse_variables($this->tagdata, $vars);
		
			// Backspace?
			if ($this->params['backspace']) {
				if (is_numeric($this->params['backspace'])) {
					$parsed = substr($parsed, 0, - (int)$this->params['backspace']);
				}
			}
		
			// taggable_tags_end
			if ($this->ee->extensions->active_hook('taggable_tags_end')) {
				$parsed = $this->ee->extensions->call('taggable_tags_end', $parsed);
			}
		
			// We're done!
			$this->return_data = $parsed;
		}
	}
	
	/**
	 * A duplicate of {exp:taggable}, exposed through
	 * an action for front-end AJAX requests.
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function api_entries() {
		// Setup
		$this->site_id = $this->ee->config->item('site_id');
		$data = array();
		
		// Parameters
		$this->params['tag_id'] 			 = $this->ee->input->get('tag_id');
		$this->params['tag_name'] 			 = $this->ee->input->get('tag_name');
		$this->params['tag_url_name'] 		 = $this->ee->input->get('tag_url_name');
		$this->params['entry_id'] 			 = $this->ee->input->get('entry_id');
		$this->params['entry_url_title'] 	 = $this->ee->input->get('entry_url_title');
		$this->params['orderby']	 		 = ($this->ee->input->get('orderby') && in_array($this->ee->input->get('orderby'), array('id', 'name', ''))) ? 
								strtolower($this->ee->input->get('orderby')) : 'name';
		$this->params['sort']		 		 = strtolower($this->ee->input->get('sort'));
		$this->params['limit']				 = $this->ee->input->get('limit');
		$this->params['url_separator']		 = ($u = $this->ee->input->get('url_separator')) ? $u : '_' ;
		$this->params['min_size'] 	   		 = $this->ee->input->get('min_size');
		$this->params['max_size'] 	   		 = $this->ee->input->get('max_size');
		$min_size 							 = ($this->params['min_size']) ? $this->params['min_size'] : 12;
		$max_size 							 = ($this->params['max_size']) ? $this->params['max_size'] : 12;
		
		// Run the lookup
		$tags = $this->_search_tags();
	
		// Get the min and max, then calculate the spread...
		$min_qty = (empty($counts)) ? 0 : min($counts);
		$max_qty = (empty($counts)) ? 0 : max($counts);
		$spread = $max_qty - $min_qty;
	
		if ($spread == 0) {
                $spread = 1;
        }
	
		// Figure out each step
        $step = ($max_qty - $min_qty) / ($spread);
		
		// Set up the tag variables
		if ($tags) {
			foreach ($tags as $tag) {
				$size = round($min_size + (($tag->entry_count - $min_qty) * $step));
				if ($size < $min_size) { $size = $min_size; }
				if ($size > $max_size) { $size = $max_size; }
			
				$data[] = array(
					'name'			=> $tag->name,
					'id'			=> $tag->id,
					'description'	=> $tag->description,
					'entry_count'	=> $tag->entry_count,
					'size'			=> $size,
					'url_name'		=> str_replace(' ', $this->params['url_separator'], $tag->name),
					'pretty_name'	=> $this->_pretty_tag($tag->name)
				);
			}
		}
		
		// We're done! Set the output type as JSON and go go go
		header('Content-type: application/json');
		exit(json_encode($data));
	}
	
	/**
	 * Search the tags based on $this->params,
	 * and return them.
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	protected function _search_tags() {
		// Keep track whether it's an 'entry' query
		$entry_query = FALSE;
		$orderby_ent = FALSE;
		
		// Limit
		if ($this->params['limit']) {
			$this->ee->db->limit($this->params['limit']);
		}
		
		// Orderby and Sort
		if ($this->params['orderby']) {
			$orderby_ent = TRUE;
			$this->params['orderby'] = 'name';
		}
		
		if ($this->params['sort']) {
			$this->ee->db->order_by($this->params['orderby'] . " " . strtoupper($this->params['sort']));
		}

		// Tag ID
		if ($this->params['tag_id']) {
			$this->parse_multiple_params('exp_taggable_tags.id', $this->params['tag_id']);
		}

		// Tag Name
		if ($this->params['tag_name']) {	
			$this->parse_multiple_params('exp_taggable_tags.name', $this->params['tag_name']);
		}

		// Tag URL Name
		if ($this->params['tag_url_name']) {
			$this->params['tag_url_name'] = str_replace($this->params['url_separator'], ' ', $this->params['tag_url_name']);
			$this->parse_multiple_params('exp_taggable_tags.name', $this->params['tag_url_name']);
		}
		
		// Template/Field Name
		if ($this->params['field']) {
			$entry_query = TRUE;
			$this->ee->db->where('exp_taggable_tags_entries.template', $this->params['field']);
		}

		// Entry ID
		if ($this->params['entry_id']) {
			$entry_query = TRUE;
			$this->parse_multiple_params('exp_taggable_tags_entries.entry_id', $this->params['entry_id']);
		}

		// Entry Title
		if ($this->params['entry_url_title']) {
			$entry_query = TRUE;
			$this->parse_multiple_params("exp_channel_titles.url_title", $this->params['entry_url_title']);
			$this->ee->db->where("exp_taggable_tags_entries.entry_id = exp_channel_titles.entry_id")->from('exp_channel_titles');
		}

		// Distinct?
		if ($entry_query) {
			$this->ee->db->distinct();
		}

		// Select star
		$this->ee->db->select('exp_taggable_tags.id, exp_taggable_tags.name, exp_taggable_tags.description');

		// MSM
		$this->ee->db->where('exp_taggable_tags.site_id', $this->site_id);

		// Find the tags
		if ($entry_query) {
			$this->ee->db->where('exp_taggable_tags.id = exp_taggable_tags_entries.tag_id');
			$this->ee->db->from('exp_taggable_tags_entries');
		}

		// taggable_tags_find_query
		$this->ee->extensions->call('taggable_tags_find_query');

		// Find the tags
		$this->ee->db->save_queries = TRUE;
		$tags = $this->ee->tags->get_all();
		
		// Entry count
		if ($tags) {
			foreach ($tags as $tag) {
				$ids[] = (int)$tag->id;
				$tgs[$tag->id] = $tag;
				$tgs[$tag->id]->entry_count = 0;
			}
			
			$counts = $this->ee->tags->tag_entries_counts($ids);
			
			foreach ($counts as $id => $count) {
				$tgs[$id]->entry_count = $count;
			}
			
			foreach ($tags as $tag) {
				$tag->entry_count = $tgs[$tag->id]->entry_count;
			}
			
			// Order by entry count?
			if ($orderby_ent) {
				usort($tags, array($this, '_order_by_count'));
			}
		}
		
		// Done!
		return $tags;
	}
	
	/**
	 * Used in usort() to order the
	 * entry count
	 *
	 * @author Jamie Rumbelow
	 */
	private function _order_by_count($a, $b) {
		return ($a->entry_count < $b->entry_count) ? 1 : -1 ;
	}
	
	/**
	 * Return pretty tag name
	 *
	 * @param string $name 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _pretty_tag($name) {
		// @todo want to do more with this, not sure what yet :)
		return ucwords($name);
	}
	
	/**
	 * Fetch the tag ID from the tag name
	 *
	 * @param string $name 
	 * @return void
	 * @author Jamie Rumbelow
	 */
	private function _fetch_tag_id($name) {
		return $this->ee->db->where('name', $name)->get('taggable_tags')->row('id');
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
						$v = $this->ee->db->where($lookup_col, $val)->get($lookup_table);
						
						if ($v->num_rows > 0) {
							$new_vals[] = $v->row($lookup_id);
						} else {
							$new_vals[] = $val;
						}
					}
				} else {
					$new_vals = $vals;
				}
				
				$this->ee->db->where_not_in($id_col, $new_vals);
			} else {
				// one not
				$string = str_replace("not ", "", $string);
				$string = trim($string);
				
				// Lookup?
				if ($lookup_table) {
					$new_val = array();
					$v = $this->ee->db->where($lookup_col, $string)->get($lookup_table);
						
					if ($v->num_rows > 0) {
						$new_val = $v->row($lookup_id);
					} else {
						$new_val = $string;
					}
				} else {
					$new_val = $string;
				}
				
				$this->ee->db->where($id_col.' !=', $new_val);
			}
		} else {
			if (strpos($string, '|')) {
				// multiple vals
				$string = str_replace(" ", "", $string);
				$vals = explode('|', $string);
				
				// Lookup?
				if ($lookup_table) {
					$new_vals = array();
					
					foreach ($vals as $key => $val) {
						$v = $this->ee->db->where($lookup_col, $val)->get($lookup_table);
						
						if ($v->num_rows > 0) {
							$new_vals[] = $v->row($lookup_id);
						} else {
							$new_vals[] = $val;
						}
					}
				} else {
					$new_vals = $vals;
				}
				
				$this->ee->db->where_in($id_col, $new_vals);
			} else {
				// single value
				$string = str_replace("not ", "", $string);
				$string = trim($string);
				
				// Lookup?
				if ($lookup_table) {
					$new_val = array();
					$v = $this->ee->db->where($lookup_col, $string)->get($lookup_table);
						
					if ($v->num_rows > 0) {
						$new_val = $v->row($lookup_id);
					} else {
						$new_val = $string;
					}
				} else {
					$new_val = $string;
				}
				
				$this->ee->db->where($id_col, $new_val);
			}
		}
	}
}