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

class Taggable_tag_model extends Model {
	public $primary_key 	= 'tag_id';
	public $_table 			= 'tags';
	public $before_create 	= array('lowercase_tag', 'site_id', 'trimmed_name');
	public $site_id			= 0;
	
	public function __construct() {
		parent::__construct();
	}
	
	public function order_by_entries() {
		$this->db->select('COUNT(exp_tags_entries.entry_id) AS entry_count');
		$this->db->join('exp_tags_entries', 'exp_tags_entries.tag_id = exp_tags.tag_id');
		$this->order_by('entry_count');
	}
	
	public function entry_tagged_with_tag($entry, $id) {
		return (bool)$this->db->where('entry_id', $entry)->where('tag_id', $tag)->get('tags_entries')->num_rows;
	}
	
	public function get_alphabet_list() {
		$tags = $this->db->select("exp_tags.tag_name")
		 				 ->from("exp_tags, exp_tags_entries")
						 ->where("exp_tags.tag_id = exp_tags_entries.tag_id")
						 ->where('exp_tags.site_id', $this->site_id) 
						 ->order_by("exp_tags.tag_name ASC")
						 ->get();
		$letters = array();
					
		if ($tags->num_rows > 0) {
			foreach ($tags->result() as $tag) {
				$first_letter = substr($tag->tag_name, 0, 1);
				
				if (!isset($letters[$first_letter])) { 
					$letters[$first_letter] = 1;
				} else {
					$letters[$first_letter]++;
				}
			}
		}
		
		return $letters;
	}
	
	public function tag_entries($id) {
		return $this->db->select("COUNT(DISTINCT exp_tags_entries.entry_id) AS total")
						->from("exp_tags, exp_tags_entries")
						->where("exp_tags_entries.tag_id", $id)
						->get()
						->row('total');
	}
	
	public function tags_list_entry($entry) {
		$tags = $this->tags_entry($entry);
		$tagnames = array();
		
		foreach ($tags as $tag) {
			$tagnames[] = "<a href=\"".TAGGABLE_URL.AMP."method=tag_entries".AMP."tag_id=$tag->tag_id\">$tag->tag_name</a>";
		}
		
		return implode(', ', $tagnames);
	}
	
	public function tags_entry($entry) {
		return $this->db->where('exp_tags_entries.entry_id', $entry)
						 ->join('exp_tags_entries', 'exp_tags.tag_id = exp_tags_entries.tag_id')
						 ->get('exp_tags')
						 ->result();
	}
	
	public function tags_entry_url_title($url_title) {
		return $this->db->select("exp_tags.tag_id, exp_tags.tag_name, exp_tags.tag_description")
						->where("exp_tags.tag_id = exp_tags_entries.tag_id")
						->where("exp_tags_entries.entry_id = exp_channel_titles.entry_id")
						->where("exp_channel_titles.url_title", $url_title)
						->from("exp_tags, exp_tags_entries, exp_channel_titles")
						->get()
						->result();
	}
	
	public function top_five_tags() {
		$tags = $this->db->select("exp_tags.tag_id, exp_tags.tag_name, exp_tags.tag_description, COUNT(*) AS total")
  	 				 	 ->from("exp_tags, exp_tags_entries")
 						 ->where('exp_tags.site_id', $this->site_id) 
						 ->where("exp_tags.tag_id = exp_tags_entries.tag_id")
						 ->order_by("total DESC")
						 ->group_by("exp_tags.tag_id")
						 ->limit(5)
						 ->get()
						 ->result();
		$string = '';
		
		if ($tags) {
			foreach ($tags as $tag) {
				$string .= "<a href=\"".TAGGABLE_URL.AMP."method=tag_entries".AMP."tag_id=".$tag->tag_id."\" title=\"$tag->tag_description\">".$tag->tag_name."</a><br />";
			}
		} else {
			$string = lang('taggable_not_applicable');
		}
		
		return $string;
	}
	
	public function stats() {
		$stats[lang('taggable_stats_total_tags')] 			= $this->count_all();
		$stats[lang('taggable_stats_total_tagged_entries')]	= $this->db->select('COUNT(DISTINCT entry_id)')->from("exp_tags_entries")->get()->row('COUNT(DISTINCT entry_id)');
		$stats[lang('taggable_stats_top_five_tags')]		= $this->top_five_tags();
		
		return $stats;
	}
	
	protected function lowercase_tag($tag) {
		if ($this->preferences->get_by('preference_key', 'convert_to_lowercase')->preference_value == 'y') {
			$tag['name'] = strtolower($tag['name']);
		}
		
		return $tag;
	}
	
	protected function site_id($tag) {
		$tag['site_id'] = $this->ee->config->item('site_id');
		
		return $tag;
	}
	
	protected function trimmed_name($tag) {
		$tag['name'] = trim($tag['name']);
		
		return $tag;
	}
}