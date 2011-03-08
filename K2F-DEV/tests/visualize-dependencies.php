<?php

	/**
	 * This works for statically-linked occurances of the uses clause (function).
	 * If, instead, you are loading files dynamicaly (as per example below),
	 * this script won't work.
	 * Good:  uses('core/security.php');
	 * Bad:   uses('core/'.$file);
	 */

	function uq($string){ // removes double and single quotes from a string
		return str_replace(array("'",'"'),'',$string);
	}

	$GLOBALS['uses']=array();
	function check_deps($dir){
		static $indent=0; $indent++;
		$stctkns=array(T_CONSTANT_ENCAPSED_STRING,T_WHITESPACE,T_LNUMBER,T_DNUMBER);
		xlog(str_repeat('  ',$indent).'Checking folder "'.$dir.'"...'); $c=0;
		foreach(glob(CFG::get('ABS_K2F').$dir.'/*') as $file)
			if(file_exists($file)){
				if(is_file($file) && strtolower(substr($file,-4))=='.php' && strtolower(basename($file))!='index.php'){
					$inuses=false; $files=array(); $tknbuf=array(); $tkndyn=false;
					foreach(token_get_all(file_get_contents($file)) as $token){
						// detect start of uses clause/function
						if(!$inuses && is_array($token) && $token[0]==T_STRING && $token[1]=='uses'){
							$inuses=true;
							continue; // skip the rest
						}
						// if inside clause and token is a string...
						if($inuses && is_array($token) && $token[0]==T_CONSTANT_ENCAPSED_STRING)$tknbuf=$token;
						// check as characters
						if($inuses && is_string($token) && $token==','){                  // if comma found in root...
							if(count($tknbuf)==3 && !$tkndyn)
								isset($files[$tknbuf[2]]) ? $files[$tknbuf[2]][]=uq($tknbuf[1]) : $files[$tknbuf[2]]=array(uq($tknbuf[1]));
							$tknbuf=array(); $tkndyn=false;
						}elseif($inuses && is_string($token) && $token==')'){             // if parenthesis found in root...
							// if parenthesis found in root...
							$inuses=false;
							if(count($tknbuf)==3 && !$tkndyn)
								isset($files[$tknbuf[2]]) ? $files[$tknbuf[2]][]=uq($tknbuf[1]) : $files[$tknbuf[2]]=array(uq($tknbuf[1]));
							$tknbuf=array(); $tkndyn=false;
						}elseif($inuses && is_string($token) && $token!='(')$tkndyn=true; // other characters are bad
						// if token not recognized invalidate the rest until ',' or ')'
						if($inuses && is_array($token) && !in_array($token[0],$stctkns))$tkndyn=true;
					}
					if(count($files))$GLOBALS['uses'][str_replace(CFG::get('ABS_K2F'),'',$file)]=$files;
					$c+=count($files);
				}
				if(is_dir($file))check_deps($dir.'/'.basename($file));
			}
		xlog(str_repeat('  ',$indent).'Found '.$c.' item(s) in "'.$dir.'".'); $indent--;
	}

foreach(array('core','exts','libs','apps') as $dir)check_deps($dir);
$data=array(); // array( array( source target line ) )

foreach($GLOBALS['uses'] as $source=>$list)
	foreach($list as $line=>$files)
		foreach($files as $target)
			$data[]=array($source,$target,$line);

?><div>
	<input type="button" value="Reorganize & Redraw" onclick="redrawd();"/>
	<label for="noln" onmousedown="return false;"><input type="checkbox" id="noln" onclick="redrawd();"/> Show Line Numbers</label>
	<label for="nopt" onmousedown="return false;"><input type="checkbox" id="nopt" onclick="redrawd();"/> Show File Path</label>
	<img src="?file=throbber" alt="loading..." width="16" height="16" style="display:none;" id="ldr"/>
	<span style="padding-left:32px;font-size:14px;">
		&nbsp;<span style="color:#c0c0c0;font-size:38px;vertical-align:middle;">&bull;</span>&nbsp;Core System&nbsp;
		&nbsp;<span style="color:#26bf00;font-size:38px;vertical-align:middle;">&bull;</span>&nbsp;Extensions&nbsp;
		&nbsp;<span style="color:#bf6b26;font-size:38px;vertical-align:middle;">&bull;</span>&nbsp;Libraries&nbsp;
		&nbsp;<span style="color:#004cbf;font-size:38px;vertical-align:middle;">&bull;</span>&nbsp;Applications&nbsp;
	</span>
</div><div id="canvas" style="margin-left:-40px;"><!----></div><script type="text/javascript">
	function redrawd(){ // delayed
		document.getElementById('ldr').style.display='inline-block';
		window.setTimeout(redraw,500);
	}
	function redraw(){
		// cleanup canvas
		document.getElementById('canvas').innerHTML='';
		/* config and data */
		var g = new Graph();
		var w = ( !window.innerWidth ? (
					!document.documentElement.clientWidth
						? document.documentElement.clientWidth
						: document.body.clientWidth
				) : window.innerWidth)-200;
		var h = 600;
		var d = <?php echo @json_encode($data); ?>;
		var l = document.getElementById('noln').checked;
		var p = document.getElementById('nopt').checked;
		var c = {'core':'#c0c0c0','exts':'#26bf00','libs':'#bf6b26','apps':'#004cbf'};
		var r = function(r,n){
			var b=4;
			if(typeof c[n.fdir]=='undefined')c[n.fdir]=Raphael.getColor();
			var txt=r.text(n.point[0]+b+18,n.point[1]+b+8,n.label).attr({'font-size':'12px','font-weight':'bold','text-anchor':'start'});
			var ico=r.image('?file='+n.fdir,n.point[0]+b,n.point[1]+b,16,16).attr({'fill':'#000'});
			var box=r.rect(n.point[0],n.point[1],txt.getBBox().width+b*2+18,txt.getBBox().height+b*2,2).attr({'fill':c[n.fdir]});
			return r.set().push(box).push(ico).push(txt);
		};
		var basename=function(file){
			file=file.split('/');
			return file[file.length-1];
		}
		var fdirname=function(file){
			return file.split('/')[0].toLowerCase();
		}
		/* add nodes from data */
		for(var i=0; i<d.length; i++){
			g.addNode(d[i][0],{label:p?d[i][0]:basename(d[i][0]),render:r,fdir:fdirname(d[i][0])});
			g.addNode(d[i][1],{label:p?d[i][1]:basename(d[i][1]),render:r,fdir:fdirname(d[i][1])});
			g.addEdge(d[i][0],d[i][1],{directed:true,label:l?'(line '+d[i][2]+')':''});
		}
		/* layout the graph using the Spring layout implementation */
		var layouter = new Graph.Layout.Spring(g);
		layouter.layout();
		/* draw the graph using the RaphaelJS draw implementation */
		var renderer = new Graph.Renderer.Raphael('canvas', g, w, h);
		renderer.draw();
		// hide loader
		document.getElementById('ldr').style.display='none';
	}
	window.onload = redrawd;
</script><?php

?>