<?php defined('K2F') or die;

	uses('core/database.php');

	define('CR_UNKNOWN_ERROR',2000); // mysql error constant for "unknown error".

	class Database_mysql extends Database_Base {
		protected $link=null;
		protected static $temp_column='K2F_TMP_COL';
		public function login($host,$user,$pass){
			return ($this->link=@mysql_connect($host,$user,$pass)) ? true : false;
		}
		public function logout(){
			return $this->link && @mysql_close($this->link);
		}
		public function raw_query($query){
			xlog('Warning: You should not run raw queries since they might not be portable.',$query,debug_backtrace());
			return $this->_run_query($query);
		}
		protected function _run_query($query){
			$this->last_query=$query;
			$this->last_result=@mysql_query($query,$this->link);
			if(CFG::get('DEBUG_VERBOSE'))xlog($query);
			return $this->last_result ? true : false;
		}
		public function database_create($database){
			return $this->_run_query('CREATE DATABASE `'.Security::escape($database).'`');
		}
		public function database_all(){
			$result=@mysql_list_dbs($this->link); $dbs=array();
			while($row=@mysql_fetch_object($result))$dbs[]=$row->Database;
			return $result;
		}
		public function database_exists($database){
			$databases=$this->database_all();
			return $databases ? in_array($database,$this->database_all()) : false;
		}
		public function database_remove($database){
			return $this->_run_query('DROP DATABASE `'.Security::escape($database).'`');
		}
		public function database_select($database){
			$this->last_dbname=$database;
			return @mysql_select_db($database,$this->link);
		}
		public function table_create($table){
			//return self::_run_query('CREATE TABLE `'.Security::escape($table).'` (`'.Security::escape(self::$temp_column).'` BIT(1) NOT NULL) ENGINE = MYISAM ');
			// the new code is used to (by default) create an "id" key
			return self::_run_query('CREATE TABLE `'.Security::escape($table).'` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY) ENGINE = MYISAM ');
		}
		public function table_all(){
			$tables=array();
			if(self::_run_query('SHOW TABLES')){
				while($row=@mysql_fetch_object($this->last_result)){
					$row=array_values((array)$row);
					$tables[]=array_pop($row);
				}
				return $tables;
			}else return null;
		}
		public function table_exists($table){
			self::_run_query('SHOW TABLES LIKE "'.Security::escape($table).'"');
			return (self::affectedRows()==1);
		}
		public function table_remove($table){
			return $this->_run_query('DROP TABLE `'.Security::escape($table).'`');
		}
		public function rows_load($table,$condition=''){
			if($condition!='')$condition=' WHERE '.$condition;
			$this->_run_query('SELECT * FROM `'.Security::escape($table).'`'.$condition);
			$rows=array();
			while(($row=@mysql_fetch_object($this->last_result)))$rows[]=$row;
			return $rows;
		}
		public function rows_affected(){
			return @mysql_affected_rows($this->link);
		}
		public function rows_insert($table,$objects){
			if(!is_array($objects))$objects=array($objects);
			foreach($objects as $i=>$object){
				$props=(array)get_object_vars($object);
				$keys='`'.implode('`,`',array_keys($props)).'`';
				$values='"'.implode('","',Security::escape(array_values($props))).'"';
				$objects[$i]=$this->_run_query('INSERT INTO `'.Security::escape($table).'` ('.$keys.') VALUES ('.$values.')');
				if($objects[$i])$objects[$i]=@mysql_insert_id($this->link);
			}
			return $objects;
		}
		public function rows_update($table,$objects,$unique_prop){
			if(!is_array($objects))$objects=array($objects);
			if(!is_array($unique_prop))$unique_prop=array((string)$unique_prop);
			foreach($objects as $i=>$object){
				$fields=array();
				foreach(get_object_vars($object) as $prop=>$val)
					if(!in_array($prop,$unique_prop))
						$fields[]='`'.Security::escape($prop).'`="'.Security::escape($val).'"';
				$fields=implode(', ',$fields);
				$cond='1';
				foreach($unique_prop as $key)
					$cond.=' AND `'.Security::escape($key).'`="'.Security::escape($object->$key).'"';
				$objects[$i]=$this->_run_query('UPDATE `'.Security::escape($table).'` SET '.$fields.' WHERE '.$cond);
			}
			return $objects;
		}
		public function rows_delete($table,$objects,$unique_prop){
			if(!is_array($objects))$objects=array($objects);
			if(!is_array($unique_prop))$unique_prop=array((string)$unique_prop);
			foreach($objects as $i=>$object){
				$cond='1';
				foreach($unique_prop as $key)
					$cond.=' AND `'.Security::escape($key).'`="'.Security::escape($object->$key).'"';
				$objects[$i]=$this->_run_query('DELETE FROM `'.Security::escape($table).'` WHERE '.$cond);
			}
			return $objects;
		}
		public function rows_count($table,$condition=''){
			if($condition!='')$condition=' WHERE '.$condition;
			$this->_run_query('SELECT COUNT(*) FROM `'.Security::escape($table).'`'.$condition);
			$rows=array_values((array)@mysql_fetch_object($this->last_result));
			return (int)$rows[0];
		}
		public function cols_all($table){
			$this->_run_query('SHOW COLUMNS FROM `'.Security::escape($table).'`');
			$rows=array();
			while(($row=@mysql_fetch_object($this->last_result))){
				$row->Type=explode(' ',$row->Type);
				$rows[$row->Field]=$row->Type[0];
			}
			return $rows;
		}
		public function cols_add($table,$name,$type,$options=array()){
			$sql='ALTER TABLE `'.Security::escape($table).'` ADD `'.Security::escape($name).'` '.$type.' NOT NULL';
			if(in_array('autoincrement',$options))$sql.=' AUTO_INCREMENT';
			if(in_array('primary',$options))      $sql.=', ADD PRIMARY KEY (`'.Security::escape($name).'`)';
			if(in_array('unique',$options))       $sql.=', ADD UNIQUE (`'.Security::escape($name).'`)';
			return $this->_run_query($sql);
		}
		public function cols_remove($table,$name){
			$sql='ALTER TABLE `'.Security::escape($table).'` DROP `'.Security::escape($name).'`';
			return $this->_run_query($sql);
		}
		public function error(){
			return !$this->link || @mysql_errno($this->link)!=0;
		}
		public function error_id(){
			return !$this->link ? CR_UNKNOWN_ERROR : @mysql_errno($this->link);
		}
		public function error_msg(){
			return !$this->link ? 'Interface not connected.' : @mysql_error($this->link);
		}
	}

?>