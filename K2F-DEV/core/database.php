<?php defined('K2F') or die;

	uses('core/security.php');

	/**
	 * A class which abstracts database management.<br>
	 * <b>NB:</b> In order to use your own DB interface (let's name it myDb):<br>
	 *   <b>1.</b> Create file name database.myDb.php in ext folder.<br>
	 *   <b>2.</b> Create a class in that file named Database_myDb.<br>
	 *   <b>3.</b> Make sure your class extends 'Database_Base' and that you override all non-static class methods.<br>
	 *   <b>4.</b> Modify the value of DB_TYPE in config.php and set it to 'myDb' (defaults to 'mysql').<br>
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 01/06/2010
	 */
	class Database {
		/**
		 * @var Database_Base The database object to issue calls to.
		 */
		private static $db=null;
		/**
		 * Initializes database system.<br>
		 * <b>Important:</b> Do not call this directly yourself!
		 */
		public static function _init(){
			// load database interface class
			uses('exts/database.'.CFG::get('DB_TYPE').'.php');
			// create datbase interface object, store it and attempt server connection
			$db='Database_'.CFG::get('DB_TYPE');
			if(class_exists($db)){
				self::$db=new $db();
				if(!self::db()->login(CFG::get('DB_HOST'),CFG::get('DB_USER'),CFG::get('DB_PASS'))){
					xlog('Error: Could not connect to database.',self::db()->error_id(),self::db()->error_msg());
				}else{
					$name=CFG::get('DB_NAME'); $create=false;
					if( !self::db()->database_select($name) && !($create=(self::db()->database_create($name) && self::db()->database_select($name))) ){
						xlog('Error: Could not select or create database "'.Security::snohtml(CFG::get('DB_NAME')).'".',self::db()->error_id(),self::db()->error_msg());
					}else
						$create ? xlog('Warning: Database not found, recreated successfully.') : xlog('Database initialization success.');
				}
			} else xlog('Error: Unsupported database type "'.Security::snohtml(CFG::get('DB_TYPE')).'".');
		}
		/**
		 * Finalizes database system.<br>
		 * <b>Important:</b> Do not call this directly yourself!
		 */
		public static function _fini(){
			// disconnect from server
			if(!self::db()->logout())xlog('Error: Could not disconnect from database.',self::db()->error_id(),self::db()->error_msg());
		}

		/**
		 * Returns currently opened database.
		 * @return Database_Base Database manager.
		 */
		public static function db(){
			return self::$db;
		}
	}

	/**
	 * Base abstract class for different databases.
	 */
	class Database_Base {

		/// UNUSED FUNCTIONALITY AND MISC/CONFIG STORAGE ///
		
		/**
		 * @var string The last query/sql code.
		 */
		protected $last_query='';
		/**
		 * @var resource The last query's result.
		 */
		protected $last_result=null;
		/**
		 * @var string Name of the current database.
		 */
		protected $last_dbname='';
		/**
		 * Class instance constructor.
		 */
		public function __construct(){}
		/**
		 * Class instance destructor.
		 */
		public function __destruct(){}

		/// DATABASE SERVER COMMUNICATION AND AUTHENTICATION ///
		
		/**
		 * Attempts logging into database server.
		 * @param string $host Server host (domain/ip).
		 * @param string $user Database username.
		 * @param string $pass Database password.
		 * @return boolean Whether successful or not.
		 */
		public function login($host,$user,$pass){ return false; }
		/**
		 * Attempts logout from database server.
		 * @return boolean Whether successful or not.
		 */
		public function logout(){ return false; }
		
		/// DEPRECATED FUNCTIONS ///
		
		/**
		 * Runs a database type-dependent (raw) query.
		 * @param string $query The sql query to run.
		 * @return boolean Whether it was successful or not.
		 */
		public function raw_query($query){
			xlog('Warnining: You should not run raw queries since they might not be portable.',$query,debug_backtrace());
			$this->last_query=$query;
			$this->last_result=null;
			return $this->last_result ? true : false;
		}

		/// DATABASE MANAGEMENT ///

		/**
		 * Creates a new database.
		 * @param string $database The name of target database to create.
		 * @return boolean If true, the database was created successfuly.
		 */
		public function database_create($database){ return false; }
		/**
		 * Returns an array of database names (string).
		 * @return array|null An array of databases or null on error.
		 */
		public function database_all(){ return null; }
		/**
		 * Returns whether the specified database exists or not.
		 * @param string $database The name of target database to check.
		 * @return boolean Database's existence.
		 */
		public function database_exists($database){ return false; }
		/**
		 * Removes a database.
		 * @param string $database The name of target database to remove.
		 * @return boolean If true, the database was removed successfuly.
		 */
		public function database_remove($database){ return false; }
		/**
		 * Attempts selecting database name.
		 * @param string $database Database name.
		 * @return boolean Whether successful or not.
		 */
		public function database_select($database){ $this->last_dbname=$database; return false; }

		/// TABLE MANAGEMENT ///

		/**
		 * Creates a new table.
		 * @param string $table The name of target table to create.
		 * @return boolean If true, the table was created successfuly.
		 */
		public function table_create($table){ return false; }
		/**
		 * Returns an array of table names (string).
		 * @return array|null An array of tables or null on error.
		 */
		public function table_all(){ return null; }
		/**
		 * Returns whether the specified table exists or not.
		 * @param string $table The name of target table to check.
		 * @return boolean Table's existence.
		 */
		public function table_exists($table){ return false; }
		/**
		 * Removes a table.
		 * @param string $table The name of target table to remove.
		 * @return boolean If true, the table was removed successfuly.
		 */
		public function table_remove($table){ return false; }

		/// ROW MANAGEMENT ///

		/**
		 * Returns an array of objects which result from running a query.
		 * @param string $table Target table.
		 * @param string $condition A simple SQL expression used to specify which objects to load.<br>
		 *                          <b>NB:</b> Be sure not to use anything specific to any database system!
		 * @return array An array of resulting objects.
		 */
		public function rows_load($table,$condition=''){ return array(); }
		/**
		 * Returns the number of rows affected by the last query.
		 * @return integer Number of affected rows.
		 */
		public function rows_affected(){ return 0; }
		/**
		 * Inserts a set of objects into database as rows.
		 * @param string $table Target table.
		 * @param array $objects An array of objects to insert.
		 * @return array An array with each inserted object's id, order by the same ordering of the input array.
		 */
		public function rows_insert($table,$objects){ foreach($objects as $i=>$o)$objects[$i]=false; return $objects; }
		/**
		 * Updates a set of rows given data objects and field conditions.
		 * @param string $table Target table.
		 * @param array $objects An array of objects to insert.
		 * @param array|string $unique_prop The object's unique property names (eg "id").
		 * @return array An array with each inserted object's id, order by the same ordering of the input array.
		 */
		public function rows_update($table,$objects,$unique_prop){ foreach($objects as $i=>$o)$objects[$i]=false; return $objects; }
		/**
		 * Returns the number of rows matching a condition (or all rows if condition isn't set).
		 * @param string $table Target table.
		 * @param string $condition A simple SQL expression used to specify which objects to load.<br>
		 *                          <b>NB:</b> Be sure not to use anything specific to any database system!
		 * @return integer Number of affected rows.
		 */
		public function rows_count($table,$condition=''){ return 0; }

		/// COLUMN MANAGEMENT ///

		/**
		 * Returns an array of columns in the format of column_name => data_type.
		 * @param string $table The table to get columns from.
		 * @return array Array of columns.
		 */
		public function cols_all($table){ return array(); }

		/**
		 * Adds a column to the table.
		 * @param string $table Table to add column to.
		 * @param string $name Column name. Refrain from using special characters (including spaces!), use underscore instead.
		 * @param string $type Standard data type: byte, char, float, int, text, varchar, blob
		 * @param array $options Any option of: autoincrement, primary or unique.
		 * @return boolean Whether successful or not.
		 * @todo Research what kind of types we will be supporting or not depending on compatbility.
		 */
		public function cols_add($table,$name,$type,$options=array()){ return false; }

		/**
		 * Adds a column to the table.
		 * @param string $table Table to add column to.
		 * @return boolean Whether successful or not.
		 */
		public function cols_remove($table,$name){ return false; }

		/// ERROR MANAGEMENT ///

		/**
		 * If last transaction caused an error, this returns false.
		 * @return boolean True on error false otherwise.
		 */
		public function error(){ return true; }
		/**
		 * Returns the error ID of last transaction.
		 * @return integer The error ID (or 0 if none occured).
		 */
		public function error_id(){ return 1; }
		/**
		 * Returns the error message of last transaction.
		 * @return string The error message (or empty string if none occured).
		 */
		public function error_msg(){ return 'Abstract database interface being used'; }
	}

	if(CFG::get('DB_TYPE')!='' && CFG::get('DB_NAME')!=''){
		// Initialize database automatically
		Database::_init();
		// Ensure database finalization is done at very end
		function _db_fini(){ register_shutdown_function(array('Database','_fini')); }
		// Finalize database automatically
		register_shutdown_function('_db_fini');
	}

?>