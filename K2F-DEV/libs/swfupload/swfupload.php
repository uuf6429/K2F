<?php defined('K2F') or die;

	uses('core/events.php','exts/jswidget.php');

	/**
	 * A class to ease interaction with SWFUpload API.
	 *
	 * http://demo.swfupload.org/Documentation/
	 */
	class SwfUpload extends jsWidget {

		/// STATIC FUNCTIONALITY AND CONFIG ///

		private static $INITTED=false;
		public static function init(){
			if(!self::$INITTED){
				echo '<script type="text/javascript" src="'.Security::snohtml(CFG::get('REL_K2F')).'libs/swfupload/swfupload.js"></script>';
				echo '<script type="text/javascript">window.ksu={};</script>';
				self::$INITTED=true;
			}
		}
		
		/**
		 * The swfUploadLoaded event is fired by flashReady. It is settable. swfUploadLoaded is called to let you know that it is safe to call SWFUpload methods.
		 */
		const EVENT_LOADED             = 'swfupload_loaded_handler';
		/**
		 * fileDialogStart is fired after selectFile for selectFiles is called. This event is fired immediately before the File Selection Dialog window is displayed. However, the event may not execute until after the Dialog window is closed.
		 */
		const EVENT_DIALOG_START       = 'file_dialog_start_handler';
		/**
		 * The fileQueued event is fired for each file that is queued after the File Selection Dialog window is closed.
		 */
		const EVENT_QUEUE_NEXT         = 'file_queued_handler';
		/**
		 * The fileQueueError event is fired for each file that was not queued after the File Selection Dialog window is closed. A file may not be queued for several reasons such as, the file exceeds the file size, the file is empty or a file or queue limit has been exceeded.
		 * The reason for the queue error is specified by the error code parameter. The error code corresponds to a SWFUpload.QUEUE_ERROR constant.
		 */
		const EVENT_QUEUE_ERROR        = 'file_queue_error_handler';
		/**
		 * The fileDialogComplete event fires after the File Selection Dialog window has been closed and all the selected files have been processed. The 'number of files queued' argument indicates the number of files that were queued from the dialog selection (as opposed to the number of files in the queue).
		 * If you want file uploading to begin automatically this is a good place to call 'this.startUpload()'.
		 */
		const EVENT_DIALOG_END         = 'file_dialog_complete_handler';
		/**
		 * uploadStart is called immediately before the file is uploaded. This event provides an opportunity to perform any last minute validation, add post params or do any other work before the file is uploaded.
		 * The upload can be cancelled by returning 'false' from uploadStart. If you return 'true' or do not return any value then the upload proceeds. Returning 'false' will cause an uploadError event to fired.
		 */
		const EVENT_UPLOAD_START       = 'upload_start_handler';
		/**
		 * The uploadProgress event is fired periodically by the Flash Control. This event is useful for providing UI updates on the page.
		 * Note: The Linux Flash Player fires a single uploadProgress event after the entire file has been uploaded. This is a bug in the Linux Flash Player that we cannot work around.
		 */
		const EVENT_UPLOAD_PROGRESS    = 'upload_progress_handler';
		/**
		 * The uploadError event is fired any time an upload is interrupted or does not complete successfully. The error code parameter indicates the type of error that occurred. The error code parameter specifies a constant in SWFUpload.UPLOAD_ERROR.
		 * Stopping, Cancelling or returning 'false' from uploadStart will cause uploadError to fire. Upload error will not fire for files that are cancelled but still waiting in the queue.
		 */
		const EVENT_UPLOAD_ERROR       = 'upload_error_handler';
		/**
		 * uploadSuccess is fired when the entire upload has been transmitted and the server returns a HTTP 200 status code. Any data outputted by the server is available in the server data parameter.
		 * Due to some bugs in the Flash Player the server response may not be acknowledged and no uploadSuccess event is fired by Flash. In this case the assume_success_timeout setting is checked to see if enough time has passed to fire uploadSuccess anyway. In this case the received response parameter will be false.
		 * The http_success setting allows uploadSuccess to be fired for HTTP status codes other than 200. In this case no server data is available from the Flash Player.
		 * At this point the upload is not yet complete. Another upload cannot be started from uploadSuccess.
		 */
		const EVENT_UPLOAD_SUCCESS     = 'upload_success_handler';
		/**
		 * uploadComplete is always fired at the end of an upload cycle (after uploadError or uploadSuccess). At this point the upload is complete and another upload can be started.
		 * If you want the next upload to start automatically this is a good place to call this.uploadStart(). Use caution when calling uploadStart inside the uploadComplete event if you also have code that cancels all the uploads in a queue.
		 */
		const EVENT_UPLOAD_COMPLETE    = 'upload_complete_handler';
		/**
		 * Debug handler. Not documented.
		 */
		const EVENT_DEBUG              = 'debug_handler';

		/**
		 * When the Flash button is clicked the file dialog will only allow a single file to be selected.
		 */
		const ACTION_SELECT_FILE       = 'SWFUpload.BUTTON_ACTION.SELECT_FILE';
		/**
		 * When the Flash button is clicked the file dialog allows multiple files to be selected.
		 */
		const ACTION_SELECT_FILES      = 'SWFUpload.BUTTON_ACTION.SELECT_FILES';
		/**
		 * While the Flash button is clicked the first queued file will be uploaded.
		 */
		const ACTION_START_UPLOAD      = 'SWFUpload.BUTTON_ACTION.START_UPLOAD';

		/**
		 * The cursor will be displayed as an arrow pointer.
		 */
		const CURSOR_ARROW             = 'SWFUpload.CURSOR.ARROW';
		/**
		 * The cursor will be displayed as a finger/hand pointer.
		 */
		const CURSOR_HAND              = 'SWFUpload.CURSOR.HAND';

		/**
		 * @var array Array of options.
		 */
		protected $options=array();

		/// PUBLIC FUNCTIONS ///

		/**
		 * Constructs a new swf upload instance.
		 * @param string $name SWFUpload name/id - ensure it is unique.
		 */
		public function __construct($name){
			self::init();
			$this->name=$name;
		}
		/**
		 * Set the url that will be recieving uploads.
		 * @param string $url Javascript code which evaluates to an upload url.
		 */
		public function set_url($url){
			$this->options['upload_url']=$url;
		}
		/**
		 * Set acceptable file types.
		 * @param string $types Some JS code that evaluates a string.
		 * @example '"*.jpg;*.gif"'
		 */
		public function set_types($types){
			$this->options['file_types']=$types;
		}
		/**
		 * Set files description in dialog box.
		 * @param string $desc Some JS code that evaluates a string.
		 * @example '"Web Image Files"'
		 */
		public function set_description($desc){
			$this->options['file_types_description']=$desc;
		}
		/**
		 * Set upload file maximum size.
		 * @param string|integer $size Some JS code that evaluates to an integer.
		 * @example '1024'
		 */
		public function set_max_size($size){
			$this->options['file_size_limit']=''.$size;
		}
		/**
		 * Set button action.
		 * @param string $action Value of any ACTION_* constant.
		 */
		public function set_button_action($action){
			$this->options['button_action']=$action;
		}
		/**
		 * Set button cursor.
		 * @param string $cursor Value of any CURSOR_* constant.
		 */
		public function set_button_cursor($cursor){
			$this->options['button_cursor']=$cursor;
		}
		/**
		 * Set button size.
		 * @param integer $width JS code that must evaluate to an integer.
		 * @param integer $height JS code that must evaluate to an integer.
		 */
		public function set_button_size($width,$height){
			$this->options['button_width']=$width;
			$this->options['button_height']=$height;
		}
		/**
		 * Set URL to an image used as button's different states.
		 * <br><b>Important:</b> Sprite height must be 4 times the height of the button:
		 * States (top/left to bottom/right): normal, hover, clicked, disabled
		 * @param string $url JS code which must evaluate to a URL.
		 */
		public function set_button_sprite($url){
			$this->options['button_image_url']=$url;
		}
		/**
		 * Set ID of element to be used as a placeholder, ie, will be replaced with upload button.
		 * @param string $id JS code which must evaluate to element ID.
		 */
		public function set_placeholder($id){
			$this->options['button_placeholder_id']=$id;
		}
		/**
		 * Set maximum amount of queued files until widget starts uploading.
		 * <br/>If 0, the file is uploaded directly.
		 * @param string|integer $count JS code which must evaluate to a number.
		 */
		public function set_max_queue($count){
			$this->options['file_queue_limit']=$count;
		}
		/**
		 * Sets the post data to be submitted with the upload.
		 * @param string $post JS code which must evaluate to an object of POST key=>value pairs.
		 */
		public function set_post($post){
			$this->options['post_params']=$post;
		}

		/// RENDER FUNCTION ///

		/**
		 * Send map code directly to browser.
		 * @param string $mode (Optional) The output mode. See jsWidget::AS_* constants.
		 * @param array $raw_options (Optional) Used to set raw widget options (overrides existing options).
		 */
		public function render($mode=self::AS_HTML,$raw_options=null){
			// options and initial events
			$options=$this->options;
			$options['flash_url']=@json_encode(CFG::get('REL_K2F').'libs/swfupload/swfupload.swf');
			foreach($this->events as $event=>$handler)$options[$event]=$handler;
			// this is kinda stupid, if people don't want it transparent, they just fill it up;
			// transparency is a feature and it doesn't make sense to allow devs to turn it off
			$options['button_window_mode']='SWFUpload.WINDOW_MODE.TRANSPARENT';
			if($raw_options)foreach($raw_options as $k=>$v)$options[$k]=$v;
			// render widget
			if($mode==self::AS_HTML){ ?><script type="text/javascript"><?php } ?>
			var swfu = new SWFUpload(<?php echo self::a2o($options); ?>);
			<?php if($mode==self::AS_HTML){ ?></script><?php }
			// done!
			$this->rendered=true;
		}
		/**
		 * Returns an array of uploaded files.
		 * @return array|SwfUploadFile The uploaded files. IMPORTANT: A file might have failed, check ->failed() before using object.
		 */
		public static function uploaded(){
			header('HTTP/1.0 200 OK',true,200); // Status 200 hotfix
			$files=array();
			if(isset($_FILES['Filedata']) && isset($_FILES['Filedata']['name'])){
				if(is_array($_FILES['Filedata']['name'])){
					// list of files
					foreach($_FILES['Filedata']['name'] as $index=>$unused)
						$files[]=new SwfUploadFile('Filedata',$index);
				}else{
					// single file
					$files[]=new SwfUploadFile('Filedata');
				}
			}
			return $files;
		}
	}


	class SwfUploadFile {
		/**
		 * @var string Original file name.
		 */
		public $name='';
		/**
		 * @var string File contents.
		 */
		public $data='';
		/**
		 * @var integer Errors associated with upload.
		 */
		public $error=UPLOAD_ERR_OK;
		/**
		 * Size of (uncompressed) file.
		 * @return integer File size. WARNING: may give wrong results on 32bit platforms.
		 */
		public function length(){
			return strlen($data);
		}
		/**
		 * Constructs a new instance.
		 * @param string $name (Optional) Name of input box element.
		 * @param integer $index (Optional) In case of multiple uploads of same name, use this to select which one.
		 */
		public function __construct($name=null,$index=null){
			if($name!==null){
				if($index!==null){ // by index
					$this->name=$_FILES[$name]['name'][$index];
					if($_FILES[$name]['tmp_name'][$index]!='')
						$this->data=file_get_contents($_FILES[$name]['tmp_name'][$index]);
					$this->error=$_FILES[$name]['error'][$index];
				}else{ // the file
					$this->name=$_FILES[$name]['name'];
					if($_FILES[$name]['tmp_name']!='')
						$this->data=file_get_contents($_FILES[$name]['tmp_name']);
					$this->error=$_FILES[$name]['error'];
				}
			}
		}
		/**
		 * Returns some human readable text on why upload failed (if at all).
		 * @return string Reason text.
		 */
		public function reason(){
			switch($this->error){
				case UPLOAD_ERR_OK         : return 'Upload success.';
				case UPLOAD_ERR_INI_SIZE   : return 'File too big (server restriction).';
				case UPLOAD_ERR_FORM_SIZE  : return 'File too big (client restriction).';
				case UPLOAD_ERR_PARTIAL    : return 'Connection closed before completion.';
				case UPLOAD_ERR_NO_FILE    : return 'No file was actually uploaded.'; // what the??!?
				case UPLOAD_ERR_NO_TMP_DIR : return 'Nowhere to store file temporarily.';
				case UPLOAD_ERR_CANT_WRITE : return 'Could not write file to disk.';
				case UPLOAD_ERR_EXTENSION  : return 'An extension halted file upload.';
				default                    : return 'Unknown upload failure.';
			}
		}
		/**
		 * Returns whether uploaded failed or not.
		 * @return boolean True if failed, false otherwise.
		 */
		public function failed(){
			return $this->error!=UPLOAD_ERR_OK;
		}
	}

?>