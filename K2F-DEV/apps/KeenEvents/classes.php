<?php defined('K2F') or die;

	uses('exts/oodb.php');

	/// LOGIC / MODEL CLASSES ///

	class keEvent extends DatabaseRow {
		/**
		 * @var integer Timestamp of when event starts.
		 */
		public $date_start=0;
		/**
		 * @var integer Timestamp of when event ends.
		 */
		public $date_end=0;
		/**
		 * @var string Short event title.
		 */
		public $title='';
		/**
		 * @var string The event's venue name.
		 */
		public $venue='';
		/**
		 * @var integer Marker ID (for when KeenMaps is installed).
		 */
		public $marker=0;
		/**
		 * @var string Short description of event.
		 */
		public $desc_short='';
		/**
		 * @var string Long description of event.
		 */
		public $desc_long='';
		/**
		 * @var string If not empty, this defines the metric this event is repeated on.
		 * <br/>Note/Exmaple: "yearly" means each year, not 356 days later.
		 */
		public $repeats='';
		/**
		 * Returns the length in time betwen date_start and date_end.
		 * @return integer The event duration in seconds.
		 */
		public function length(){
			return $this->date_end-$this->date_start;
		}
		/**
		 * Returns marker instance or null on error.
		 * @return kmMarker|null Null is returned if marker couldn't be loaded (no KeenMaps, deleted marker etc).
		 */
		public function marker(){
			if(!isset($this->_marker)){
				if(Applications::loaded('KeenMaps')){
					$marker=new kmMarker($this->marker);
					$this->_marker=$marker->load() ? $marker : null;
				}else $this->_marker=null;
			}
			return $this->_marker;
		}
		/**
		 * Returns whether this event is a national holiday or not.
		 * @return boolean True if national, false otherwise.
		 */
		public function national(){
			return $this->marker<1;
		}
		public function table(){
			return 'ke_events';
		}
	}

	class keEvents extends DatabaseRows {
		public function table() {
			return 'ke_events';
		}
	}

?>