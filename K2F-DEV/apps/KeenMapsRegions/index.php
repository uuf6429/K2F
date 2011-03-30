<?php defined('K2F') or die;

	uses('core/cms.php','exts/google.maps.php','apps/KeenMaps/index.php');
	
	// load logic/data classes
	require_once(CFG::get('ABS_K2F').'apps/KeenMapsRegions/classes.php');
	
	// load views/ui classes
	require_once(CFG::get('ABS_K2F').'apps/KeenMapsRegions/views.php');

	/**
	 * Keen Client Product Management
	 * This application allows a shop to manage sold products to provide
	 * servicing and maintenance.
	 */
	class KeenMapsRegions extends Application {
		public $name='Keen Maps Regions';
		public $description='Regional Support for Keen Map Markers Management.';
		public $version='0.2.5.4';
		public $date='08 Mar 2010';
		public $tags='maps markers ratings region management keen-advertising';
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
			$menu=CmsHost::cms()->admin_add_menu('Keen Regions','Keen Map Regions Management.',self::icons('main'),ClassMethod('kmrViewRegions','view'));
			CmsHost::cms()->admin_add_submenu($menu,'Manage Regions','Manage Regions',self::icons('main'),ClassMethod('kmrViewRegions','view'));
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
			);
			return $icon ? $icons[$icon] : $icons;
		}
		public static function route($segments){
			// /user/holiday-planner/id-name
			$_REQUEST['region']=explode('-',$segments[2]);
			$_REQUEST['region']=(int)$_REQUEST['region'][0];
			require_once(ABSPATH.'wp-content/themes/oneweek/regions.php');
		}
	}
	Applications::register('KeenMapsRegions');

	// translate all /user/holiday-planner/id-region/ to /?pagename=regions&region=XX
	if(CmsHost::cms()->rewrite_enabled()){
		// TODO: Make use of rewrite url setting
		CmsHost::cms()->rewrite_url('(user)/(holiday\-planner)/([0-9]{1,})-([^/]+)/?$',ClassMethod('KeenMapsRegions','route'),true);
	}

?>