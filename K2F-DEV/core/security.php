<?php defined('K2F') or die;

	if(!headers_sent()){
		// Fix cookie acceptance policy in MSIE 6/7/8 (may not work on IE8).
		// @site http://anantgarg.com/2010/02/18/cross-domain-cookies-in-safari/comment-page-1/#comment-5114
		header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"',true);
		// Broadcast system software version.
		//header('X-Powered-By: K2F/'.K2F,true); // security issue
		// Tell browsers this site should not be used in iframes on other pages.
		header('X-Frame-Options: SAMEORIGIN',true);
		// Tell browsers that if they think an XSS attack s going on, block it.
		header('X-XSS-Protection: 1; mode=block',true);
		// Make use of HSTS (forces clients to use HTTPS only).
		// @site http://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security
		if(CFG::get('SSL_MODE')){
			if(!isset($_SERVER['HTTPS'])){
				header('Status-Code: 301');
				header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			}else header('Strict-Transport-Security: max-age=500');
		}
	}else xlog('Warning: Could not send critical headers.');

	// create hmac function for PHP 5.1 and older
	// TODO http://chat.stackoverflow.com/transcript/message/1361148#1361148
	if(!function_exists('hash_hmac')){
		function hash_hmac($algo, $data, $key, $raw_output = false) {
			$pack=array('sha1'=>'H40','md5'=>'H32');
			if(!isset($pack[$algo]))return false;
			$pack=$pack[$algo];
			if(strlen($key)>64)$key=pack($pack,$algo($key));
			$key=str_pad($key,64,chr(0));
			$ipad=(substr($key,0,64)^str_repeat(chr(0x36),64));
			$opad=(substr($key,0,64)^str_repeat(chr(0x5C),64));
			$hmac=$algo($opad.pack($pack,$algo($ipad.$data)));
			if($raw_output)return pack($pack,$hmac);
			return $hmac;
		}
	}

	/**
	 * A class for more secure computing.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 19/09/2010
	 *          31/12/2010 - Added $ignoreChars parameter to Security::filename().
	 *          18/03/2011 - Method `stoident` should perform better now.
	 *          07/04/2011 - Fixed method `stoident` to handle when something else than a string was passed.
	 *          25/06/2011 - Fixed method `escape` to handle different data types better.
	 *          25/06/2011 - Added method `escapeQuote` which escapes and adds double quotes to input. 
	 *          16/08/2011 - Added method `removeHomoglyphs`, used to remove similarly-looking characters.
	 *          31/08/2011 - Checked by Anthony Ferrara (http://chat.stackoverflow.com/transcript/message/1361128#1361128)
	 */
	class Security {
		/**
		 * @return string Generates 16-byte random string.
		 * @author Anthony Ferrara
		 */
		public static function genUID() {
			$seed = '';
			if (!function_exists('mcrypt_create_iv'))
				for ($i = 0; $i < 16; $i++)
					$seed .= chr(mt_rand(0, 255) ^ mt_rand(0, 255));
			else
				$seed = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
			$seed[6] = chr((ord($seed[6]) & 0x0F) | 0x40);
			$seed[8] = chr((ord($seed[8]) & 0x3F) | 0x80);
			return $seed;
		}
		/**
		 * @return string A Globally Unique Identifier in GUID text-hex format (excl braces).
		 */
		public static function genGUID(){
			$uid_ar = array_map('ord', str_split(self::genUID()));
			$format = '%02X%02X%02X%02X-%02X%02X-%02X%02X-%02X%02X-%02X%02X%02X%02X%02X%02X';
			return vsprintf($format, $uid_ar);
		}
		/**
		 * @var array Holds token constants for genToken function.
		 */
		protected static $rngchrs=array(
			'comma'     => array(','),
			'uppercase' => array('A','B','C','D','E','F','G','H','I','J','K',
				'L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'),
			'lowercase' => array('a','b','c','d','e','f','g','h','i','j','k',
				'l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'),
			'numeric'   => array('0','1','2','3','4','5','6','7','8','9'),
			'brackets'  => array('<','>','{','}','[',']'),
			'quotes'    => array('`','\'','"')
		);
		/**
		 * Generates a random sequence of a given length of certain characters.
		 * @param string $letters A comma-separated list of character (ranges):<br>
		 *       'a,b,d'    -> a b d<br>
		 *       'a-e'      -> a b c d e<br>
		 *       'a-d,i-l'  -> a b c d i j k l<br>
		 *       'a-e,-c'   -> a b d e<br>
		 *       'bla,b,a'  -> bla b a<br>
		 *       'quotes,f' -> ` ' " f<br>
		 *     Special tokens:<br>
		 *       comma     -> comma character<br>
		 *       uppercase -> uppercase characters<br>
		 *       lowercase -> lowercase characters<br>
		 *       numeric   -> numeric characters<br>
		 *       brackets  -> all brackets: < > [ ] { } ( )<br>
		 *       quotes    -> all quotes: ` ' "
		 * @param integer $length The length of the returned string.
		 * @param boolean $nohomoglyphs (Optional, default is false) Whether to
		 *     remove homoglyphs or not. Homoglyphs are like "rn"=>"m" or "1"=>"l".
		 * @param integer $limit The number of iterations possible.
		 *     This is a safeguard against infinite looping.
		 * @return string The generated random token.
		 * @todo http://chat.stackoverflow.com/transcript/message/1361180#1361180
		 */
		public static function genToken($letters,$length,$nohomoglyphs=false,$limit=PHP_INT_MAX){
			// split tokens and initialize variables
			$tokens=explode(',',$letters); $chars=array();
			// parse input letters
			foreach($tokens as $token){
				if(strlen($token)==1){
					// handle add character
					if(!in_array($token,$chars))
							$chars[]=$token;
				}elseif(strlen($token)==2 && $token{0}=='-'){
					// handle character removal
					if(in_array($token{1},$chars))
						unset($chars[array_search($token{1},$chars)]);
				}elseif(strlen($token)==3 && $token{1}=='-'){
					// handle add range
					$a=ord($token{0}); $b=ord($token{2});
					if($a>$b){ // handle a-c ranges
						for($i=$a; $i>=$b; $i--){
							$c=chr($i);
							if(!in_array($c,$chars))
								$chars[]=$c;
						}
					}elseif($a<$b){ // handle c-a ranges
						for($i=$a; $i<=$b; $i++){
							$c=chr($i);
							if(!in_array($c,$chars))
								$chars[]=$c;
						}
					}else // handle c-c ranges
						if(!in_array($token{0},$chars))
							$chars[]=$token{0};
				}elseif(isset(self::$rngchrs[$token])){
					// handle special constants
					foreach(self::$rngchrs[$token] as $c)
						if(!in_array($c,$chars))
							$chars[]=$c;
				}// else error?
			}
			// generate the random token
			$charc=count($chars); $data='';
			while(strlen($data)<$length && $charc>0)
				$data=$nohomoglyphs
					? self::removeHomoglyphs($data.$chars[mt_rand(0,$charc-1)])
					: $data.$chars[mt_rand(0,$charc-1)];
			// ensure token is at most $length characters long and return it
			return substr($data,0,$length);
		}
		/**
		 * Remove similar characters (homoglyphs).
		 * @param string $text The original text.
		 * @return string The new text.
		 */
		public static function removeHomoglyphs($text){
			return str_replace(array(
				'I','1','l','0','O','rn','vv'
			), '', $text);
		}
		/**
		 * Used to signal that an enumerated type (array or object) is being converted. Later on, the field is removed.
		 * @var string Infinite recurse protection string.
		 */
		private static $RECURSE_PROTECT='%INFINITE:RECURSION+PROTECT!BLOCK%';
		/**
		 * Converts a string's HTML metacharacters into their representation.
		 * @param string $data The original/insecure data.
		 * @param string $charset (Optional) The target character set.
		 * @return string The new data.
		 */
		public static function snohtml($data,$charset='ISO-8859-1'){
			$data=htmlspecialchars($data,ENT_QUOTES,$charset);
			return $data;
		}
		/**
		 * Converts an array's values (and optionally, keys) into HTML-safe data.
		 * @param array $array The original array to secure.
		 * @param boolean $keys_too (Optional) set to false to disable securing keys. Default is "true".
		 * @param string $charset (Optional) The target character set.
		 * @return array The newly secured array.
		 */
		public static function anohtml(&$array,$keys_too=true,$charset='ISO-8859-1'){
			if(isset($array[self::$RECURSE_PROTECT]))return '[RECURSION]';				// recursion protect
			$array[self::$RECURSE_PROTECT]='';											// recursion protect
			$new=array();
			foreach($array as $key=>$value)if($key!==self::$RECURSE_PROTECT){
				$value=self::vnohtml($value);
				if($keys_too)$key=self::snohtml($key,$charset);
				$new[$key]=$value;
			}
			unset($array[self::$RECURSE_PROTECT]);										// recursion protect
			return $new;
		}
		/**
		 * Converts an object's property values (and optionally, property names/keys) into HTML-safe data.
		 * @param object $array The original object to secure.
		 * @param boolean $keys_too (Optional) set to false to disable securing keys. Default is "true".
		 * @param string $charset (Optional) The target character set.
		 * @return object The newly secured object.
		 */
		public static function onohtml($object,$keys_too=true,$charset='ISO-8859-1'){
			if(isset($object->{self::$RECURSE_PROTECT}))return '[RECURSION]';			// recursion protect
			$object->{self::$RECURSE_PROTECT}='';										// recursion protect
			$new=$object;
			foreach(get_object_vars($object) as $key=>$value)if($key!==self::$RECURSE_PROTECT){
				$value=self::vnohtml($value);
				if($keys_too)$key=self::snohtml($key,$charset);
				$new->{$key}=$value;
			}
			unset($object->{self::$RECURSE_PROTECT});									// recursion protect
			return $new;
		}
		/**
		 * Secure a variable for HTML output (by removing metacharacters).
		 * @param misc $var The original variable.
		 * @param string $charset (Optional) The target character set.
		 * @return misc The secured variable.
		 */
		public static function vnohtml($var,$charset='ISO-8859-1'){
			if(is_array($var))	return self::anohtml($var,$charset);
			if(is_bool($var))	return $var; // safe type
			if(is_float($var))	return $var; // safe type
			if(is_integer($var))return $var; // safe type
			if(is_null($var))	return $var; // safe type
			if(is_object($var))	return self::onohtml($var,$charset);
			if(is_string($var))	return self::snohtml($var,$charset);
			return null; // unknown variable type, assume unsafe.
		}
		/**
		 * Returns a JS-safe string (CRLF removed, special chars converted and escaped).
		 * @param string $data The original data.
		 * @return string The secured data.
		 */
		public static function snojs($data){
			return substr(json_encode((string)$data),1,-1);
		}
		/**
		 * Redirects securely on this same server.
		 * @param string $url The url to redirect to.
		 */
		public static function redirect($url){
			if(headers_sent())
				die('Cannot redirect to <a href="'.self::snohtml($url).'">'.self::snohtml($url).'</a>; headers have already been sent.');
			$host=parse_url($url,PHP_URL_HOST);
			$goodhost=($_SERVER['SERVER_NAME']==$host)||($host=='');
			if(!$goodhost)
				die('Cannot redirect to <a href="'.self::snohtml($url).'">'.self::snohtml($url).'</a>; untrusted host.');
			header('Location: '.$url);
			die;
		}
		/**
		 * Escapes a value for use within a DB query.<br>
		 * <b>WARNING</b> If the original value is of enumerable type, the index is NEVER escaped.
		 * @deprecated WARNING DO NOT USE THE METHOD. Instead use Database::db()->escape().
		 * @param mixed $val Original value.
		 * @return array|string The escaped value.
		 */
		public static function escape($val){
			switch(gettype($val)){
				case 'array': case 'object':
					$val=(array)$val;
					foreach($val as $k=>$v)
						$val[$k]=self::escape($v);
					return $val;
				case 'string':
					return mysql_real_escape_string($val);
				case 'NULL':
					return 'NULL';
				case 'boolean':
					return $val ? 'TRUE' : 'FALSE';
				default:
					return strval($val); 
			}
		}
		/**
		 * Similar tp escape(), but adds double quotes when necesary.<br>
		 * <b>WARNING</b> If the original value is of enumerable type, the index is NEVER escaped.
		 * @deprecated WARNING DO NOT USE THE METHOD. Instead use Database::db()->escape().
		 * @param mixed $val Original value.
		 * @return array|string The escaped value.
		 * @example Security::escapeQuote(array("Hi",45,true)); // array('"Hi"','45','1')
		 */
		public static function escapeQuote($val){
			switch(gettype($val)){
				case 'array': case 'object':
					$val=(array)$val;
					foreach($val as $k=>$v)
						$val[$k]=self::escapeQuote($v);
					return $val;
				case 'string':
					return '"'.self::escape($val).'"';
				case 'NULL':
					return 'NULL';
				case 'boolean':
					return $val ? 'TRUE' : 'FALSE';
				default:
					return strval($val); 
			}
		}
		/**
		 * Replaces all characters except underscore, alphabet and numerals.
		 * Usefull in eg; HTML id and name attributes, filenames, variable names etc...
		 * @param string $orig The original string.
		 * @param string $replace The replace string (defaults to a single underscore).
		 * @return string The new string.
		 */
		public static function stoident($original,$replace='_'){
			return preg_replace('/[^a-zA-Z0-9_]/', $replace, $original);
		}
		/**
		 * Removes special characters from a file name, such as quotes, newlines, path delimiters...
		 * @param string $filename The file name to check/fix.
		 * @param string $replaceChar (Optional) Replacement character to be used when a prohibited character is found (default it is empty).
		 * @param array $ignoreChars (Optional) An array of characters to ignore (let pass through).
		 * @return string The safe filename (with special characters stripped out/replaced).
		 * @todo Clean this up.
		 */
		public static function filename($filename,$replaceChar='',$ignoreChars=array()){
			$ignoreChars = preg_quote(implode('',$ignoreChars),'/');
			$pattern = '/[^a-zA-Z0-9-_ \(\)\[\]\{\}'.$ignoreChars.']/';
			return preg_replace($pattern, $replaceChar, $filename);
		}
	}
	
?>