<?php defined('K2F') or die;

	uses('exts/oodb.php','core/security.php','core/cms.php','libs/swfupload/swfupload.php');

	/// SYSTEM LOGIC OBJECTS ///

	class kmmCategory extends DatabaseRow {
		/**
		 * @var string Display name of category.
		 */
		public $name='Uncategorized';
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kmm_categories';
		}
	}

	class kmmCategories extends DatabaseRows {
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kmm_categories';
		}
	}

	class kmmItem extends DatabaseRow {
		/**
		 * @var string Class name of media type.
		 */
		public $type='';
		/**
		 * @var integer ID of containing category.
		 */
		public $category=0;
		/**
		 * @var integer Internal object size counter.
		 */
		protected $_size=0;
		/**
		 * @return kmmCategory The loaded category instance.
		 */
		public function category(){
			$cat=new kmmCategory($this->category);
			$cat->load();
			return $cat;
		}
		/**
		 * @return integer Returns the size of data for instance.
		 */
		public function size(){
			return $this->_size;
		}
		/**
		 * Creates new instance, and initilizes properties.
		 * @param integer|stdClass|array $source
		 *   When this is an integer, it is the database row's id.
		 *   When an object or array, it is the source of initial data.
		 * @example
		 * <samp style="color:#000000"><samp style="color:#0000BB"></samp><samp style="color:#007700">class&nbsp;</samp><samp style="color:#0000BB">Person&nbsp;</samp><samp style="color:#007700">extends&nbsp;</samp><samp style="color:#0000BB">DatabaseRow&nbsp;</samp><samp style="color:#007700">{</samp><br>
		 * <samp>&nbsp;&nbsp;public&nbsp;</samp><samp style="color:#0000BB">$name</samp><samp style="color:#007700">=</samp><samp style="color:#DD0000">''</samp><samp style="color:#007700">;</samp><br>
		 * <samp>&nbsp;&nbsp;public&nbsp;</samp><samp style="color:#0000BB">$surname</samp><samp style="color:#007700">=</samp><samp style="color:#DD0000">''</samp><samp style="color:#007700">;</samp><br>
		 * <samp>}</samp><br>
		 * <br>
		 * <samp style="color:#FF8000">//&nbsp;load&nbsp;Person&nbsp;#5&nbsp;from&nbsp;DB.</samp><br>
		 * <samp style="color:#0000BB">$person&nbsp;</samp><samp style="color:#007700">=&nbsp;new&nbsp;</samp><samp style="color:#0000BB">Person</samp><samp style="color:#007700">(</samp><samp style="color:#0000BB">5</samp><samp style="color:#007700">);</samp><br>
		 * <samp style="color:#0000BB">$person</samp><samp style="color:#007700">-&gt;</samp><samp style="color:#0000BB">load</samp><samp style="color:#007700">();</samp><br>
		 * <br>
		 * <samp style="color:#FF8000">//&nbsp;insert&nbsp;a&nbsp;new&nbsp;person&nbsp;in&nbsp;DB.</samp><br>
		 * <samp style="color:#0000BB">$person&nbsp;</samp><samp style="color:#007700">=&nbsp;new&nbsp;</samp><samp style="color:#0000BB">Person</samp><samp style="color:#007700">(&nbsp;array(&nbsp;</samp><samp style="color:#DD0000">'name'</samp><samp style="color:#007700">=&gt;</samp><samp style="color:#DD0000">'John'</samp><samp style="color:#007700">,&nbsp;</samp><samp style="color:#DD0000">'surname'</samp><samp style="color:#007700">=&gt;</samp><samp style="color:#DD0000">'Doe'&nbsp;</samp><samp style="color:#007700">)&nbsp;);</samp><br>
		 * <samp style="color:#0000BB">$person</samp><samp style="color:#007700">-&gt;</samp><samp style="color:#0000BB">save</samp><samp style="color:#007700">();</samp></samp>
		 */
		public function __construct($source = null){
			$this->_size=memory_get_usage();
			parent::__construct($source);
			$this->_size=memory_get_usage()-$this->_size;
		}
		/**
		 * @return kmmType A 'type' instance containing this object, ready for editing/viewing.
		 */
		public function type(){
			$type=new $this->type($this);
			return $type;
		}
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kmm_items';
		}
	}
	
	class kmmItems extends DatabaseRows {
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kmm_items';
		}
	}

	class kmmType {
		/**
		 * @var kmmItem The item object we're managing.
		 */
		public $item=null;
		/**
		 * Construct new type editor instance.
		 * @param kmmType $item The media item to operate on.
		 */
		public function __construct($item){
			$this->item=$item;
		}
		/**
		 * @return string Some text describing this resource.
		 */
		public function text(){
			return 'Generic Type';
		}
		/**
		 * Renders a form's content for editing resource.
		 * @example <code>
		 *            echo '<input type="text" name="test" value="'.Security::snohtml($this->item->data['test']).'"/>';
		 *          </code>
		 */
		public function edit(){
		}
		/**
		 * Handles a submited form's data for saving resource.
		 * @return boolean Whether item was saved successfully or not.
		 * @example <code>
		 *            $this->item->data['test']=$_REQUEST['test'];
		 *            return $this->item->save();
		 *          </code>
		 */
		public function save(){
			return $this->item->save();
		}
	}

	class kmmView {
		public static $name='Abstract KMM View';
		// ...
	}

?>