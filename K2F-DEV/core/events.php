<?php defined('K2F') or die;

	uses('core/debug.php');

	/**
	 * A class for event-driven patterns.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 19/09/2010 - Original implementation.
	 *          06/12/2010 - Now supports debugable interface.
	 */
	class Events implements Debugable {
		protected static $events=array();
		/**
		 * Add event to a function stack.
		 * @param string $event The event to be fired (eg: 'click').
		 * @param string|array $action What to call when event fires (eg: 'onclick').
		 *   <br>$action can be a function, class method or object method.
		 *   <br>In case of the later two, use an array: array('class','mtd') or array($obj,'mtd')
		 * @param boolean $topmost (Optional) If this is true, the action is put at the very top
		 *   (and called before non-topmost actions). However, other topmost actions may interfere.
		 * @example <code>
		 *     function myonclick(){
		 *         echo 'clicked!';
		 *     }
		 *     Events::add('click','myonclick');
		 * </code>
		 * <br><b>IMPORTANT:</b> If you want to break the event chain, make $acction return false.
		 */
		public static function add($event,$action,$topmost=false){
			if(!isset(self::$events[$event]))self::$events[$event]=array();
			$topmost
				? array_unshift(self::$events[$event],$action)
				: array_push(self::$events[$event],$action);
		}
		/**
		 * Fires the specified event.
		 * @param string $event Event name to fire (eg: 'click').
		 * @param array $arguments Event-specific arguments to pass to event handlers.
		 */
		public static function call($event,$arguments=array()){
			if(isset(self::$events[$event]))
				foreach(self::$events[$event] as $action)
					if(call_user_func_array($action,$arguments)===false)
						break;
		}
		/**
		 * Removes all actions related to $action and $event.<br>
		 * <b>WARNING!!!</b> Calling this method without arguments causes it to REMOVE ALL EVENTS!
		 * @param string $event The event to be fired (eg: 'click') or an empty string for all events.
		 * @param string|array $action What to call when event fires (eg: 'onclick').
		 *   <br>$action can be a function, class method or object method.
		 *   <br>In case of the later two, use an array: array('class','mtd') or array($obj,'mtd')
		 */
		public static function remove($event='',$action=''){
			// small shortcut to convert object to action array
			if(is_object($action))$action=array($action,'');
			// do some looping, check a condition or two and then delete!
			foreach(self::$events as $ev=>$actions)
				if($event==null || $event==$ev)
					foreach($actions as $id=>$actn)
						if( (is_string($action) && is_string($actn) && ($action=='' || $action==$actn))
						 || ((is_array($actn) && count($actn)==2 && is_array($action) && count($action)==2)
						 && (($action[0]=='' || $action[0]==$actn[0]) && ($action[1]=='' || $action[1]==$actn[1])))
						  ) unset(self::$events[$ev][$id]);
		}
		/**
		 * Returns debug information.
		 */
		public static function onDebug(){
			return self::$events;
		}
	}

?>