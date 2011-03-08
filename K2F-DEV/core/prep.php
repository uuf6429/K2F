<?php defined('K2F') or die;
	
	/**
	 * A class which abstracts preprocessing data. A preprocessor modifies existing
	 * code/string and returns a similar but resulting derivative.
	 * PHP is probably the best example; the initial HTML contains non-HTML structures which
	 * only PHP understands and which replaces them with meaningful HTML.
	 * Other uses of preprocessors can be BBCode, ShortCode or the Wiki format.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 01/01/2011 - Initial implementation.
	 */
	class Preprocessor {
		/**
		 * @var string The original data.
		 */
		protected $original='';
		/**
		 * @var string The converted data.
		 */
		protected $converted='';
		/**
		 * @var string Whether conversion was successful or not.
		 */
		protected $finished=false;
		/**
		 * @var integer When a parse error occurs, this is set to the error id, otherwise 0 denotes success.
		 */
		protected $last_errno=0;
		/**
		 * @var string When an error occurs, thi is the textual error message.
		 */
		protected $last_error='';
		/**
		 * Set the original data.
		 * @param string $original The original data to operate on.
		 */
		public function set($original){
			$this->original=$original;
			$this->finished=false;
			$this->converted='';
		}
		/**
		 * Returns the converted data (if any).
		 * @return string The converted data or an empty string on error.
		 */
		public function get(){
			return $this->converted;
		}
		/**
		 * Returns the original data (in case the dev forgot all about it!).
		 * @return string The original data.
		 */
		public function getOriginal(){
			return $this->original;
		}
		/**
		 * Returns true if conversion was a success, false otherwise.
		 * @return boolean True on success, false otherwise.
		 */
		public function finished(){
			return $this->finished && $this->last_errno==0;
		}
		/**
		 * Do the preprocessor conversion. If you're doing a new preprocessor class, you MUST OVERRIDE this method.
		 * @return boolean True on success, false on failure.
		 */
		public function process(){
			return $this->set_error(0,'success');
		}
		/**
		 * Returns the last error code number.
		 * @return integer Error code number or 0 if no errors occured.
		 */
		public function error_num(){
			return $this->last_errno;
		}
		/**
		 * Returns last error's textual message.
		 * @return string Error message.
		 */
		public function error_msg(){
			return $this->last_error;
		}
		/**
		 * This is a handy function to do set the error handler and quit all at once. You should use it like:<br>
		 *     if(!unlink('somefile.txt'))return $this->set_error(13,'Deleting file failed!');
		 * @param integer $num The error code number.
		 * @param string $msg The textual error message.
		 * @return boolean True if $num is 0, false otherwise.
		 */
		protected function set_error($num,$msg){
			$this->last_errno=$num;
			$this->last_error=$msg;
			return $num!=0;
		}
	}

?>