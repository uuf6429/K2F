<?php defined('K2FORMS') or die;

	/**
	 * The base class used to create new form types; they need to extend this class.
	 */
	class Form_Base {
		/**
		 * @var array A list of strings (HTML) and objects (fields) describing the form's contents.
		 */
		protected $data=array();
		/**
		 * @var string Holds the file name that created the form. Initially, it would be the file that defined the class.
		 * @property-read string You should not overwrite this manually, ever.
		 * @internal You should not access this directly, use file() method instead.
		 */
		public $file=__FILE__;
		/**
		 * @var string This is the form's name. Can be used via javascript (form's id and name attributes).
		 * @property-read string You should not overwrite this manually, ever.
		 * @internal You should not access this directly, use name() method instead.
		 */
		public $name='Form';
		/**
		 * Creates a new basic form given a list of fields.
		 * @param array $data An array of field objects.
		 */
		public function __construct($data=array()){
			if(is_array($data))$this->data=$data;
		}
		/**
		 * Returns a PHP string that can be used to re-construct the instance.
		 * @return string Constructor all fields.
		 */
		final public function export(){
			$fields=array();
			foreach($this->data as $item)$fields[]=is_object($item) ? $item->export() : $item;
			return 'new '.get_class($this).'('.var_export($fields).')';
		}
		/**
		 * Renders this form's content directly to output.
		 */
		public function render($action='',$mode='submit',$method='post'){
			$name=Security::snohtml($this->name);
			?><form action="" method="post" name="<?php echo $name; ?>" id="<?php echo $name; ?>"><?php
				foreach($this->data as $item)
					if(is_object($item)){ $item->data_editor(); }else{ echo ''.$item; }
			?></form><?php
		}
		/**
		 * Handles an uploaded form's data.
		 */
		public function handle(){
			foreach($this->data as $item)
				if(is_object($item))$item->data_handle();
		}
	}

?>