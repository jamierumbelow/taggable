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
 * @version 1.2.0b
 **/

require_once BASEPATH.	"core/Model.php";
require_once PATH_THIRD."taggable/libraries/Model.php";
require_once PATH_THIRD."taggable/config.php";

class Taggable {
	private $ee;
	
	public $tagdata;
	public $site_id;
	
	public function __construct() {
		$this->ee =& get_instance();
		
		$this->ee->load->library('model');
			
		$this->ee->load->model('taggable_preferences_model', 'preferences');
		$this->ee->load->model('taggable_tag_model', 'tags');
		
		$this->tagdata = $this->ee->TMPL->tagdata;
		
		// MSM
		$this->site_id = $this->ee->config->item('site_id');
		
		// taggable_vanilla_tagdata
		if ($this->ee->extensions->active_hook('taggable_vanilla_tagdata')) {
			$this->tagdata = $this->ee->extensions->call('taggable_vanilla_tagdata', $this->tagdata);
			if ($this->ee->extensions->end_script === TRUE) return $this->tagdata;
		}
	}
	
	public function tags() {
		$tag_id 			 = $this->ee->TMPL->fetch_param('tag_id');
		$tag_name 			 = $this->ee->TMPL->fetch_param('tag_name');
		$entry_id 			 = $this->ee->TMPL->fetch_param('entry_id');
		$entry_url_title 	 = $this->ee->TMPL->fetch_param('entry_url_title');
		$channel			 = $this->ee->TMPL->fetch_param('channel');
		$sort		 		 = strtolower($this->ee->TMPL->fetch_param('sort'));
		$backspace 			 = $this->ee->TMPL->fetch_param('backspace');
		$limit				 = $this->ee->TMPL->fetch_param('limit');
		$url_separator		 = ($u = $this->ee->TMPL->fetch_param('url_separator')) ? $u : '-' ;
		$lookup_tag_url_name = ($this->ee->TMPL->fetch_param('lookup_tag_url_name') == 'no') ? FALSE : TRUE;
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
			$this->parse_multiple_params('exp_tags.tag_id', $tag_id);
		}
		
		// Tag Name
		if ($tag_name) {
			if ($lookup_tag_url_name) {
				foreach ($tag_name as $key => $tag) {
					$tag_name[$key] = str_replace($url_separator, ' ', $tag);
				}
			}
			
			$this->parse_multiple_params('exp_tags.tag_name', $tag_name);
		}
		
		// taggable_tags_find_query
		$this->ee->extensions->call('taggable_tags_find_query');
		
		// Channel
		if ($channel) {
			$this->parse_multiple_params('exp_channel_titles.channel_id', $channel, 'exp_channels', 'channel_name', 'channel_id');
			$this->ee->db->join('exp_tags_entries', 'exp_tags.tag_id = exp_tags_entries.tag_id');
			$this->ee->db->join('exp_channel_titles', 'exp_tags_entries.entry_id = exp_channel_titles.entry_id');
		}
		
		// Find the tags
		if ($entry_id) {
			$this->ee->db->where('exp_tags.site_id', $this->site_id);
			$tags = $this->ee->tags->tags_entry($entry_id);
		} elseif ($entry_url_title) {
			$this->ee->db->where('exp_tags.site_id', $this->site_id);
			$tags = $this->ee->tags->tags_entry_url_title($entry_url_title);
		} else {
			// careful with this one...
			$this->ee->db->where('exp_tags.site_id', $this->site_id);
			$tags = $this->ee->tags->get_all();
		}
		
		// taggable_tags_pre_loop
		if ($this->ee->extensions->active_hook('taggable_tags_pre_loop')) {
			$tags = $this->ee->extensions->call('taggable_tags_pre_loop', $tags, $this->tagdata);
			if ($this->ee->extensions->end_script === TRUE) return $this->tagdata;
		}
		
