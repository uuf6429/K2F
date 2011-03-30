<?php defined('K2F') or die;

	uses('exts/oodb.php','core/security.php','core/cms.php','core/connect.php','libs/swfupload/swfupload.php','core/list.php');
	
	/// SYSTEM LOGIC OBJECTS ///

	class kmCategory extends DatabaseRow {
		/**
		 * @var string Display name of category.
		 */
		public $name='Uncategorized';
		/**
		 * @var string URL to image related to category.
		 */
		public $icon='';
		/**
		 * @var string A short description for this category.
		 */
		public $description='';
		/**
		 * @var array List of default fields which are copied to markers when a new marker is created.
		 */
		public $fields=array();
		/**
		 * @var boolean Shows up as ticked by default.
		 */
		public $ticked=false;
		/**
		 * @var boolean Whether to show up or not.
		 */
		public $published=true;
		/**
		 * Gets and/or sets a field's value.
		 * @param string $name The field value to set/get.
		 * @param mixed $value (Optioanl) Used to overwrite field's value with it.
		 * @return mixed The value of the field (if $value is set, the older value is returned).
		 */
		public function field($name,$value='%some^rand&const$val'){
			$flds=NoCaseArray($this->fields);
			$val=$flds->get($name);
			if($value!='%some^rand&const$val')$flds->set($name,$value);
			return $val;
		}
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'km_categories';
		}
	}

	class kmCategories extends DatabaseRows {
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'km_categories';
		}
	}

	class kmMarker extends DatabaseRow {
		/**
		 * @var string Display name of category.
		 */
		public $title='';
		/**
		 * @var string Page alias/slug/name etc for SEO.
		 */
		public $alias='';
		/**
		 * @var string Longitude of marker (string to preserve precission).
		 */
		public $longitude='';
		/**
		 * @var string Latitude of marker (string to preserve precission).
		 */
		public $latitude='';
		/**
		 * @var integer Category id which this mark best relates to.
		 */
		public $cid=0;
		/**
		 * @var array Array of tag names (strings). Must be lowercase and without whitespace.
		 */
		public $tags=array();
		/**
		 * @var string URL of marker thumbnail.
		 */
		public $thumbnail='';
		/**
		 * @var array Array of fields with default values and settings.
		 */
		public $fields=array();
		/**
		 * @var array List of image URLs for marker page.
		 */
		public $images=array();
		/**
		 * @var string Short description.
		 */
		public $desc_short='';
		/**
		 * @var string Long description.
		 */
		public $desc_long='';
		/**
		 * @var boolean Whether to show up or not.
		 */
		public $published=true;
		/**
		 * @var string Marker type (see settings > marker types).
		 */
		public $type='';
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'km_markers';
		}
		/**
		 * Gets and/or sets a field's value.
		 * @param string $name The field value to set/get.
		 * @param mixed $value (Optioanl) Used to overwrite field's value with it.
		 * @return mixed The value of the field (if $value is set, the older value is returned).
		 */
		public function field($name,$value='%some^rand&const$val'){
			$flds=NoCaseArray($this->fields);
			$val=$flds->get($name);
			if($value!='%some^rand&const$val')$flds->set($name,$value);
			return $val;
		}
		/**
		 * Ensures $alias is unique to this object, otherwise returns a slightly modified alias.
		 * @param string $alias User-desired alias name which should be unique.
		 * @return string Either $alias, or a new one if $alias is not unique.
		 */
		public function unique_alias($alias){
			$rplc='-';
			if($alias=='')$alias=$this->title;
			if($alias=='')$alias='Marker #'.$this->id;
			$mks=new kmMarker(); $alias=str_replace(' ',$rplc,Security::filename($alias,$rplc));
			while($mks->load('`alias`="'.Security::escape($alias).'" AND `id`!='.(int)$this->id)){
				$alias=explode('-',$alias);
				$n=$alias[count($alias)-1];
				if($n==''.(int)$n){
					unset($alias[count($alias)-1]);
					$n=(int)$n+1;
				}else $n=1;
				$alias[]=$n;
				$alias=implode($rplc,$alias);
			}
			return $alias;
		}
		/**
		 * Returns category object for this marker.
		 * @return kmCategory Loaded category instance.
		 */
		public function category(){
			$cat=new kmCategory($this->cid);
			$cat->load();
			return $cat;
		}
		/**
		 * Generates a map and map marker for this marker specifically.<br/>
		 * Note: Render assumes you are currently in HTML, not JS.
		 * @param integer $width Map width in pixels.
		 * @param integer $height Map height in pixels.
		 * @param integer $zoom Map zoom level (from 0 to 20).
		 * @return array Array of MAP instance and MARKER instance.
		 */
		public function render($width,$height,$zoom){
			xlog_enable(false);
			$map=new Google_Map('map');
			$map->set_size((int)$width,(int)$height);
			$map->set_location($this->latitude,$this->longitude);
			$map->set_zoom($zoom);
			$map->set_ctrl_maptype(true);
			$map->set_ctrl_pan(true);
			$map->set_ctrl_scale(false);
			$map->set_ctrl_streetview(false);
			$map->set_ctrl_zoom(true);
			$map->render(Google_Map::AS_HTML);
			$marker=$map->add_marker('marker'.$this->id);
			$marker->set_location(
				Security::filename($row->latitude,'',array('.')),
				Security::filename($row->longitude,'',array('.'))
			);
			$marker->set_icon(@json_encode($this->category()->icon));
			$marker->set_visible(true);
			$marker->render(Google_Marker::AS_HTML);
			xlog_revert();
			return array($map,$marker);
		}
	}

	class kmMarkers extends DatabaseRows {
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'km_markers';
		}
	}

	/**
	 * Loads a marker by alias.
	 * @param string|int $alias Alias (aka slug, page name) or marker id.
	 * @return kmMarker|false Marker object or false on failure.
	 */
	function kmLoadMarker($alias){
		$mkr=new kmMarker();
		return $mkr->load(is_integer($alias) ? '`id`='.(int)$alias : '`alias`="'.Security::escape($alias).'"') ? $mkr : false;
	}

	/**
	 * Convert file size from bytes to human-readable/compact format.
	 * @param integer $size Original file size in bytes.
	 * @return string Human-readable size.
	 */
	function kmBytesToHuman($size){
		$type=array('bytes','KB','MB','GB','TB','PB','EB','ZB','YB');
		$i=0;
		while($size>=1024){
			$size/=1024;
			$i++;
		}
		return (ceil($size*100)/100).' '.$type[$i];
	}

	/**
	 * Shortens the filename to a human-readable format.<br/>
	 * The filename BECOMES UNRECOVERABLE. Do not reuse file name!!
	 * @param string $name Original filename, path or url
	 * @param string $maxlength (Optional) maximum length of filename. Use 0 to disable (default is 10).
	 * @param string $rangetext (Optional) text used to denote replaced text (default to three dots).
	 * @return string The shortened filename.
	 */
	function kmShortFileName($name,$maxlength=10,$rangetext='...'){
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

	class kmUploader {
		/**
		 * Generates an uploader button.
		 * @param integer $id Uploader id.
		 * @param integer $w (Optional) Button width in pixels (defaults to 71).
		 */
		public static function button($id,$w=71){
			$src=Security::snohtml(Ajax::url(__CLASS__,'uploader').'&i=').$id;
			$style='background: url(\''.KeenMaps::url().'img/upload-btn-'.CFG::get('CMS_HOST').'.gif'.'\') no-repeat left -54px; margin-top: 1px; border: none; vertical-align: middle;';
			?><iframe width="<?php echo (int)$w; ?>" height="18" frameborder="none" scrolling="no" src="<?php echo $src; ?>" style="<?php echo $style; ?>"></iframe><?php
		}
		/**
		 * Generates the HTML for the uploader button (iframe), including the real flash uploader widget.
		 */
		public static function uploader(){
			?><!DOCTYPE HTML><html><head><title></title><style type="text/css">body,html{margin:0;padding:0;}</style></head><body>
			<?php SwfUpload::init(); ?>
			<span id="km-upload-thumb"><!----></span>
			<script type="text/javascript">
				window.onload=function(){
					try {<?php echo CRLF;
						$url='http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].Ajax::url(__CLASS__,'uploaded');
						$upload=new SwfUpload('swfupload');
						$upload->set_placeholder('"km-upload-thumb"');
						$_REQUEST['i'] = isset($_REQUEST['i']) ? (int)$_REQUEST['i'] : 0; // error correction and input filtering
						$upload->handle(SwfUpload::EVENT_UPLOAD_START,    'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.km_thumb_update_start.apply( parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_UPLOAD_PROGRESS, 'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.km_thumb_update_progrs.apply(parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_UPLOAD_SUCCESS,  'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.km_thumb_update_finish.apply(parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_DIALOG_END,      'function(){ this.startUpload(); }');
						$upload->set_url(@json_encode($url));
						$upload->set_max_queue(0);
						$upload->set_button_cursor(SwfUpload::CURSOR_HAND);
						$upload->set_button_action(SwfUpload::ACTION_SELECT_FILE);
						$upload->set_button_size(71,18);
						$upload->set_button_sprite(@json_encode(KeenMaps::url().'img/upload-btn-'.CFG::get('CMS_HOST').'.gif'));
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
						'file'=>kmShortFileName($file->name),
						'size'=>'('.kmBytesToHuman(strlen($file->data)).')'
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
	Ajax::register('kmUploader','uploader');
	Ajax::register('kmUploader','uploaded');
	Ajax::register('kmUploader','authorized');

?>