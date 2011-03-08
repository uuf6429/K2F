<?php defined('K2F') or die;

	uses('core/database.php','core/security.php','core/connect.php');

	/**
	 * Class for crawling the web.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 06/09/2010
	 */
	class Crawler {
		/**
		 * @var string The crawler's instance ID, used in database to identify crawler acctivity. cid=msec.rand;
		 */
		public static $instance='';
		/**
		 * @var array List of case-insensitive supported (crawlable) protocols.
		 */
		public static $protocols=array('http','https');
		/**
		 * @var boolean Specifies whether crawler is registered or not.
		 */
		public static $registered=false;
		/**
		 * Performs initialization routines.
		 */
		public static function init(){
			// generate crawler using microtime and randsalt
			self::$instance=microtime();
			// get milliseconds
			self::$instance=substr(self::$instance,2,strpos(self::$instance,' ')-2);
			// add random salt
			self::$instance.='_'.mt_rand();
		}
		/**
		 * Performs finalization routines.
		 */
		public static function fini(){
			if(self::$registered){
				// unregister crawler thread
				Database::db()->rows_update(CFG::get('DB_PRFX').'crawlers',(object)array('id'=>self::$instance,'current_url'=>'','current_time'=>time()),'id');
			}
		}
		/**
		 * Add URL to crawler list.
		 * @param string $url Target URL.
		 * @return boolean Whether successful or not.
		 */
		public static function add($url){
			return (count(Database::db()->rows_load(CFG::get('DB_PRFX').'tocrawl','url="'.Security::escape($url).'"'))>0)
				? false : Database::db()->rows_insert(CFG::get('DB_PRFX').'tocrawl',(object)array('url'=>$url));
		}
		/**
		 * Returns the next URL to be crawled while removing it from crawler list.
		 * @return string|null Next url or null if none left.
		 */
		public static function next(){
			if(!self::$registered){
				// register crawler thread
				Database::db()->rows_insert(CFG::get('DB_PRFX').'crawlers',(object)array('id'=>self::$instance,'current_url'=>'','current_time'=>time()));
				self::$registered=true;
			}
			Database::db()->raw_query('BEGIN');
			$res=Database::db()->rows_load(CFG::get('DB_PRFX').'tocrawl','crawler="" LIMIT 1');
			$res=count($res)>0 ? $res[0] : null;
			if($res)Database::db()->rows_update(CFG::get('DB_PRFX').'tocrawl',(object)array('crawler'=>self::$instance,'id'=>$res->id),'id');
			Database::db()->raw_query('COMMIT');
			return $res ? $res->url : null;
		}
		/**
		 * Crawls the specified URL.
		 * @param string $url 
		 */
		public static function crawl($url){
			// set current crawling url
			Database::db()->rows_update(CFG::get('DB_PRFX').'crawlers',(object)array('id'=>self::$instance,'current_url'=>$url,'current_time'=>time()),'id');
			// get base url and domain from crawl url
			$base_url=self::_url_get_base($url);
			$base_dom=self::_url_get_domain($url);
			// download html content
			$html=Connect::get($url);
			xlog('crawler','download',$url,strlen($html));
			// load content into domdocument
			Errors::hide_errors();
			$dom=new DOMDocument();
			@$dom->loadHTML($html);
			Errors::show_errors();
			// find all links
			$xpath=new DOMXPath($dom);
			$hrefs=$xpath->evaluate("/html/body//a");
			for($i=0; $i<$hrefs->length; $i++){
				// get url from anchor and normalize it
				$href=self::_url_normalize($base_dom,$base_url,$hrefs->item($i)->getAttribute('href'));
				// if the protocol is supported, process link
				if(in_array(self::_url_get_proto($href),self::$protocols))
					if(self::add($href))
						self::link($base_dom,self::_url_get_domain($href));
			}
		}
		/**
		 * Links a domain to another.
		 * @param string $domain1 The domain containing the link (the original/crawl domain).
		 * @param string $domain2 The domain the link points to (the found domain).
		 */
		public static function link($domain1,$domain2){
			$domain1=self::domain($domain1);
			$domain2=self::domain($domain2);
			if(!in_array($domain2->id,$domain1->refers)){
				$domain1->refers[]=$domain2->id;
				$domain1->refers=implode(',',$domain1->refers);
				Database::db()->rows_update(CFG::get('DB_PRFX').'domains',$domain1,'id');
			}
		}
		/**
		 * Creates a new domain if it doesn't exist.
		 * @param string $domain The domain name.
		 * @return object The domain details.
		 */
		public static function domain($domain){
			$dom=Database::db()->rows_load(CFG::get('DB_PRFX').'domains','domain="'.Security::escape($domain).'"');
			if(count($dom)==0){
				$dom=array((object)array('domain'=>$domain,'refers'=>''));
				Database::db()->rows_insert(CFG::get('DB_PRFX').'domains',$dom[0]);
			}
			$dom[0]->refers=explode(',',$dom[0]->refers);
			return $dom[0];
		}
		/**
		 * Returns list of crawler threads.
		 * @return array Array of crawler threads.
		 */
		public static function threads(){
			return Database::db()->rows_load(CFG::get('DB_PRFX').'crawlers');
		}
		/**
		 * Cleans up crawling system.
		 * @return array List of cleanup log messages.
		 */
		public static function clean(){
			$res=array();
			Database::db()->raw_query('DELETE FROM '.CFG::get('DB_PRFX').'tocrawl WHERE crawler!=""');
			$res[]='Cleaned up crawled urls cache (purged '.Database::db()->rows_affected().' row[s])';
			Database::db()->raw_query('DELETE FROM '.CFG::get('DB_PRFX').'crawlers WHERE current_url="" AND current_time<'.strtotime('-2 minutes'));
			$res[]='Cleaned up crawler threads (purged '.Database::db()->rows_affected().' row[s])';
			return $res;
		}
		/**
		 * Returns an array of all domains as objects.
		 * @return array List of object domains (id,domain,refers).
		 */
		public static function domains(){
			$res=Database::db()->rows_load(CFG::get('DB_PRFX').'domains');
			foreach($res as $dom)$dom->refers=explode(',',$dom->refers);
			return $res;
		}
		/**
		 * Returns whether URL is absolute (fully-qualified) or not.
		 * @param string $url Target URL to extract the information from.
		 * @return boolean Whether it is absolute or relative.
		 */
		public static function _url_is_abs($url){
			$url=parse_url($url);
			return isset($url['scheme']);
		}
		/**
		 * Returns the protocol part in lowercase.
		 * @param string $url Target URL to extract the information from.
		 * @return string Lowercase protocol, without the colon, or an empty string on failure.
		 */
		public static function _url_get_proto($url){
			$url=parse_url($url);
			return isset($url['scheme']) ? strtolower($url['scheme']) : '';
		}
		/**
		 * Returns the base url string (ie, without a given filename).
		 * @param string $url The original/full URL.
		 * @return string Base URL including the final slash.
		 */
		public static function _url_get_base($url){
			return dirname($url).'/';
		}
		/**
		 * Normalizes $target URL by taking $base into consideration.
		 * @param string $base_domain The base domain, eg; http://test.com/
		 * @param string $base_url The base url, eg; http://test.com/a/b/
		 * @param string $target The target url to be fixed, eg; ../b/c/file?query
		 * @return string The complete and normalized URL.
		 */
		public static function _url_normalize($base_domain,$base_url,$target){
			// if target is already absolute, return it directly
			if(self::_url_is_abs($target))return $target;
			// if target is starts with a slash...
			if($target[0]=='/'){
				// append to base domain and return
				return $base_domain.$target;
			}else{
				// append to base url and return
				return $base_url.$target;
			}
		}
		/**
		 * Returns the domain part of a url, including any subdomains.
		 * @param string $url The original url.
		 * @return string Domain including any subdomains.
		 */
		public static function _url_get_domain($url){
			$url=parse_url($url);
			return isset($url['host']) ? strtolower($url['host']) : '';
		}
	}
	Crawler::init();
	register_shutdown_function(array('Crawler','fini'));

?>