		// Set up the tag variables
		if ($tags) {
			foreach ($tags as $tag) {
				$vars[] = array(
					'tag_name'			=> $tag->tag_name,
					'tag_id'			=> $tag->tag_id,
					'tag_description'	=> $tag->tag_description,
					'entry_count'		=> $this->ee->tags->tag_entries($tag->tag_id),
					'tag_url_name'		=> str_replace(' ', $url_separator, $tag->tag_name),
					'tag_pretty_name'	=> $this->_pretty_tag($tag->tag_name)
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
		return $parsed;
	}
	
	public function entries() {
		$tag_ids 		= $this->ee->TMPL->fetch_param('tag_id');
		$tag_names		= $this->ee->TMPL->fetch_param('tag_name');
		$url_separator	= ($this->ee->TMPL->fetch_param('url_separator')) ? $this->ee->TMPL->fetch_param('url_separator') : "_";
		$tag_url_name	= $this->ee->TMPL->fetch_param('tag_url_name');
		$tags			= array();
		
		// Is there a 'not ' ?
		if (stristr($tag_ids, "not ") && stristr($tag_names, "not ")) {
			$string = "not ";
		} else {
			$string = "";
		}
		
		// Tag IDs are easy peasy
		if ($tag_ids) {
			if (!stristr($tag_ids, '|')) {
				$tags[] = $tag_ids;
			} else {
				$ts	= explode('|', $tag_ids);
			
				foreach ($ts as $t) {
					$tags[] = $t;
				}
			}
		}
		
		// Tag names with URL separator
		if ($tag_url_name == 'yes') {
			// Tag names require a DB lookup
			if ($tag_names) {
				if (!stristr($tag_names, '|')) {
					$tags[] = $this->_fetch_tag_id(str_replace(' ', $url_separator, $tag_names));
				} else {
					$ts	= explode('|', $tag_names);
			
					foreach ($ts as $t) {
						$tags[] = $this->_fetch_tag_id(str_replace(' ', $url_separator, $t));
					}
				}
			}
		} else {
			// Tag names require a DB lookup
			if ($tag_names) {
				if (!stristr($tag_names, '|')) {
					$tags[] = $this->_fetch_tag_id($tag_names);
				} else {
					$ts	= explode('|', $tag_names);
			
					foreach ($ts as $t) {
						$tags[] = $this->_fetch_tag_id($t);
					}
				}
			}
		}
		
		// Get the entries
		$entries = $this->ee->db->where_in('tag_id', $tags)->get('tags_entries')->result();
		$es = array();
		
		foreach ($entries as $entry) {
			$es[] = $entry->entry_id;
		}
		
		// Prepare the vars
		$parse[] = array(
			'entries' => implode('|', $es)
		);
		
		// Parse!
		$parsed = $this->ee->TMPL->parse_variables($this->tagdata, $parse);
		
		// Done!
		return $parsed;
	}
	
	public function cloud() {
		$min_size 	   = $this->ee->TMPL->fetch_param('min_size');
		$max_size 	   = $this->ee->TMPL->fetch_param('max_size');
		$channel	   = $this->ee->TMPL->fetch_param('channel');
		$tag_id   	   = $this->ee->TMPL->fetch_param('tag_id');
		$url_seperator = $this->ee->TMPL->fetch_param('url_seperator');
		$backspace 	   = $this->ee->TMPL->fetch_param('backspace');
		
		// Sensible defaults
		$min_size 		= ($min_size) ? $min_size : 12;
		$max_size 		= ($max_size) ? $max_size : 12;
		$url_seperator  = ($url_seperator) ? $url_seperator : '-';
		$vars			= array();
		
		// Filter by tag ID
		if ($tag_id) {
			$this->parse_multiple_params('exp_tags.tag_id', $tag_id);
		}
		
		// Channel
		if ($channel) {
			$this->parse_multiple_params('exp_channel_titles.channel_id', $channel, 'exp_channels', 'channel_name', 'channel_id');
			$this->ee->db->join('exp_tags_entries', 'exp_tags.tag_id = exp_tags_entries.tag_id');
			$this->ee->db->join('exp_channel_titles', 'exp_tags_entries.entry_id = exp_channel_titles.entry_id');
		}

		// Find
		$this->ee->db->where('exp_tags.site_id', $this->site_id);
		$tags = $this->ee->db->get('exp_tags')->result();
		$counts = array();

		// Loop through the tags and count them
        foreach ($tags as $tag) {
        	$tag->entry_count = $this->ee->tags->tag_entries($tag->tag_id);
			$counts[] = $tag->entry_count;
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
		
		// Loop through and build the $vars array
		if ($tags) {
			foreach ($tags as $tag) {        	
				$size = round(12 + (($tag->entry_count - $min_qty) * $step));
            
	            $vars[] = array(
	            	'tag_name' 			=> $tag->tag_name,
	            	'tag_url_name'		=> str_replace(' ', $url_seperator, $tag->tag_name),
					'tag_pretty_name'	=> $this->_pretty_tag($tag->tag_name),
	            	'tag_id'			=> $tag->tag_id,
	            	'tag_description'	=> $tag->tag_description,
	            	'entry_count'		=> $tag->entry_count,
	            	'size'				=> $size
	            );
	        }
		} else {
			$vars[] = array(
            	'tag_name' 			=> '',
            	'tag_url_name'		=> '',
				'tag_pretty_name'	=> '',
            	'tag_id'			=> '',
            	'tag_description'	=> '',
            	'entry_count'		=> '',
            	'size'				=> ''
            );
		}
		
		// Parse the template
		$parsed = $this->ee->TMPL->parse_variables($this->tagdata, $vars);
		
		// Away we go!
		return $parsed;
	}
	
	private function _pretty_tag($name) {
		// @todo want to do more with this, not sure what yet :)
		return ucwords($name);
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
			if (strpos('|', $string)) {
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