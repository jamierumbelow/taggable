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

require_once PATH_THIRD."taggable/config.php";

class Taggable_upd {
	public $version = TAGGABLE_VERSION;
	private $ee;
	
	/**
	 * Constructor
	 *
	 * @author Jamie Rumbelow
	 */
	public function __construct() {
		$this->ee =& get_instance();
		$this->ee->load->dbforge();
	}
	
	/**
	 * Install
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function install() {
		// exp_modules
		$module = array(
			'module_name' 			=> 'Taggable',
			'module_version'		=> $this->version,
			'has_cp_backend'		=> 'y',
			'has_publish_fields' 	=> 'n'
		);
		
		$this->ee->db->insert('modules', $module);
		
		// exp_actions
		$action = array(
			'class' => 'Taggable',
			'method' => 'api_entries'
		);
		
		$this->ee->db->insert('actions', $action);
		
		// We're done!
		return TRUE;
	}
	
	/**
	 * Uninstall
	 *
	 * @return void
	 * @author Jamie Rumbelow
	 */
	public function uninstall() {
		// Goodbye!
		$this->ee->db->where('module_name', 'Taggable')->delete('modules');
		
		// We're done
		return TRUE;
	}
	
	/**
	 * Update
	 *
	 * @param string $version 
	 * @return void
	 * @author Jamie Rumbelow
	 */
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
		
		// -------------------------
		// 		TAGGABLE 1.3 IS INCOMPATIBLE WITH 1.2
		// -------------------------
		
		if ($version < 1.3) {
			show_error("Taggable 1.3 is incompatible with Taggable 1.2. Please re-install 1.2, export your tags, un-install 1.2 and install 1.3 from scratch before importing your tags.");
		}
		
		// Update from 1.2 to 1.3:
		//   - Rename tables so they're all prefixed with 'taggable_'
		//   - Drop table prefix from columns
		//	 - Get rid of the extension
		// 	 - Move license key to config file
		//	 - Drop preferences table
		// if ($version < 1.3) {
		// 			$this->ee->dbforge->rename_table($this->ee->db->dbprefix . 'tags', $this->ee->db->dbprefix . 'taggable_tags');
		// 			$this->ee->dbforge->rename_table($this->ee->db->dbprefix . 'tags_entries', $this->ee->db->dbprefix . 'taggable_tags_entries');
		// 			$this->ee->db->where('class', 'Taggable_ext')->delete('exp_extensions');
		// 			$this->ee->config->_update_config(array('taggable_license_key' => $this->ee->db->where('preference_key', 'license_key')->get('taggable_preferences')->row('preference_value')));
		// 			$this->ee->dbforge->drop_table('taggable_preferences');
		// 			
		// 			$this->ee->dbforge->modify_column('taggable_tags', array(
		// 				'tag_id' => array('name' => 'id'),
		// 				'tag_name' => array('name' => 'name'),
		// 				'tag_description' => array('name' => 'description')
		// 			));
		// 		}
		
		// Update from 1.3 to 1.4
		// 	  - Add a config option for default theme
		if ($version < 1.4) {
			$this->ee->config->_update_config(array('taggable_default_theme' => 'taggable-tokens'));
		}
		
		// Update from 1.4 to 1.4.5
		//    - Add site_id column to taggable_tags_entries
		//    - Loop through and update the site IDs
		if ($version < "1.4.5") {
			$this->ee->dbforge->add_column('taggable_tags_entries', array('site_id' => array('type' => 'INT', 'default' => '1')));
			$this->ee->db->query('UPDATE `exp_taggable_tags_entries`, `exp_taggable_tags` SET `exp_taggable_tags_entries`.`site_id` = `exp_taggable_tags`.`site_id` WHERE `exp_taggable_tags_entries`.`tag_id` = `exp_taggable_tags`.`id`');
		}

		return TRUE;
	}
}