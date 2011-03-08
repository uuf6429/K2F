<?php defined('K2F') or die;

	uses('core/security.php');

	/**
	 * This file is an experimental software for dealing with the client's request
	 * as well as issuing a response.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 27/02/2009 - Original implementation code.
	 */

	/**
	 * The main class for dealing with client request and issuing a response.
	 */
	class Connection {
		public static function get($name){
			return new SafeType($_GET['name']);
		}
		public static function post($name){
			return new SafeType($_POST['name']);
		}
		public static function request($name){
			return new SafeType($_REQEUEST['name']);
		}
		public static function status($code=200,$msg=null){
			if($msg===null)
				switch($code){
					// 1xx Informational
					case 100: $msg='HTTP/1.1 100 Continue'; break;
					case 101: $msg='HTTP/1.1 101 Switching Protocols'; break;
					case 102: $msg='HTTP/1.1 102 Processing'; break;
					// 2xx Success
					case 200: $msg='HTTP/1.0 200 OK'; break;
					case 201: $msg='HTTP/1.0 201 Created'; break;
					case 202: $msg='HTTP/1.0 202 Acepted'; break;
					case 203: $msg='HTTP/1.0 203 Non-Authoritative Information'; break;
					case 204: $msg='HTTP/1.0 204 No Content'; break;
					case 205: $msg='HTTP/1.0 205 Reset Content'; break;
					case 206: $msg='HTTP/1.0 206 Partial Content'; break;
					case 207: $msg='HTTP/1.0 207 Multi-Status'; break;
					case 226: $msg='HTTP/1.0 226 IM Used'; break;
					// 3xx Redirection
					case 300: $msg='HTTP/1.0 300 Multiple Choices'; break;
					case 301: $msg='HTTP/1.0 301 Moved Permanently'; break;
					case 302: $msg='HTTP/1.0 302 Found'; break;
					case 303: $msg='HTTP/1.0 303 See Other'; break;
					case 304: $msg='HTTP/1.0 304 Not Modified'; break;
					case 305: $msg='HTTP/1.0 305 Use Proxy'; break;
					case 306: $msg='HTTP/1.0 306 Switch Proxy'; break;
					case 307: $msg='HTTP/1.0 307 Temporary Redirect'; break;
					// 4xx Client Error
					case 400: $msg='HTTP/1.0 400 Bad Request'; break;
					case 401: $msg='HTTP/1.0 401 Unauthorized'; break;
					case 402: $msg='HTTP/1.0 402 Payment Required'; break;
					case 403: $msg='HTTP/1.0 403 Forbidden'; break;
					case 404: $msg='HTTP/1.0 404 Not Found'; break;
					case 405: $msg='HTTP/1.0 405 Method Not Allowed'; break;
					case 406: $msg='HTTP/1.0 406 Not Acceptable'; break;
					case 407: $msg='HTTP/1.0 407 Proxy Authentication Required'; break;
					case 408: $msg='HTTP/1.0 408 Request Timeout'; break;
					case 409: $msg='HTTP/1.0 409 Conflict'; break;
					case 410: $msg='HTTP/1.0 410 Gone'; break;
					case 411: $msg='HTTP/1.0 411 Length Required'; break;
					case 412: $msg='HTTP/1.0 412 Precondition Failed'; break;
					case 413: $msg='HTTP/1.0 413 Request Entity Too Large'; break;
					case 414: $msg='HTTP/1.0 414 Request-URI Too Long'; break;
					case 415: $msg='HTTP/1.0 415 Unsupported Media Type'; break;
					case 416: $msg='HTTP/1.0 416 Requested Range Not Satisfiable'; break;
					case 417: $msg='HTTP/1.0 417 Expectation Failed'; break;
					case 418: $msg='HTTP/1.0 418 I\'m a teapot'; break;
					case 422: $msg='HTTP/1.0 422 Unprocessable Entity'; break;
					case 423: $msg='HTTP/1.0 423 Locked'; break;
					case 424: $msg='HTTP/1.0 424 Failed Dependency'; break;
					case 425: $msg='HTTP/1.0 425 Unordered Collection'; break;
					case 426: $msg='HTTP/1.0 426 Upgrade Required'; break;
					case 444: $msg='HTTP/1.0 444 No Response'; break;
					case 449: $msg='HTTP/1.0 449 Retry With'; break;
					case 450: $msg='HTTP/1.0 450 Blocked by Windows Parental Controls'; break;
					case 499: $msg='HTTP/1.0 499 Client Closed Request'; break;
					// 5xx Server Error
					case 500: $msg='HTTP/1.0 500 Internal Server Error'; break;
					case 501: $msg='HTTP/1.0 501 Not Implemented'; break;
					case 502: $msg='HTTP/1.0 502 Bad Gateway'; break;
					case 503: $msg='HTTP/1.0 503 Service Unavailable'; break;
					case 504: $msg='HTTP/1.0 504 Gateway Timeout'; break;
					case 505: $msg='HTTP/1.0 505 HTTP Version Not Supported'; break;
					case 506: $msg='HTTP/1.0 506 Variant Also Negotiates'; break;
					case 507: $msg='HTTP/1.0 507 Insufficient Storage'; break;
					case 509: $msg='HTTP/1.0 509 Bandwidth Limit Exceeded'; break;
					case 510: $msg='HTTP/1.0 510 Not Extended'; break;
					// The default (?)
					$msg='HTTP/1.1 '.(int)$code.' Unknown';
				}
			if(!headers_sent())
				header($msg,true,$code);
		}
	}

	class SafeType {

		/// VARS SECTION ///

		protected $raw='';

		/// MAGIC METHODS ///

		/**
		 * Creates a new instance.
		 * @param string $raw The raw data to wrap.
		 */
		public function __construct($raw=''){
			$this->raw=$raw;
		}
		/**
		 * The default is secured against HTML and possibly JS.
		 * @return string HTML-encoded string.
		 */
		public function __toString(){
			return $this->s_html();
		}

		/// SQL TYPES ///

		/**
		 * @return mixed The escaped data (good for SQL statements).
		 */
		public function s_sql(){
			return Security::escape($this->raw);
		}

		/// JS(ON) TYPES ///

		/**
		 * @return string A PHP string respresenting data as a JS string (contained within double quotes!).
		 */
		public function s_js(){
			return @json_encode($this->s());
		}

		/**
		 * @return string A PHP string representing JS data encoded as JSON.
		 */
		public function m_js(){
			return @json_encode($this->r());
		}

		/// HTML TYPES ///

		/**
		 * @return string The HTML-escaped data.
		 */
		public function s_html(){
			return Security::snohtml($this->s());
		}
		
		/// PHP TYPES ///

		/**
		 * @return mixed The raw data.
		 */
		public function r(){
			return $this->raw;
		}
		/**
		 * @return string An string (text) representation of data.
		 */
		public function s(){
			return (string)$this->raw;
		}
		/**
		 * @return integer An integeral (whole number) representation of data.
		 */
		public function i(){
			return (int)$this->raw;
		}
		/**
		 * @return float A floating-point (decimal) representation of data.
		 */
		public function f(){
			return (float)$this->raw;
		}
		/**
		 * @return boolean A boolean (true or false) representation of data.
		 */
		public function b(){
			return (boolean)$this->raw;
		}
	}

?>