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
 * @version 1.3.2
 **/

require_once PATH_THIRD."taggable/libraries/Model.php";

class Taggable_tag_model extends Model {
	public $primary_key 	= 'id';
	public $_table 			= 'exp_taggable_tags';
	public $before_create 	= array('site_id', 'trimmed_name');
	public $site_id			= 0;
	public $filters			= array();
	
	private $entry_counted  = FALSE;
	
	public function __construct() {
		parent::__construct();
		
		$this->site_id = $this->config->item('site_id');
	}
	
	public function order_by_entries() {
		$this->db->select('COUNT(exp_taggable_tags_entries.entry_id) AS entry_count');
		$this->db->join('exp_taggable_tags_entries', 'exp_taggable_tags_entries.tag_id = exp_taggable_tags.id', 'left');
		$this->order_by('entry_count');
		$this->entry_counted = TRUE;
	}
	
	public function reset_filters() {
		$this->filters['order'] 			 = 'tag_id';
		$this->filters['text_search_order']  = 'sw';
		$this->filters['text_search'] 		 = '';
		$this->filters['entry_count_order']  = 'mt';
		$this->filters['entry_count'] 		 = '';
	}
	
	public function where_tag_name_based_on_text_search_term($pos, $term) {
		if ($pos == 'sw') {
			$this->ee->db->where("exp_taggable_tags.name LIKE ", $term."%");
		} elseif ($pos == 'co') {
			$this->ee->db->where("exp_taggable_tags.name LIKE ", "%".$term."%");
		} elseif ($pos == 'ew') {
			$this->ee->db->where("exp_taggable_tags.name LIKE ", "%".$term);
		}
		
		$this->filters['text_search_order']  = $pos;
		$this->filters['text_search']		 = $term;
	}
	
	public function where_entry_count_based_on_entry_count_order($pos, $term) {
		if (!$this->entry_counted) {
			$this->db->select('COUNT(DISTINCT exp_taggable_tags_entries.entry_id) AS entry_count');
			$this->db->join('exp_taggable_tags_entries', 'exp_taggable_tags_entries.tag_id = exp_taggable_tags.id', 'left');
		}
		
		if ($pos == 'mt') {
			$this->db->having('entry_count > ', $term);
		} elseif ($pos == 'lt') {
			$this->db->having('entry_count < ', $term);
		} elseif ($pos == 'et') {
			$this->db->having('entry_count = ', $term);
		}
		
		$this->filters['entry_count_order'] = $pos;
		$this->filters['entry_count'] 		= $term;
	}
	
	public function order_by($what) {
		if ($what == 'entries') {
			$this->order_by_entries();
		} else {
			parent::order_by($what);
		}
		
		$this->filters['order'] = $what;
	}
	
	public function delete_entries($tags) {
		$this->db->where_in('tag_id', $tags)->delete('exp_taggable_tags_entries');
	}
	
	public function grouped_tags_lookup() {
		$this->db->select('exp_taggable_tags.id, exp_taggable_tags.name, exp_taggable_tags.description');
		$this->db->where('exp_taggable_tags.site_id', $this->site_id);
		$this->db->group_by('exp_taggable_tags.id');
	}
	
	public function entry_tagged_with_tag($entry, $id) {
		return (bool)$this->db->where('entry_id', $entry)->where('tag_id', $tag)->get('tags_entries')->num_rows;
	}
	
	public function get_alphabet_list() {
		$tags = $this->db->select("exp_taggable_tags.name")
		 				 ->from("exp_taggable_tags, exp_taggable_tags_entries")
						 ->where("exp_taggable_tags.id = exp_taggable_tags_entries.tag_id")
						 ->where('exp_taggable_tags.site_id', $this->site_id) 
						 ->order_by("exp_taggable_tags.name ASC")
						 ->get();
		$letters = array();
					
		if ($tags->num_rows > 0) {
			foreach ($tags->result() as $tag) {
				$first_letter = substr($tag->name, 0, 1);
				
				if (!isset($letters[$first_letter])) { 
					$letters[$first_letter] = 1;
				} else {
					$letters[$first_letter]++;
				}
			}
		}
		
		return $letters;
	}
	
	public function tag_entries($tag) {
		return $this->db->select('DISTINCT exp_taggable_tags_entries.entry_id, exp_channel_titles.title, exp_channel_titles.url_title, exp_channel_titles.channel_id')
				 	 	->where('exp_taggable_tags_entries.tag_id', $tag)
  	  			 		->where('exp_channel_titles.entry_id = exp_taggable_tags_entries.entry_id')
		   		 		->get('exp_taggable_tags_entries, exp_channel_titles')
				 		->result();
	}
	
	public function tag_entries_count($id) {
		return $this->db->select("COUNT(DISTINCT exp_taggable_tags_entries.entry_id) AS total")
						->from("exp_taggable_tags, exp_taggable_tags_entries")
						->where("exp_taggable_tags_entries.tag_id", $id)
						->get()
						->row('total');
	}
	
	public function tags_list_entry($entry) {
		$tags = $this->tags_entry($entry);
		$tagnames = array();
		
		foreach ($tags as $tag) {
			$tagnames[] = "<a href=\"".TAGGABLE_URL.AMP."method=tag_entries".AMP."tag_id=$tag->id\">$tag->name</a>";
		}
		
		return implode(', ', $tagnames);
	}
	
	public function tags_entry($entry) {
		return $this->db->get('exp_taggable_tags')
						 ->result();
	}
	
	public function tags_entry_url_title($url_title) {
		return $this->db->select("exp_taggable_tags.tag_id, exp_taggable_tags.tag_name, exp_taggable_tags.tag_description")
						->where("exp_taggable_tags.tag_id = exp_taggable_tags_entries.tag_id")
						->where("exp_taggable_tags_entries.entry_id = exp_channel_titles.entry_id")
						->where("exp_channel_titles.url_title", $url_title)
						->from("exp_taggable_tags, exp_taggable_tags_entries, exp_channel_titles")
						->get()
						->result();
	}
	
	public function top_five_tags() {
		$tags = $this->db->select("exp_taggable_tags.id, exp_taggable_tags.name, exp_taggable_tags.description, COUNT(*) AS total")
  	 				 	 ->from("exp_taggable_tags, exp_taggable_tags_entries")
 						 ->where('exp_taggable_tags.site_id', $this->site_id)
						 ->where("exp_taggable_tags.id = exp_taggable_tags_entries.tag_id")
						 ->order_by("total DESC")
						 ->group_by("exp_taggable_tags.id")
						 ->limit(5)
						 ->get()
						 ->result();
		$string = '';
		
		if ($tags) {
			foreach ($tags as $tag) {
				$string .= "<a href=\"".TAGGABLE_URL.AMP."method=tag_entries".AMP."tag_id=".$tag->id."\" title=\"$tag->description\">".$tag->name."</a><br />";
			}
		} else {
			$string = lang('taggable_not_applicable');
		}
		
		return $string;
	}
	
	public function stats() {
		$stats[lang('taggable_stats_total_tags')] 			= $this->count_all();
		$stats[lang('taggable_stats_total_tagged_entries')]	= $this->db->select('COUNT(DISTINCT entry_id)')->from("exp_taggable_tags_entries")->get()->row('COUNT(DISTINCT entry_id)');
		$stats[lang('taggable_stats_top_five_tags')]		= $this->top_five_tags();
		
		return $stats;
	}
	
	protected function site_id($tag) {
		$tag['site_id'] = $this->config->item('site_id');
		
		return $tag;
	}
	
	protected function trimmed_name($tag) {
		$tag['name'] = trim($tag['name']);
		
		return $tag;
	}
}