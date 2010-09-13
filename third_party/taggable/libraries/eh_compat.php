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
 * @version 1.3.4
 **/

/**
 * EngineHost (PHP 5.1) compatibility
 */

if (!function_exists('json_encode')) {
	
	/**
	 * Input an object, returns a json-ized string of said object
	 * 
	 * This function doesn't support special UTF-8 characters.
	 * 
	 * @param mixed $obj The array or object to encode
	 * @return string JSON formatted output
	 * @author Chip Harlan (Original Creator)
	 * @author Jay Williams (Added additional variable types)
	 * @link http://www.post-hipster.com/2008/02/15/elegant-little-php-json-encoder/
	 */
	function json_encode($obj) {
	    switch (gettype($obj)) {
	        case 'object':
	            $obj = get_object_vars($obj);   
	        case 'array':
	            if (array_is_associative($obj)) {
	                $arr_out = array();
	                foreach ($obj as $key=>$val) {
	                    $arr_out[] = '"' . $key . '":' . json_encode($val);
	                }
	                return '{' . implode(',', $arr_out) . '}';
	            } else {
	                $arr_out = array();
	                $ct = count($obj);
	                for ($j = 0; $j < $ct; $j++) {
	                    $arr_out[] = json_encode($obj[$j]);
	                }
	                return '[' . implode(',', $arr_out) . ']';
	            }
	            break;
	        case 'NULL':
	            return 'null';
	            break;
	        case 'boolean':
	            return ($obj)? 'true' : 'false';
	            break;
	        case 'integer':
	        case 'double':
	            return $obj;
	            break;
	        case 'string':
	        default:
	            $obj = str_replace(array('\\','/','"',), array('\\\\','\/','\"'), $obj);
	            return '"' . $obj . '"';
	            break;
	    }

	}

	function array_is_associative($array) {
		$count = count($array);
		for ($i = 0; $i < $count; $i++) {
			if (!array_key_exists($i, $array)) {
				return true;
			}
		}
		return false;
	}
}