<?php defined('K2F') or die;

	/**
	 * A simple list management class.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 11/04/2010
	 */
	class SimpleList {
		protected $list=array();
		/**
		 * Add item to list.
		 * @param mixed $item The new item to add.
		 * @return integer The new item's index.
		 */
		public function add($item){
			$this->list[]=$item;
			$keys=array_keys($this->list);
			return array_pop($keys);
		}
		/**
		 * Returns the value of an item.
		 * @param integer $id The item's index.
		 * @return mixed The item value or null if not found (use exists not this one).
		 */
		public function get($id){
			return isset($this->list[$id])?$this->list[$id]:null;
		}
		/**
		 * Returns a string with imploded values.
		 * @param string $glue The string to use between each item.
		 * @return string Imploded string.
		 */
		public function implode($glue=''){
			return implode($glue,$this->list);
		}
		/**
		 * Sets the value of an item.
		 * @param integer $id The item's index.
		 * @param mixed $value The new value.
		 */
		public function set($id,$value){
			$this->list[$id]=$value;
		}
		/**
		 * Returns whether the particular item exists or not.
		 * @param integer $id The item's index.
		 * @return boolean Existence.
		 */
		public function exists($id){
			return isset($this->list[$id]);
		}
		/**
		 * Removes an item by its id.
		 * @param integer $id The id of the item to remove.
		 */
		public function remove($id){
			unset($this->list[$id]);
			$this->list=array_values($this->list);
		}
		/**
		 * Remove several items at once. You must put each item as a different function argument.
		 */
		public function removeItems(){
			$items=func_get_args();
			foreach($this->list as $k=>$v)if(in_array($v,$items))unset($this->list[$k]);
			$this->list=array_values($this->list);
		}
		/**
		 * Clears the list.
		 */
		public function clear(){
			$this->list=array();
		}
	}

?>