<?php defined('K2F') or die;

	uses('exts/oodb.php','core/security.php','core/cms.php','core/connect.php','libs/swfupload/swfupload.php');
	
	/// SYSTEM LOGIC OBJECTS ///

	class kmrRegionTab { // this class is mostly used as a model
		public $name='';
		public $html='';
		public $categories=array();
		/**
		 * Small hackish script that ensures an object has this class' properties.
		 * @param array|kmrRegionTab $data The data to modify.
		 * @return array|kmrRegionTab The modified data.
		 */
		public static function sync($data){
			static $tmp=null; if(!$tmp){ $tmp=__CLASS__; $tmp=new $tmp; }
			if(is_array($data)){
				foreach($data as $dto)
					foreach($tmp as $n=>$v)
						if(!isset($dto->$n))
							$dto->$n=$v;
			}elseif(is_object($data)){
				foreach($tmp as $n=>$v)
					if(!isset($obj->$n))
						$obj->$n=$v;
			}
			return $data;
		}

		// the following code is to make class compatible with DatabaseRow

		public $region=null;
		public function __construct($r=null,$t=''){
			$done=false;
			if($r){
				$this->region=new kmrRegion($r);
				if($this->region->load())
					foreach($this->region->tabs as $tab)
						if($tab->name==$t){
							$this->name=$tab->name;
							$this->html=$tab->html;
							$this->categories=(array)$tab->categories;
							$done=true;
							break;
						}
			}
			if(!$done) // load failed somewhere
				$this->region=null;
		}
		public function load(){
			return $this->region!=null;
		}
		public function save(){
			if($this->region)
				foreach($this->region->tabs() as $tab)
					if($tab->name==$this->name){
						$tab->name=$this->name;
						$tab->html=$this->html;
						$tab->categories=$this->categories;
						return $this->region->save();
					}
			return false;
		}
	}

	class kmrRegion extends DatabaseRow {
		/**
		 * @var integer Region number.
		 */
		public $number=0;
		/**
		 * @var string Region title/name.
		 */
		public $title='';
		/**
		 * @var array Array of marker IDs.
		 */
		public $markers=array();
		/**
		 * @var string Short description.
		 */
		public $desc='';
		/**
		 * @var string Content as tabs (use tab?_*() functions).
		 */
		public $tabs=array();
		/**
		 * @var string Longitude of map view center (string to preserve precission).
		 */
		public $longitude='';
		/**
		 * @var string Latitude of map view center (string to preserve precission).
		 */
		public $latitude='';
		/**
		 * @var integer Zoom level of map view.
		 */
		public $zoom=8;
		/**
		 * @var string Thumbnail URL for region.
		 */
		public $thumb='';
		/**
		 * @var string A URL to region video (last minute addon bullshit).
		 */
		public $video='';
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kmr_regions';
		}
		/**
		 * Returns an array of markers for this region.
		 * @param boolean $cache Whether to return cached result or not.
		 * @return array A list of kmMarker instances.
		 */
		public function markers($cache=true){
			if(!isset($this->_markers) || !$cache){
				$this->_markers=array();
				foreach($this->markers as $id){
					$mkr=new kmMarker($id);
					if($mkr->load())$this->_markers[]=$mkr;
				}
			}
			return $this->_markers;
		}
		/**
		 * The category icon is used as icon URL.
		 */
		const MODE_ICON=0;
		/**
		 * The category icon together with marker type is used as icon URL.
		 */
		const MODE_ICONTYPE=1;
		/**
		 * The marker type is used as icon URL.
		 */
		const MODE_TYPE=2;
		/**
		 * Generates a map and markers for this region specifically.<br/>
		 * Note: Render assumes you are currently in HTML, not JS.
		 * @param integer $width Map width in pixels.
		 * @param integer $height Map height in pixels.
		 * @param array $typeMode Defines how marker icon URL is determined (see self::MODE_* constants).
		 * @return array Array of MAP instance and MARKER instances.
		 */
		public function render_map($width,$height,$typeMode=false){
			xlog_enable(false);
			$map=new Google_Map('region-map');
			$map->set_size((int)$width,(int)$height);
			$map->set_location($this->latitude,$this->longitude);
			$map->set_zoom($this->zoom);
			$map->render(Google_Map::AS_JS);
			$mkr=new kmMarker(); $markers=array();
			foreach($this->markers() as $mkr){
				$marker=$map->add_marker('marker'.$mkr->id);
				$marker->set_location(
					Security::filename($mkr->latitude,'',array('.')),
					Security::filename($mkr->longitude,'',array('.'))
				);
				$ext='.'.pathinfo($mkr->category()->icon,PATHINFO_EXTENSION);
				$icon=dirname($mkr->category()->icon).'/';
				$name=basename($mkr->category()->icon,$ext);

//				$mkr->type=='' && $showType ? $mkr->category()->icon : str_replace('.png','-'.rawurlencode($mkr->type).'.png',$mkr->category()->icon)
				$marker->set_icon(@json_encode($icon));
				$marker->set_visible($mkr->category()->ticked);
				$marker->render(Google_Marker::AS_JS);
				$marker->row=$mkr;
				$markers[]=$marker;
			}
			xlog_revert();
			return array($map,$markers);
		}
		/**
		 * Returns a specific region tab.
		 * @param kmrRegionTab $id The tab's id to laod.
		 */
		public function tab($id){
			if(!isset($this->tabs[$id]))
				$this->tabs[$id]=new kmrRegionTab();
			return kmrRegionTab::sync($this->tabs[$id]);
		}
		/**
		 * Returns an array of tab objects.
		 * @return array Array of kmrRegionTab instances.
		 */
		public function tabs(){
			kmrRegionTab::sync($this->tabs);
			return $this->tabs;
		}
		/**
		 * Add tab to list of tabs.
		 * @param kmrRegionTab $tab The tab to add.
		 */
		public function tab_add($tab){
			$this->tabs[]=$tab;
		}
		/**
		 * Remove tab from list and reorder tabs.
		 * @param integer $id The tab to remove.
		 */
		public function tab_remove($id){
			unset($this->tabs[$id]);
			$this->tabs=array_values($this->tabs);
		}
		/**
		 * Remove all tabs.
		 */
		public function tabs_clear(){
			$this->tabs=array();
		}
	}

	class kmrRegions extends DatabaseRows {
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kmr_regions';
		}
	}

	/// MISC. FUNCTIONS ///

	/**
	 * Shortens the filename to a human-readable format.<br/>
	 * The filename BECOMES UNRECOVERABLE. Do not reuse file name!!
	 * @param string $name Original filename, path or url
	 * @param string $maxlength (Optional) maximum length of filename. Use 0 to disable (default is 10).
	 * @param string $rangetext (Optional) text used to denote replaced text (default to three dots).
	 * @return string The shortened filename.
	 */
	function kmrShortFileName($name,$maxlength=10,$rangetext='...'){
		$url=@parse_url($name);
		if(isset($url['scheme']) && isset($url['path']))$url['path']; // uri
		$name=basename($name); // filename
		$xtra=strlen($name)-$maxlength;
		if($maxlength && $xtra>0){
			$left=round($maxlength/2);
			$name=substr_replace($name,$rangetext,$left,strlen($rangetext)-$left*2);
		}
		return $name;
	}

	class kmrUploader {
		/**
		 * Generates an uploader button.
		 * @param integer $id Uploader id.
		 * @param integer $w (Optional) Button width in pixels (defaults to 71).
		 */
		public static function button($id,$w=71){
			$src=Security::snohtml(Ajax::url(__CLASS__,'uploader').'&i=').$id;
			$style='background: url(\''.KeenMapsRegions::url().'img/upload-btn-'.CFG::get('CMS_HOST').'.gif'.'\') no-repeat left -54px; margin-top: 1px; border: none; vertical-align: middle;';
			?><iframe width="<?php echo (int)$w; ?>" height="18" frameborder="none" scrolling="no" src="<?php echo $src; ?>" style="<?php echo $style; ?>"></iframe><?php
		}
		/**
		 * Generates the HTML for the uploader button (iframe), including the real flash uploader widget.
		 */
		public static function uploader(){
			?><!DOCTYPE HTML><html><head><title></title><style type="text/css">body,html{margin:0;padding:0;}</style></head><body>
			<?php SwfUpload::init(); ?>
			<span id="kmr-upload-thumb"><!----></span>
			<script type="text/javascript">
				window.onload=function(){
					try {<?php echo CRLF;
						$url='http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].Ajax::url(__CLASS__,'uploaded');
						$upload=new SwfUpload('swfupload');
						$upload->set_placeholder('"kmr-upload-thumb"');
						$_REQUEST['i'] = isset($_REQUEST['i']) ? (int)$_REQUEST['i'] : 0; // error correction and input filtering
						$upload->handle(SwfUpload::EVENT_UPLOAD_START,    'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.kmr_thumb_update_start.apply( parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_UPLOAD_PROGRESS, 'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.kmr_thumb_update_progrs.apply(parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_UPLOAD_SUCCESS,  'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.kmr_thumb_update_finish.apply(parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_DIALOG_END,      'function(){ this.startUpload(); }');
						$upload->set_url(@json_encode($url));
						$upload->set_max_queue(0);
						$upload->set_button_cursor(SwfUpload::CURSOR_HAND);
						$upload->set_button_action(SwfUpload::ACTION_SELECT_FILE);
						$upload->set_button_size(71,18);
						$upload->set_button_sprite(@json_encode(KeenMapsRegions::url().'img/upload-btn-'.CFG::get('CMS_HOST').'.gif'));
						$upload->set_types('"*.bmp;*.png;*.jpg;*.gif;*.ico"');
						$upload->set_description('"Image Files"');
						$upload->set_post(@json_encode(array('tmpcks'=>@json_encode($_COOKIE))));
						$upload->render(SwfUpload::AS_JS);
					?>swfu.getMovieElement().title='Browse';
					} catch(e) {
						// this is a hack to route back errors to firebug from iframe
						(typeof parent.console!='undefined' ? parent.console : console).exception(e);
					}
				}
			</script></body></html><?php
			die;
		}
		/**
		 * Handle uploaded file.
		 * @return array|boolean Returns info for uploaded file or false on error.
		 */
		public static function uploaded(){
			// do a request to ::authorize() with specified cookies and check if return is true
			$cookies=isset($_POST['tmpcks']) ? (array)@json_decode($_POST['tmpcks']) : null;
			if(!$cookies)return false;
			$url='http://'.$_SERVER['SERVER_NAME'].'/'.$_SERVER['PHP_SELF'].Ajax::url(__CLASS__,'authorized');
			$resp=Connect::get($url,false,null,true,-1,$cookies);
			if(!@json_decode(trim($resp,'()')))return false;
			// handle the upload(s) and return details as json
			$file=new SwfUploadFile(); $result=null;
			foreach(SwfUpload::uploaded() as $file)
				if(file_put_contents(CmsHost::cms()->upload_dir().$file->name,$file->data))
					$result=array( // only use the last uploaded file
						'url'=>CmsHost::cms()->upload_url().$file->name,
						'file'=>kmrShortFileName($file->name),
						'size'=>'('.bytes_to_human(strlen($file->data)).')'
					);
			return $result ? $result : false;
		}
		/**
		 * AJAX method to return whether current user is logged in and an administrator or not.
		 * @return boolean True if logged in user is an administrator, false otherwise.
		 */
		public static function authorized(){
			// this function is mostly used for flash uploader session bug
			// in short, we do an ajax call to this method while providing and verifying cookies.
			return CmsHost::cms()->is_admin();
		}
	}
	// register ajax/api calls
	Ajax::register('kmrUploader','uploader');
	Ajax::register('kmrUploader','uploaded');
	Ajax::register('kmrUploader','authorized');

?>