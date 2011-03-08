<?php defined('K2F') or die;

	uses('core/prep.php');

	/**
	 * A class which converts bbcode text to html.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 01/01/2011 - Initial implementation.
	 */
	class BBCodePreprocessor extends Preprocessor {
		/**
		 * @var array List of replacements (key/match => value/replacement).
		 */
		private static $rules=array(
			'/\[b\](.*?)\[\/b\]/eis'			 => '<b>$1</b>',
			'/\[i\](.*?)\[\/i\]/eis'			 => '<i>$1</i>',
			'/\[u\](.*?)\[\/u\]/eis'			 => '<u>$1</u>',
			'/\[img\](.*?)\[\/img\]/eis'		 => '<img src="$1">',
			'/\[url\=(.*?)\](.*?)\[\/url\]/eis'	 => '<a href="$1">$2</a>',
			'/\[code\](.*?)\[\/code\]/eis'		 => '<code>$1</code>'
		);
		public function process(){
			$this->finished=false;
			try {
				$this->converted=@preg_replace(array_keys(self::$rules),array_values(self::$rules),Security::snohtml($this->original));
				$this->finished=true;
				return $this->set_error(0,'success');
			}catch(Exception $e){
				return $this->set_error($e->getCode(),'Exception on line '.$e->getLine().': '.$e->getMessage());
			}
		}
	}

//	xlog('Error: K2F is loading a defective class, ('.__FILE__.') and possibly even using it.');
//	$prep=new BBCodePreprocessor();
//	$prep->set('[b]This is bold and [i]italic[/i].[/b] Say hello to [url=http://google.com/]google.com[/url]!');
//	die_r($prep,$prep->process(),$prep->get());
?>