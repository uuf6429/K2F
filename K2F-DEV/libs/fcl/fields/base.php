<?php defined('K2FORMS') or die;

	/**
	 * The base class used to create new field types; they need to extend this class.
	 */
	class Field_Base {
		/**
		 * @var string Used to create unique form element names as well as match with a data object's properties.
		 */
		public $name='Component';
		/**
		 * @var mixed Either default data for this field, or the data loaded from DB (or persistent storage).
		 */
		public $data=null;
		/**
		 * @var mixed Either default settings for this field, or the settings loaded from DB (or persistent storage).
		 */
		public $conf=null;
		/**
		 * Show configuration editor form.
		 */
		public function conf_editor(){ }
		/**
		 * Handle request from configuration editor form.
		 */
		public function conf_handle(){ }
		/**
		 * Show data editor form.
		 */
		public function data_editor(){ }
		/**
		 * Handle request from data editor form.
		 */
		public function data_handle(){ }
		/**
		 * Outputs HTML for current field data.
		 */
		public function display(){ }
		/**
		 * Creates a new field instance.
		 * @param string $id Field name (as used in html).
		 * @param mixed $data Data for the field (or null to not use).
		 * @param mixed $conf Settings for the field (or null to not use).
		 */
		public function __construct($name,$data=null,$conf=null){
			$this->name=$name;
			if($data!==null)$this->data=$data;
			if($conf!==null)$this->conf=$conf;
		}
		/**
		 * Returns a PHP string that can be used to re-construct the instance.
		 * @return string Constructor with name, data and config.
		 */
		final public function export(){
			return 'new '.get_class($this).'('.var_export($this->name,true).','.var_export($this->data,true).','.var_export($this->conf,true).')';
		}
		/**
		 * Save field to database given a row object.
		 * @param DatabaseRow $dbo Database row object.
		 * @return boolean Whether saving was a success or not.
		 * @example $field->save(new MyDatabaseRow());
		 */
		public function save($dbo){
			$dbo->id=$this->id;
			$dbo->data=$this->data;
			$dbo->conf=$this->conf;
			return $dbo->save();
		}
		/**
		 * Load field from database given a row object.
		 * @param DatabaseRow $dbo Database row object.
		 * @return boolean Whether loading was a success or not.
		 * @example $field->load(new MyDatabaseRow(45)); // 45 = row id
		 */
		public function load($dbo){
			$result=$dbo->load();
			$this->id=$dbo->id;
			$this->data=$dbo->data;
			$this->conf=$dbo->conf;
			return $result;
		}
	}

?>