<?php
	defined('K2F') or die; // K2F not set?! Unforgivable!!
	
	defined('K2FUS') or define('K2FUS','https://174.121.25.12/');
	defined('K2FUAGENT') or define('K2FUAGENT','Mozilla/4.0 (compatible; K2FUC 1.0; '.php_uname('s').' '.php_uname('r').')');

	/**
	 * Reads data from a URL.
	 * @param string $url The URL to read.
	 * @param integer $timeout_ms Connection timeout in milliseconds.
	 * @param string $onprogress A callback function with parameters $download_size, $downloaded, $upload_size and $uploaded.
	 * @return string|null Data or null on error.
	 */
	function readurl($url,$timeout_ms=-1,$onprogress=null){
		curl_setopt($ch,CURLOPT_URL,$url);
		if(substr($url,0,8)=='https://'){
			curl_setopt($ch,CURLOPT_HTTPAUTH,CURLAUTH_ANY);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		}
		if($onprogress){
			curl_setopt($ch,CURLOPT_NOPROGRESS,false);
			curl_setopt($ch,CURLOPT_PROGRESSFUNCTION,$onprogress);
		}
		if($timeout_ms!=-1)curl_setopt($ch,CURLOPT_TIMEOUT_MS,$timeout_ms);
		curl_setopt($ch,CURLOPT_HEADER,false);
		curl_setopt($ch,CURLOPT_USERAGENT,K2FUAGENT);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($ch,CURLOPT_MAXREDIRS,50);
		$data=curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	class K2FUpdater {
		protected static $inited=false;
		protected static $next_update=null;
		protected static $next_upgrade=null;
		/**
		 * Returns API url to call.
		 * @param string $method Name of API method to call.
		 * @param array $params Array of sequential parameters.
		 * @return string K2FUS API URL.
		 */
		protected static function call($method,$params){
			foreach($params as $i=>$param)$params[$i]='&p[]='.urlencode($param);
			return K2FUS.'updt.php?mtd='.urlencode($method).implode('',$params);
		}
		/**
		 * Run some requests to discover versions and stuff.
		 */
		protected static function init(){
			$data=@json_decode(readurl(self::call('version',array(K2F))));
			if($data->success){
				self::$next_update=$data->next_update;
				self::$next_upgrade=$data->next_upgrade;
			}
			self::$inited=true;
		}
		/**
		 * @return string|null The next update or null if none.
		 */
		public static function next_update(){
			if(!self::$inited)self::init();
			return self::$next_update;
		}
		/**
		 * @return string|null The next upgrade or null if none.
		 */
		public static function next_upgrade(){
			if(!self::$inited)self::init();
			return self::$next_upgrade;
		}
		/**
		 * old new action
		 *      x  new is ignored
		 *  x      old is ignored
		 *  x   x  new replaces old
		 */
		const TYPE_SYNCRONIZE=0;
		/**
		 * old new action
		 *      x  new is added
		 *  x      old is ignored
		 *  x   x  new replaces old
		 */
		const TYPE_UPDATE=1;
		/**
		 * old new action
		 *      x  new is added
		 *  x      old is removed
		 *  x   x  new replaces old
		 */
		const TYPE_CLONE=2;
		/**
		 * Performs the update given update type and zip file.
		 * @param integer $type Type of update (use TYPE_* constants).
		 * @param string $zip Location of zipfile.
		 */
		public static function update_perform($type,$zip){

		}
		/**
		 * Download K2F install file from UpdateServer.
		 * @param string $version The K2F version to download.
		 * @param string $onprogress Function called each time download progresses.
		 *                           A single argument is passed, of type K2FProgress.
		 * @return string Path to downloaded zip file (on server).
		 */
		public static function update_download($version,$onprogress=null){
			$file=''; while($file=='' || file_exists($file))$file=sys_get_temp_dir().uniqid('update',true).'.zip';
			@file_put_contents($file,readurl(self::call('zipfile',array(K2F)),-1,$onprogress));
			return $file;
		}
	}

?>