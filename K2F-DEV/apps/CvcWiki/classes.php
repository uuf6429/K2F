<?php defined('K2F') or die;

	uses('exts/oodb.php','exts/prep.wiki.php');

	/// SYSTEM LOGIC OBJECTS ///

	class cwWikiPage extends DatabaseRow {
		/**
		 * @var integer Timestamp this wiki page was created on.
		 */
		public $created=0;
		/**
		 * @var integer Timestamp this wiki page was last updated on.
		 */
		public $updated=0;
		/**
		 * @var integer ID of the user that created this wiki page.
		 */
		public $created_uid=0;
		/**
		 * @var integer ID of user that last updated this wiki page.
		 */
		public $updated_uid=0;
		/**
		 * @var string Formal wiki page title (unsuitable for use in URL; use method ->name() instead).
		 */
		public $title='';
		/**
		 * @var string Original wiki page content data (in wiki markup syntax).
		 */
		public $data_orig='';
		/**
		 * @var string HTML last generated from wiki markup content (this serves as a cache).
		 */
		public $data_html='';
		/**
		 * @var integer Level of access required to do anything with page.
		 */
		public $access=cwWikiPages::XS_UNCLASSIFIED;
		/**
		 * @var boolean Whether this page has been deleted or not. Note that it can only be restored according to ->access.
		 */
		public $retired=false;
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'cw_pages';
		}
		/**
		 * An override to the default save method, so that we can do some stuff such as handling authorization.
		 * @return boolean True on success, false otherwise.
		 */
		public function save(){
			if($this->id<1){
				// handle insert
				$this->created=time();
				$this->created_uid=CmsHost::cms()->user_id();
				// handle caching
				$this->_wiki2html();
				// no need to check auth, allow by default
				return parent::save();
			}else{
				// handle update
				$this->updated=time();
				$this->updated_uid=CmsHost::cms()->user_id();
				$rev=new cwWikiPageRev();
				// handle caching
				$this->_wiki2html();
				// do security check over existing page details
				$auth=new cwWikiPage($this->id); $auth->load();
				return ( // this code controls unauthorized modification
					 $auth->access==cwWikiPages::XS_UNCLASSIFIED
				 || ($auth->access==cwWikiPages::XS_CONFIDENTIAL && CmsHost::cms()->is_client() )
				 || ($auth->access==cwWikiPages::XS_SECRET       && CmsHost::cms()->is_admin() )
				 || ($auth->access==cwWikiPages::XS_TOPSECRET    && $auth->created_uid==CmsHost::cms()->user_id() )
				) ? and_r($rev->backup($auth),parent::save()) : false;
			}
		}
		/**
		 * An override to the default load method, so that we can do some stuff such as handling authorization.
		 * @return boolean True on success, false otherwise.
		 */
		public function load($condition=''){
			if($condition=='')$condition=$this->id>0 ? '`id`='.$this->id : '1';
			if(CmsHost::cms()->is_admin()){ // admin
				$condition='(`access`!='.cwWikiPages::XS_TOPSECRET.' OR `created_uid`='.(int)CmsHost::cms()->user_id().') AND '.$condition;
			}elseif(CmsHost::cms()->is_client()){ // user
				$condition='(`access`='.cwWikiPages::XS_UNCLASSIFIED.' OR `access`='.cwWikiPages::XS_CONFIDENTIAL.') AND '.$condition;
			}else{ // guest
				$condition='`access`='.cwWikiPages::XS_UNCLASSIFIED.' AND '.$condition;
			}
			return parent::load($condition);
		}
		/**
		 * This method MUST be called each time object is saved.
		 */
		protected function _wiki2html(){
			$wiki=new WikiPreprocessor();
			$wiki->set($this->data_orig);
			$wiki->process();
			$this->data_html=$wiki->get();
		}
		/**
		 * Returns a URL/SEO friendly wiki page name.
		 * @return string Page name; you still need to secure with correct encoding.
		 */
		public function name(){
			return str_replace(' ','_',Security::filename(ucfirst(trim($this->title)),'_',array('"','\'')));
		}
	}

	class cwWikiPages extends DatabaseRows {
		/**
		 * Anyone can view page (but only registered users can manage it).
		 */
		const XS_UNCLASSIFIED = 0;
		/**
		 * For registered users only.
		 */
		const XS_CONFIDENTIAL = 1;
		/**
		 * For administrators only.
		 */
		const XS_SECRET       = 2;
		/**
		 * For page owner only.
		 */
		const XS_TOPSECRET    = 3;
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'cw_pages';
		}
		/**
		 * An override to the default load method, so that we can ensure there is correct authorization.
		 * @return boolean True on success, false otherwise.
		 */
		public function load($condition='1',$class='cwWikiPage'){
			if(CmsHost::cms()->is_admin()){ // admin
				$condition='(`access`!='.self::XS_TOPSECRET.' OR `created_uid`='.(int)CmsHost::cms()->user_id().') AND '.$condition;
			}elseif(CmsHost::cms()->is_client()){ // user
				$condition='(`access`='.self::XS_UNCLASSIFIED.' OR `access`='.self::XS_CONFIDENTIAL.') AND '.$condition;
			}else{ // guest
				$condition='`access`='.self::XS_UNCLASSIFIED.' AND '.$condition;
			}
			return parent::load($condition,$class);
		}
	}

	class cwWikiPageRev extends DatabaseRow {
		/**
		 * @var integer Timestamp this revision was taken on.
		 */
		public $created=0;
		/**
		 * @var integer Wiki page ID this revision is for.
		 */
		public $pageid=0;
		/**
		 * @var array Key-value pairs of original data (see cwWikePage for keys).
		 */
		public $data=array();
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'cw_revs';
		}
		/**
		 * Creates a revision instance of the passed object.
		 * @param cwWikiPage $page Wiki page object.
		 * @return boolean True on success, false otherwise.
		 */
		public function backup($page){
			$this->created=time();
			$this->pageid=$page->id;
			$this->data=(array)$page;
			return $this->save();
		}
		/**
		 * Restores this current revision instance (creating a new revision of the current page).
		 * @return boolean True on success, false otherwise.
		 */
		public function restore(){
			$s=true;
			$page=new cwWikiPage($this->pageid);
			$s|=$page->load();
			$rev=new cwWikiPageRev();
			$s|=$rev->backup($page);
			foreach($this->data as $k=>$v)
				$page->$k=$v;
			$s|=$page->save();
			return $s;
		}
		/**
		 * Returns the current page relevant to revision.
		 * @param boolean $forceReturn Always return page even when loading fails.
		 * @return cwWikiPage The loaded page or false on error (unless $forceReturn).
		 */
		public function current($forceReturn=false){
			$page=new cwWikiPage($this->pageid);
			return ($forceReturn || $page->load()) ? $page : false;
		}
		/**
		 * Returns a property's value or an empty string if not found.
		 * @param string $name Data name.
		 * @return string Data value.
		 */
		public function data($name){
			return isset($this->data[$name]) ? $this->data[$name] : '';
		}
	}

	class cwWikiPageRevs extends DatabaseRows {
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'cw_revs';
		}
		/**
		 * An override to the default load method, so that we can load specific revisions.
		 * @return boolean True on success, false otherwise.
		 */
		public function load($oid){
			$oid=(int)(is_object($oid)?$oid->id:$oid);
			return parent::load('`pageid`='.$oid.' ORDER BY `created` DESC','cwWikiPageRev');
		}
	}

	/// MISC FUNCTIONALITY ///

	/**
	 * Returns a number together with it's ordinal.
	 * @param integer $number Original number.
	 * @return string Number with ordinal (eg; 1=>1st, 40=>40th)
	 */
	function cwOrdinal($number){
		// check for 11, 12 and 13
		if($number%100>10 && $number%100<14){
			$s='th';
		}
		// check for zero
		elseif($number==0){
			$s='';
		}else{
			// get the last digit
			switch(substr($number,-1,1)){
				case '1': $s='st'; break;
				case '2': $s='nd'; break;
				case '3': $s='rd'; break;
				default:  $s='th';
			}
		}
		// finished!
		return $number.$s;
	}

	/**
	 * Loads a wiki page entry or returns false on failure.
	 * @param mixed $wiki Either wiki page name (string) or id (integer).
	 * @param boolean $forceReturn If true, a cwWikiPage will always be returned, even if not loaded.
	 * @param boolean $filterRetired If true, retired pages won't show up (only makes sense when $wiki is the title). Defaults to false.
	 * @return cwWikiPage A cwWikiPage object on success, false on failure.
	 */
	function getWikiPage($wiki,$forceReturn=false,$filterRetired=false){
		$wiki=trim($wiki,'/');
		if($wiki==''.(int)$wiki){
			// Show by id
			$page=new cwWikiPage((int)$wiki);
			$s=$page->load();
		}else{
			// Show by title
			$page=new cwWikiPage();
			$s=$page->load('`title`="'.Security::escape($wiki).'"'.($filterRetired ? ' AND !`retired`' : ''));
		}
		return ($s || $forceReturn) ? $page : false;
	}

	/**
	 * Returns a nicer version of a wiki page name.
	 * @param string $urlname A name often used in urls.
	 * @return string A nicer page name.
	 */
	function cwWikiNiceName($urlname){
		return ucwords(trim(trim(str_replace(array('/','_'),array(' - ',' '),$urlname)),'- '));
	}

	/*
		Paul's Simple Diff Algorithm v 0.1
		(C) Paul Butler 2007 <http://www.paulbutler.org/>
		May be used and distributed under the zlib/libpng license.

		This code is intended for learning purposes; it was written with short
		code taking priority over performance. It could be used in a practical
		application, but there are a few ways it could be optimized.

		Given two arrays, the function diff will return an array of the changes.
		I won't describe the format of the array, but it will be obvious
		if you use print_r() on the result of a diff on some test data.

		htmlDiff is a wrapper for the diff command, it takes two strings and
		returns the differences in HTML. The tags used are <ins> and <del>,
		which can easily be styled with CSS.
	*/

	function diff($old, $new){
		$maxlen = 0;
		foreach($old as $oindex => $ovalue){
			$nkeys = array_keys($new, $ovalue);
			foreach($nkeys as $nindex){
				$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
					$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
				if($matrix[$oindex][$nindex] > $maxlen){
					$maxlen = $matrix[$oindex][$nindex];
					$omax = $oindex + 1 - $maxlen;
					$nmax = $nindex + 1 - $maxlen;
				}
			}
		}
		if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
		return array_merge(
			diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
			array_slice($new, $nmax, $maxlen),
			diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
	}

	function htmlDiff($old, $new){
		$ret = '';
		$diff = diff(explode(' ', $old), explode(' ', $new));
		foreach($diff as $k){
			if(is_array($k))
				$ret .= (!empty($k['d'])?'<del>'.implode(' ',$k['d']).'</del> ':'').
					(!empty($k['i'])?'<ins>'.implode(' ',$k['i']).'</ins> ':'');
			else $ret .= $k . ' ';
		}
		return $ret;
	}

?>