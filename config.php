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

// NSM Addon Updater
$config['name'] 								= "Taggable";
$config['version'] 								= "1.2.0";
$config['nsm_addon_updater']['versions_xml'] 	= "http://";

// Version constant
if (!defined("TAGGABLE_VERSION")) {
	define('TAGGABLE_VERSION', $config['version']);
}