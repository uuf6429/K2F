<?php defined('K2F') or die;

	uses('exts/mobile.php','core/list.php','core/security.php','core/ajax.php');

	//define('THEME_PATH',CFG::get('BASE_PATH').'themes/'.(Mobile::is_mobile()?'mobile_':'full_').THEME.'/');

	class Theme {
		/**
		 * @var SimpleList List of header elements.
		 */
		public static $header=null;
		/**
		 * @var string Page title.
		 */
		public static $title='';
		/**
		 * Returns generated HTML header code.
		 * @return string Header code.
		 */
		protected static function mkhead(){
			return '<title>'.Security::snohtml(self::$title).'</title>'.self::$header->implode(CRLF);
		}
		/**
		 * Initialize variables.
		 */
		public static function init(){
			xlog('page start');
			self::$header=new SimpleList();
			ob_start();
			register_shutdown_function(array('Theme','fini'));
		}
		/**
		 * Finishes theme and renders page.
		 */
		public static function fini(){
			$content=ob_get_clean();
			xlog('theme start');
			ob_start();
			uses('../..'.THEME_PATH.'index.php');
			$html=ob_get_clean();
			xlog('theme finish');
			$html=str_ireplace('<header/>',self::mkhead(),$html);
			$html=str_ireplace('<content/>',$content,$html);
			echo $html;
			xlog('page finish');
		}
		public static function add_js_file($file){
			if(!self::$header)self::$header=new SimpleList();
			self::$header->add('<script type="text/javascript" src="js/'.$file.'"></script>');
		}
		public static function add_css_file($file){
			if(!self::$header)self::$header=new SimpleList();
			self::$header->add('<link rel="stylesheet" href="css/'.$file.'" type="text/css">');
		}
		public static function add_js_code($code){
			if(!self::$header)self::$header=new SimpleList();
			self::$header->add('<script type="text/javascript">'.$code.'</script>');
		}
		public static function add_css_code($code){
			if(!self::$header)self::$header=new SimpleList();
			self::$header->add('<style type="text/css">'.$code.'</style>');
		}
	}

	//if(!Ajax::is_on())Theme::init();

?>