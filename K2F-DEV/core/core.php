<?php defined('K2F') or die;

	/**
	 * This is the system's core;<br>
	 *   the glue that keeps stuff together,<br>
	 *   the gears that drives the system<br>
	 *   and the cloth that hides hideous abstractions!
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 17/08/2010 - Original implementation code.
	 *          05/12/2010 - Minor fix to console code (FireBug Issue 3767).
	 *          07/01/2011 - Added script_ functions.
	 *          10/01/2010 - Fixed tab constants (both were wrong) and added htab constant.
	 *          10/01/2010 - Add error constants not available in PHP 4.
	 *          14/01/2011 - xlog can now be temporally deactivated with the xlog_enable function.
	 *          14/01/2011 - Did some optimization in xlogr, now performs faster when logging is off (or 'none').
	 *          15/01/2011 - Fix issue introduced by 14.1/01/2011 in xlogr.
	 */

	/**
	 * @var string Carriage Return character.
	 */
	defined('CR') or define('CR',chr(13));
	/**
	 * @var string Line Feed character.
	 */
	defined('LF') or define('LF',chr(10));
	/**
	 * @var string Windows end of line marker.
	 */
	defined('CRLF') or define('CRLF',CR.LF);
	/**
	 * @var string Horizontal tab character.
	 */
	defined('HTAB') or define('HTAB',chr(9));
	/**
	 * @var string Vertical tab character.
	 */
	defined('VTAB') or define('VTAB',chr(11));
	/**
	 * @var string Tab character (alias of horizontal tab).
	 */
	defined('TAB') or define('TAB',HTAB);
	/**
	 * @var string Space character.
	 */
	defined('SPACE') or define('SPACE',chr(32));
	/**
	 * @var string Null character; actually PHP has it's own.
	 */
	defined('NULL') or define('NULL',chr(0));

	/**
	 * Date timezone warning hotfix
	 */
	date_default_timezone_set('Europe/Malta');
	
	/**
	 * Enable to have PHP suggest changes to your code which will ensure the best interoperability and forward compatibility of your code.
	 */
	defined('E_STRICT') or define('E_STRICT', 2048);
	
	/**
	 * Catchable fatal error. It indicates that a probably dangerous error occured, but did not leave the Engine in an unstable state.
	 * If the error is not caught by a user defined handle (see also set_error_handler()), the application aborts as it was an E_ERROR.
	 */
	defined('E_RECOVERABLE_ERROR ') or define('E_RECOVERABLE_ERROR ', 4096);
	
	/**
	 * Run-time notices. Enable this to receive warnings about code that will not work in future versions.
	 */
	defined('E_DEPRECATED') or define('E_DEPRECATED', 8192);
	
	/**
	 * User-generated warning message. This is like an E_DEPRECATED, except it is generated in PHP code by using the PHP function trigger_error().
	 */
	defined('E_USER_DEPRECATED') or define('E_USER_DEPRECATED', 16384);

	/// Bitwise Functionality ///
	// WARNING: Experimental functionality! ///
	/**
	 * Turns a specific bit in a value to on.
	 * @param integer $val Value to change.
	 * @param integer $bit Bit position.
	 * @return integer The modified value.
	 */
	function bit_set($val,$bit){
		return bit_isset($val,$bit) ? $val : $val+'0x'.dechex(1<<($bit-1));
	}
	/**
	 * Turns a specific bit in a value to off.
	 * @param integer $val Value to change.
	 * @param integer $bit Bit position.
	 * @return integer The modified value.
	 */
	function bit_unset($val,$bit){
		return bit_isset($val, $bit) ? $val^(0+('0x'.dechex(1<<($bit-1)))) : $val;
	}
	/**
	 * Returns whether a specific bit is on or off.
	 * @param integer $val Value to check.
	 * @param integer $bit Bit position.
	 * @return boolean True if on, false otherwise.
	 */
	function bit_isset($val,$bit){
		return (boolean)($val&(0+('0x'.dechex(1<<($bit-1)))));
	}
	/**
	 * Returns a string representation of the bits in $val.
	 * @param integer $val Value to change.
	 * @param integer $len Representation length (number of bits to display).
	 * @return string A textual representation of the bits in $val.
	 */
	function bit_debug($val,$len=32){
		$ret='';
		for($j=$len; $j>0; $j--)$ret.=bit_isset($val,$j) ? '1' : '0';
		return $ret;
	}

	/**
	 * Constructs a GET url given certain details.
	 * @param string $page Path and file name relative to REL_WWW.
	 * @param array $args Array of arguments.
	 * @return string The newly constructed url.
	 */
	if(!function_exists('url')){
		function url($page,$args=array()){
			xlog('Error: K2F Function `url` has been deprecated!');
			die('WARNING: A function has been called which has been deprecated!');
			$a=array();
			foreach($args as $k=>$v){
				// if value is an object, convert to an array
				if(is_object($v))$v=get_object_vars($v);
				// if value is an array, write multiple arguments
				if(is_array($v)){
					foreach($v as $prop=>$val){
						$a[]=urlencode($k).'[]';
						$a[]=urlencode($val);
					}
				// if it scalar (float,integer,string...) add argument
				}elseif(is_scalar($v)){
					$a[]=urlencode($k);
					$a[]=urlencode($v);
				}else xlog('Error: Illegal data type "'.gettype($v).'"',$args,debug_backtrace());
			}
			return CFG::get('REL_K2F').$page.'/'.implode('/',$a);
		}
	}

	/**
	 * Debug log rendering function. The debug log goes according to DEBUG_MODE variable.
	 * @param mixed Any number of arguments to be included in the log.
	 */
	function xlog(){
		$args=func_get_args();
		echo call_user_func_array('xlogr',$args);
	}

	/**
	 * By default, xlog should be on. NEVER turn this off manually. It is controlled by xlog_enable
	 * function and to be used in special occasions only.
	 */
	$GLOBALS['K2FXLOG']=array(true);

	/**
	 * If $mode is boolean, it turns xlog on or off. Otherwise, returns whether xlog is on or off.
	 * @param boolean $mode (Optional) If set, it switches xlog on or off.
	 * @return boolean The current state of whether xlog is on or off.
	 * @deprecated Not needed anymore after the introduction of bufferred logging.
	 */
	function xlog_enable($mode=null){
		xlog('Warning: function "xlog_enable" is deperecated.');
		if($mode!==null)$GLOBALS['K2FXLOG'][]=$mode;
		return $GLOBALS['K2FXLOG'][count($GLOBALS['K2FXLOG'])-1];
	}

	/**
	 * Reverts to the previous xlog logging state.
	 * @deprecated Not needed anymore after the introduction of bufferred logging.
	 */
	function xlog_revert(){
		xlog('Warning: function "xlog_enable" is deperecated.');
		array_pop($GLOBALS['K2FXLOG']);
	}

	/**
	 * Function used to decide whether we should output debug info or not.
	 * @ignore Do not call function directly.
	 */
	function xlogr_auth(){
		static $result=null;
		if($result===null){
			$result=true; // TODO: Actually evaluate some sort of condition.
		}
		return $result;
	}
	
	/**
	 * Debug log output function. The debug log goes according to DEBUG_MODE variable.
	 * @param mixed Any number of arguments to be included in the log.
	 * @return string Debug log data.
	 * @todo $_REQUEST['ajax'] is legacy code. Modify ajax.php class to turn off xlog and then modify this function.
	 *       <br>If framework updates both files at the same time, nothing should break up. Hopefully.
	 */
	function xlogr(){
		if(!isset($_REQUEST['ajax']) && ($logmode=CFG::get('DEBUG_MODE'))!='none' && xlogr_auth()){
			$time=microtime(); $time='"'.date(/*'d-m-y '.*/'H:i:s ').substr(substr($time,strpos('.',$time)+2),0,4).'","-"';
			$result=''; $mode='info'; foreach(func_get_args() as $k=>$v){
				if((is_string($k) && stripos($k,'warning:')!==false) || (is_string($v) && stripos($v,'warning:')!==false))$mode='warn';
				if((is_string($k) && stripos($k,'debug:')!==false)   || (is_string($v) && stripos($v,'debug:')  !==false))$mode='debug';
				if((is_string($k) && stripos($k,'error:')!==false)   || (is_string($v) && stripos($v,'error:')  !==false))$mode='error';
				if((is_string($k) && stripos($k,'failure:')!==false) || (is_string($v) && stripos($v,'failure:')!==false))$mode='error';
				if((is_string($k) && stripos($k,'fatal:')!==false)   || (is_string($v) && stripos($v,'fatal:')  !==false))$mode='error';
			}
			switch($logmode){
				case 'html':
					$result.='<div style="color:#FFF;background:#000;font-family:\'Lucida Console\';font-size:11px;padding:4px;">';
						foreach(func_get_args() as $arg)
							$result.='<div style="margin:0 4px;padding:4px;border-bottom:1px dashed #777;"><i>'.$time
								   .'</i> - '.( (is_array($arg) || is_object($arg)) ? '<pre>'.htmlspecialchars(print_r($arg,true),ENT_QUOTES).'</pre>' : $arg ).'</div>';
						if(func_num_args()==0)$result.='<div style="margin:4px;padding:4px;">No arguments</div>';
					$result.='</div>';
					break;
				case 'comment':
					$result.='<!-- '.$time.CRLF;
						foreach(func_get_args() as $arg)
							$result.=(is_array($arg) || is_object($arg)) ? print_r($arg,true).CRLF : $arg.CRLF;
						if(func_num_args()==0)$result.='No arguments'.CRLF;
					$result.='-->';
					break;
				case 'console':
					$a=array($time); foreach(func_get_args() as $arg)$a[]=@json_encode($arg);
					if(!CFG::get('DEBUG_BUFFERED',true)){
						// This is the old code; JS was spit out anywhere...
						if(!isset($GLOBALS['console_fix'])){
							$result.=str_replace(array(CR,LF,TAB,'  '),'','<script type="text/javascript">
								if(typeof window.k2fcon==\'undefined\'){
									window.k2fcon={
										"log": function(){   console.log.apply(console,arguments);   },
										"info": function(){  console.info.apply(console,arguments);  },
										"warn": function(){  console.warn.apply(console,arguments);  },
										"error": function(){ console.error.apply(console,arguments); },
										"debug": function(){ console.debug.apply(console,arguments); }
									};
								}
								if(typeof window.console==\'undefined\'){
									window.console={
										"log": function(){ },
										"info": function(){ },
										"warn": function(){ },
										"error": function(){ },
										"debug": function(){ }
									};
								}
							</script>');
							$GLOBALS['console_fix']=true;
							$result.='<script type="text/javascript">k2fcon.'.$mode.'('.implode(',',$a).');</script>';
						}
					}else{
						// This is the new code, JS is written at the end of file, fixing loads of trouble...
						if(!isset($GLOBALS['k2f_console_buff'])){
							$GLOBALS['k2f_console_buff']=str_replace(array(CR,LF,TAB,'  '),'','<script type="text/javascript">
								if(typeof window.k2fcon==\'undefined\'){
									window.k2fcon={
										"log": function(){   console.log.apply(console,arguments);   },
										"info": function(){  console.info.apply(console,arguments);  },
										"warn": function(){  console.warn.apply(console,arguments);  },
										"error": function(){ console.error.apply(console,arguments); },
										"debug": function(){ console.debug.apply(console,arguments); }
									};
								}
								if(typeof window.console==\'undefined\'){
									window.console={
										"log": function(){ },
										"info": function(){ },
										"warn": function(){ },
										"error": function(){ },
										"debug": function(){ }
									};
								}
							</script>');
							function _xlogr_render(){ echo $GLOBALS['k2f_console_buff']; $GLOBALS['k2f_console_buff']=true; }
							register_shutdown_function('_xlogr_render');
						}elseif(is_string($GLOBALS['k2f_console_buff'])){
							$GLOBALS['k2f_console_buff'].='<script type="text/javascript">k2fcon.'.$mode.'('.implode(',',$a).');</script>';
						}else{
							return '<script type="text/javascript">k2fcon.'.$mode.'('.implode(',',$a).');</script>';
						}
					}
					break;
			}
			return $result;
		}
	}

	/**
	 * This is just eye-candy to make method passing more understandable.
	 * @param string $class Class name.
	 * @param string $method Method name.
	 * @return array The class method!
	 */
	function ClassMethod($class,$method){
		return array($class,$method);
	}

	/**
	 * This is a merge of die and print_r.
	 * It can accept any number of arguments.
	 */
	function die_r(){
		$arg=func_num_args()==1 ? func_get_arg(0) : func_get_args();
		die('<pre>'.htmlspecialchars(print_r($arg,true),ENT_QUOTES).'</pre>');
	}
	
	/**
	 * ANDs all arguments together (or just the first one when it is an array).
	 * <br>See example on why it is useful.
	 * @param array Array of values to test. Function also accepts multiple arguments.
	 * @return boolean Operation result.
	 * @example
	 *     // if test() returns false, the others won't be executed.
	 *     if( test() && test1() && test2() )die('ok');
	 *     // all of the functions are called this time
	 *     if( and_r( test() && test1() && test2() ) )die('ok');
	 */
	function and_r($vals){
		$a=func_get_args(); if(func_num_args()!=1)return and_r($a);
		foreach($vals as $val)if(!$val)return false;
		return true;
	}
	
	/**
	 * ORs all arguments together (or just the first one when it is an array).
	 * <br>See example on why it is useful.
	 * @param array Array of values to test. Function also accepts multiple arguments.
	 * @return boolean Operation result.
	 * @example
	 *     // if test() returns true, the others won't be executed.
	 *     if( test() && test1() && test2() )die('ok');
	 *     // all of the functions are called this time
	 *     if( or_r( test() && test1() && test2() ) )die('ok');
	 */
	function or_r($vals){
		$a=func_get_args(); if(func_num_args()!=1)return and_r($a);
		foreach($vals as $val)if($val)return true;
		return false;
	}

	/**
	 * Returns an array of non-empty parameters.
	 * <br>Accepts any number of parameters.
	 * @return array Array of non-empty values.
	 */
	function array_filled(){
		$r=func_get_args();
		foreach($r as $k=>$v)if(!$v)unset($r[$k]);
		return array_values($r);
	}

	/**
	 * Wrap script tag around some (presumably) javascript code.
	 * @param string $javascript The javascript to wrap.
	 * @return string HTMLized javascript.
	 */
	function script_wrap($javascript){
		return '<script type="text/javascript">'.$javascript.'</script>';
	}

	/**
	 * Any output after calling this function is threated as javascript.
	 */
	function script_start(){
		ob_start();
	}

	/**
	 * Any output before calling this function is threated as javascript.
	 */
	function script_end(){
		echo script_wrap(ob_get_clean());
	}

?>