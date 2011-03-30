<?php defined('K2F') or die;

	uses('core/cms.php','core/apps.php');

	// load logic/data classes
	require_once(CFG::get('ABS_K2F').'apps/KeenCPM/classes.php');
	
	// load views/ui classes
	require_once(CFG::get('ABS_K2F').'apps/KeenCPM/views.php');

	/**
	 * Keen Client Product Management
	 * This application allows a shop to manage sold products to provide
	 * servicing and maintenance.
	 */
	class KeenCPM extends Application {
		public $name='Keen CPM';
		public $description='Keen Client-Product Management';
		public $version='1.3.4.4';
		public $date='1 Nov 2010';
		public $tags='clients models stock products management keen-advertising';
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
			$menu=CmsHost::cms()->admin_add_menu('Keen CPM','Easy client and product management.',self::icons('main'),ClassMethod('kcmViewDashboard','dashboard'));
			CmsHost::cms()->admin_add_submenu($menu,'Dashboard','Dashboard',self::icons('main'),ClassMethod('kcmViewDashboard','dashboard'));
			CmsHost::cms()->admin_add_submenu($menu,'Clients','Add or change clients.',self::icons('clients'),ClassMethod('kcmViewClients','manage'));
			CmsHost::cms()->admin_add_submenu($menu,'Categories','Add or change product categories.',self::icons('categories'),ClassMethod('kcmViewCategories','manage'));
			CmsHost::cms()->admin_add_submenu($menu,'Models','Add or change models.',self::icons('models'),ClassMethod('kcmViewModels','manage'));
			CmsHost::cms()->admin_add_submenu($menu,'Stock','Add or change stock.',self::icons('stock'),ClassMethod('kcmViewStock','manage'));
			CmsHost::cms()->admin_add_submenu($menu,'Templates','Add or change document templates.',self::icons('documents'),ClassMethod('kcmViewDocuments','manage'));
			CmsHost::cms()->admin_add_submenu($menu,'Events','Add or change events.',self::icons('events'),ClassMethod('kcmViewEvents','manage'));
			CmsHost::cms()->admin_add_submenu($menu,'Settings','Manage KeenCPM settings.',self::icons('settings'),ClassMethod('kcmViewSettings','manage'));
			CmsHost::cms()->admin_add_submenu($menu,'Archives','Manage archived data.',self::icons('archive'),ClassMethod('kcmViewArchive','manage'));
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
				'clients' => new AppIcon(
					$url.'img/clients16.png',
					$url.'img/clients32.png',
					$url.'img/clients48.png'
				),
				'categories' => new AppIcon(
					$url.'img/categories16.png',
					$url.'img/categories32.png',
					$url.'img/categories48.png'
				),
				'models' => new AppIcon(
					$url.'img/models16.png',
					$url.'img/models32.png',
					$url.'img/models48.png'
				),
				'stock' => new AppIcon(
					$url.'img/stock16.png',
					$url.'img/stock32.png',
					$url.'img/stock48.png'
				),
				'documents' => new AppIcon(
					$url.'img/documents16.png',
					$url.'img/documents32.png',
					$url.'img/documents48.png'
				),
				'events' => new AppIcon(
					$url.'img/events16.png',
					$url.'img/events32.png',
					$url.'img/events48.png'
				),
				'settings' => new AppIcon(
					$url.'img/settings16.png',
					$url.'img/settings32.png',
					$url.'img/settings48.png'
				),
				'archive' => new AppIcon(
					$url.'img/archive16.png',
					$url.'img/archive32.png',
					$url.'img/archive48.png'
				),
				'service' => new AppIcon(
					$url.'img/service16.png'
				),
				'install' => new AppIcon(
					$url.'img/install16.png'
				),
				'purchase' => new AppIcon(
					$url.'img/purchase16.png'
				),
				'public' => new AppIcon(
					$url.'img/public16.png'
				),
				'private' => new AppIcon(
					$url.'img/private16.png'
				),
				'preview' => new AppIcon(
					$url.'img/preview16.png'
				),
				'download' => new AppIcon(
					$url.'img/download16.png'
				),
				'email' => new AppIcon(
					$url.'img/email16.png'
				),
				'yes' => new AppIcon(
					$url.'img/yes16.png'
				),
				'no' => new AppIcon(
					$url.'img/no16.png'
				)
			);
			return $icon ? $icons[$icon] : $icons;
		}
		public static function route($segments){
			// TODO: Make use of rewrite url setting to find the model id
			$_REQUEST['model']=explode('-',$segments[2]);
			$_REQUEST['model']=(int)$_REQUEST['model'][0];
			// TODO: Make use of rewrite url view to actually show page
			require_once ABSPATH.'wp-content/themes/jtabone/products.php';
			die;
		}
	}
	Applications::register('KeenCPM');


	// translate all /group/sub/id-model/ to /?pagename=products&model=XX
	if(CmsHost::cms()->rewrite_enabled()){
		$groups=new kcmModels();
		$groups->load('`published` GROUP BY `group`');

		if($groups->count()){
			$names=array(); foreach($groups->rows as $row)$names[]=preg_quote($row->group); $names=implode('|',$names);
			// TODO: Make use of rewrite url setting
			CmsHost::cms()->rewrite_url('('.$names.')/([^/]+)/([0-9]{1,})-([^/]+)/?$',ClassMethod('KeenCPM','route'),true);
		}
	}
	
?>