<?php defined('K2F') or die;

	/**
	 * Classes for parsing simplistic PHPDoc.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 06/09/2010 - Initial implementation.
	 *          05/07/2011 - Now supports relating blocks to code as well as other minor enhancements.
	 * @bugs Code limitations.
	 *  - Does not parse spanning PHPDoc syntax
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
		/**
		 * @return string Value of tag.
		 */
		function __toString(){
			return $this->content;
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
		 * @param string $tag PHPDoc tag to look for, must start with an "@" character.
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
		/**
		 * Returns the PhpDocTag of the first matching tag.
		 * @param string $tag PHPDoc tag to look for, must start with an "@" character.
		 * @param boolean $case_sensitive Whether to do a case sensitive search or not.
		 * @return PhpDocTag|mixed The tag instance, or the default if none matched.
		 */
		public function tag($tag='@tag',$case_sensitive=true,$default=null){
			$cst=strtolower($tag);
			foreach($this->taglines as $tagline)
				if($tagline->doctag==$tag || ($case_sensitive && strtolower($tagline->doctag)==$cst))
					return $tagline;
			return $default;
		}
	}

	class PhpDoc {
		/**
		 * Given PHP code, returns array of PHPDoc block objects.
		 * @param string $code Original PHP code.
		 * @param boolean $relate True to relate docblocks to items, false to ignore relations.
		 * @return array Array of PhpDocBlock objects.
		 */
		public static function parse($code,$relate=false){
			$result=array();
			$tokens=token_get_all($code);
			foreach($tokens as $i=>$token)
				if(is_array($token) && $token[0]==T_DOC_COMMENT){
					if($relate){
						// find what the docblock relates to
						$related=''; $locked=true; $unlock=array(T_FUNCTION,T_CLASS,T_CONST,T_NAMESPACE);
						for($p=$i; $p<count($tokens); $p++){
							if(in_array($tokens[$p][0],$unlock))$lock=false;
							if($tokens[$p][0]==T_VARIABLE){
								$related=str_replace('$','',$tokens[$p][1]);
								break;
							}
							if(!$lock && $tokens[$p][0]==T_STRING){
								$related=$tokens[$p][1];
								break;
							}
						}
						// parse the found docblock
						if($related!='')
							$result[$related]=new PhpDocBlock($token[1]);
					}else
						$result[]=new PhpDocBlock($token[1]);
				}
			return $result;
		}
	}

?>