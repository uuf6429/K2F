<?php

	/**
	 * This is a class to give mixin capabilities to PHP. To use, each of your mixin classes must:
	 *  - extend this Mixin class
	 *  - have a public property named $mixins containing an array of mixin class names.
	 * @copyright 2010 Covac Software
	 * @author Ivo Jansch (original), Christian Sciberras (modified)
	 * @version 16/05/2011 - Original implementation.
	 * @link http://www.jansch.nl/2006/08/23/mixins-in-php/
	 * @example <code>
	 *     class Alertable {
	 *       function alert($msg) {
	 *         echo '<script language="javascript"> alert('.json_encode($msg).'); </script>';
	 *       }
	 *     }
	 *     class Blinkable {
	 *       function blink($msg){
	 *         echo '<blink>'.Security::snohtml($msg).'</blink>';
	 *       }
	 *     }
	 *     class Notification { // the mixin class
	 *       public $mixins=array('Alertable','Blinkable'); // mixin classes, order matters
	 *     }
     *     // example
	 *     $n = new Notification();
	 *     $n->alert('Good morning!');
	 *     $n->blink('Error!');
	 * </code>
	 */
	class Mixin {
		protected $_mixinlookuptable = array();

		/**
		 * Mixin constructor, loads all methods of each mixin class.
		 */
		function __construct(){
			if(is_array($this->mixins))
				foreach($this->mixins as $mixin){
					$methods = get_class_methods($mixin);
					if(is_array($methods))
						foreach($methods as $method)
							$this->_mixinlookuptable[$method] = $mixin;
				}
		}

		/**
		 * PHP magic method used to call the right mixin class/method.
		 * @param string $method Method name of mixin to call.
		 * @param array $args List of arguments to pass to method.
		 * @return mixed Whatever the called method returns.
		 */
		function __call($method, $args){
			if(isset($this->_mixinlookuptable[$method])){
				$elems = array(); $result=null;
				for ($i=0, $_i=count($args); $i<$_i; $i++) $elems[] = '\$args['.$i.']';
				eval('$result = '.$this->_mixinlookuptable[$method].'::'.$method.'('.implode(',',$elems).');');
				return $result;
			}
			trigger_error('Call to undefined function '.$method, E_USER_WARNING);
		}
	}

?>