<?php defined('K2F') or die;

	uses('core/events.php','exts/jswidget.php','core/ajax.php','core/security.php');

	/**
	 * A class for loading jQuery via CDN with some various options.
	 */
	class jQueryCDN {
		/**
		 * @var boolean Whether minified version is used or not.
		 */
		public static $minified=true;
		/**
		 * @var boolean Whether to use no-conflict mode or not.
		 */
		public static $noconflict=true;
		/**
		 * @var string If not empty, this is the name of the jQuery UI style to load.
		 * <br/>Use 'other' to instead load your own (note: you must also write your own link tag to do this).
		 * @example Cupertino, Dot-Luv, Mint-Choc, Other
		 */
		public static $load_ui='';
		/**
		 * @var boolean Whether to load jQuery Cycle or not.
		 */
		public static $load_cycle=false;
		/**
		 * @var boolean Whether to load jQuery Validate or not.
		 */
		public static $load_valid=false;
		/**
		 * Generates a script tag.
		 * @param string $url If set, this is the script's SRC attribute value (url).
		 * @param string $code If set, this is the script's JS code contents.
		 */
		protected static function _render($url='',$code=''){
			?><script type="text/javascript"<?php if($url)echo ' src="'.Security::snohtml($url).'"'; ?>><?php echo $code; ?></script><?php
		}
		/**
		 * This is intended to be called in the head section, after any other script tag.
		 */
		public static function render(){
			self::_render(self::$minified ? 'http://code.jquery.com/jquery.min.js' : 'http://code.jquery.com/jquery.js');
			if(self::$load_ui && strtolower(self::$load_ui)!='other'){
				self::_render(self::$minified ? 'http://ajax.aspnetcdn.com/ajax/jquery.ui/1.8.9/jquery-ui.min.js' : 'http://ajax.aspnetcdn.com/ajax/jquery.ui/1.8.9/jquery-ui.js');
				?><link rel="stylesheet" href="http://ajax.microsoft.com/ajax/jquery.ui/1.8.9/themes/<?php echo strtolower(self::$load_ui); ?>/jquery-ui.css" type="text/css" /><?php
			}
			if(self::$load_cycle)self::_render(self::$minified ? 'http://ajax.aspnetcdn.com/ajax/jquery.cycle/2.88/jquery.cycle.all.min.js' : 'http://ajax.aspnetcdn.com/ajax/jquery.cycle/2.88/jquery.cycle.min.js');
			if(self::$load_valid)self::_render(self::$minified ? 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.7/jquery.validate.min.js' : 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.7/jquery.validate.js');
			if(self::$noconflict)self::_render('','jQuery.noConflict();');
		}
	}

?>