<?php defined('K2F') or die;

	uses('core/security.php');

	/**
	 * A list of curl handles which you can use for various reasons (such as profiling).
	 * This also fixes an issue with the garbage collecter automagically closing the handle if the variable is dereferenced.
	 */
	$GLOBALS['K2F_CURL']=array();

	/**
	 * This is a class which abstracts data transfers and simplifies HTTP-related tasks.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 08/11/2009
	 */
	class Connect {
		/**
		 * @var string Request useragent, it is set dynamically after class decleration below.
		 */
		public static $USERAGENT='';
		/**
		 * @var array Buffer to contain list of the downloaded headers.
		 */
		public static $HEADERS=array();
		/**
		 * Convert an array to a query string.
		 * @param array $vars Array of key-value pairs.
		 * @return string Query string.
		 */
		public static function makeGetVars($vars){
			$res='';
			foreach($vars as $key=>$value)$res.=urlencode($key).'='.urlencode($value).'&';
			return $res;
		}
		/**
		 * Parses CURL IPs out of STDERR.
		 * @param resource $fp File pointer to STDERR.
		 * @return array List of found IPs.
		 */
		protected static function _get_curl_remote_ips($fp){
			rewind($fp); $bf=fread($fp,8192); $re='/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/';
			return preg_match_all($re,$bf,$matches) ? $matches[0] : array();
		}
		/**
		 * Performs a GET request.
		 * @param string $url Target url (with get parameters).
		 * @param boolean $extended Whether to return extended info or not (defaults to false).
		 * @param array|null $headers An array of any headers you may want to set.
		 * @param boolean $closeAfterUse Whether to close curl after use or not (defaults to true).
		 * @param integer $timeout The maximum time (in milliseconds) to wait for curl functions to finish (defaults to -1).
		 * @param array|null $cookies Some specific cookies to be used for connection.
		 * @return string|array If extended is false, returns request body.
		 *     <br>Otherwise return array of extended info:
		 *     <br>ip - The last IP used (where the content came from).
		 *     <br>ips - All the IPs CURL had to pass through to get to destitation.
		 *     <br>curl - The curl resource handle which you might want to reuse (ensure $closeAfterUser is false).
		 *     <br>status - The HTTP status code (eg, 200 is ok, 404 is file not found...)
		 *     <br>response - The resulting (body) data from the transaction.
		 *     <br>headers - Compatbility with legacy code (same as headers_received).
		 *     <br>headers_received - The headers received after the request has been sent.
		 *     <br>headers_sent - The headers used while sending the request.
		 *     <br>Note that headers are the same headers_received, this is to be compatible with legacy code which relies on header.
		 */
		public static function get($url,$extended=false,$headers=null,$closeAfterUse=true,$timeout=-1,$cookies=null){
			if(!function_exists('connect_store_headers_callback')){
				function connect_store_headers_callback($ch,$header){
					Connect::$HEADERS[]=$header;
					return strlen($header);
				}
			}
			self::$HEADERS=array();
			$ch=curl_init();
			$wp=fopen('php://temp','r+');
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_STDERR,$wp);
			curl_setopt($ch,CURLOPT_VERBOSE,true);
			if(substr($url,0,8)=='https://'){
				curl_setopt($ch,CURLOPT_HTTPAUTH,CURLAUTH_ANY);
				curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
			}
			if($headers)curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
			if($timeout!=-1)curl_setopt($ch,CURLOPT_TIMEOUT_MS,$timeout);
			if($cookies){
				foreach($cookies as $k=>$v)$cookies[$k]=$k.'='.urlencode(is_scalar($v) ? $v.'' : '');
				curl_setopt($ch,CURLOPT_COOKIE,implode('; ',$cookies));
			}
			curl_setopt($ch,CURLOPT_HEADER,false);
			curl_setopt($ch,CURLOPT_USERAGENT,self::$USERAGENT);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
			curl_setopt($ch,CURLOPT_MAXREDIRS,50);
			curl_setopt($ch,CURLOPT_HEADERFUNCTION,'connect_store_headers_callback');
			$data=curl_exec($ch);
			if(CFG::get('DEBUG_VERBOSE'))xlog('HTTP GET ',$url,':',$data!==false?'success':'failure');
			if($data===false)$data='';
			$ips=self::_get_curl_remote_ips($wp);
			if(!$extended && (curl_getinfo($ch,CURLINFO_HTTP_CODE)>=400))$data='';
			if($extended)$data=array(
				'ip'=>count($ips)>0 ? end($ips) : '',
				'ips'=>$ips,
				'curl'=>$ch,
				'status'=>curl_getinfo($ch,CURLINFO_HTTP_CODE),
				'response'=>!$data?'':$data,
				'headers'=>self::$HEADERS,
				'headers_received'=>self::$HEADERS,
				'headers_sent'=>curl_getinfo($ch,CURLINFO_HEADER_OUT)
			);
			if($closeAfterUse)curl_close($ch);
			fclose($wp);
			$GLOBALS['K2F_CURL'][]=$ch;
			return $data;
		}
		/**
		 * Performs a POST request.
		 * @param string $url Target url (with GET parameters).
		 * @param array $vars Array of POST parameters.
		 * @param boolean $extended Whether to return extended info or not (defaults to false).
		 * @param array|null $headers An array of any headers you may want to set.
		 * @param boolean $closeAfterUse Whether to close curl after use or not (defaults to true).
		 * @param integer $timeout The maximum time (in milliseconds) to wait for curl functions to finish (defaults to -1).
		 * @param array|null $cookies Some specific cookies to be used for connection.
		 * @return string|array If extended is false, returns request body.
		 *     <br>Otherwise return array of extended info:
		 *     <br>ip - The last IP used (where the content came from).
		 *     <br>ips - All the IPs CURL had to pass through to get to destitation.
		 *     <br>curl - The curl resource handle which you might want to reuse (ensure $closeAfterUser is false).
		 *     <br>status - The HTTP status code (eg, 200 is ok, 404 is file not found...)
		 *     <br>response - The resulting (body) data from the transaction.
		 *     <br>headers - Compatbility with legacy code (same as headers_received).
		 *     <br>headers_received - The headers received after the request has been sent.
		 *     <br>headers_sent - The headers used while sending the request.
		 *     <br>Note that headers are the same headers_received, this is to be compatible with legacy code which relies on header.
		 */
		public static function post($url,$vars,$extended=false,$headers=null,$closeAfterUse=true,$timeout=-1,$cookies=null){
			if(!function_exists('connect_store_headers_callback')){
				function connect_store_headers_callback($ch,$header){
					Connect::$HEADERS[]=$header;
					return strlen($header);
				}
			}
			self::$HEADERS=array();
			$ch=curl_init();
			$wp=fopen('php://temp','r+');
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_STDERR,$wp);
			curl_setopt($ch,CURLOPT_VERBOSE,true);
			if(substr($url,0,8)=='https://'){
				curl_setopt($ch,CURLOPT_HTTPAUTH,CURLAUTH_ANY);
				curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
			}
			if($headers)curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
			if($timeout!=-1)curl_setopt($ch,CURLOPT_TIMEOUT_MS,$timeout);
			if($cookies){
				foreach($cookies as $k=>$v)$cookies[$k]=$k.'='.urlencode($v);
				curl_setopt($ch,CURLOPT_COOKIE,implode('; ',$cookies));
			}
			curl_setopt($ch,CURLOPT_HEADER,false);
			curl_setopt($ch,CURLOPT_USERAGENT,self::$USERAGENT);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
			curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
			curl_setopt($ch,CURLOPT_MAXREDIRS,50);
			curl_setopt($ch,CURLOPT_HEADERFUNCTION,'connect_store_headers_callback');
			$data=curl_exec($ch);
			if(CFG::get('DEBUG_VERBOSE'))xlog('HTTP POST ',$url,':',$data!==false?'success':'failure');
			if($data===false)$data='';
			$ips=self::_get_curl_remote_ips($wp);
			if(!$extended && (curl_getinfo($ch,CURLINFO_HTTP_CODE)>=400))$data='';
			if($extended)$data=array(
				'ip'=>count($ips)>0 ? end($ips) : '',
				'ips'=>$ips,
				'curl'=>$ch,
				'status'=>curl_getinfo($ch,CURLINFO_HTTP_CODE),
				'response'=>!$data?'':$data,
				'headers'=>self::$HEADERS,
				'headers_received'=>self::$HEADERS,
				'headers_sent'=>curl_getinfo($ch,CURLINFO_HEADER_OUT)
			);
			if($closeAfterUse)curl_close($ch);
			fclose($wp);
			$GLOBALS['K2F_CURL'][]=$ch;
			return $data;
		}
		/**
		 * Returns the web user's IP address.
		 * @param boolean $asipv6 If enabled, this ensures the IP is v6.
		 * @return string The user's IP.
		 */
		public static function clientIp($asipv6=false){
			return !$asipv6 ? $_SERVER['REMOTE_ADDR'] : self::toIpv6($_SERVER['REMOTE_ADDR']);
		}
		/**
		 * Returns whether the IP is version 4 or not.
		 * @param string $ip The IP to check.
		 * @return boolean Whether it is IP v4 or not.
		 */
		public static function isIpv4($ip){
			return count('.',explode($ip))==4;
		}
		public static function toIpv6($ip) {
			// define
			static $mask='::ffff:';
			static $default='2002:0:0:0:0:0:0:0';
			if(!function_exists('__expandIpv6')){
				function __expandIpv6($ip) {
					if(strpos($ip,'::')!==false)$ip=str_replace('::',str_repeat(':0',8-substr_count($ip,':')).':',$ip);
					if(strpos($ip,':')===0)$ip='0'.$ip;
					return $ip;
				}
			}
			// convert
			$ipv6=(strpos($ip,'::')===0);
			$ipv4=(strpos($ip,'.')>0);
			if(!$ipv4 && !$ipv6)return $default;
			if($ipv6 && $ipv4)$ip=substr($ip,strrpos($ip,':')+1);
			elseif (!$ipv4)return __expandIpv6($ip);
			$ip=array_pad(explode('.',$ip),4,0);
			if(count($ip)>4)return $default;
			for($i=0; $i<4; $i++)if($ip[$i]>255)return $default;
			$part7=base_convert(($ip[0]*256)+$ip[1],10,16);
			$part8=base_convert(($ip[2]*256)+$ip[3],10,16);
			return __expandIpv6($mask.$part7.':'.$part8);
		}
		/**
		 * Returns the IP in it's fullest format.
		 * @example ::1              => 0000:0000:0000:0000:0000:0000:0000:0001
		 *          220F::127.0.0.1  => 220F:0000:0000:0000:0000:0000:7F00:0001
		 *          2F:A1::1         => 002F:00A1:0000:0000:0000:0000:0000:0001
		 * @param string $ip Original/compressed/packed IPv6.
		 * @return string Full IP.
		 */
		protected static function fixIpv6($ip){
			// fix double colon
			if(strpos($ip,'::')!==false)$ip=str_replace('::',str_repeat(':',9-substr_count($ip,':')),$ip);
			// fix each slot
			$ip=explode(':',$ip);
			foreach($ip as $k=>$v){
				// fix empty/compressed slots
				$ip[$k]=$v=str_pad($v,4,'0',STR_PAD_LEFT);
				// fix ipv4-style slot
				if(strpos($v,'.')!==false){
					// initially empty buffer
					$ip[$k]='';
					// replace each number(byte) with a two-digit hex representation
					foreach(explode('.',$v) as $v2){
						$v=dechex(min((int)$v2,255));
						if(strlen($v)==1)$v='0'.$v;
						$ip[$k].=$v;
					}
					// add colon in between two pairs(bytes) (FFFFFFFF=>FFFF:FFFF)
					$ip[$k]=implode(':',str_split($ip[$k],4));
				}
			}
			return strtoupper(implode(':',$ip));
		}
		/**
		 * Compresses an IP to its binary representation.
		 * @param string $ip A well-formatted full IPv4 or IPv6 address.
		 * @return string Binary representation of address.
		 */
		public static function compressIp($ip){
			if(strpos($ip,':')!==false){ // ipv6
				$ip=str_split(str_replace(':','',self::fixIpv6($ip)),2);
				foreach($ip as $k=>$v)$ip[$k]=chr(hexdec($v));
				return implode('',$ip);
			}elseif(strpos($ip,'.')!==false){ // ipv4
				$ip=explode('.',$ip);
				if(count($ip)!=4)$ip=array(0,0,0,0);
				return chr($ip[0]).chr($ip[1]).chr($ip[2]).chr($ip[3]);
			}else throw new Exception('Unrecognized IP format: '.Security::snohtml($ip));
		}
		const IPV4_LENGTH=4;
		const IPV6_LENGTH=16;
		protected static function faviconGetLink($url){
			$html=self::get($url);
			if($html=='')return ''; // could not get any html whatsoever
			if(preg_match('/<link[^>]+rel="(?:shortcut )?icon"[^>]+?href="([^"]+?)"/si',$html,$m)==0)
				if(preg_match('/<link[^>]+href="([^"]*)"[^>]+rel="(?:shortcut )?icon"/si',$html,$m)==0)
					return ''; // shortcut icon link tag not found
			$m=$m[1];
			if(substr($m,0,1)=='/')$m=$url.$m;
			return $m;
		}
		/**
		 * Attempts to get favicon URL directly. <b>NB:</b> Internal use only.
		 * @param string $url Whatever URL this is, only the domain+favicon.ico are queried.
		 * @return string URL to favicon or empty string if it was not found.
		 */
		protected static function faviconGetDirect($url){
			$url=parse_url($url);
			if(!isset($url['scheme']) || !isset($url['host']))return '';
			$url=$url['scheme'].'://'.$url['host'].'/favicon.ico';
			$data=self::get($url,true);
			return ($data['response']!='' && $data['status']<400) ? $url : '';
		}
		/**
		 * Attempts to find URL to favicon. Behavior is like browsers;<br>
		 *   first it looks for a <link> tag with favicon and then it tries<br>
		 *   looking for /favicon.ico directly.<br>
		 * <b>WARNING:</b> This code involves getting data from a 3rd party, thus slowing down your site considerably.
		 * You should either cache responses (favicon urls) or at leas call this code via AJAX.
		 * @param string $url Original site to look for.
		 * @param string $asHtml If this is true, HTML image is returned instead of favicon url.
		 * @return srting The favicon's URL is found, or an empty string otherwise.
		 */
		public static function faviconGet($url,$asHtml=false){
			$favicon=self::faviconGetLink($url); if($favicon=='')$favicon=self::faviconGetDirect($url);
			return ($asHtml && $favicon!='') ? '<img src="'.Security::snohtml($favicon).'" width="16" height="16" alt=""/>' : $favicon;
		}
	}
	Connect::$USERAGENT='Mozilla/5.0 (compatible; K2F/'.K2F.'; +http://www.covac-software.com/)';

?>