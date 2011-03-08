<?php defined('K2F') or die;

	/**
	 * Classes for parsing simplistic PHPDoc.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 06/09/2010
	 * @bugs Code limitations.
	 *  - Does not parse spanning PHPDoc syntax
	 *  - Does not find what PHPDoc refers to (eg: function etc)
	 *  - PHPDoc parsing uses a raw algorithm, with possibly unknown consequences
	 */


	class PhpDocTag {
		/**
		 * @var string Tag name (eg: "\@author").
		 */
		public $doctag='';
		/**
		 * @var string Tag content (switches, text etc).
		 */
		public $content='';
		/**
		 * Constructs a new tag line instance.
		 * @param string Type of tag.
		 * @param string Tag text.
		 */
		function __construct($tag='',$text=''){
			$this->doctag=$tag;
			$this->content=$text;
		}
		/**
		 * Add a new line of text to the tag.
		 * @param string New line of text to add.
		 */
		function add($text=''){
			$this->content.=CRLF.$text;
		}
	}

	class PhpDocBlock {
		/**
		 * @var string DocBlock description.
		 */
		public $description='';
		/**
		 * @var array Array of PhpDocTag objects.
		 */
		public $taglines=array();
		/**
		 * Creates a PhpDocBlock object from new comment code.
		 * @param string $code Full PhpDoc comment code.
		 */
		function __construct($code){
			$this->parse($code);
		}
		/**
		 * Repopulate this PhpDocBlock object from new comment code.
		 * @param string $code Full PhpDoc comment code.
		 */
		public function parse($code){
			$code=explode(CR,str_replace(LF,CR,str_replace(CRLF,CR,$code)));
			$lasttag=null;
			foreach($code as $i=>$line){
				$line=ltrim(ltrim($line),'*');
				if($i>0 && $i<count($code)-1){
					$ln=trim($line);
					if(isset($ln[0]) && $ln[0]=='@'){	// add tag
						$line=explode(' ',ltrim($line),2);
						$lasttag=new PhpDocTag($line[0],isset($line[1]) ? $line[1] : '');
						$this->taglines[]=$lasttag;
					}elseif($lasttag){					// add line to tag
						$lasttag->add($line);
					}else{								// add line to description
						$this->description.=($this->description=='' ? '' : CRLF).$line;
					}
				}
			}
		}
		/**
		 * Returns PhpDoc block comment given this object's details.
		 * @return string PhpDoc comments.
		 */
		public function render(){
			$res='/'.'**'.CRLF.str_replace(CR,CRLF.' * ',str_replace(LF,CR,str_replace(CRLF,CR,$this->description)));
			$tag=new PhpDocTag(); // phpdoc hack
			foreach($this->taglines as $tag)
				$res.=CRLF.' * '.$tag->doctag.' '.str_replace(CR,CRLF.' * ',str_replace(LF,CR,str_replace(CRLF,CR,$tag->content)));
			$res.=' *'.'/';
			return $res;
		}
		/**
		 * Returns an array of PhpDocTag matching tag.
		 * @param string $tag PHPDoc tag to look for.
		 * @param boolean $case_sensitive Whether to do a case sensitive search or not.
		 * @return array Array of PhpDocTag objects.
		 */
		public function tags($tag='@tag',$case_sensitive=true){
			$result=array(); $tagline=new PhpDocTag(); $cst=strtolower($tag);
			foreach($this->taglines as $tagline)
				if($tagline->doctag==$tag || ($case_sensitive && strtolower($tagline->doctag)==$cst))
					$result[]=$tagline;
			return $result;
		}
	}

	class PhpDoc {
		/**
		 * Given PHP code, returns array of PHPDoc block objects.
		 * @param string $code Original PHP code.
		 * @return array Array of PhpDocBlock objects.
		 */
		public static function parse($code){
			$result=array();
			$tokens=token_get_all($code);
			foreach($tokens as $token)
				if(is_array($token) && $token[0]==T_DOC_COMMENT)
					$result[]=new PhpDocBlock($token[1]);
			return $result;
		}
	}

?>