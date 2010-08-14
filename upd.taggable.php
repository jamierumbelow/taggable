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

require_once PATH_THIRD."taggable/config.php";

class Taggable_upd {
	public $version = TAGGABLE_VERSION;
	private $ee;
	
	public function __construct() {
		$this->ee =& get_instance();
		$this->ee->load->dbforge();
	}
	
	public function install() {
		// exp_modules
		$module = array(
			'module_name' 			=> 'Taggable',
			'module_version'		=> $this->version,
			'has_cp_backend'		=> 'y',
			'has_publish_fields' 	=> 'n'
		);
		
		$this->ee->db->insert('modules', $module);
		
		// exp_taggable
		$this->ee->dbforge->add_field(array('name' => array('type' => 'VARCHAR', 'constraint' => 250), 'entry_count' => array('type' => 'INT')));
		$this->ee->dbforge->add_key('name'); $this->ee->dbforge->add_key('entry_count');
		$this->ee->dbforge->create_table('taggable');
		
		// We're done!
		return TRUE;
	}
	
	public function uninstall() {
		// Goodbye!
		$this->ee->dbforge->drop_table('taggable');
		$this->ee->db->where('module_name', 'Taggable')->delete('modules');
		
		// We're done
		return TRUE;
	}
	
	public function update($version = '') {
		// Update from 1.0 to 1.1:
		//   - Add the site ID to the tags table, set the current ID as the default
		//   - Get rid of the 'search_tags' extension hook
		if ($version < 1.1) {			
			$this->ee->dbforge->add_column('tags', array('site_id' => array('type' => 'INT')));
			$this->ee->db->set('site_id', $this->ee->config->item('site_id'))->update('tags');
			$this->ee->db->where('class', 'Taggable_ext')->where('method', 'search_tags')->delete('extensions');
		}
		
		// Update from 1.1 to 1.2:
		//   - Add the template column to tags_entries
		//   - Get rid of the publish tab
		//   - Get rid of the 'parse_tags_tag' hook
		//   - Add the site ID to the preferences table
		if ($version < 1.2) {
			$this->ee->dbforge->add_column('tags_entries', array('template' => array('type' => 'VARCHAR', 'constraint' => 250, 'default' => 'UPGRADE')));
			$this->ee->db->set('has_publish_fields', 'n')->where('module_name', 'Taggable')->update('modules');
			$this->ee->db->where('preference_key', 'enable_autotagging')->where('preference_key', 'alchemy_api_key')->delete('taggable_preferences');
			$this->ee->db->where('method', 'parse_tags_tag')->where('class', 'Taggable_ext')->delete('exp_extensions');
			$this->ee->dbforge->add_column('taggable_preferences', array('site_id' => array('type' => 'INT', 'default' => '1')));
		}
		
		// Update from 1.2 to 1.3:
		//   - Get rid of the preferences and entries table
		// 	 - Update the config file with the license key
		// 	 - Get all the tags, drop the tags table and rebuild
		// 	 - Re-index all the tags with the denormalised entry count
		// 	 - Get rid of the extension hooks
		if ($version < 1.3) {
			$license_key = $this->ee->db->where('preference_key', 'license_key')->get('taggable_preferences')->row('preference_value');
			$this->ee->dbforge->drop_table('taggable_preferences');
			$this->ee->config->_update_config(array('taggable_license_key' => $license_key));
			
			$tags = $this->ee->db->get('tags');
			$entries = $this->ee->db->get('tags_entries');
			$this->ee->dbforge->drop_table('tags');
			$this->ee->dbforge->drop_table('tags_entries');
			
			$new_tags = array();
			foreach ($tags as $tag) { $new_tags[$tag->id] = array($tag->name, 0); }			
			foreach ($entries as $entry) { $new_tags[$entry->tag_id][1] = $new_tags[$entry->tag_id][1] + 1; }
			
			$this->ee->dbforge->add_field(array('name' => array('type' => 'VARCHAR', 'constraint' => 250), 'entry_count' => array('type' => 'INT')));
			$this->ee->dbforge->add_key('name'); $this->ee->dbforge->add_key('entry_count');
			$this->ee->dbforge->create_table('taggable');
			foreach ($new_tags as $tag) { $this->ee->db->insert('taggable', array('name' => $tag[0], 'entry_count' => $tag[1])); }
			
			$this->ee->db->where('class', 'Taggable_ext')->delete('extensions');
		}

		return TRUE;
	}
}