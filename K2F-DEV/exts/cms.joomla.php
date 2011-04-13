<?php defined('K2F') or die;

	uses('core/security.php','core/apps.php','core/cms.php','core/events.php');

	if(!defined('_JEXEC')){
		// define dependencies in case of a disaster
		function jimport(){}
		class JController{}
		class JView{}
	}

	// import joomla requirements
	jimport('joomla.database.database');
	jimport('joomla.plugin.plugin');
	jimport('joomla.html.parameter');
	jimport( 'joomla.html.editor' );

	/**
	 * CMS host interface for joomla.
	 */
	class CmsHost_joomla extends CmsHost_Base {
		/**
		 * @var JParameter|null The cached parameters wrapper class.
		 */
		protected static $params=null;
		/**
		 * @var string Temporary buffer for page content.
		 */
		public static $_contents='';
		/**
		 * @var boolean This is set to true if/when toolbar is used.
		 */
		public static $_adminlist=false;
		/**
		 * Writes file contents for jQuery FancyBox plugin files.
		 */
		public static function _jquery_fancy_box($name){
			switch($name){
				case 'jquery.fancybox-1.3.4.pack.js':
					header('Content-type: text/javascript');
?>/*
 * FancyBox - jQuery Plugin
 * Simple and fancy lightbox alternative
 *
 * Examples and documentation at: http://fancybox.net
 *
 * Copyright (c) 2008 - 2010 Janis Skarnelis
 * That said, it is hardly a one-person project. Many people have submitted bugs, code, and offered their advice freely. Their support is greatly appreciated.
 *
 * Version: 1.3.4 (11/11/2010)
 * Requires: jQuery v1.3+
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */

;(function(b){var m,t,u,f,D,j,E,n,z,A,q=0,e={},o=[],p=0,d={},l=[],G=null,v=new Image,J=/\.(jpg|gif|png|bmp|jpeg)(.*)?$/i,W=/[^\.]\.(swf)\s*$/i,K,L=1,y=0,s="",r,i,h=false,B=b.extend(b("<div/>")[0],{prop:0}),M=b.browser.msie&&b.browser.version<7&&!window.XMLHttpRequest,N=function(){t.hide();v.onerror=v.onload=null;G&&G.abort();m.empty()},O=function(){if(false===e.onError(o,q,e)){t.hide();h=false}else{e.titleShow=false;e.width="auto";e.height="auto";m.html('<p id="fancybox-error">The requested content cannot be loaded.<br />Please try again later.</p>');F()}},I=function(){var a=o[q],c,g,k,C,P,w;N();e=b.extend({},b.fn.fancybox.defaults,typeof b(a).data("fancybox")=="undefined"?e:b(a).data("fancybox"));w=e.onStart(o,q,e);if(w===false)h=false;else{if(typeof w=="object")e=b.extend(e,w);k=e.title||(a.nodeName?b(a).attr("title"):a.title)||"";if(a.nodeName&&!e.orig)e.orig=b(a).children("img:first").length?b(a).children("img:first"):b(a);if(k===""&&e.orig&&e.titleFromAlt)k=e.orig.attr("alt");c=e.href||(a.nodeName?b(a).attr("href"):a.href)||null;if(/^(?:javascript)/i.test(c)||c=="#")c=null;if(e.type){g=e.type;if(!c)c=e.content}else if(e.content)g="html";else if(c)g=c.match(J)?"image":c.match(W)?"swf":b(a).hasClass("iframe")?"iframe":c.indexOf("#")===0?"inline":"ajax";if(g){if(g=="inline"){a=c.substr(c.indexOf("#"));g=b(a).length>0?"inline":"ajax"}e.type=g;e.href=c;e.title=k;if(e.autoDimensions)if(e.type=="html"||e.type=="inline"||e.type=="ajax"){e.width="auto";e.height="auto"}else e.autoDimensions=false;if(e.modal){e.overlayShow=true;e.hideOnOverlayClick=false;e.hideOnContentClick=false;e.enableEscapeButton=false;e.showCloseButton=false}e.padding=parseInt(e.padding,10);e.margin=parseInt(e.margin,10);m.css("padding",e.padding+e.margin);b(".fancybox-inline-tmp").unbind("fancybox-cancel").bind("fancybox-change",function(){b(this).replaceWith(j.children())});switch(g){case "html":m.html(e.content);F();break;case "inline":if(b(a).parent().is("#fancybox-content")===true){h=false;break}b('<div class="fancybox-inline-tmp" />').hide().insertBefore(b(a)).bind("fancybox-cleanup",function(){b(this).replaceWith(j.children())}).bind("fancybox-cancel",function(){b(this).replaceWith(m.children())});b(a).appendTo(m);F();break;case "image":h=false;b.fancybox.showActivity();v=new Image;v.onerror=function(){O()};v.onload=function(){h=true;v.onerror=v.onload=null;e.width=v.width;e.height=v.height;b("<img />").attr({id:"fancybox-img",src:v.src,alt:e.title}).appendTo(m);Q()};v.src=c;break;case "swf":e.scrolling="no";C='<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'+e.width+'" height="'+e.height+'"><param name="movie" value="'+c+'"></param>';P="";b.each(e.swf,function(x,H){C+='<param name="'+x+'" value="'+H+'"></param>';P+=" "+x+'="'+H+'"'});C+='<embed src="'+c+'" type="application/x-shockwave-flash" width="'+e.width+'" height="'+e.height+'"'+P+"></embed></object>";m.html(C);F();break;case "ajax":h=false;b.fancybox.showActivity();e.ajax.win=e.ajax.success;G=b.ajax(b.extend({},e.ajax,{url:c,data:e.ajax.data||{},error:function(x){x.status>0&&O()},success:function(x,H,R){if((typeof R=="object"?R:G).status==200){if(typeof e.ajax.win=="function"){w=e.ajax.win(c,x,H,R);if(w===false){t.hide();return}else if(typeof w=="string"||typeof w=="object")x=w}m.html(x);F()}}}));break;case "iframe":Q()}}else O()}},F=function(){var a=e.width,c=e.height;a=a.toString().indexOf("%")>-1?parseInt((b(window).width()-e.margin*2)*parseFloat(a)/100,10)+"px":a=="auto"?"auto":a+"px";c=c.toString().indexOf("%")>-1?parseInt((b(window).height()-e.margin*2)*parseFloat(c)/100,10)+"px":c=="auto"?"auto":c+"px";m.wrapInner('<div style="width:'+a+";height:"+c+";overflow: "+(e.scrolling=="auto"?"auto":e.scrolling=="yes"?"scroll":"hidden")+';position:relative;"></div>');e.width=m.width();e.height=m.height();Q()},Q=function(){var a,c;t.hide();if(f.is(":visible")&&false===d.onCleanup(l,p,d)){b.event.trigger("fancybox-cancel");h=false}else{h=true;b(j.add(u)).unbind();b(window).unbind("resize.fb scroll.fb");b(document).unbind("keydown.fb");f.is(":visible")&&d.titlePosition!=="outside"&&f.css("height",f.height());l=o;p=q;d=e;if(d.overlayShow){u.css({"background-color":d.overlayColor,opacity:d.overlayOpacity,cursor:d.hideOnOverlayClick?"pointer":"auto",height:b(document).height()});if(!u.is(":visible")){M&&b("select:not(#fancybox-tmp select)").filter(function(){return this.style.visibility!=="hidden"}).css({visibility:"hidden"}).one("fancybox-cleanup",function(){this.style.visibility="inherit"});u.show()}}else u.hide();i=X();s=d.title||"";y=0;n.empty().removeAttr("style").removeClass();if(d.titleShow!==false){if(b.isFunction(d.titleFormat))a=d.titleFormat(s,l,p,d);else a=s&&s.length?d.titlePosition=="float"?'<table id="fancybox-title-float-wrap" cellpadding="0" cellspacing="0"><tr><td id="fancybox-title-float-left"></td><td id="fancybox-title-float-main">'+s+'</td><td id="fancybox-title-float-right"></td></tr></table>':'<div id="fancybox-title-'+d.titlePosition+'">'+s+"</div>":false;s=a;if(!(!s||s==="")){n.addClass("fancybox-title-"+d.titlePosition).html(s).appendTo("body").show();switch(d.titlePosition){case "inside":n.css({width:i.width-d.padding*2,marginLeft:d.padding,marginRight:d.padding});y=n.outerHeight(true);n.appendTo(D);i.height+=y;break;case "over":n.css({marginLeft:d.padding,width:i.width-d.padding*2,bottom:d.padding}).appendTo(D);break;case "float":n.css("left",parseInt((n.width()-i.width-40)/2,10)*-1).appendTo(f);break;default:n.css({width:i.width-d.padding*2,paddingLeft:d.padding,paddingRight:d.padding}).appendTo(f)}}}n.hide();if(f.is(":visible")){b(E.add(z).add(A)).hide();a=f.position();r={top:a.top,left:a.left,width:f.width(),height:f.height()};c=r.width==i.width&&r.height==i.height;j.fadeTo(d.changeFade,0.3,function(){var g=function(){j.html(m.contents()).fadeTo(d.changeFade,1,S)};b.event.trigger("fancybox-change");j.empty().removeAttr("filter").css({"border-width":d.padding,width:i.width-d.padding*2,height:e.autoDimensions?"auto":i.height-y-d.padding*2});if(c)g();else{B.prop=0;b(B).animate({prop:1},{duration:d.changeSpeed,easing:d.easingChange,step:T,complete:g})}})}else{f.removeAttr("style");j.css("border-width",d.padding);if(d.transitionIn=="elastic"){r=V();j.html(m.contents());f.show();if(d.opacity)i.opacity=0;B.prop=0;b(B).animate({prop:1},{duration:d.speedIn,easing:d.easingIn,step:T,complete:S})}else{d.titlePosition=="inside"&&y>0&&n.show();j.css({width:i.width-d.padding*2,height:e.autoDimensions?"auto":i.height-y-d.padding*2}).html(m.contents());f.css(i).fadeIn(d.transitionIn=="none"?0:d.speedIn,S)}}}},Y=function(){if(d.enableEscapeButton||d.enableKeyboardNav)b(document).bind("keydown.fb",function(a){if(a.keyCode==27&&d.enableEscapeButton){a.preventDefault();b.fancybox.close()}else if((a.keyCode==37||a.keyCode==39)&&d.enableKeyboardNav&&a.target.tagName!=="INPUT"&&a.target.tagName!=="TEXTAREA"&&a.target.tagName!=="SELECT"){a.preventDefault();b.fancybox[a.keyCode==37?"prev":"next"]()}});if(d.showNavArrows){if(d.cyclic&&l.length>1||p!==0)z.show();if(d.cyclic&&l.length>1||p!=l.length-1)A.show()}else{z.hide();A.hide()}},S=function(){if(!b.support.opacity){j.get(0).style.removeAttribute("filter");f.get(0).style.removeAttribute("filter")}e.autoDimensions&&j.css("height","auto");f.css("height","auto");s&&s.length&&n.show();d.showCloseButton&&E.show();Y();d.hideOnContentClick&&j.bind("click",b.fancybox.close);d.hideOnOverlayClick&&u.bind("click",b.fancybox.close);b(window).bind("resize.fb",b.fancybox.resize);d.centerOnScroll&&b(window).bind("scroll.fb",b.fancybox.center);if(d.type=="iframe")b('<iframe id="fancybox-frame" name="fancybox-frame'+(new Date).getTime()+'" frameborder="0" hspace="0" '+(b.browser.msie?'allowtransparency="true""':"")+' scrolling="'+e.scrolling+'" src="'+d.href+'"></iframe>').appendTo(j);f.show();h=false;b.fancybox.center();d.onComplete(l,p,d);var a,c;if(l.length-1>p){a=l[p+1].href;if(typeof a!=="undefined"&&a.match(J)){c=new Image;c.src=a}}if(p>0){a=l[p-1].href;if(typeof a!=="undefined"&&a.match(J)){c=new Image;c.src=a}}},T=function(a){var c={width:parseInt(r.width+(i.width-r.width)*a,10),height:parseInt(r.height+(i.height-r.height)*a,10),top:parseInt(r.top+(i.top-r.top)*a,10),left:parseInt(r.left+(i.left-r.left)*a,10)};if(typeof i.opacity!=="undefined")c.opacity=a<0.5?0.5:a;f.css(c);j.css({width:c.width-d.padding*2,height:c.height-y*a-d.padding*2})},U=function(){return[b(window).width()-d.margin*2,b(window).height()-d.margin*2,b(document).scrollLeft()+d.margin,b(document).scrollTop()+d.margin]},X=function(){var a=U(),c={},g=d.autoScale,k=d.padding*2;c.width=d.width.toString().indexOf("%")>-1?parseInt(a[0]*parseFloat(d.width)/100,10):d.width+k;c.height=d.height.toString().indexOf("%")>-1?parseInt(a[1]*parseFloat(d.height)/100,10):d.height+k;if(g&&(c.width>a[0]||c.height>a[1]))if(e.type=="image"||e.type=="swf"){g=d.width/d.height;if(c.width>a[0]){c.width=a[0];c.height=parseInt((c.width-k)/g+k,10)}if(c.height>a[1]){c.height=a[1];c.width=parseInt((c.height-k)*g+k,10)}}else{c.width=Math.min(c.width,a[0]);c.height=Math.min(c.height,a[1])}c.top=parseInt(Math.max(a[3]-20,a[3]+(a[1]-c.height-40)*0.5),10);c.left=parseInt(Math.max(a[2]-20,a[2]+(a[0]-c.width-40)*0.5),10);return c},V=function(){var a=e.orig?b(e.orig):false,c={};if(a&&a.length){c=a.offset();c.top+=parseInt(a.css("paddingTop"),10)||0;c.left+=parseInt(a.css("paddingLeft"),10)||0;c.top+=parseInt(a.css("border-top-width"),10)||0;c.left+=parseInt(a.css("border-left-width"),10)||0;c.width=a.width();c.height=a.height();c={width:c.width+d.padding*2,height:c.height+d.padding*2,top:c.top-d.padding-20,left:c.left-d.padding-20}}else{a=U();c={width:d.padding*2,height:d.padding*2,top:parseInt(a[3]+a[1]*0.5,10),left:parseInt(a[2]+a[0]*0.5,10)}}return c},Z=function(){if(t.is(":visible")){b("div",t).css("top",L*-40+"px");L=(L+1)%12}else clearInterval(K)};b.fn.fancybox=function(a){if(!b(this).length)return this;b(this).data("fancybox",b.extend({},a,b.metadata?b(this).metadata():{})).unbind("click.fb").bind("click.fb",function(c){c.preventDefault();if(!h){h=true;b(this).blur();o=[];q=0;c=b(this).attr("rel")||"";if(!c||c==""||c==="nofollow")o.push(this);else{o=b("a[rel="+c+"], area[rel="+c+"]");q=o.index(this)}I()}});return this};b.fancybox=function(a,c){var g;if(!h){h=true;g=typeof c!=="undefined"?c:{};o=[];q=parseInt(g.index,10)||0;if(b.isArray(a)){for(var k=0,C=a.length;k<C;k++)if(typeof a[k]=="object")b(a[k]).data("fancybox",b.extend({},g,a[k]));else a[k]=b({}).data("fancybox",b.extend({content:a[k]},g));o=jQuery.merge(o,a)}else{if(typeof a=="object")b(a).data("fancybox",b.extend({},g,a));else a=b({}).data("fancybox",b.extend({content:a},g));o.push(a)}if(q>o.length||q<0)q=0;I()}};b.fancybox.showActivity=function(){clearInterval(K);t.show();K=setInterval(Z,66)};b.fancybox.hideActivity=function(){t.hide()};b.fancybox.next=function(){return b.fancybox.pos(p+1)};b.fancybox.prev=function(){return b.fancybox.pos(p-1)};b.fancybox.pos=function(a){if(!h){a=parseInt(a);o=l;if(a>-1&&a<l.length){q=a;I()}else if(d.cyclic&&l.length>1){q=a>=l.length?0:l.length-1;I()}}};b.fancybox.cancel=function(){if(!h){h=true;b.event.trigger("fancybox-cancel");N();e.onCancel(o,q,e);h=false}};b.fancybox.close=function(){function a(){u.fadeOut("fast");n.empty().hide();f.hide();b.event.trigger("fancybox-cleanup");j.empty();d.onClosed(l,p,d);l=e=[];p=q=0;d=e={};h=false}if(!(h||f.is(":hidden"))){h=true;if(d&&false===d.onCleanup(l,p,d))h=false;else{N();b(E.add(z).add(A)).hide();b(j.add(u)).unbind();b(window).unbind("resize.fb scroll.fb");b(document).unbind("keydown.fb");j.find("iframe").attr("src",M&&/^https/i.test(window.location.href||"")?"javascript:void(false)":"about:blank");d.titlePosition!=="inside"&&n.empty();f.stop();if(d.transitionOut=="elastic"){r=V();var c=f.position();i={top:c.top,left:c.left,width:f.width(),height:f.height()};if(d.opacity)i.opacity=1;n.empty().hide();B.prop=1;b(B).animate({prop:0},{duration:d.speedOut,easing:d.easingOut,step:T,complete:a})}else f.fadeOut(d.transitionOut=="none"?0:d.speedOut,a)}}};b.fancybox.resize=function(){u.is(":visible")&&u.css("height",b(document).height());b.fancybox.center(true)};b.fancybox.center=function(a){var c,g;if(!h){g=a===true?1:0;c=U();!g&&(f.width()>c[0]||f.height()>c[1])||f.stop().animate({top:parseInt(Math.max(c[3]-20,c[3]+(c[1]-j.height()-40)*0.5-d.padding)),left:parseInt(Math.max(c[2]-20,c[2]+(c[0]-j.width()-40)*0.5-d.padding))},typeof a=="number"?a:200)}};b.fancybox.init=function(){if(!b("#fancybox-wrap").length){b("body").append(m=b('<div id="fancybox-tmp"></div>'),t=b('<div id="fancybox-loading"><div></div></div>'),u=b('<div id="fancybox-overlay"></div>'),f=b('<div id="fancybox-wrap"></div>'));D=b('<div id="fancybox-outer"></div>').append('<div class="fancybox-bg" id="fancybox-bg-n"></div><div class="fancybox-bg" id="fancybox-bg-ne"></div><div class="fancybox-bg" id="fancybox-bg-e"></div><div class="fancybox-bg" id="fancybox-bg-se"></div><div class="fancybox-bg" id="fancybox-bg-s"></div><div class="fancybox-bg" id="fancybox-bg-sw"></div><div class="fancybox-bg" id="fancybox-bg-w"></div><div class="fancybox-bg" id="fancybox-bg-nw"></div>').appendTo(f);D.append(j=b('<div id="fancybox-content"></div>'),E=b('<a id="fancybox-close"></a>'),n=b('<div id="fancybox-title"></div>'),z=b('<a href="javascript:;" id="fancybox-left"><span class="fancy-ico" id="fancybox-left-ico"></span></a>'),A=b('<a href="javascript:;" id="fancybox-right"><span class="fancy-ico" id="fancybox-right-ico"></span></a>'));E.click(b.fancybox.close);t.click(b.fancybox.cancel);z.click(function(a){a.preventDefault();b.fancybox.prev()});A.click(function(a){a.preventDefault();b.fancybox.next()});b.fn.mousewheel&&f.bind("mousewheel.fb",function(a,c){if(h)a.preventDefault();else if(b(a.target).get(0).clientHeight==0||b(a.target).get(0).scrollHeight===b(a.target).get(0).clientHeight){a.preventDefault();b.fancybox[c>0?"prev":"next"]()}});b.support.opacity||f.addClass("fancybox-ie");if(M){t.addClass("fancybox-ie6");f.addClass("fancybox-ie6");b('<iframe id="fancybox-hide-sel-frame" src="'+(/^https/i.test(window.location.href||"")?"javascript:void(false)":"about:blank")+'" scrolling="no" border="0" frameborder="0" tabindex="-1"></iframe>').prependTo(D)}}};b.fn.fancybox.defaults={padding:10,margin:40,opacity:false,modal:false,cyclic:false,scrolling:"auto",width:560,height:340,autoScale:true,autoDimensions:true,centerOnScroll:false,ajax:{},swf:{wmode:"transparent"},hideOnOverlayClick:true,hideOnContentClick:false,overlayShow:true,overlayOpacity:0.7,overlayColor:"#777",titleShow:true,titlePosition:"float",titleFormat:null,titleFromAlt:false,transitionIn:"fade",transitionOut:"fade",speedIn:300,speedOut:300,changeSpeed:300,changeFade:"fast",easingIn:"swing",easingOut:"swing",showCloseButton:true,showNavArrows:true,enableEscapeButton:true,enableKeyboardNav:true,onStart:function(){},onCancel:function(){},onComplete:function(){},onCleanup:function(){},onClosed:function(){},onError:function(){}};b(document).ready(function(){b.fancybox.init()})})(jQuery);
<?php				break;
				case 'jquery.fancybox-1.3.4.pack.css':
					header('Content-type: text/css');
					ob_start();
?>/*
 * FancyBox - jQuery Plugin
 * Simple and fancy lightbox alternative
 *
 * Examples and documentation at: http://fancybox.net
 *
 * Copyright (c) 2008 - 2010 Janis Skarnelis
 * That said, it is hardly a one-person project. Many people have submitted bugs, code, and offered their advice freely. Their support is greatly appreciated.
 *
 * Version: 1.3.4 (11/11/2010)
 * Requires: jQuery v1.3+
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */

#fancybox-loading{position:fixed;top:50%;left:50%;width:40px;height:40px;margin-top:-20px;margin-left:-20px;cursor:pointer;overflow:hidden;z-index:1104;display:none;}#fancybox-loading div{position:absolute;top:0;left:0;width:40px;height:480px;background-image:url('fancybox.png');}#fancybox-overlay{position:absolute;top:0;left:0;width:100%;z-index:1100;display:none;}#fancybox-tmp{border:0;overflow:auto;display:none;margin:0;padding:0;}#fancybox-wrap{position:absolute;top:0;left:0;z-index:1101;outline:none;display:none;padding:20px;}#fancybox-outer{position:relative;width:100%;height:100%;background:#fff;}#fancybox-content{width:0;height:0;outline:none;position:relative;overflow:hidden;z-index:1102;border:0 solid #fff;padding:0;}#fancybox-hide-sel-frame{position:absolute;top:0;left:0;width:100%;height:100%;background:transparent;z-index:1101;}#fancybox-close{position:absolute;top:-15px;right:-15px;width:30px;height:30px;background:transparent url('fancybox.png') -40px 0;cursor:pointer;z-index:1103;display:none;}#fancybox-error{color:#444;font:normal 12px/20px Arial;margin:0;padding:14px;}#fancybox-img{width:100%;height:100%;border:none;outline:none;line-height:0;vertical-align:top;margin:0;padding:0;}#fancybox-frame{width:100%;height:100%;border:none;display:block;}#fancybox-left,#fancybox-right{position:absolute;bottom:0;height:100%;width:35%;cursor:pointer;outline:none;background:transparent url('blank.gif');z-index:1102;display:none;}#fancybox-left{left:0;}#fancybox-right{right:0;}#fancybox-left-ico,#fancybox-right-ico{position:absolute;top:50%;left:-9999px;width:30px;height:30px;margin-top:-15px;cursor:pointer;z-index:1102;display:block;}#fancybox-left-ico{background-image:url('fancybox.png');background-position:-40px -30px;}#fancybox-right-ico{background-image:url('fancybox.png');background-position:-40px -60px;}#fancybox-left:hover,#fancybox-right:hover{visibility:visible;}#fancybox-left:hover span{left:20px;}#fancybox-right:hover span{left:auto;right:20px;}.fancybox-bg{position:absolute;border:0;width:20px;height:20px;z-index:1001;margin:0;padding:0;}#fancybox-bg-n{top:-20px;left:0;width:100%;background-image:url('fancybox-x.png');}#fancybox-bg-ne{top:-20px;right:-20px;background-image:url('fancybox.png');background-position:-40px -162px;}#fancybox-bg-e{top:0;right:-20px;height:100%;background-image:url('fancybox-y.png');background-position:-20px 0;}#fancybox-bg-se{bottom:-20px;right:-20px;background-image:url('fancybox.png');background-position:-40px -182px;}#fancybox-bg-s{bottom:-20px;left:0;width:100%;background-image:url('fancybox-x.png');background-position:0 -20px;}#fancybox-bg-sw{bottom:-20px;left:-20px;background-image:url('fancybox.png');background-position:-40px -142px;}#fancybox-bg-w{top:0;left:-20px;height:100%;background-image:url('fancybox-y.png');}#fancybox-bg-nw{top:-20px;left:-20px;background-image:url('fancybox.png');background-position:-40px -122px;}#fancybox-title{font-family:Helvetica;font-size:12px;z-index:1102;}.fancybox-title-inside{padding-bottom:10px;text-align:center;color:#333;background:#fff;position:relative;}.fancybox-title-outside{padding-top:10px;color:#fff;}.fancybox-title-over{position:absolute;bottom:0;left:0;color:#FFF;text-align:left;}#fancybox-title-over{background-image:url('fancy_title_over.png');display:block;padding:10px;}.fancybox-title-float{position:absolute;left:0;bottom:-20px;height:32px;}#fancybox-title-float-wrap{border:none;border-collapse:collapse;width:auto;}#fancybox-title-float-wrap td{border:none;white-space:nowrap;}#fancybox-title-float-left{background:url('fancybox.png') -40px -90px no-repeat;padding:0 0 0 15px;}#fancybox-title-float-main{color:#FFF;line-height:29px;font-weight:700;background:url('fancybox-x.png') 0 -40px;padding:0 0 3px;}#fancybox-title-float-right{background:url('fancybox.png') -55px -90px no-repeat;padding:0 0 0 15px;}.fancybox-ie6 #fancybox-close{background:transparent;filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_close.png', sizingMethod='scale');}.fancybox-ie6 #fancybox-left-ico{background:transparent;filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_nav_left.png', sizingMethod='scale');}.fancybox-ie6 #fancybox-right-ico{background:transparent;filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_nav_right.png', sizingMethod='scale');}.fancybox-ie6 #fancybox-title-over{background:transparent;filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_title_over.png', sizingMethod='scale');zoom:1px;}.fancybox-ie6 #fancybox-title-float-left{background:transparent;filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_title_left.png', sizingMethod='scale');}.fancybox-ie6 #fancybox-title-float-main{background:transparent;filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_title_main.png', sizingMethod='scale');}.fancybox-ie6 #fancybox-title-float-right{background:transparent;filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_title_right.png', sizingMethod='scale');}.fancybox-ie6 #fancybox-bg-w,.fancybox-ie6 #fancybox-bg-e,.fancybox-ie6 #fancybox-left,.fancybox-ie6 #fancybox-right,#fancybox-hide-sel-frame{height:expression(this.parentNode.clientHeight + "px");}#fancybox-loading.fancybox-ie6{position:absolute;margin-top:0;top:expression( (-20 + (document.documentElement.clientHeight ? document.documentElement.clientHeight/2 : document.body.clientHeight/2 ) 0 ( ignoreMe = document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop )) 0 'px');}#fancybox-loading.fancybox-ie6 div{background:transparent;filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_loading.png', sizingMethod='scale');}.fancybox-ie .fancybox-bg{background:transparent !important;}.fancybox-ie #fancybox-bg-n{filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_shadow_n.png', sizingMethod='scale');}.fancybox-ie #fancybox-bg-ne{filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_shadow_ne.png', sizingMethod='scale');}.fancybox-ie #fancybox-bg-e{filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_shadow_e.png', sizingMethod='scale');}.fancybox-ie #fancybox-bg-se{filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_shadow_se.png', sizingMethod='scale');}.fancybox-ie #fancybox-bg-s{filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_shadow_s.png', sizingMethod='scale');}.fancybox-ie #fancybox-bg-sw{filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_shadow_sw.png', sizingMethod='scale');}.fancybox-ie #fancybox-bg-w{filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_shadow_w.png', sizingMethod='scale');}.fancybox-ie #fancybox-bg-nw{filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='fancybox/fancy_shadow_nw.png', sizingMethod='scale');}
<?php				$css=ob_get_clean();
					echo str_replace(array(
							'fancybox.png',
							'fancybox-x.png',
							'fancybox-y.png',
							'blank.gif'
						),array(
							Security::escape(Ajax::url('CmsHost_joomla','_jquery_fancy_box')).'&name=fancybox.png',
							Security::escape(Ajax::url('CmsHost_joomla','_jquery_fancy_box')).'&name=fancybox-x.png',
							Security::escape(Ajax::url('CmsHost_joomla','_jquery_fancy_box')).'&name=fancybox-y.png',
							Security::escape(Ajax::url('CmsHost_joomla','_jquery_fancy_box')).'&name=blank.gif'
						),$css);
					break;
				case 'fancybox.png':
					header('Content-type: image/png');
					echo base64_decode('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');
					break;
				case 'fancybox-x.png':
					header('Content-type: image/png');
					echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAABICAYAAAA3Qp8tAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAG1JREFUeNq0jzEOgDAMA90WChMDUsXKp3kcTwFRQp0hUR9AhpPtJo0CEQG2UkIEIMRAqAUR+0xVNpt7eLNPjES171U9ptzexGXZa5nbYM2KpA/QE1pNtAutLtI67Y5/0G87UsNOrMRMgKifAAMA8xojdBZ1K0cAAAAASUVORK5CYII=');
					break;
				case 'fancybox-y.png':
					header('Content-type: image/png');
					echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAACgAAAABCAYAAACsXeyTAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAFJJREFUeNpMjF0OgCAMg4eAegLC/U9HOIEJutnGPtjkS/fTLUWEUb21BMuAvtmnojqLQ875qR2putsBH97AwQKPWD+YuZRx9a471jHmZG+vAAMAMUgY/0A1OZkAAAAASUVORK5CYII=');
					break;
				case 'blank.gif':
					header('Content-type: image/gif');
					echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
					break;
				default:
					header('HTTP/1.0 404 Not Found');
			}
			die;
		}
		/**
		 * Writes file contents for various used files.
		 */
		public static function _file_data($name){
			switch($name){
				case 'loader16.gif':
					header('Content-type: image/gif');
					echo base64_decode('R0lGODlhEAAQAOMAAAQCBISChMTCxOTm5ERCRMzKzPz+/Ly+vOzu7Hx+fAQGBISGhMTGxOzq7FRSVMzOzCH/C05FVFNDQVBFMi4wAwEAAAAh+QQJBgAGACwAAAAAEAAQAAAESNDISau9OOvNe0VM0XCCcDAcc5RcwwhjNwiD9SzPNARJUE8FBQBQkAh6CUHB4WAshoCFZNcbOAgERwFanNIM16zhlrsImAdLBAAh+QQJBgAMACwAAAAAEAAQAIMEAgSMiozU1tS0srREQkT08vQUFhSkoqTk4uS8vrzc2tx8fnz8/vwcGhykpqTEwsQER5DJSau9OOvNu59D0wxcAwAGZ5zpNhijVCCFpTjKVDzJU08KglAgQSSOCEUgoDgICQcZzxdYLAKCJ1FGY1SvDIFjaxEsc5UIACH5BAkGAAYALAAAAAAQABAAAARH0MhJq7VvvVuLAkDBTQsILKNUmGJqZJsrMY7DyA5BOLjOuzTHwdUQDGSDQCJxtCAKAoRgGRBcBAeBURloVhjY28DIaYAblggAIfkECQYADAAsAAAAABAAEACDBAIEjIqM1NbUtLK0REJE9PL0FBYUpKKk5OLkvL683NrcfH58/P78HBocpKakxMLEBEiQyTmaGTPrBoBhiqNok9EZCqGO5GA0w6ESB6kJs2Dfjr5rikCA9WMEFotAUXJMLkFC4q6AKDwLicTDWkRoE4glVsstUsvPSQQAIfkECQYABgAsAAAAABAAEAAABErQyMmcYzObt55xBOEYjTBMhQIABSgOSRCcxrICS+UcQuILksKtkBkEYrRNR2MYmJhQEqPQiGYYAp51khUAtwaEgFEFm8/otBodAQAh+QQJBgAMACwAAAAAEAAQAIMEAgSMiozU1tS0srREQkT08vQUFhSkoqTk4uS8vrzc2tx8fnz8/vwcGhykpqTEwsQERpDJqUJQMzPlcFhLwBRIQRGo8IXFkzwmc6DEoV5IoiOSQAuZwisR29g0o5Jk0DAMkEgDAGCAaqRUa2ZgaDy14LB4TC6boREAIfkECQYABgAsAAAAABAAEAAABEvQyNkYQzObJoZhwsFo0hAkwRAKAmkIaCJUAkaaqOdmqr4bBYdj9JM4CARH0YhULhlH4tLwWDymBQVAUVguAODFsgAGdJfV63RtiAAAIfkECQYADAAsAAAAABAAEACDBAIEjIqM1NbUtLK0REJE9PL0FBYUpKKk5OLkvL683NrcfH58/P78HBocpKakxMLEBEWQyUnrLKhYW1562jYhSYmIlweiV8YyShAILxMsS1DfeR0HippE4Qi+FIQkjXVIEhyDRmOwETgFBgDAIBI4aNltbWCYWiIAOw==');
					break;
				default:
					header('HTTP/1.0 404 Not Found');
			}
			die;
		}
		/**
		 * Utility function which translates an action to a Joomla button.
		 * @param string The action button to translate.
		 */
		protected static function _make_joom_button($action){
			$name=strtolower($action);
			$translate=array(
				0=>'config',
				'add'=>'new',
				'new'=>'new',
				'create'=>'new',
				'edit'=>'edit',
				'manage'=>'edit',
				'delete'=>'delete',
				'remove'=>'delete',
				'publish'=>'publish',
				'unpublish'=>'unpublish',
				'update'=>'apply',
				'apply'=>'apply',
				'save'=>'save',
				'help'=>'help'
			);
			$icon=isset($translate[$name]) ? $translate[$name] : $translate[0];
			JToolBarHelper::customX($name,$icon,$icon,$action,$name!='new',false);
		}
		protected static $pagecounter=0;
		protected static $paginations=array(5,10,15,20,25,30,50,100,0);
		public function adminlist($rows=array(),$colkey='id',$columns=array(),$options=array(),$actions=array(),$handler='',$emptymsg='No items found'){
			$rows=(array)$rows;
			// perform search filter on $rows, if not empty
			if(count($rows) && isset($_REQUEST['k2f-search']) && ($search=trim($_REQUEST['k2f-search']))!=''){
				foreach($rows as $i=>$row){
					$found=false;
					foreach(get_object_vars($row) as $k=>$data)
						if(stripos(is_scalar($data) ? ''.$data : implode(' ',array_filter((array)$data,'is_scalar')),$search)!==false){
							$found=true;
							break; // performance hotfix
						}
					if(!$found) // if not found as normal row, try search in formatted data (the conditional is a performance fix)
						foreach($columns as $colid=>$colname){
							$data=strip_tags((count($handler)>0 && $handler!='')
								? call_user_func($handler,$row->$colkey,$row,$colid,isset($row->$colid) ? $row->$colid : null)
								: (isset($row->$colid) ? $row->$colid : ''));
							if(stripos($data,$search)!==false){
								$found=true;
								break; // performance hotfix
							}
						}
					if(!$found)unset($rows[$i]);
				}
			}
			// perform pagination, if needed
			$p = isset($_REQUEST['k2f-page']) ? (int)$_REQUEST['k2f-page'] : 0;
			$l = isset($_REQUEST['k2f-limit']) ? (int)$_REQUEST['k2f-limit'] : 20;
			$t = count($rows);
			$c = ceil($t/$l)-1;
			if($l>0)$rows = array_splice($rows,$p*$l,$l);
			$p = max(min($p,$c),0);
			//die_r("p=$p, t=$t, l=$l, c=".ceil($t/$l));
			// add global actions buttons to toolbar
			foreach($actions as $action)self::_make_joom_button($action);
			?><div class="k2f-adminlist" id="k2f-al-<?php echo self::$pagecounter; ?>">
				<table width="100%">
					<tr>
						<td align="left">
							Filter:
							<input type="text" title="Filter by Search Term" onchange="k2f_search_submit(this);" class="text_area k2f-search-search" value="<?php if(isset($_REQUEST['k2f-search']))echo Security::snohtml($_REQUEST['k2f-search']); ?>" name="search">
							<button onclick="return k2f_search_submit(this);" class="k2f-search-submit">Go</button>
							<button onclick="return k2f_search_reset(this);" class="k2f-search-reset">Reset</button>
							<img src="<?php echo Ajax::url(__CLASS__,'_file_data'); ?>&name=loader16.gif" class="k2f-search-throbber" width="16" height="16" alt="Loading..." style="display:none; margin:-4px 0 0 4px; vertical-align:middle;"/>
						</td>
						<td align="right"><!--{TODO: Options Filter}--></td>
					</tr>
				</table><table class="adminlist" cellspacing="1">
					<thead>
						<tr><?php
							if($colkey=='id')echo '<th width="20"> # </th>';
							if(in_array('multiselect',$options))echo '<th width="20"><input type="checkbox" onclick="k2f_checkall(this);" value="" name="toggle" id="cb-'.self::$pagecounter.'"></th>';
							if(in_array('singleselect',$options))echo '<th width="20">&nbsp;</th>';
							foreach($columns as $key=>$column)echo '<th style="'.(is_array($column) ? $column[1] : '').'"><!--a title="Click to sort by this column" href="javascript:tableOrdering(\'c.title\',\'desc\',\'\');"-->'.Security::snohtml(is_array($column) ? $column[0] : $column).'<!--/a--></th>';
						?></tr>
					</thead><tfoot>
						<tr><td colspan="<?php echo count($columns)+($colkey=='id' ? 2 : 1); ?>">
							<div class="pagination">
								<div class="limit">
									Display #<select onchange="k2f_pgnation_limit(this);" size="1" class="inputbox k2f-page-limit" name="limit"><?php
										foreach(self::$paginations as $n)
											echo '<option '.($n==$l ? 'selected="selected"' : '').' value="'.$n.'">'.($n==0 ? 'all' : $n).'</option>';
									?></select>
								</div>
								<?php if(count($rows)<$t){ ?>
								<div class="button2-right<?php if($p==0)echo ' off'; ?>"><div class="start">
									<?php if($p==0){ ?><span>Start</span><?php }else{ ?><a title="Start" href="javascript:;" onclick="k2f_pgnation_page(this,0);">Start</a><?php } ?>
								</div></div>
								<div class="button2-right<?php if($p==0)echo ' off'; ?>"><div class="prev">
									<?php if($p==0){ ?><span>Prev</span><?php }else{ ?><a title="Prev" href="javascript:;" onclick="k2f_pgnation_page(this,<?php echo $p-1; ?>);">Prev</a><?php } ?>
								</div></div>
								<div class="button2-left">
									<div class="page"><?php
										for($i=max($p-4,1); $i<=min($p+5+abs(min($p-4,0)),$c+1); $i++)
											echo $i==($p+1) ? '<span>'.$i.'</span>' :
												'<a title="'.$i.'" href="javascript:;" onclick="k2f_pgnation_page(this,'.($i-1).');">'.$i.'</a>';
									?></div>
								</div>
								<div class="button2-left<?php if($p==$c)echo ' off'; ?>"><div class="next">
									<?php if($p==$c){ ?><span>Next</span><?php }else{ ?><a title="Next" href="javascript:;" onclick="k2f_pgnation_page(this,<?php echo $p+1; ?>);">Next</a><?php } ?>
								</div></div>
								<div class="button2-left<?php if($p==$c)echo ' off'; ?>"><div class="end">
									<?php if($p==$c){ ?><span>End</span><?php }else{ ?><a title="End" href="javascript:;" onclick="k2f_pgnation_page(this,<?php echo $c; ?>);">End</a><?php } ?>
								</div></div>
								<div class="limit"><?php echo 'Page '.($p+1).' of '.($c+1); ?></div>
								<?php } ?>
								<img src="<?php echo Ajax::url(__CLASS__,'_file_data'); ?>&name=loader16.gif" class="k2f-pgnation-throbber" width="16" height="16" alt="Loading..." style="visibility:hidden; margin:3px 0 0 4px;"/>
							</div>
						</td></tr>
					</tfoot><tbody><?php
						$c=0;
						foreach($rows as $id=>$row){
							?><tr class="row<?php echo $c%2; ?>"><?php
								echo '<td>'.Security::snohtml($row->$colkey.'').'</td>';
								if(in_array('singleselect',$options))
									echo '<td align="center"><input type="radio" onclick="k2f_checked(this);" value="'.Security::snohtml($row->$colkey.'').'" name="checked[]" id="cb-'.self::$pagecounter.'-'.Security::snohtml($row->$colkey.'').'"/></td>';
								if(in_array('multiselect',$options))
									echo '<td align="center"><input type="checkbox" onclick="k2f_checked(this);" value="'.Security::snohtml($row->$colkey.'').'" name="checked[]" id="cb-'.self::$pagecounter.'-'.Security::snohtml($row->$colkey.'').'"/></td>';
								foreach($columns as $colid=>$colname){
									$res=(count($handler)>0 && $handler!='')
										? call_user_func($handler,$row->$colkey,$row,$colid,isset($row->$colid) ? $row->$colid : null)
										: Security::snohtml(isset($row->$colid) ? $row->$colid : 'null');
									echo '<td>'.($res=='' ? '&nbsp;' : $res).'</td>';
								}
							?></tr><?php
							$c++;
						}
					if(!$c)
						echo '<tr><td style="font-style:italic; text-align:center; padding:32px;" colspan="'.(count($columns)+2).'">'.$emptymsg.'</td></tr>';
					?></tbody>
				</table><table cellspacing="0" cellpadding="4" border="0" align="center">
					<!-- Icons Legend -->
				</table>
			</div><?php
		}
		public function adminlist_begin($icon,$title,$options=array(),$actions=array(),$callback=array()){
			if(self::$pagecounter==0){
				?><script type="text/javascript">
					var k2f_refresh_ajax=null;
					var k2f_pgnation_page=0;
					function k2f_pgnation_limit(el){
						var l=el.value*1;
						var s=jQuery(el).parents('.k2f-adminlist').find('.k2f-search-search').val();
						// stop any previous searches and show throbber
						if(k2f_refresh_ajax && k2f_refresh_ajax.readyState!=0){
							k2f_refresh_ajax.abort();
							k2f_refresh_ajax=null;
						}
						jQuery(el).parents('.k2f-adminlist').find('.k2f-pgnation-throbber').css('visibility','visible');
						// do the search request
						var url=location.href.replace('k2f-search','k2f-ign').replace('k2f-limit','k2f-ign').replace('k2f-page','k2f-ign');
						url+='&k2f-limit='+l+'&k2f-page='+k2f_pgnation_page+'&k2f-search='+encodeURIComponent(s);
						k2f_refresh_ajax=k2f_refresh(url,function(){
							jQuery(el).parents('.k2f-adminlist').find('.k2f-pgnation-throbber').css('visibility','hidden');
						});
					}
					function k2f_pgnation_page(el,p){
						var l=jQuery(el).parents('.k2f-adminlist').find('.k2f-page-limit').val()*1;
						var s=jQuery(el).parents('.k2f-adminlist').find('.k2f-search-search').val();
						k2f_pgnation_page=p;
						// stop any previous searches and show throbber
						if(k2f_refresh_ajax && k2f_refresh_ajax.readyState!=0){
							k2f_refresh_ajax.abort();
							k2f_refresh_ajax=null;
						}
						jQuery(el).parents('.k2f-adminlist').find('.k2f-pgnation-throbber').css('visibility','visible');
						// do the search request
						var url=location.href.replace('k2f-search','k2f-ign').replace('k2f-limit','k2f-ign').replace('k2f-page','k2f-ign');
						url+='&k2f-limit='+l+'&k2f-page='+k2f_pgnation_page+'&k2f-search='+encodeURIComponent(s);
						k2f_refresh_ajax=k2f_refresh(url,function(){
							jQuery(el).parents('.k2f-adminlist').find('.k2f-pgnation-throbber').css('visibility','hidden');
						});
					}
					function k2f_search_submit(el){
						var l=jQuery(el).parents('.k2f-adminlist').find('.k2f-page-limit').val()*1;
						var s=jQuery(el).parents('.k2f-adminlist').find('.k2f-search-search').val();
						// stop any previous searches and show throbber
						if(k2f_refresh_ajax && k2f_refresh_ajax.readyState!=0){
							k2f_refresh_ajax.abort();
							k2f_refresh_ajax=null;
						}
						jQuery(el).parents('.k2f-adminlist').find('.k2f-search-throbber').show();
						// do the search request
						var url=location.href.replace('k2f-search','k2f-ign').replace('k2f-limit','k2f-ign').replace('k2f-page','k2f-ign');
						url+='&k2f-limit='+l+'&k2f-page='+k2f_pgnation_page+'&k2f-search='+encodeURIComponent(s);
						k2f_refresh_ajax=k2f_refresh(url,function(){
							jQuery(el).parents('.k2f-adminlist').find('.k2f-search-throbber').hide();
						});
						return false; // hack to stop button from submitting form
					}
					function k2f_search_reset(el){
						jQuery(el).parents('.k2f-adminlist').find('.k2f-search-search').val('');
						k2f_search_submit(el);
						return false; // hack to stop button from submitting form
					}
					var k2fajax=null;
					function k2f_popup(url){
						jQuery('#keenfbh').attr('href',url).click();
					}
					function k2f_submit(elem,action){
						// compute some variables...
						var s=jQuery('.k2f-search').val(); // this is not threadsafe! (multiple tables)
						var url=location.href.replace('k2f-search','k2f-ign').replace('k2f-page','k2f-ign');
						url+='&k2f-search='+encodeURIComponent(s)+"&k2f-page="+k2f_page;
						// continue...
						if(action!='refresh' && action!='close' && action!='cancel'){
							var el=jQuery(elem).parents('form');
							if(el.length>0){
								el=el[0];
								// show "loading..." message
								var vars=jQuery(el).serialize();
								jQuery(el).html(
									'<p>Loading, please wait...</p>'+
									'<input type="button" value="Cancel" style="font-size:16px;" onclick="k2f_cancel();">'+
									'<br/>&nbsp;'
								);
								// do it! do it! do it!
								k2fajax=jQuery.post(el.action,vars+'&k2f-action='+encodeURIComponent(action),function(data){
									// show resulting message
									jQuery('#fancybox-content').html(data);
									if(jQuery('#fancybox-content').length==0)
										jQuery('#k2f-nopopup').html(data);
									// refresh page (well, parts of it)
									k2f_refresh(url);
								});
							}
						}else{
							jQuery('#k2f-nopopup').hide();
							jQuery('.k2f-adminlist').show();
							if(action=='refresh')k2f_refresh(url);
							jQuery.fancybox.close();
						}
					}
					function k2f_cancel(){
						if(k2fajax && k2fajax.readyState!=0){
							k2fajax.abort();
							k2fajax=null;
						}
						jQuery.fancybox.close();
					}
					function k2f_refresh(url,ondone){
						// this is a bit of a hack:
						// - first, it gets the new content of this page (GET/POST location.href)
						// - converts the returned HTML to DOM using jQuery
						// - replaces all <tbody>s of current page with the new HTML using DOM
						// todo: maybe do this via POST for cases like pagination
						if(typeof url=='undefined')url=location.href;
						return jQuery.get(url,function(data){
							// get list of checked checkboxes with an id
							var cbs=[];
							jQuery('input[type=checkbox]:checked').each(function(){ cbs.push(jQuery(this).attr('id')) });
							// overwrite each adminlist with new content
							jQuery('.k2f-adminlist').each(function(){
								jQuery(this).html(jQuery(data).find('#'+jQuery(this).attr('id')).html());
							});
							// tick back checkboxes
							for(var i=0; i<cbs.length; i++)if(cbs[i]!='')jQuery('#'+cbs[i]).attr('checked',true);
							// call callback if any
							if(typeof ondone=='function')ondone();
						});
					}
					function k2f_checkall(el){
						jQuery(el).parents('form').find('input[name!=toggle]:checkbox').attr('checked',el.checked);
						document.adminForm.boxchecked.value=el.checked ? 1 : 0;
					}
					function k2f_checked(el){
						var cbA=jQuery(el).parents('form').find('input[name!=toggle]:checkbox').length;
						var cbC=jQuery(el).parents('form').find('input[name!=toggle]:checkbox:checked').length;
						jQuery(el).parents('form').find('input[name=toggle]:checkbox').attr('checked',cbC==cbA);
						document.adminForm.boxchecked.value=cbC;
					}
					function k2f_apply(action,tbl){
						var ids='';
						jQuery('#k2f-al-'+(tbl.replace('k2f-al-','')*1)+' input[name="checked\\[\\]"]:checked').each(function(id,el){
							ids+='&k2f-checked[]='+encodeURIComponent(el.value);
						});
						return k2f_popup(location.href+'<?php
								echo (count($callback)==2) ? Ajax::url($callback[0],$callback[1],'&') : '&k2f-notajax';
							?>&k2f-table='+(tbl.replace('k2f-al-','')*1)+'&k2f-action='+encodeURIComponent(action)+ids);
					}
					function k2f_applyNP(action,tbl){
						var ids='';
						jQuery('#k2f-al-'+(tbl.replace('k2f-al-','')*1)+' input[name="checked\\[\\]"]:checked').each(function(id,el){
							ids+='&k2f-checked[]='+encodeURIComponent(el.value);
						});
						var url=location.href+'<?php
							echo (count($callback)==2) ? Ajax::url($callback[0],$callback[1],'&') : '&k2f-notajax';
							?>&k2f-table='+(tbl.replace('k2f-al-','')*1)+'&k2f-action='+encodeURIComponent(action)+ids;
						jQuery('.k2f-adminlist').hide();
						jQuery.get(url,function(data){
							jQuery('#k2f-nopopup').html(data);
							jQuery('#k2f-nopopup').show();
						});
						return false;
					}
					function k2f_action(id,tbl){
						var act=jQuery('#k2f-al-ba-'+id).val();
						/*var res=window['k2f-options-'+tbl].indexOf('nopopup:'+act)!=-1
							? k2f_applyNP(act,tbl) :*/ k2f_apply(act,tbl);
						jQuery('#k2f-al-ba-'+id).val('');
						jQuery('#k2f-al-bb-'+id).attr('disabled',true);
						return res;
					}
					function k2f_edit(link,id,action){
						var tbl=jQuery(jQuery(link).parents('.k2f-adminlist')[0]).attr('id').replace('k2f-al-','')*1;
						var url=location.href;
						if(url[url.length-1]=='#')url=url.substr(0,url.length-1);
						url+='<?php // <- hash-in-url hotfix
							echo (count($callback)==2) ? Ajax::url($callback[0],$callback[1],'&') : '&k2f-notajax';
							?>&k2f-table='+tbl+'&k2f-action='+encodeURIComponent(action)+'&k2f-checked[]='+(id*1);
						/*if(window['k2f-options-'+tbl].indexOf('nopopup:'+action)!=-1){
							jQuery('.k2f-adminlist').hide();
							jQuery.get(url,function(data){
								jQuery('#k2f-nopopup').html(data);
								jQuery('#k2f-nopopup').show();
							});
							return false;
						}else*/ return k2f_popup(url);
					}
				</script><?php
			}
			self::$_adminlist=true; self::$pagecounter++;
			if(!is_array($options))$options=array($options);
			if(!is_array($actions))$actions=array($actions);
			if(in_array('allowadd',$options))array_unshift($actions,'new');
			// add toolbar icon and title to toolbar as well as css (icon)
			$icnm='k2f-'.Security::filename(basename($icon->_48));
			JToolBarHelper::title($title,$icnm);
			JFactory::getDocument()->addStyleDeclaration('.icon-48-'.$icnm.'{ background:url("'.Security::snohtml($icon->_48).'"); }');
			// add global options buttons
			foreach($actions as $action)self::_make_joom_button($action);
			// write beginning of wrap form
			?><form name="adminForm" method="post" action="">
				<!--joomla stuf-->
				<input type="hidden" name="option" value="com_k2f" />
				<input type="hidden" name="task" value="" />
				<input type="hidden" name="boxchecked" value="0" />
				<!--ajax call stuff-->
				<?php if(count($callback)){ ?>
					<input type="hidden" id="k2f-cls" name="k2f-cls" value="<?php echo Security::snohtml($callback[0]); ?>"/>
					<input type="hidden" id="k2f-mtd" name="k2f-mtd" value="<?php echo Security::snohtml($callback[1]); ?>"/>
				<?php }
		}
		public function adminlist_end(){
			?><div id="k2f-nopopup" style="display:none;"><!----></div></form><?php
		}
		protected static $hints=array();
		public function popup_begin($title,$hint,$width=0,$height=0,$callback=array()){
			self::$hints[]=$hint;
			?><div style="background:#444; color:#CACACA; font-size:14px; padding:6px;"><?php echo $title; ?></div>
			<form id="<?php echo Security::snohtml($id); ?>" class="type-form" action="<?php echo Security::snohtml($_SERVER['REQUEST_URI']);
//				echo '?page='.Security::snohtml(urlencode($_REQUEST['page'])).((count($callback)==2)
//						? Ajax::url($callback[0],$callback[1],'&') : '&k2f-notajax').'&k2f-table='.(int)$_REQUEST['k2f-table'];
				?>" method="post" style="display:inline-block; width:630px; margin:8px 16px;"><?php
				// reflect checked item[s] as hidden input elements
				if(isset($_REQUEST['k2f-checked']))foreach((array)$_REQUEST['k2f-checked'] as $id)
					echo '<input type="hidden" name="k2f-checked[]" value="'.(int)$id.'"/>';
		}
		protected static $buttons=array();
		public function popup_button($text,$action,$type){
			self::$buttons[]=array($text,$action,$type);
		}
		public function popup_end(){
				?><div align="right" style="font-size:18px; padding-top:12px; margin-top:16px; border-top:1px solid gray;"><?php
				foreach(self::$buttons as $i=>$button){
					list($text,$action,$type)=$button;
					switch($type){
						case 'button':
							?><input type="button" value="<?php echo Security::snohtml($text);
							?>" style="font-size:16px;" onclick='k2f_submit(this,<?php echo @json_encode($action); ?>);'/><?php
							break;
						case 'primary':
							?><input type="button" value="<?php echo Security::snohtml($text);
							?>" style="font-size:16px; font-weight:bold;" onclick='k2f_submit(this,<?php echo @json_encode($action); ?>);'/><?php
							break;
						case 'critical':
							?><input type="button" value="<?php echo Security::snohtml($text);
							?>" style="font-size:16px; font-weight:bold;" onclick='k2f_submit(this,<?php echo @json_encode($action); ?>);'/><?php
							break;
						case 'link':
							?><a style="font-size:14px;" href="javascript:;" onclick='k2f_submit(this,<?php echo @json_encode($action); ?>);'><?php
							echo Security::snohtml($text); ?></a><?php
							break;
					}
					if(isset(self::$buttons[$i+1]))echo '&nbsp;&nbsp;';
				}
				?></div><?php
				if(($hint=array_pop(self::$hints))!==null)
					echo $hint=='' ? '&nbsp;' : '<p class="howto">'.Security::snohtml($hint).'</p>';
			?></form><?php
		}
		protected static $_jk_cfg_tbl_checked=false;
		protected static function _k2f_joom_cfg_tbl_name(){
			// return correct table name
			return CFG::get('DB_PRFX').'k2f_config';
		}
		protected static function k2f_joom_cfg_tbl_check(){
			// install table if it ain't there yet
			self::$_jk_cfg_tbl_checked=true;
			// check and install table
			$tbl=self::_k2f_joom_cfg_tbl_name();
			if(!Database::db()->table_exists($tbl)){
				Database::db()->table_create($tbl);
				Database::db()->cols_add($tbl,'key','text');
				Database::db()->cols_add($tbl,'val','text');
				return;
			}
			// check and install columns
			$col=Database::db()->cols_all($tbl);
			if(!in_array('key',$col))
				Database::db()->cols_add($tbl,'val','text');
			if(!in_array('val',$col))
				Database::db()->cols_add($tbl,'val','text');
		}
		public function config_get($key){
			if(!self::$_jk_cfg_tbl_checked)self::k2f_joom_cfg_tbl_check();
			$tbl=self::_k2f_joom_cfg_tbl_name();
			$val=Database::db()->rows_load($tbl,'`key`="'.Security::escape($key).'"');
			if(!isset($val[0]) || !isset($val[0]->val))return '';
			return $val[0]->val;
		}
		public function config_set($key,$value){
			if(!self::$_jk_cfg_tbl_checked)self::k2f_joom_cfg_tbl_check();
			$tbl=self::_k2f_joom_cfg_tbl_name();
			$val=Database::db()->rows_load($tbl,'`key`="'.Security::escape($key).'"');
			if(count($val)){
				// update
				Database::db()->rows_update($tbl,(object)array('key'=>$key,'val'=>$value),'key');
			}else{
				// insert
				Database::db()->rows_insert($tbl,(object)array('key'=>$key,'val'=>$value));
			}
		}
		public function is_admin(){
			$user=&JFactory::getUser();
			return ($user->usertype=='Super Administrator') || ($user->usertype=='Administrator');
		}
		public function is_client(){
			return !JFactory::getUser()->guest;
		}
		public function is_guest(){
			return JFactory::getUser()->guest;
		}
		public function user_id(){
			return JFactory::getUser()->id;
		}
		public function user_username($id){
			return JFactory::getUser()->username;
		}
		public static $menus=array(
			'admin'=>array(
				'menu'=>array(),
				'func'=>array(),
			),
			'client'=>array(
				'menu'=>array(),
				'func'=>array(),
			),
			'guest'=>array(
				'menu'=>array(),
				'func'=>array(),
			),
		);
		public function admin_add_menu($name,$text,$icons,$handler){
			self::$menus['admin']['func'][]=$handler;
			return array_push(self::$menus['admin']['menu'],array('name'=>$name,'text'=>$text,'icons'=>$icons,'handler'=>$handler,'items'=>array()))-1;
		}
		public function admin_add_submenu($parent,$name,$text,$icons,$handler){
			self::$menus['admin']['func'][]=$handler;
			self::$menus['admin']['menu'][$parent]['items'][]=array('name'=>$name,'text'=>$text,'icons'=>$icons,'handler'=>$handler,'items'=>array());
			return array($parent,count(self::$menus['admin']['menu'][$parent]['items']));
		}
		public function client_add_menu($name,$text,$icons,$handler){
			self::$menus['client']['func'][]=$handler;
			return array_push(self::$menus['client']['menu'],array('name'=>$name,'text'=>$text,'icons'=>$icons,'handler'=>$handler,'items'=>array()))-1;
		}
		public function client_add_submenu($parent,$name,$text,$icons,$handler){
			self::$menus['client']['func'][]=$handler;
			self::$menus['client']['menu'][$parent]['items'][]=array('name'=>$name,'text'=>$text,'icons'=>$icons,'handler'=>$handler,'items'=>array());
			return array($parent,count(self::$menus['client']['menu'][$parent]['items']));
		}
		public function guest_add_menu($name,$text,$icons,$handler){
			self::$menus['guest']['func'][]=$handler;
			return array_push(self::$menus['guest']['menu'],array('name'=>$name,'text'=>$text,'icons'=>$icons,'handler'=>$handler,'items'=>array()))-1;
		}
		public function guest_add_submenu($parent,$name,$text,$icons,$handler){
			self::$menus['guest']['func'][]=$handler;
			self::$menus['guest']['menu'][$parent]['items'][]=array('name'=>$name,'text'=>$text,'icons'=>$icons,'handler'=>$handler,'items'=>array());
			return array($parent,count(self::$menus['guest']['menu'][$parent]['items']));
		}
		public function url_to_menu($menu,$args=array()){
			$handler=is_array($menu) ? self::$menus['client']['menu'][$menu[0]]['items'][$menu[0]]['handler'] : self::$menus['admin']['menu'][$menu]['handler'];
			foreach($args as $k=>$v)$args[$k]='&'.$k.'='.urlencode($v);
			return 'index.php?option=com_k2f&k2facm='.urlencode(implode('.',$handler)).implode('',$args);
		}
		public static $WriteRules=array();      // holds a list of accessible urls
		public static $RewriteRules=array();    // holds a list of url rewriter rules
		public static $RewriteRulesImp=array(); // holds a list of (important) url rewriter rules
		public function rewrite_url($search,$replace,$important=false){
			!$important ? self::$RewriteRules[$search]=$replace
				: self::$RewriteRulesImp=array_merge(array($search=>$replace),self::$RewriteRulesImp);
		}
		public function rewrite_enabled(){
			return (boolean) JFactory::getConfig()->getValue('config.sef', false);
			// the following looks ok as well, but perhaps the raw config is better in the long run?
			// return JFactory::getApplication()->getRouter()->getMode()==JROUTER_MODE_SEF;
		}
		public function write_url($handler,$arguments){
			self::$WriteRules[]=$handler;
			foreach($arguments as $n=>$v)$arguments[$n]='&'.urlencode($n).'='.urlencode($v);
			return '/index.php?option=com_k2f&k2facm='.implode('.',$handler).implode('',$arguments).'&k2fsef';
		}
		public function upload_dir(){
			// see: http://docs.joomla.org/Creating_a_file_uploader_in_your_component
			$j15=JPATH_SITE.DS.'images'.DS.'stories'.DS;
			$j16=JPATH_SITE.DS.'images'.DS;
			return is_dir($j15) ? $j15 : $j16;
		}
		public function upload_url(){
			return str_replace(array(JPATH_SITE.DS,'\\'),array(JURI::root(),'/'),$this->upload_dir());
		}
		public function login_url($redirect=''){
			return JRoute::_('index.php?option=com_user&view=login&return='.urlencode(base64_encode($redirect)));
		}
		public function logout_url($redirect=''){
			return JRoute::_('index.php?option=com_user&task=logout&return='.urlencode(base64_encode($redirect)));
		}
		public function register_url($redirect=''){
			return JRoute::_('index.php?option=com_user&view=register&return='.urlencode(base64_encode($redirect)));
		}
		public function wysiwyg($name,$html,$width,$height){
			$editor=&JFactory::getEditor();
			$params=array('smilies'=>false,'style'=>true,'layer'=>false,'table'=>false,'clear_entities'=>false);
			echo $editor->display($name,$html,$width,$height,'0','0',true,$params);
		}
		public function wysiwyg_paginate($content){
			// code from: %joomla/plugins/content/pagebreak.php
			$regex='#<hr([^>]*?)class=(\"|\')system-pagebreak(\"|\')([^>]*?)\/*>#iU'; $replc='[K2FJR'.mt_Rand().']';
			return explode($replc,preg_replace($regex,$replc,$content));
		}
	}
	Ajax::register('CmsHost_joomla','_jquery_fancy_box',array('name'=>'string'));
	Ajax::register('CmsHost_joomla','_file_data',array('name'=>'string'));

	function k2f_joom_init(){
		// K2F to Joomla router (rewrite handler)
		$rules=array_merge(CmsHost_joomla::$RewriteRulesImp,CmsHost_joomla::$RewriteRules);
		$subject=$_SERVER['REQUEST_URI']; // might need to remove root difference path (REL_WEB)
		foreach($rules as $pattern=>$handler){ // eg: '(wiki)/.*$' => ClassMethod
			$replacement='index.php?option=com_k2f&k2facm='.implode('.',$handler).'&k2fsef';
			if(($new=preg_replace('#'.$pattern.'#',$replacement,$subject))!==null && $subject!=$new){
				$new=explode('?',$new,2);
				if(isset($new[1])){
					parse_str($new[1],$new);
					foreach($new as $name=>$value)
						JRequest::setVar($name,$value,'get',true);
				}
				break;
			}
		}
		// menus stuff
		if(CmsHost::cms()->is_admin())Events::call('on_admin_menu');
		if(CmsHost::cms()->is_client())Events::call('on_registered_menu');
		if(CmsHost::cms()->is_guest())Events::call('on_guest_menu');
		// header stuff
		ob_start();
		Events::call('on_head');
		//// TEMPORARY HACK--> ////
		if(!isset($_REQUEST['option']) && !isset($_REQUEST['k2facm'])){
			?><script type="text/javascript">window.onload=function(){var div=null;var cpn=document.getElementById('cpanel');<?php
				foreach(CmsHost_joomla::$menus['admin']['menu'] as $menu){
					$html='<div class="icon"><a href="?option=com_k2f&amp;k2facm='.urlencode($menu['handler'][0].'.'.$menu['handler'][1]).'">
							<img alt="'.Security::snohtml($menu['name']).'" src="'.Security::snohtml($menu['icons']->_48).'">
							<span>'.Security::snohtml($menu['name']).'</span>
						</a></div>';
					?>div=document.createElement('DIV');div.className='icon-wrapper';div.innerHTML=<?php
						echo @json_encode($html); ?>;cpn.appendChild(div);<?php
				}
			?>}</script><?php
		}
		?><script type="text/javascript" src="http://code.jquery.com/jquery.min.js"></script>
		<script type="text/javascript" src="<?php echo Security::snohtml(Ajax::url('CmsHost_joomla','_jquery_fancy_box')); ?>&amp;name=jquery.fancybox-1.3.4.pack.js"></script>
		<link rel="stylesheet" type="text/css" href="<?php echo Security::snohtml(Ajax::url('CmsHost_joomla','_jquery_fancy_box')); ?>&amp;name=jquery.fancybox-1.3.4.pack.css" media="screen" />
		<script type="text/javascript">jQuery.noConflict();</script><?php
		//// <--TEMPORARY HACK ////
		$html=ob_get_clean();
		$doc=&JFactory::getDocument();
		$doc->addCustomTag($html);
		// show submenu items for a specific application
		foreach(CmsHost_joomla::$menus['admin']['menu'] as $parent=>$menu){
			$fnd=false;
			foreach($menu['items'] as $item)
				if(implode('.',$item['handler'])==(isset($_REQUEST['k2facm']) ? $_REQUEST['k2facm'] : '')){
					$fnd=true;
					break;
				}
			if($fnd){
				foreach($menu['items'] as $item){
					$url='?option=com_k2f&amp;k2facm='.urlencode($item['handler'][0].'.'.$item['handler'][1]);
					$iac=implode('.',$item['handler'])==(isset($_REQUEST['k2facm']) ? $_REQUEST['k2facm'] : '');
					JToolBar::getInstance('submenu')->appendButton($item['name'],$url,$iac); // text,link,is_active
				}
				break;
			}
		}
	}
	Events::add('on_after_apps_load','k2f_joom_init');

	function k2f_joom_backend(){
		// generate content for page
		if(isset($_REQUEST['k2facm'])){
			// show page content
			ob_start(); $found=false;
			?><a href="" id="keenfbh" style="display:none;"><!----></a>
			<script type="text/javascript">
				var ksbo=submitbutton;
				submitbutton=function(task){
					var cbs=[];
					jQuery.each(jQuery('input:checked'),function(i,e){ if(jQuery(e).val()!='')cbs.push('k2f-checked[]='+jQuery(e).val()); });
					k2f_popup((<?php echo @json_encode(Ajax::url('thek2fclass','thek2fmethod')); ?>)
						.replace('thek2fclass',encodeURIComponent(jQuery('#k2f-cls').val()))
						.replace('thek2fmethod',encodeURIComponent(jQuery('#k2f-mtd').val()))
						+'&k2f-table=1&k2f-action='+encodeURIComponent(task)+'&'+cbs.join('&')+'&random='+Math.random());
				}
				jQuery(document).ready(function(){
					jQuery('#keenfbh').fancybox({
						"modal":false,
						"width":500,
						"height":300,
						"padding":1
					});
				});
			</script><?php
			// BEGIN joomla hack to make wysiwyg editors work
			ob_start(); JFactory::getEditor()->display('k2fdummy','','10','10','20','20',true,array()); ob_get_clean();
			// END joomla hack to make wysiwyg editors work
			foreach(CmsHost_joomla::$menus['admin']['func'] as $handler)
				if($_REQUEST['k2facm']==implode('.',$handler)){
					if(class_exists($handler[0])){
						$args = !isset($_REQUEST['k2fsef']) ? array()
							: array(explode('/',ltrim(str_replace(CFG::get('REL_WWW'),'',$_SERVER['REQUEST_URI']),'/')));
						call_user_func_array($handler,$args);
						$found=true;
					}
					break;
				}
			if(!$found)echo 'Page not found or handler is wrong.';
			CmsHost_joomla::$_contents=ob_get_clean();
		}else{
			// show applications dashboard (this is mostly a hotfix rather than a feature)
			?><div id="cpanel"><?php
			foreach(CmsHost_joomla::$menus['admin']['menu'] as $menu)
				echo '<div class="icon"><a href="?option=com_k2f&amp;k2facm='.urlencode($menu['handler'][0].'.'.$menu['handler'][1]).'">
						<img alt="'.Security::snohtml($menu['name']).'" src="'.Security::snohtml($menu['icons']->_48).'">
						<span>'.Security::snohtml($menu['name']).'</span>
					</a></div>';
			?></div><?php
		}
		// execute controller
		$ctrl=new k2fController(array());
		$ctrl->execute('');
		$ctrl->redirect();
		// hide toolbar if it ain't used by controller
		if(!CmsHost_joomla::$_adminlist)
			JFactory::getDocument()->addStyleDeclaration('#toolbar-box{display:none;}');
	}
	Events::add('k2f_joom_backend_com','k2f_joom_backend');

	// import joomla requirements
	jimport('joomla.application.component.controller');
	jimport('joomla.application.component.view');
	// declare controller class
	class k2fController extends JController {
		function display(){
			JRequest::setVar('view','');
			JRequest::setVar('task','');
			parent::display();
		}
	}
	// declare view class
	class k2fViewk2f extends JView {
		function loadTemplate(){
			return CmsHost_joomla::$_contents;
		}
	}

	function k2f_joom_frontend(){
		// remove mootools and related crap (joomla hotfix)
		$headerstuff = JFactory::getDocument()->getHeadData();
		unset($headerstuff['scripts'][JURI::root().'includes/js/joomla.javascript.js']);
		unset($headerstuff['scripts'][JURI::root().'media/system/js/mootools.js']);
		unset($headerstuff['scripts'][JURI::root().'media/system/js/caption.js']);
		unset($headerstuff['scripts'][JURI::root().'media/system/js/validator.js']);
		unset($headerstuff['script']['text/javascript']); // remove jtooltips script
		JFactory::getDocument()->setHeadData($headerstuff);
		// generate content for page
		$mode=CmsHost::cms()->is_client() ? 'client' : 'guest';
		if(isset($_REQUEST['k2facm'])){
			// show page content
			ob_start(); $found=false;
			$handlers=CmsHost_joomla::$menus[$mode]['func']+array_values(CmsHost_joomla::$RewriteRules)+array_values(CmsHost_joomla::$RewriteRulesImp)+CmsHost_joomla::$WriteRules;
			foreach($handlers as $handler)
				if($_REQUEST['k2facm']==implode('.',$handler)){
					if(class_exists($handler[0])){
						$args = !isset($_REQUEST['k2fsef']) ? array()
							: array(explode('/',ltrim(str_replace(CFG::get('REL_WWW'),'',$_SERVER['REQUEST_URI']),'/')));
						call_user_func_array($handler,$args);
						$found=true;
					}
					break;
				}
			if(!$found)echo 'Page not found or handler is wrong.';
			CmsHost_joomla::$_contents=ob_get_clean();
		}else{
			// show applications dashboard (this is mostly a hotfix rather than a feature)
			?><div style="margin:48px 0;"><h3>Pick a Task</h3><?php
			foreach(CmsHost_joomla::$menus[$mode]['menu'] as $menu)
				echo '<a href="?option=com_k2f&amp;k2facm='.urlencode($menu['handler'][0].'.'.$menu['handler'][1]).'"
						style="display:inline-block; height:64px; padding:8px; text-align:center; text-decoration:none; width:64px;">
						<img alt="'.Security::snohtml($menu['name']).'" src="'.Security::snohtml($menu['icons']->_48).'">
						<br/><span>'.Security::snohtml($menu['name']).'</span>
					</a>';
			?></div><?php
		}
		// execute controller
		$ctrl=new k2fController(array());
		$ctrl->execute('');
		$ctrl->redirect();
	}
	Events::add('k2f_joom_frontend_com','k2f_joom_frontend');
	
?>