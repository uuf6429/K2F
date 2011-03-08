<?php defined('K2F') or die;

	/**
	 * jsWidget is a small system where one can use a PHP class in JS, in a way where one can use events, functions etc with it.
	 */
	abstract class jsWidget {
		const AS_HTML='html';
		const AS_JS='js';
		/**
		 * @var boolean Whether map has been rendered or not.
		 */
		protected $rendered=false;
		/**
		 * @var string Name of widget.
		 */
		public $name='something';
		/**
		 * @var array Holds an array of event_name => event_handler pairs.
		 */
		protected $events=array();
		/**
		 * Sets the event handler for an event (removes any existing handler!).
		 * <br>You can leave the second parameter empty to remove current handler instead of setting a new one.
		 * @param string $event The event name (see EVENT_* constants).
		 * @param string $handler Either name of javascript function to call, or an anonymous function.
		 */
		public function handle($event,$handler=null){
			if($handler===null){
				unset($this->events[$event]);
			}else{
				$this->events[$event]=$handler;
			}
		}
		/**
		 * Send widget html directly to browser.
		 * @param string $mode (Optional) The output mode. See jsWidget::AS_* constants.
		 */
		public function render($mode=self::AS_HTML){
			if($this->rendered)trigger_error('Widget has already been rendered');
			// write new object
			// pass options ot object
			// set object event handlers
			$this->rendered=true;
		}
		/**
		 * @return boolean Whether this widget has been rendered or not.
		 */
		public function rendered(){
			return $this->rendered;
		}
		/**
		 * Returns variable name of widget.
		 * @return string Javascript code representing widget.
		 */
		public function name(){
			return $this->name;
		}
		/**
		 * Utility function to call a widget method (used in JS).
		 * @param string $method Method name to call.
		 * @param array $args Arguments to pass to function. You must encode strings.
		 * @return string Javascript code.
		 */
		protected function mtd($method,$args=array()){
			return $this->name().'.'.$method.'('.implode(',',$args).')';
		}
		/**
		 * Returns a (purposefully) insecure json object. "Insecure" so that the code gets executed.
		 * @param array $array The array to serialize.
		 * @return string The serialized array.
		 */
		protected static function a2o($array){
			foreach($array as $k=>$v)$array[$k]=@json_encode($k).':'.(is_scalar($v) ? $v : self::a2o($v));
			return '{'.CRLF.TAB.implode(','.CRLF.TAB,array_values($array)).CRLF.'}';
		}
		/**
		 * Converts a boolean value to a string counterpart.
		 * @param mixed $boolean Original value.
		 * @return string Javascript value.
		 */
		protected static function b2s($boolean){
			if($boolean===true)$boolean='true';
			if($boolean===false)$boolean='false';
			return $boolean;
		}
	}

?>