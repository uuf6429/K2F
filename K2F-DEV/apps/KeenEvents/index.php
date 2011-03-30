<?php defined('K2F') or die;

	uses('core/cms.php','core/apps.php');

	// resolve application dependencies
	if(Applications::installed('KeenMaps'))Applications::load('KeenMaps');

	// load logic/data classes
	require_once(CFG::get('ABS_K2F').'apps/KeenEvents/classes.php');
	
	// load views/ui classes
	require_once(CFG::get('ABS_K2F').'apps/KeenEvents/views.php');

	/**
	 * KeenEvents Event Management Management
	 * This application allows an administrator to manage a list of events.
	 */
	class KeenEvents extends Application {
		public $name='KeenEvents';
		public $description='Keen Events Management';
		public $version='1.0.0.0';
		public $date='28 Feb 2011';
		public $tags='events management keen-advertising';
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
			$menu=CmsHost::cms()->admin_add_menu('Keen Events','Easy event management.',self::icons('main'),ClassMethod('keEventsView','manage'));
			CmsHost::cms()->admin_add_submenu($menu,'Manage Events','Manage Events',self::icons('main'),ClassMethod('keEventsView','manage'));
			CmsHost::cms()->admin_add_submenu($menu,'Manage Settings','Manage Settings.',self::icons('settings'),ClassMethod('keSettingsView','manage'));
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
				'settings' => new AppIcon(
					$url.'img/settings16.png',
					$url.'img/settings32.png',
					$url.'img/settings48.png'
				),
			);
			return $icon ? $icons[$icon] : $icons;
		}
		public static function route($segments){
			global $event; $event=null;
			// load some variables
			$stru=CmsHost::cms()->config_get('ke-event-url');
			if($stru=='')$stru='/events/%venue%/%title%/';
			$view=CmsHost::cms()->config_get('ke-event-view');
			if($view=='')$view=CFG::get('ABS_WWW');
			$url=str_replace(CFG::get('REL_WWW'),'',$_SERVER['REQUEST_URI']);
			// handle rewritten url
			if(strpos($stru,'%id%')!==false){
				$event=new keEvent(CmsHost::struct_parse($stru,$url,array_keys(get_object_vars(new keEvent())),'id'));
				$event->load();
				$_REQUEST['event']=$event->id;
			}elseif(strpos($stru,'%title%')!==false){
				$t=CmsHost::struct_parse($stru,$url,array_keys(get_object_vars(new keEvent())),'title');
				$event=new keEvent();
				$event->load('`title`="'.Security::escape($t).'"');
				$_REQUEST['event']=$event->id;
			}else xlog('Error: (Keen Events) Either %id% or %title% were expected in permalink, but none found.');
			// load theme file
			if(!(include_once $view))xlog('Error: (Keen Events) You need to set the path to the view template file.');
			die;
		}
	}
	Applications::register('KeenEvents');

	// translate all /events/id-event/
	if(CmsHost::cms()->rewrite_enabled()){
		$stru=CmsHost::cms()->config_get('ke-event-url');
		if($stru=='')$stru='/events/%venue%/%title%/';
		$regx=CmsHost::struct_apply($stru,array_keys(get_object_vars(new keEvent())));
		CmsHost::cms()->rewrite_url($regx,ClassMethod('KeenEvents','route'),true);
	}
	
?>