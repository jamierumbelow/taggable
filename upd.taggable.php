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
 * @version 1.1.0
 **/

if (!defined("TAGGABLE_VERSION")) {
	require PATH_THIRD.'taggable/config.php';
	define('TAGGABLE_VERSION', $config['version']);
}

class Taggable_upd {
	public $version = TAGGABLE_VERSION;
	private $ee;
	
	public function __construct() {
		$this->ee =& get_instance();
	}
	
	public function tabs() {
		$tabs['tags'] = array(
			'tag' => array(
				'visible'		=> 'true',
				'collapse'		=> 'false',
				'htmlbuttons'	=> 'false',
				'width'			=> '100%'
			),
			'autotag' => array(
				'visible'		=> 'true',
				'collapse'		=> 'false',
				'htmlbuttons'	=> 'false',
				'width'			=> '100%'
			)
		);
		
		return $tabs;
	}
	
	public function install() {
		// Load dbforge
		$this->ee->load->dbforge();
		
		// exp_modules
		$module = array(
			'module_name' 			=> 'Taggable',
			'module_version'		=> $this->version,
			'has_cp_backend'		=> 'y',
			'has_publish_fields' 	=> 'y'
		);
		
		$this->ee->db->insert('modules', $module);
		
		// exp_tags
		$tags = array(
			'tag_id' 			=> array('type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'tag_name'			=> array('type' => 'VARCHAR', 'constraint' => 100),
			'tag_description'	=> array('type' => 'TEXT'),
			'site_id'			=> array('type' => 'INT', 'default' => $this->ee->config->item('site_id'))
		);
		
		$this->ee->dbforge->add_field($tags);
		$this->ee->dbforge->add_key('tag_id', TRUE);
		$this->ee->dbforge->create_table('tags');
		
		// exp_tags_entries
		$tags_entries = array(
			'tag_id' 	=> array('type' => 'INT'),
			'entry_id'	=> array('type' => 'INT'),
			'template'  => array('type' => 'VARCHAR', 'constraint' => 250, 'default' => 'tags')
		);
		
		$this->ee->dbforge->add_field($tags_entries);
		$this->ee->dbforge->add_key('tag_id');
		$this->ee->dbforge->add_key('entry_id');
		$this->ee->dbforge->create_table('tags_entries');
		
		// exp_taggable_preferences
		$taggable_preferences = array(
			'preference_id'		=> array('type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'preference_key'	=> array('type' => 'VARCHAR', 'constraint' => 50),
			'preference_type'	=> array('type' => 'VARCHAR', 'constraint' => 10),
			'preference_value'	=> array('type' => 'TEXT')
		);
		
		$this->ee->dbforge->add_field($taggable_preferences);
		$this->ee->dbforge->add_key('preference_id', TRUE);
		$this->ee->dbforge->create_table('taggable_preferences');
		
		// Insert default preference values
		$this->ee->config->load('default_preferences', TRUE);
		
		foreach ($this->ee->config->item('default_preferences') as $key => $value) {			
			$this->ee->db->set('preference_key', $key)
						 ->set('preference_value', $value['value'])
						 ->set('preference_type', $value['type'])
						 ->insert('taggable_preferences');
		}
		
		// Add layout tabs
		$this->ee->cp->add_layout_tabs($this->tabs());
		
		// Automatically enable extensions
		if ($this->ee->config->item('allow_extensions') == 'n') {
			$this->ee->config->_update_config(array('allow_extensions' => 'y'));
		}
		
		// We're done!
		return TRUE;
	}
	
	public function uninstall() {
		// Load dbforge
		$this->ee->load->dbforge();
		
		// Goodbye!
		$this->ee->dbforge->drop_table('tags');
		$this->ee->dbforge->drop_table('tags_entries');
		$this->ee->dbforge->drop_table('taggable_preferences');
		$this->ee->db->where('module_name', 'Taggable')->delete('modules');
		
		// Remove layout tabs
		$this->ee->cp->delete_layout_tabs($this->tabs());
		
		// We're done
		return TRUE;
	}
	
	public function update($version = '') {
		if ($version < 1.1) {
			$this->ee->load->dbforge();
			
			$this->ee->dbforge->add_column('tags', array('site_id' => array('type' => 'INT')));
			$this->ee->db->set('site_id', $this->ee->config->item('site_id'))->update('tags');
			$this->ee->db->where('class', 'Taggable_ext')->where('method', 'search_tags')->delete('extensions');
		}
		
		if ($version < 1.2) {
			$this->ee->load->dbforge();
			
			$this->ee->dbforge->add_column('tags_entries', array('template' => array('type' => 'VARCHAR', 'constraint' => 250, 'default' => 'tags')));
		}

		return TRUE;
	}
}