<?php
/**
 * Object class, allowing __construct and __destruct in PHP4.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Core
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */


/**
 * Object class provides a few generic methods used in several subclasses.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
 *
 * @package       Cake.Core
 */
class Object {

/**
 * constructor, no-op
 *
 */
	public function __construct() {
	}

/**
 * Object-to-string conversion.
 * Each class can override this method as necessary.
 *
 * @return string The name of this class
 */
	public function toString() {
		$class = get_class($this);
		return $class;
	}

/**
 * Calls a method on this object with the given parameters. Provides an OO wrapper
 * for `call_user_func_array`
 *
 * @param string $method  Name of the method to call
 * @param array $params  Parameter list to use when calling $method
 * @return mixed  Returns the result of the method call
 */
	public function dispatchMethod($method, $params = array()) {
		switch (count($params)) {
			case 0:
				return $this->{$method}();
			case 1:
				return $this->{$method}($params[0]);
			case 2:
				return $this->{$method}($params[0], $params[1]);
			case 3:
				return $this->{$method}($params[0], $params[1], $params[2]);
			case 4:
				return $this->{$method}($params[0], $params[1], $params[2], $params[3]);
			case 5:
				return $this->{$method}($params[0], $params[1], $params[2], $params[3], $params[4]);
			default:
				return call_user_func_array(array(&$this, $method), $params);
			break;
		}
	}

/**
 * Stop execution of the current script.  Wraps exit() making
 * testing easier.
 *
 * @param integer|string $status see http://php.net/exit for values
 * @return void
 */
	protected function _stop($status = 0) {
		exit($status);
	}

/**
 * Convenience method to write a message to CakeLog.  See CakeLog::write()
 * for more information on writing to logs.
 *
 * @param string $msg Log message
 * @param integer $type Error type constant. Defined in app/Config/core.php.
 * @return boolean Success of log write
 */
	public function log($msg, $type = LOG_ERROR) {
		//App::uses('CakeLog', 'Log');
		if (!is_string($msg)) {
			$msg = print_r($msg, true);
		}
		return Log::write($type, $msg);
	}

/**
 * Allows setting of multiple properties of the object in a single line of code.  Will only set
 * properties that are part of a class declaration.
 *
 * @param array $properties An associative array containing properties and corresponding values.
 * @return void
 */
	protected function _set($properties = array()) {
		if (is_array($properties) && !empty($properties)) {
			$vars = get_object_vars($this);
			foreach ($properties as $key => $val) {
				if (array_key_exists($key, $vars)) {
					$this->{$key} = $val;
				}
			}
		}
	}

/**
 * Merges this objects $property with the property in $class' definition.
 * This classes value for the property will be merged on top of $class'
 *
 * This provides some of the DRY magic CakePHP provides.  If you want to shut it off, redefine
 * this method as an empty function.
 *
 * @param array $properties The name of the properties to merge.
 * @param string $class The class to merge the property with.
 * @param boolean $normalize Set to true to run the properties through Set::normalize() before merging.
 * @return void
 */
	protected function _mergeVars($properties, $class, $normalize = true) {
		$classProperties = get_class_vars($class);
		foreach ($properties as $var) {
			if (
				isset($classProperties[$var]) &&
				!empty($classProperties[$var]) &&
				is_array($this->{$var}) &&
				$this->{$var} != $classProperties[$var]
			) {
				if ($normalize) {
					$classProperties[$var] = Set::normalize($classProperties[$var]);
					$this->{$var} = Set::normalize($this->{$var});
				}
				$this->{$var} = Set::merge($classProperties[$var], $this->{$var});
			}
		}
	}
}
