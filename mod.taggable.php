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
 * @version 1.3.1
 **/

require_once BASEPATH.	"core/Model.php";
require_once PATH_THIRD."taggable/libraries/Model.php";
require_once PATH_THIRD."taggable/config.php";

class Taggable {
	private $ee;
	
	public $tagdata;
	public $site_id;
	
	public function Taggable() {
		$this->ee =& get_instance();
		
		$this->ee->load->library('model');
			
		$this->ee->load->model('taggable_tag_model', 'tags');
		
		$this->tagdata = $this->ee->TMPL->tagdata;
		$this->site_id = $this->ee->config->item('site_id');
		
		// taggable_vanilla_tagdata
		if ($this->ee->extensions->active_hook('taggable_vanilla_tagdata')) {
			$this->tagdata = $this->ee->extensions->call('taggable_vanilla_tagdata', $this->tagdata);
			if ($this->ee->extensions->end_script === TRUE) return $this->tagdata;
		}
	
		// Parameters
		$tag_id 			 = $this->ee->TMPL->fetch_param('tag_id');
		$tag_name 			 = $this->ee->TMPL->fetch_param('tag_name');
		$tag_url_name 		 = $this->ee->TMPL->fetch_param('tag_url_name');
		$entry_id 			 = $this->ee->TMPL->fetch_param('entry_id');
		$entry_url_title 	 = $this->ee->TMPL->fetch_param('entry_url_title');
		$channel			 = $this->ee->TMPL->fetch_param('channel');
		$sort		 		 = strtolower($this->ee->TMPL->fetch_param('sort'));
		$backspace 			 = $this->ee->TMPL->fetch_param('backspace');
		$limit				 = $this->ee->TMPL->fetch_param('limit');
		$url_separator		 = ($u = $this->ee->TMPL->fetch_param('url_separator')) ? $u : '_' ;
		$min_size 	   		 = $this->ee->TMPL->fetch_param('min_size');
		$max_size 	   		 = $this->ee->TMPL->fetch_param('max_size');
		$min_size 			 = ($min_size) ? $min_size : 12;
		$max_size 			 = ($max_size) ? $max_size : 12;
		$vars				 = array();
		
		// Limit
		if ($limit) {
			$this->ee->db->limit($limit);
		}
		
		// Sort
		if ($sort) {
			$this->ee->db->order_by("tag_name ".strtoupper($sort));
		}
		
		// Tag ID
		if ($tag_id) {
			$this->parse_multiple_params('exp_taggable_tags.id', $tag_id);
		}
		
		// Tag Name
		if ($tag_name) {	
			$this->parse_multiple_params('exp_taggable_tags.name', $tag_name);
		}
		
		// Tag URL Name
		if ($tag_url_name) {
			$tag_url_name = str_replace($url_separator, ' ', $tag_url_name);
			$this->parse_multiple_params('exp_taggable_tags.name', $tag_url_name);
		}
		
		// taggable_tags_find_query
		$this->ee->extensions->call('taggable_tags_find_query');
		
		// Channel
		if ($channel) {
			$this->parse_multiple_params('exp_channel_titles.channel_id', $channel, 'exp_channels', 'channel_name', 'channel_id');
			$this->ee->db->join('exp_channel_titles', 'exp_taggable_tags_entries.entry_id = exp_channel_titles.entry_id');
		}
		
		// Entry count
		$this->ee->db->select('exp_taggable_tags.*');
		$this->ee->db->select('COUNT(DISTINCT exp_taggable_tags_entries.entry_id) AS entry_count');
		
		// MSM
		$this->ee->db->where('exp_taggable_tags.site_id', $this->site_id);
		
		// Find the tags
		if ($entry_id) {
			$tags = $this->ee->tags->tags_entry($entry_id);
		} elseif ($entry_url_title) {
			$tags = $this->ee->tags->tags_entry_url_title($entry_url_title);
		} else {
			// careful with this one...
			$this->ee->db->join('exp_taggable_tags_entries', 'exp_taggable_tags_entries.tag_id = exp_taggable_tags.id');
			$tags = $this->ee->tags->get_all();
		}
		
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
				$size = round(12 + (($tag->entry_count - $min_qty) * $step));
				
				$vars[] = array(
					'name'			=> $tag->name,
					'id'			=> $tag->id,
					'description'	=> $tag->description,
					'entry_count'	=> $tag->entry_count,
					'size'			=> $size,
					'url_name'		=> str_replace(' ', $url_separator, $tag->name),
					'pretty_name'	=> $this->_pretty_tag($tag->name)
				);
			}
		} else {
			return $this->ee->TMPL->no_results();
		}
		
		// taggable_tags_post_loop
		if ($this->ee->extensions->active_hook('taggable_tags_post_loop')) {
			$vars = $this->ee->extensions->call('taggable_tags_post_loop', $vars, $this->tagdata);
			if ($this->ee->extensions->end_script === TRUE) return $this->tagdata;
		}
		
		// Parse it!
		$parsed = $this->ee->TMPL->parse_variables($this->tagdata, $vars);
		
		// Backspace?
		if ($backspace) {
			if (is_numeric($backspace)) {
				$parsed = substr($parsed, 0, - (int)$backspace);
			}
		}
		
		// taggable_tags_end
		if ($this->ee->extensions->active_hook('taggable_tags_end')) {
			$parsed = $this->ee->extensions->call('taggable_tags_end', $parsed);
		}
		
		// We're done!
		$this->return_data = $parsed;
	}
	
	private function _pretty_tag($name) {
		// @todo want to do more with this, not sure what yet :)
		return ucwords($name);
	}
	
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