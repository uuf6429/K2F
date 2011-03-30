<?php defined('K2F') or die;

	uses('core/cms.php','exts/google.maps.php');
	
	// load logic/data classes
	require_once(CFG::get('ABS_K2F').'apps/KeenMaps/classes.php');
	
	// load views/ui classes
	require_once(CFG::get('ABS_K2F').'apps/KeenMaps/views.php');

	/**
	 * Keen Client Product Management
	 * This application allows a shop to manage sold products to provide
	 * servicing and maintenance.
	 */
	class KeenMaps extends Application {
		public $name='Keen Map Markers';
		public $description='Keen Map Markers Management.';
		public $version='0.2.5.4';
		public $date='20 Dec 2010';
		public $tags='maps markers ratings management keen-advertising';
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
			$menu=CmsHost::cms()->admin_add_menu('Keen Maps','Keen Map Markers Management.',self::icons('main'),ClassMethod('kmapsViewMarkers','view'));
			CmsHost::cms()->admin_add_submenu($menu,'Categories','Categories',self::icons('categories'),ClassMethod('kmapsViewCategories','view'));
			CmsHost::cms()->admin_add_submenu($menu,'Markers','Markers',self::icons('markers'),ClassMethod('kmapsViewMarkers','view'));
			CmsHost::cms()->admin_add_submenu($menu,'Settings','Settings',self::icons('settings'),ClassMethod('kmapsViewSettings','view'));
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
					$url.'img/categories48.png'
				),
				'markers'=>new AppIcon(
					$url.'img/markers16.png',
					$url.'img/markers32.png',
					$url.'img/markers48.png'
				),
				'settings'=>new AppIcon(
					$url.'img/settings16.png',
					$url.'img/settings32.png',
					$url.'img/settings48.png'
				),
			);
			return $icon ? $icons[$icon] : $icons;
		}
		/**
		 * Api to fully render map with markers, tooltips etc.
		 * <br>The code echoed is javascript, so ensure you call this function inside a script tag block.
		 * @param string $width Width in CSS format.
		 * @param string $height Width in CSS format.
		 * @param string|integer|float $latitude Either the value of latitude or JS code which evaluates to an integer/float.
		 * @param string|integer|float $longitude Either the value of longitude or JS code which evaluates to an integer/float.
		 * @return [Google_Map,[Google_Marker:row] An array of prepared and resulting data objects.
		 */
		public static function render($width,$height,$latitude,$longitude,$zoom=12){
			xlog_enable(false);
			$map=new Google_Map('map');
			$map->set_size($width,$height);
			$map->set_location($latitude,$longitude);
			$map->set_zoom($zoom);
			$map->set_type(Google_Map::TYPE_ROADMAP);
			$map->set_defaultui(Google_Map::UI_DISABLED);
			$map->set_ctrl_zoom(true,Google_Map::POS_TOP_LEFT,Google_Map::STYLE_ZOOM_LARGE);
			$html='<div class="km-categories" style="position:absolute; right:0; top:0;">'; $ctg=new kmCategory();
			$ctgs=new kmCategories(); $ctgs->load('`published`');
			foreach($ctgs->rows as $ctg)if($ctg->published)
				$html.='<label for="km_cat'.$ctg->id.'">'
					.'<input type="checkbox" id="km_cat'.$ctg->id.'" onclick="km_cat_filter('.(int)$ctg->id.',this.checked)"'.($ctg->ticked ? ' checked="checked"' : '').'/>&nbsp;'.Security::snohtml($ctg->name)
					.'</label><br/>';
			$html.='</div>';
			$map->render(Google_Map::AS_JS,$html);
			$row=new kmMarker();
			$rows=new kmMarkers();
			$rows->load('`published`');
			$markers=array();
			foreach($rows->rows as $row)
				if($row->category()->published && $row->published && $row->latitude && $row->longitude){
					$marker=$map->add_marker('marker'.$row->id);
					$marker->set_location(
						Security::filename($row->latitude,'',array('.')),
						Security::filename($row->longitude,'',array('.'))
					);
					$marker->set_icon(@json_encode($row->category()->icon));
					$marker->set_visible($row->category()->ticked ? 'true' : 'false');
					$marker->render(Google_Marker::AS_JS);
					$marker->row=$row;
					$markers[]=$marker;
				}
			xlog_revert();
			return array($map,$markers);
		}
		public static function router($segments){

		}
	}
	Applications::register('KeenMaps');
	
	// Register marker url permalink/rewrite
	$url=CmsHost::cms()->config_get('km-marker-url');
	if(CmsHost::cms()->rewrite_enabled()){
		if($url=='')$url='/places/%category%/%place%/';
		$arr=array('%category%'=>'([^/]+)','%place%'=>'([^/]+)');
		if(strlen($url) && $url{0}=='/')$url=substr($url,1);
		$url=str_replace(array_keys($arr),array_values($arr),preg_quote($url)).'?$';
		CmsHost::cms()->rewrite_url($url,ClassMethod('KeenMaps','router'),true);
	}

	if(CFG::get('CMS_HOST')=='wordpress'){
		function wp_css_hotfix(){
			?><style type="text/css">#content div img { max-width:none; }</style><?php
		}
		Events::add('on_head','wp_css_hotfix');
	}

?>