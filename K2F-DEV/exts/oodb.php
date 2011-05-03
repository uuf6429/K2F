<?php defined('K2F') or die;

	uses('core/security.php','core/database.php');

	$GLOBALS['K2F-OODB-COLCACHE']=array();
	$GLOBALS['K2F-OODB-TBLCACHE']=null;

	/**
	 * This is a system of classes and objects which helps in easily implementing management of rows.<br>
	 * A typical use of this system would be in a multi-table MVC  framework.<br>
	 * <b>NB:</b> In order to use this system, you must make your database-related objects extend the following classes.<br>
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 05/09/2010 - Original implementation code.
	 *          16/09/2010 - Now handles array/object properties directly by serialization (json).
	 *          01/11/2010 - Able to handle multiple rows (DatabaseRows).
	 *          09/11/2010 - Creates non-existent columns and tables.
	 *          10/11/2010 - Fixed issue with object/array variables not being serialized from DatabaseRows.
	 *          14/11/2010 - Minor fix in constructor of DatabaseRows; it now creates table if it doesn't exist.
	 *          21/11/2010 - Added an optional $condition argument to DatabaseRow->load().
	 *          21/11/2010 - Fixed bug in DatabaseRow->load(), it should have been returning success status.
	 *          29/11/2010 - Added ->count() to DatabaseRows, which returns the number of loaded rows.
	 *          30/11/2010 - Added lines 125-126 to check if row exists or not (so that we insert non-existent rows).
	 *          17/01/2011 - Hotfix with rows..row classname relation (now works with singularize() method).
	 *          03/02/2011 - Boolean value translator hotfix in ->load() and ->save().
	 *          07/02/2011 - Issue similar to /03/02/2011, discovered by CvcWiki.
	 *          16/02/2011 - Added property loaded to DatabaseRows, used internally to tweak performance.
	 *          16/02/2011 - Using the afformentioned property and a new API call, DatabaseRows->count() works faster.
	 *          21/02/2011 - Columns/properties starting with a single underscore (eg: $_internal) are not saved/loaded.
	 *          22/02/2011 - Fixed DatabaseRows->count(); missed argument 1 (table) of Database->rows_count().
	 *          22/02/2011 - Fixed DatabaseRows->load(); missed setting $this->loaded flag.
	 *          25/04/2011 - Replaced most (array)$obj typecasts with get_object_vars($obj) function due to "\0*\0" property bug.
	 *          00/00/T0D0 - May work with variable named unique column (used to be "id").
	 */
	class DatabaseRow {
		/**
		 * @var integer Database row id. If 0 (or less), it means this is a new row.
		 */
		public $id=0;
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
		public function  __construct($source=null){
			// ensure table exists
			if(!$GLOBALS['K2F-OODB-TBLCACHE'])
				$GLOBALS['K2F-OODB-TBLCACHE']=Database::db()->table_all();
			if(!in_array($this->table(),$GLOBALS['K2F-OODB-TBLCACHE']))
				if(Database::db()->table_create($this->table()))
					$GLOBALS['K2F-OODB-TBLCACHE'][]=$this->table();
			// ensure columns exist in db tables
			$cols=isset($GLOBALS['K2F-OODB-COLCACHE'][$this->table()])
				? $GLOBALS['K2F-OODB-COLCACHE'][$this->table()]
				: $GLOBALS['K2F-OODB-COLCACHE'][$this->table()]=Database::db()->cols_all($this->table());
			foreach(get_object_vars($this) as $prop=>$val)
				if($prop{0}!='_' && !isset($cols[$prop])){
					$type='text'; // anything can be serialized to text!
					if(is_int($val))$type='int';
					if(is_float($val))$type='float';
					if(is_bool($val))$type='bit'; // should be "bool" or "boolean", but "bit" is more supported(?)
					Database::db()->cols_add($this->table(),$prop,$type,$prop=='id' ? array('autoincrement','primary','unique') : array());
				}
			// load data from source
			if(is_integer($source) || is_string($source))
				$this->id=$source;
			// load from object source
			if(is_object($source) || is_array($source))
				foreach(get_object_vars($source) as $prop=>$value)
					if($prop{0}!='_'){
						if(isset($this->$prop)){
							if(is_array($this->$prop)){
								$this->$prop=(array)@json_decode($value);
							}elseif(is_object($this->$prop)){
								$this->$prop=(object)@json_decode($value);
							}elseif(is_integer($this->$prop)){
								$this->$prop=(int)$value;
							}elseif(is_float($this->$prop)){
								$this->$prop=(float)$value;
							}elseif(is_bool($this->$prop)){
								$this->$prop=($value!==null && $value!==chr(0) && $value!=='0' && $value!==0);
							}else $this->$prop=$value; // cannot determine data type...
						}else $this->$prop=$value; // no such property in subclass...
					}
		}
		/**
		 * Loads object properties from database.
		 * @param string $condition (Optional) the condition used to load the object.
		 * @return boolean True on success, false otherwise.
		 */
		public function load($condition=''){
			if($condition=='')$condition='id='.(int)$this->id;
			if($this->id!==null){
				$rows=Database::db()->rows_load($this->table(),$condition);
				if($rows && isset($rows[0])){
					$row=$rows[0];
					foreach(get_object_vars($row) as $prop=>$value)
						if($prop{0}!='_'){
							if(isset($this->$prop)){
								if(is_array($this->$prop)){
									$this->$prop=(array)@json_decode($value);
								}elseif(is_object($this->$prop)){
									$this->$prop=(object)@json_decode($value);
								}elseif(is_integer($this->$prop)){
									$this->$prop=(int)$value;
								}elseif(is_float($this->$prop)){
									$this->$prop=(float)$value;
								}elseif(is_bool($this->$prop)){
									$this->$prop=($value!==null && $value!==chr(0) && $value!=='0' && $value!==0);
								}else $this->$prop=$value; // cannot determine data type...
							}else $this->$prop=$value; // no such property in subclass...
						}
					return true;
				}
				return false;
			}
		}
		/**
		 * Saves object to database (inserting or updating as required).
		 * @return boolean True on success, false otherwise.
		 */
		public function save(){
			// clone object, serialize any non-scalar values and remove private properties
			$save=clone($this);
			foreach(get_object_vars($save) as $p=>$v){
				if(!is_scalar($v))
					$save->$p=@json_encode($v);
				if($p{0}=='_')
					unset($save->$p);
			}
			// check if row exists or not
			Database::db()->rows_load($this->table(),'id='.(int)$this->id);
			$exists=Database::db()->rows_affected()>0;
			// commit to db
			if($save->id<1 || $save->id===null || !$exists){
				// insert
				$result=Database::db()->rows_insert($this->table(),$save);
				if(is_array($result))$result=isset($result[0]) ? $result[0] : 0;
				if($result>0)$this->id=$result;
				$result=$result>0;
			}else{
				// update
				$result=Database::db()->rows_update($this->table(),$save,'id');
			}
			return $result;
		}
		/**
		 * Deletes object from database.
		 * @return boolean True on success, false otherwise.
		 */
		public function delete(){
			if($this->id>0){
				// return $this properties' to original state (except id)
				$tmp=__CLASS__; $tmp=new $tmp();
				foreach(get_object_vars($tmp) as $k=>$v)
					if($k!='id')$this->$k=$v;
				// delete from db
				return Database::db()->rows_delete($this->table(),$this,'id');
			}
			return false;
		}
		/**
		 * You MUST override this in your class!
		 * It is used to return the table name where your class is holding the data.
		 * @return string Table name.
		 */
		public function table(){
			return '';
		}
	}

	class DatabaseRows {
		/**
		 * @var array Holds array of loaded rows.
		 */
		public $rows=array();
		/**
		 * @var boolean Holds whether stuff was loaded or not.
		 */
		protected $loaded=false;
		/**
		 * Utility function to singularize a plural word.
		 * @param string $word The original word in plural form.
		 * @return string The singular form of $word.
		 */
		protected static function singularize($word){
			$rules=array('ss'=>false,'os'=>'o','ies'=>'y','xes'=>'x','oes'=>'o','ies'=>'y','ves'=>'f','ches'=>'ch','s'=>'');
			foreach($rules as $key=>$new){
				if(substr($word,-strlen($key))!=$key)continue;
				if($key===false)return $word;
				return substr($word,0,strlen($word)-strlen($key)).$new;
			}
			return $word;
		}
		/**
		 * Loads a set of objects from the database.
		 * @param string $condition A simple SQL expression used to specify which objects to load.<br>
		 *                          <b>NB:</b> Be sure not to use anything specific to any database system!
		 * @param string $class The class name the newly loaded objects are typcasted to. It works as following:
		 * <br> - if $class is set and descends from DatabaseRow, it is used
		 * <br> - if $class is not set, it attempts to find __CLASS__ but without the final 's' (dbos->dbo)
		 *        and if that class exists and descends from DatabaseRow, it is used
		 * <br> - finally, if no such class could be found a fatal error is thrown*
		 * <br><br>*It cannot silently default to DatabaseRow since the table is not known.
		 * @return integer The number of loaded rows.
		*/
		public function load($condition='1',$class=null){
			// flip flag
			$this->loaded=true;
			// ensure table exists
			if(!$GLOBALS['K2F-OODB-TBLCACHE'])
				$GLOBALS['K2F-OODB-TBLCACHE']=Database::db()->table_all();
			if(!in_array($this->table(),$GLOBALS['K2F-OODB-TBLCACHE']))
				if(Database::db()->table_create($this->table()))
					$GLOBALS['K2F-OODB-TBLCACHE'][]=$this->table();
			// empty current list of objects
			$this->rows=array();
			// if class is empty, use depluralized class name of $this
			if(!$class)$class=self::singularize(get_class($this));
			if(!class_exists($class))$class='DatabaseRow';
			$p=class_parents($class);
			// ensure the class descends from DatabaseRow
			if(!in_array('DatabaseRow',$p) || $class=='DatabaseRow')
				xlog('Error: You should have a class named "'.substr(get_class($this),0,-1).'", but it could not be found.');
			// load each row, throw it into custom class and finally add it the $this->rows
			foreach(Database::db()->rows_load($this->table(),$condition) as $row)
				$this->rows[]=new $class($row);
			// return the number of loaded rows
			return count($this->rows);
		}
		/**
		 * Saves all current objects to database.
		 * @return array A list of boolean values for each row specifying if it was saved.
		 */
		public function save(){
			if(!$this->loaded)return false;
			$res=array();
			foreach($this->rows as $row)
				$res[]=DatabaseRow($row)->save();
			return $res;
		}
		/**
		 * Deletes all current objects to database.
		 * @return array A list of boolean values for each row specifying if it was deleted.
		 */
		public function delete(){
			if(!$this->loaded)return false;
			$res=array();
			foreach($this->rows as $row)
				$res[]=DatabaseRow($row)->delete();
			return $res;
		}
		/**
		 * You <b>MUST</b> override this in your class!
		 * It is used to return the table name where your class is holding the data.
		 * @return string Table name.
		 */
		public function table(){
			return '';
		}
		/**
		 * This is actually quite good with performance; if items weren't loaded, we just sql-count, without loading!
		 * @return integer The number of loaded items.
		 */
		public function count(){
			return $this->loaded ? count($this->rows) : Database::db()->rows_count($this->table());
		}
	}

	/**
	 * Converts a normal object to a DatabaseRow. (phpdoc hack)
	 * @param object $object Original object.
	 * @return DatabaseRow Typecasted object (not really...).
	 */
	function DatabaseRow($object){
		return $object;
	}

	/**
	 * Converts a normal object to a DatabaseRows. (phpdoc hack)
	 * @param object $object Original object.
	 * @return DatabaseRows Typecasted object (not really...).
	 */
	function DatabaseRows($object){
		return $object;
	}
	
?>