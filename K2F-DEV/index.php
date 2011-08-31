<?php ## REMOVE FILE FROM PRODUCTION SERVER ##

	function aslog($where){
		static $last=0; static $init=false; $now=time();
		if(!$init){
			?><script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js" type="text/javascript"></script>
			<script type="text/javascript">
				function chk(el){
					var btn=jQuery(el);
					if(btn.text().indexOf('[+]')!=-1){
						btn.text(btn.text().replace('[+]','[-]'));
						btn.next().slideDown();
					}else{
						btn.text(btn.text().replace('[-]','[+]'));
						btn.next().slideUp();
					}
				}
			</script><style type="text/css">
				.chk {
					font: 12px 'Lucida Console'; margin: 8px 0;
				}
				.chk span {
					color: #0AF;
					cursor: pointer;
				}
				.chk div div {
					display:none;
					padding: 2px 2px 2px 16px;
					border-left: 2px solid #DFDFDF;
					background: #F0F0F0;
				}
				.chk div pre {
					margin: 0;
					padding: 0;
				}
			</style><?php
			echo '<div class="chk">';
			$init=true;
		}
		?><div style="<?php if($last && $last+1<$now)echo 'color:red;'; ?>"><?php
		echo date('H:i:s',$now).' - '.str_replace(' ','&nbsp;',$where).' <span onclick="chk(this)">[+]</span><div>';
			$fa=func_get_args(); array_shift($fa); echo_r($fa);
		echo '</div>';
		?></div><?php
		$last=$now;
	};

	if(isset($_REQUEST['file'])){ // small hack to store data without a dedicated file
		switch($_REQUEST['file']){
			case 'throbber':
				header('Content-Type: image/gif');
				die(base64_decode('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'));
			case 'favicon':
				header('Content-Type: image/x-icon');
				die(base64_decode('AAABAAEAEBAAAAEACABoBQAAFgAAACgAAAAQAAAAIAAAAAEACAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlgAAAJcAAACYAAAAmQAAAZkBAAOdAwAVpRUAALoAAADDAAAAygAAAMsAADW3NQAYwxgAOrg6ADm5OQAA0gAAQLlAAD27PQBAu0AAANgAAES9RAAA2QAAAdkBADnFOQBLwUsAPsc+AADgAAA5yjkAA+cDAFbOVgBXzlcAQddBAErZSgA/4D8AOew5AEPqQwCL2osAh9yHAIjdiACX35cAhuaGAInniQCI6YgAlueWAKHjoQCL74sAl++XAJzvnACh9KEAvO68ALvzuwC99r0Au/u7AMn5yQDP988AyP3IAMj+yADM/cwA0fzRAMz/zADN/80A7v3uAO3+7QDu/u4A7/7vAPD/8ADx//EA8v/yAPP/8wD0//QA+//7AP7+/gD//v8A/f/9AP7//gD///8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
					AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
					AAAAAAAAAAAAAAAAAAAAAAAAATEtLS0tLS0tLS0tLS0tLTEtLS0tLS0tLS0tLS0tLS0tLSktLS0pKS0tKSktLS0tLSEtKOjAtLjNFS0hLS0tLS0o5IhoWDwoIDC9GR0tLS0s0HBoVDwkfKigXHkJLR0tLPiMTDwkfODw8NxENREtLS0tDIQkIKjw7OzwmARhAS0dHSz8gByk8Ozs8JgIBEkRLS0pLQhsZNzw8NxACAwEUPUtLR0tBHQ4lJhACAwMABTJLS0tLR0YrBgIDAwQCCzVLS0tLS0tIS0QxJyQsNklLSEtLS0tLSktLS0pKS0tLSktLS0tLS0tLS0tLS0tLS0tLTEtLS0tLS0tLS0tLS0tLTIABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIABAAA='));
			case 'core':
				header('Content-Type: image/png');
				die(base64_decode('iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAABuwAAAbsBOuzj4gAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAGMSURBVHjalVM7bsJQEFw/m88t0qSFJh2WuEKkBImWggNQcAx6zmCJgKD2CRygiIukiOSkpEYOtrH9srtojWPFkTLS6C3Lzsw+YwytNRiGcQMAd/A/vKD23aKKxFEUPyVJjGYKCWTKBDorSsuyoNFoPGJZGEAcxxB+haCUCaapSFylGHEtKAwMZaDQRAN1IQlUyUiENQYs6HY6EsGCKsQ8CIJrDyprrVYrsHs9uPZ5M7ozs9VqUa/GADmbzWA6ncJyuYR+vy8iZrvdps/1BgrpeR4Mh0M+HccpRM1mszArwyrtykM9XH8ymXBrNBqRSB6usOZXQKIBi+fzOex2Oxrm3n6/B4Ft27UbcNp4PJaHRgZU01aSzP3z+ax/3WC73bJAhoVZlsHxeITD4cBv4WazecbxVxZqzWYP+Cbq0+mkkyTRlJCmqUYhMwxD7fs+n+v1+gPnu5RJWlW+QgWUzgFoxrXrup+DweAeAHyN+LFBiknV5DzP+YyiKF8sFl45WSh/51v+8k/wnd8kWfANlBjDDCETRsEAAAAASUVORK5CYII='));
			case 'exts':
				header('Content-Type: image/png');
				die(base64_decode('iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAABuwAAAbsBOuzj4gAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAIYSURBVHjalZM9aBRBGIaf/bkfsDAiQkyKoLHQRsSgtlpYCYp6EMQ0dnZaaS0EW0FBUCRgYxWLE4QUVwrGJCQXLe5QiRdOzQ93Fy5mb3d293ac2WXR40zAFx5mdr/vfWd2hjWklBiGMQKM8X/6qLxfbRKNeZ547ftChZkKdGgMeqRXtm2TyWSuA2kACCFwOg6maWFZpjb3QyI9T2WSvjQNZUzMpmliKd43ijxcvsFCc0Y/67rm3wGmYWhjjK4LXF58fsb4iUmeV57g46WLxD2pbP5sKykYkjsfCjhI8vlDNPw17NwAt9+Nk4sspi6+1b27B7T9Jr4luX/qARveT+pulTPDp2mKDcorVd23e8Dk0j1qnSqjQ4dZ878oaqwFqwRhl4WVVaxoX2ruD0AVllvzTJy7RDvYZMkp4oY7BPhsNPI8Pf+SowNH9rgFYCR/llezs3yrCzY3HbZbBu5WjkF1FleLE9yauQuw+w4eX3jEdvCLy29uMnwwQCLRkvI7o4NZ5n5U6HQ6ZLNZ2X8GJBzI72fLjWjUIZKmIuntdiOOZ4ZY
					X1+nXC7PA5UkPWm4JoSQrutK3/elKzy54znSEZ2YZrslF5cXpeM4slgs1oCTgKG8vZ+QyjYtMoYdX2sURYQEZK0spVJptVAoXAE+SaWeHYTdrgyCQIZhKLtqrlHmePQ8L5qenp77e+WU9Hc+Fhf3VgWopiun+g2q+BM5oGwEZQAAAABJRU5ErkJggg=='));
			case 'libs':
				header('Content-Type: image/png');
				die(base64_decode('iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAABuwAAAbsBOuzj4gAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAIOSURBVHjahVPNaxNBFP/tR0xEbSQ0tioRCXopFhVUrP4D3r36DwiiN6EQb4I3vXkQVPBSodhjj4JnryHJUYpBWwzGdr9mZmfGN2/rNkkLvuXHW3bf72MejGethed5LQAXUJSH/5clDIn7LURR55Mk+ZJlwvsnQaLgx33h94NeqVRsu92+B6AUgFJ5sBdF8H2PEOx3n+HNCARhiDzPmRdOxObhIHCkgLsQArVaDUEhUgr4HAuTAhyZSAGsNdj89BY63sbZ+To+bHzGjWtLaCxcxP0HDxGGIc9qrcGmpYDvsevrV89xd3kRS5db2BpuY+v7DlauX8KdK2fw8kWHTSjpoSNwrPF4jOaxXax9XMePnyPspQKtheN4834dc3MncW5xvphpNp2AnT0Cx7t18yqajVPFYsklSSWyLIPKEuz8GrkEPFsa40AB9XodcZJBawMpFREVd5MTdA4hFM9Q8Q6mEwCc4PefGI36CWhjkKucokpoJaCpS6XLJc7ugBP4hChKkKYpLAkYkxNZEtmBEgldxj+8g/09DGl5AXJYmEJEuyMY
					jHYzSP80+v0+qtWqASCYaC0LrdCibBRFltytlNIqpaxzobOSjrG9Xo+7EJntPOusOXPilgK3MyFsFMdHCrjqdrv8b3X16QaAiuNNCbhhMU0s3R0Gg4F8/OTRuwkyY/Y6e4yjr7UmfKV5iYn6Czr8ZF2UcQBPAAAAAElFTkSuQmCC'));
			case 'apps':
				header('Content-Type: image/png');
				die(base64_decode('iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAABuwAAAbsBOuzj4gAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAIoSURBVHjahVK/a1NRFP7O+9mYdHFRO5jfWSt0EHFQg9AKzv4B4uIQFwloJ4shCJ3E0a2bix2loiBRqGspIUOwqTSknTJIaF7er+N5N+blkQ5+vO/dc++533fOeQkxM949oPtegFsQMKDhPyAgNHXs1z7xFwMRrOXqkxs3XzIFII0TFDd9vodaQzkjvN3dbwCYGmgC0s+RetQE2SmQew5nty7niASxGYmZ3JR4KoNARSx5w/KhXcpg8uGpbADTDoCECCKS0pCkPCZm0OJABOT+gWH7sB82oC0RdNuEZqUAKwPYGZC1DFrKqH3IQLID1u0Q4efn0EwD/G0LZKZVVdINtUow70I3AYaC+hVyuVy13W5/HTsOdF2Pvgk0IrXSv5hiqnM2DOOuaFsGplCJ/skJ8vk8TgcDLKJYLMb3kjAwd0BOxFHVyWQCd30dp0GAgdDc3kapVIrFsvKigUr+Pj5GUS66rourq6voDYe4s7Oj8t1uFzNUKhVcNBAWCoVoXtXBFTG4LCOJmepmBmtvL+7kwgi9Xg/lclmJfmxs4PDgALfF
					7JqY9X0fvLKC4dkZ0ul0CMBVOmZGNputOo7Do9GIx+MxiwF7nse+73Or1eJJvc6HjQaHYajym5svorl00cZ/JIbg19ERFqC6+S4j9NfWIEWw9frVx2bzzWMRB8kO7nlSLVk5CAJFqarY6XTc2rPaewBmpJmR1IvoOoCIlCAW9r7wJzO7SOAvxV4NY0W9AGoAAAAASUVORK5CYII='));
		}
	}

	/**
	 * K2F Preview Screen.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 28/08/2010
	 */

	set_time_limit(0);
	if(!isset($_REQUEST['test'])){
		ob_implicit_flush(true);
		if(ob_get_level()!=0)ob_end_flush();
	}else ob_start();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<link rel="shortcut icon" type="image/x-icon" href="?file=favicon"/>
		<title>Kraken 2 Framework - K2F Preview Screen</title>
		<script type="text/javascript">
			var k2fcon={
				"apcon": typeof console!='undefined' && typeof console.log.apply!='undefined', // IE doesn't support console.log.apply :(
				"log":   function(){ info.apply(window,arguments);  if(k2fcon.apcon)console.log.apply(console,arguments);  },
				"info":  function(){ info.apply(window,arguments);  if(k2fcon.apcon)console.info.apply(console,arguments); },
				"warn":  function(){ warn.apply(window,arguments);  if(k2fcon.apcon)console.warn.apply(console,arguments); },
				"error": function(){ error.apply(window,arguments); if(k2fcon.apcon)console.error.apply(console,arguments);},
				"debug": function(){ debug.apply(window,arguments); if(k2fcon.apcon)console.debug.apply(console,arguments);}
			};
		</script><style type="text/css">
			html, body {
				font-family: Helvetica, Arial, sans-serif;
				overflow: auto;
			}
			body {
				padding-left: 140px;
			}
			.logo-block {
				position: fixed;
				left: 20px;
				top: 20px;
				bottom: 20px;
				padding-right: 20px;
				border-right: 1px dashed #CCC;
				background: #FFF;
			}
			.logo-wrap {
				position: relative;
			}
			.logo-text {
				color: #777777;
				font-size: 48px;
				text-align: center;
			}
			.logo-subt {
				font-weight: bold;
				color: #555555;
				font-size: 11px;
			}
			.logo-cont {
				height: 64px;
			}
			.logo-eyeo {
				background-color: #090;
				border: 2px solid #AFA;
				position: absolute;
				left: 24px;
				top: -8px;
				/* background linear gradient */
				background: #009900; /* for non-css3 browsers */
				filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#009900', endColorstr='#00EE00'); /* for IE */
				background: -webkit-gradient(linear, 45% 0%, 0% 100%, from(#009900), to(#00EE00)); /* for webkit browsers */
				background: -moz-linear-gradient(0% 40% -90deg, #009900,  #00EE00); /* for firefox 3.6+ */
				/* transform */
				transform: rotate(45deg);
				-moz-transform: rotate(45deg);
				-webkit-transform: rotate(45deg);
				-o-transform: rotate(45deg);
				filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=1); /* todo: use matrix */
				/* border radius */
				border-radius: 240px 0 240px 0;
				-moz-border-radius: 240px 0 240px 0;
				-webkit-border-radius: 240px 0 240px 0;
				behavior: url('css/border-radius.htc');
			}
			.logo-eyei {
				background-color: #CFC;
				padding: 16px;
				margin: 24px 5px;
				/* border radius */
				border-radius: 16px;
				-moz-border-radius: 16px;
				-webkit-border-radius: 16px;
				behavior: url('css/border-radius.htc');
			}
			td {
				color: #777;
			}
			.box {
				background: #F5F5F5;
				border: 1px dotted #DDDDDD;
				margin: 0 2px;
				padding: 0 2px;
			}
			#srvProductionWarn {
				color: #A00;
				background-color: #FEE;
				border: 1px solid #D00;
				padding: 8px;
				margin-bottom: 8px;
				font-size: 12px;
			}
			#srvTestsNotice {
				color: #0A0;
				background-color: #EFE;
				border: 1px solid #0D0;
				padding: 8px;
				margin-bottom: 8px;
				font-size: 12px;
			}
		</style>
		<link rel="stylesheet" type="text/css" href="libs/fvlogger2/logger.css" />
		<script type="text/javascript" src="libs/dracula-0.0.3a2/dracula-0.0.3a2.js"></script>
	</head><body>
		<script type="text/javascript" src="libs/fvlogger2/logger.js"></script>
		<div class="logo-block"><div class="logo-wrap"><div class="logo-cont">
			<div class="logo-eyeo"><div class="logo-eyei"><!----></div></div>
		</div><div class="logo-text">K2F</div><div class="logo-subt">The Framework</div></div></div>

		<?php if($_SERVER['SERVER_ADDR']!='127.0.0.1'){ ?>
			<div id="srvProductionWarn"><b>WARNING!</b> This script should be removed from the server in a production environment!</div>
		<?php } ?>

		<div id="srvTestsNotice">
			<b>Notice:</b> You can also run the following QA / reliability test scripts: <select id="tests-list" onchange="run_test(this.value);"><option value="" selected="selected">No thanks!</option><?php
				foreach(glob('tests/*.php') as $file)
					echo '<option value="'.htmlspecialchars(basename($file),ENT_QUOTES).'">'.htmlspecialchars(ucwords(str_replace(array('-','.php','_'),' ',basename($file))),ENT_QUOTES).'</option>';
			?></select><script type="text/javascript">
				window.onload=function(){ document.getElementById('tests-list').value=''; }
				function run_test(value){ if(value!='')location.href='?test='+value.replace('.php',''); }
			</script> &mdash; or &mdash; <form action="" style="display:inline;"><input type="submit" name="update" value="Update K2F"/></form>
		</div><?php

			if(isset($_REQUEST['update'])){

				// preconfigure and load K2F
				$GLOBALS['K2F_AUTOCONF']=array('DEBUG_MODE'=>'none');
				require_once('boot.php');
				// preconfigure and load updater
				define('ABS_WWW',CFG::get('ABS_WWW'));
				define('ABS_K2F',CFG::get('ABS_K2F'));
				define('REL_WWW',CFG::get('REL_WWW'));
				define('REL_K2F',CFG::get('REL_K2F'));
				require_once('update.php');

			}else{

				?><div id="fvlogger"><!----></div>

				<script type="text/javascript">k2fcon.log('Initializing...');</script><?php
					if(isset($_REQUEST['test']))$GLOBALS['K2F_AUTOCONF']=array('DEBUG_VERBOSE'=>false);
					require_once('boot.php');
					if(isset($_REQUEST['test'])){
						CFG::set('DEBUG_VERBOSE',true);
						?><script type="text/javascript">k2fcon.log('--- TESTING ---');</script><?php
						require_once('tests/'.Security::filename($_REQUEST['test']).'.php');
					}
				?><script type="text/javascript">k2fcon.log('Finished!');</script><?php

			}

			if(ob_get_level()!=0)ob_flush();

//			uses('exts/wkhtmltox.php');
//
//			$pdf=new WKPDF();
//			$pdf->set_url('http://localhost/K2F/cms/cms-wordpress/');
//			$pdf->render();
//			$pdf->output(WKPDF::$PDF_SAVEFILE,mt_rand().'.pdf');

//			uses('core/connect.php');
//
//			function check_sec($dir){
//				xlog('Checking folder "'.$dir.'"...');
//				foreach(glob(CFG::get('ABS_K2F').$dir.'/*') as $file)
//					if(file_exists($file)){
//						if(is_file($file) && strtolower(substr($file,-4))=='.php')
//							xlog((Connect::get('http://'.CFG::get('SITE_NAME').'/'.CFG::get('REL_K2F').$dir.'/'.basename($file))=='')
//								? 'Passed: "'.basename($file).'".' : 'Error: "'.basename($file).'".' );
//						if(is_dir($file))
//							check_sec($dir.'/'.basename($file));
//					}
//			}
//
//			// perform security check on file inclusion
//			foreach(array('apps','core','exts') as $dir)check_sec($dir);
//
//			// perform compliance check on phpdoc in files
//

			// Path resolver test
			/*
			xlog('relative',truepath('../devel/core/ajax.php'));
			xlog('absolute',truepath('C:\\Windows\\notepad.exe'));
			// this works on my pc because www is a hardlink to dropbox
			xlog('hardlink',truepath('C:\\wamp\\www\\K2F\\devel\\index.php'));
			*/

			// Router API Test
			/*
			class Bob {
				static function say_hi(){
					xlog('Bob said hi',$_SERVER['REQUEST_URI'],$_REQUEST);
				}
				static function say_bye(){
					xlog('Bob said bye',$_SERVER['REQUEST_URI'],$_REQUEST);
				}
			}

			Router::add('/K2F/devel/hi.html',array('Bob','say_hi'));
			Router::add('/K2F/devel/bye.asp',array('Bob','say_bye'));
			*/

			// PHPDoc API Test (finds all first @version in core files)
			/*
			uses('exts/phpdoc.php');
			foreach(glob('core/*.php') as $file){
				$pdcs=PhpDoc::parse(file_get_contents($file));
				if(isset($pdcs[0])){
					$tags=$pdcs[0]->tags('@version');
					$vrsn=isset($tags[0]) ? $tags[0]->content : 'no version';
				}else $vrsn='no comments';
				xlog($file,'version: '.$vrsn,$pdcs);
			}
			*/
			
			//echo '<pre>'.htmlspecialchars(print_r(PhpDoc::parse($code),true),ENT_QUOTES).'</pre>';

			// DropBox API Test
			//uses('exts/filesystem.dropbox.php');
			//Dropbox::$APIKEY = 'v5nzjdoqhwsn0fy';
			//Dropbox::$SECRET = 'jr8vlxdej5wa971';
			//Dropbox::login('uuf6429@gmail.com','--pass--');
			//Dropbox::account_details();

			// FTP Filesystem Test
			//$dir='ftp://anonymous:anonymous@ftp.microsoft.com/';
			//$dir='ftp://anonymous:anonymous@ftp.mozilla.org/';
			//xlog(FtpStreamWrapper::getDirListing($dir));
			//xlog('pub/dir',stat($dir.'pub'),filetype($dir.'pub'));
			//xlog('index.html/file',stat($dir.'index.html'),filetype($dir.'index.html'));

			//xlog(file_get_contents($dir.'index.html'));

			//if(($dh=opendir($dir))){
			//	while(($file=readdir($dh))!==false)
			//		xlog('filename: '.$file.' : filetype: '.filetype($dir.$file));
			//	closedir($dh);
			//}else xlog('Error: Fatal error, could not open FTP directory!');

			//echo '<pre>'.print_r(glob('ftp://anonymous:anonymous@ftp.microsoft.com'),true).'</pre>';

			// Menu system test
			/*
			uses('exts/menu.php');
			$main=new Menu();
			$file=$main->add('File');
			$file->add('New');
			$file->add('Open');
			$file->add('Save');
			$file->add('Exit');
			$edit=$main->add('Edit');
			$edit->add('Cut');
			$edit->add('Copy');
			$edit->add('Paste');
			$help=$main->add('Help');
			$help->add('Help Contents');
			$help->add('Check for Updates');
			$help->add('About');
			$main->render();
			*/

			// Favicon/connect system test
			/*
			echo Connect::faviconGet('http://google.com/',true);
			echo Connect::faviconGet('http://msn.com/',true);
			echo Connect::faviconGet('http://keen-advertising.com/',true);
			echo Connect::faviconGet('http://maltabarter.com/',true);
			echo Connect::faviconGet('http://mail.google.com/',true);
			echo Connect::faviconGet('http://yahoo.com/',true);
			echo Connect::faviconGet('http://twitter.com/',true);
			echo Connect::faviconGet('http://facebook.com/',true);
			*/
		?>
	</body>
</html>