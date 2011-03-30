<?php defined('K2F') or die;

	uses('exts/oodb.php','core/security.php','core/cms.php','core/connect.php','libs/swfupload/swfupload.php');
	
	/// SYSTEM LOGIC OBJECTS ///

	class kssProduct extends DatabaseRow {
		/**
		 * @var string product name 
		 */
		public $name = '';
		
		/**
		 * @var float product price 
		 */
		public $price = 0.0;
		
		/**
		 * @var string long description
		 */
		public $description = '';
		
		/**
		 * @var array availale sizes (from kssProducts SIZE_*)
		 */
		public $sizes = array(
			kssProducts::SIZE_XS,
			kssProducts::SIZE_S,
			kssProducts::SIZE_M,
			kssProducts::SIZE_L,
			kssProducts::SIZE_XL,
			kssProducts::SIZE_XXL
		);
		
		/**
		 * @var array product image urls 
		 */
		public $images = array();
		
		/**
		 * @var boolean publish/unpublish 
		 */
		public $published = true;
		
		
		function table(){
			return 'kssProducts';
		}
	}
	
	
	class kssProducts extends DatabaseRows {
		/**
		 * List of available sizes
		 */
		const SIZE_XS = 'XS';
		const SIZE_S = 'S';
		const SIZE_M = 'M';
		const SIZE_L = 'L';
		const SIZE_XL = 'XL';
		const SIZE_XXL = 'XXL';

		function table(){
			return 'kssProducts';
		}
	}
	
	$GLOBALS['kssCurrencies'] = array(
		array('EUR','&euro;','Euro'),
		array('USD','$','US Dollar')
	);

	/// MISC. FUNCTIONS ///

	/**
	 * Shortens the filename to a human-readable format.<br/>
	 * The filename BECOMES UNRECOVERABLE. Do not reuse file name!!
	 * @param string $name Original filename, path or url
	 * @param string $maxlength (Optional) maximum length of filename. Use 0 to disable (default is 10).
	 * @param string $rangetext (Optional) text used to denote replaced text (default to three dots).
	 * @return string The shortened filename.
	 */
	function kssShortFileName($name,$maxlength=10,$rangetext='...'){
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

	class kssUploader {
		/**
		 * Generates an uploader button.
		 * @param integer $id Uploader id.
		 * @param integer $w (Optional) Button width in pixels (defaults to 71).
		 */
		public static function button($id,$w=71){
			$src=Security::snohtml(Ajax::url(__CLASS__,'uploader').'&i=').$id;
			$style='background: url(\''.KSimpleShop::url().'img/upload-btn-'.CFG::get('CMS_HOST').'.gif'.'\') no-repeat left -54px; margin-top: 1px; border: none; vertical-align: middle;';
			?><iframe width="<?php echo (int)$w; ?>" height="18" frameborder="none" scrolling="no" src="<?php echo $src; ?>" style="<?php echo $style; ?>"></iframe><?php
		}
		/**
		 * Generates the HTML for the uploader button (iframe), including the real flash uploader widget.
		 */
		public static function uploader(){
			?><!DOCTYPE HTML><html><head><title></title><style type="text/css">body,html{margin:0;padding:0;}</style></head><body>
			<?php SwfUpload::init(); ?>
			<span id="kss-upload-thumb"><!----></span>
			<script type="text/javascript">
				window.onload=function(){
					try {<?php echo CRLF;
						$url='http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].Ajax::url(__CLASS__,'uploaded');
						$upload=new SwfUpload('swfupload');
						$upload->set_placeholder('"kss-upload-thumb"');
						$_REQUEST['i'] = isset($_REQUEST['i']) ? (int)$_REQUEST['i'] : 0; // error correction and input filtering
						$upload->handle(SwfUpload::EVENT_UPLOAD_START,    'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.kss_thumb_update_start.apply( parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_UPLOAD_PROGRESS, 'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.kss_thumb_update_progrs.apply(parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_UPLOAD_SUCCESS,  'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.kss_thumb_update_finish.apply(parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_DIALOG_END,      'function(){ this.startUpload(); }');
						$upload->set_url(@json_encode($url));
						$upload->set_max_queue(0);
						$upload->set_button_cursor(SwfUpload::CURSOR_HAND);
						$upload->set_button_action(SwfUpload::ACTION_SELECT_FILE);
						$upload->set_button_size(71,18);
						$upload->set_button_sprite(@json_encode(KSimpleShop::url().'img/upload-btn-'.CFG::get('CMS_HOST').'.gif'));
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
						'file'=>kssShortFileName($file->name),
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
	Ajax::register('kssUploader','uploader');
	Ajax::register('kssUploader','uploaded');
	Ajax::register('kssUploader','authorized');

?>