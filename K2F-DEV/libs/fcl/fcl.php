<?php defined('K2F') or die;

	/**
	 * A set of base class for creating and handling managed forms.
	 * @copyright 2010-2011 Covac Software
	 * @author Christian Sciberras
	 * @version 21/12/2010 - Initial design.
	 */

	uses('core/debug.php','core/classutils.php');

	
	/**
	 * This is used by form files to ensure they are loaded from a K2F Forms
	 * environment.
	 */
	define('K2FORMS',true);

	/**
	 * This class should be used to wrap all forms. It is mainly used to provide
	 * a specific set of functionality to the end form developer. Such
	 * functionality includes: chaining, and core methods.
	 */
	class FCL_Class implements Debugable {
		
		/// (STATIC) CLASS FUNCTIONALITY ///
		
		/**
		 * @var boolean Serves as a dual purpose: To read the current state and to allow (in extreme cases) to force a reload.
		 */
		public static $initialized=false;
		/**
		 * @var array List of form class names.
		 */
		public static $forms=array();
		/**
		 * @var array List of field class names.
		 */
		public static $fields=array();
		/**
		 * Loads FCL system by finding and loading form and field files.
		 */
		public static function initialize(){
			if(!self::$initialized){
				// load forms
				foreach(glob(CFG::get('ABS_K2F').'libs/fcl/forms/*.php') as $file)
					if(!(include_once($file)))
						xlog('Error: Fatal error in loading form file "'.$file.'".');
				self::$forms=get_class_grandchildren('Form_Base');
				// load fields
				foreach(glob(CFG::get('ABS_K2F').'libs/fcl/fields/*.php') as $file)
					if(!(include_once($file)))
						xlog('Error: Fatal error in loading field file "'.$file.'".');
				self::$fields=get_class_grandchildren('Field_Base');
				// initialization success
				self::$initialized=true;
			}
		}

		/// (NON-STATIC) INSTANCE FUNCTIONALITY ///

		/**
		 * @var Form_Base The loaded form object which we're wrapping.
		 */
		protected $form=null;
		/**
		 * Loads a new form file or data from a form object.
		 * @param Form_Base|string $source Either a file name (string) or form object fields (object).
		 * @param string $name Form name and id.
		 */
		public function __construct($source,$name=''){
			// if string, load from file, set details and overwrite source
			if(is_string($source)){
				if(!$data=(include_once $source))
					xlog('Warning: Failed loading form file at "'.$source.'"!');
				if(is_object($data)){
					$data->name=$name;
					$data->file=$source;
					$source=&$data;
				}
			}
			// if source is an object, asume it is a form
			if(is_object($source)){
				$this->form=&$source;
				$this->form->name=$name;
			}
		}
		/**
		 * Renders this form's content directly to output.
		 * @param string $action The target URL the form will be sent to.
		 * @param string $mode (Optional) Sets the way the form is submitted. Possible values are "submit" or "ajax" (default is "submit").
		 * @param string $method (Optional) Sets the method for sending the form. Possible values are "get" or "post" (default is "post").
		 * @return FCL_Class This same object, used for chaining.
		 */
		public function render($action='',$mode='submit',$method='post'){
			$this->form->render($action='',$mode='submit',$method='post');
			return $this; // for chaining
		}
		/**
		 * Handles an uploaded form's data.
		 * @return FCL_Class This same object, used for chaining.
		 */
		public function handle(){
			$this->form->handle();
			return $this; // for chaining
		}
		/**
		 * Returns the form's name. This is readonly (no setter) since the form cannot be renamed after rendering.
		 * @return string The form's name. Can be used to selectively access form markup (used in both form name and id).
		 */
		public function get_name(){
			return $this->form->name;
		}
		/**
		 * Returns the file this form was loaded from.
		 * @return string The absolute file name.
		 */
		public function get_file(){
			return $this->form->file;
		}
		/**
		 * Returns the form type (class name), used to distinguish between form types.
		 * @return string The form's class name (form classes start with Form_*).
		 */
		public function get_type(){
			return get_class($this->form);
		}
		/**
		 * Event handler for debug call.
		 * @return array Debug information (fields).
		 */
		public static function onDebug(){
			return array('Forms'=>self::$forms,'Fields'=>self::$fields);
		}
	}

	/**
	 * This function servers a dual purpose depending on the data type of $form:
	 * - string: Shorthand function for FCL_Class constructor which allows us to do chaining.
	 * - object: Allows us to perform PHPDoc(fake) typecasting on an object.
	 * @param string|object $form Either the filename holding the form (string) or a form instance (object).
	 * @return FCL_Class An FCL form instance.
	 */
	function FCL($form){
		return is_object($form) ? $form : new FCL_Class($form);
	}

	/**
	 * Similar to K2F's uses(), this loads the given set of FCL files.
	 * @example <code>
	 *     fcl_uses('fields/myfield.php');
	 *
	 *     class MyNewField extends MyField {
	 *         // ...
	 *     }
	 * </code>
	 */
	function fcl_uses(){
		foreach(func_get_args() as $file)
			include_once CFG::get('ABS_K2F').'libs/fcl/'.$file;
	}

	/**
	 * Initialize FCL files by loading FCL files and defining and populating data fields.
	 */
	FCL_Class::initialize();

?>