<?php defined('K2F') or die;

	uses('core/cms.php');
	
	// load logic/data classes
	require_once(CFG::get('ABS_K2F').'apps/MediaMan/classes.php');

	// load views/ui classes
	require_once(CFG::get('ABS_K2F').'apps/MediaMan/views.php');

	// load media type classes
	foreach(glob(CFG::get('ABS_K2F').'apps/MediaMan/types/*.php') as $file)
		require_once($file);

	// load media view classes
	foreach(glob(CFG::get('ABS_K2F').'apps/MediaMan/views/*.php') as $file)
		require_once($file);

	/**
	 * Media Manager System
	 * MediaMan is a system whereby one can easily manage a list of related
	 * media and use it later on in somewhere specific (through API code).
	 */
	class MediaMan extends Application {
		public $name='Keen Map Markers';
		public $description='Keen Map Markers Management.';
		public $version='0.0.2.6';
		public $date='21 Feb 2010';
		public $tags='media pictures images videos music files management keen-advertising';
		public static function path(){
			return CFG::get('ABS_K2F').'apps/'.__CLASS__.'/';
		}
		public static function url(){
			return CFG::get('REL_K2F').'apps/'.__CLASS__.'/';
		}
		public function mainicon() {
			return self::icons('main');
		}
		public function on_admin_menu(){
			// create main and submenu items
			$menu=CmsHost::cms()->admin_add_menu('Media Manager','Keen Media Management.',self::icons('main'),ClassMethod('kmmMainView','ViewCats'));
			CmsHost::cms()->admin_add_submenu($menu,'Dashboard','Dashboard',self::icons('categories'),ClassMethod('kmmMainView','ViewCats'));
			CmsHost::cms()->admin_add_submenu($menu,'API Code','API Code',self::icons('apicode'),ClassMethod('kmmMainView','ViewCode'));
		}
		/**
		 * Returns an array of icons or a specified AppIcon.
		 * @param string $icon (optional) The icon name to query.
		 * @return AppIcon|array Array of icons or an AppIcon.
		 */
		public static function icons($icon=null){
			// generate a couple of icons
			$url=self::instance()->url();
			$icons=array(
				'main'=>new AppIcon(
					$url.'img/icon16.png',
					$url.'img/icon32.png',
					$url.'img/icon48.png',
					$url.'img/icon64.png',
					$url.'img/icon128.png'
				),
				'categories'=>new AppIcon(
					$url.'img/categories16.png',
					$url.'img/categories32.png',
					$url.'img/categories48.png',
					$url.'img/categories64.png',
					$url.'img/categories128.png'
				),
				'apicode'=>new AppIcon(
					$url.'img/apicode16.png',
					$url.'img/apicode32.png',
					$url.'img/apicode48.png',
					$url.'img/apicode64.png',
					$url.'img/apicode128.png'
				),
			);
			return $icon ? $icons[$icon] : $icons;
		}

		/// API SECTION ///

		/**
		 * Render category viewer given view class and category id.
		 * @param string $view View class name.
		 * @param integer $category Category id.
		 */
		public static function render($view,$category){
			// ...
		}
	}
	Applications::register('MediaMan');

?>