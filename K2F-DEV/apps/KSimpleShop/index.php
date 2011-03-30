<?php defined('K2F') or die;

	uses('core/cms.php');
	
	// load logic/data classes
	require_once(CFG::get('ABS_K2F').'apps/KSimpleShop/classes.php');
	
	// load views/ui classes
	require_once(CFG::get('ABS_K2F').'apps/KSimpleShop/views.php');

	/**
	 * Keen Client Product Management
	 * This application allows a shop to manage sold products to provide
	 * servicing and maintenance.
	 */
	class KSimpleShop extends Application {
		public $name='KSimple Shop';
		public $description='Product/Merchandise Management Component.';
		public $version='1.0';
		public $date='29 Mar 2011';
		public $tags='products merchandise shop management keen-advertising';
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
			$menu=CmsHost::cms()->admin_add_menu('KSimple Shop','Product/Merchandise Management.',self::icons('main'),ClassMethod('kssManageProducts','view'));
			CmsHost::cms()->admin_add_submenu($menu,'Manage Products','Manage Products',self::icons('main'),ClassMethod('kssManageProducts','view'));
			CmsHost::cms()->admin_add_submenu($menu,'Settings','Settings',self::icons('settings'),ClassMethod('kssManageSettings','view'));
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
				'settings'=>new AppIcon(
					$url.'img/settings16.png',
					$url.'img/settings32.png',
					$url.'img/settings48.png',
					$url.'img/settings64.png',
					$url.'img/settings128.png'
				),
			);
			return $icon ? $icons[$icon] : $icons;
		}
	}
	Applications::register('KSimpleShop');

?>