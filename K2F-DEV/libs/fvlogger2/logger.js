/**
 * FVLogger
 * @copyright (c) 2005 davidfmiller
 *            (c) 2010 Covac Software
 * @link http://www.fivevoltlogic.com/code/fvlogger/
 *       http://www.covac-software.com/
 * @version 2.0
 * @requires wz_tooltip.js and json2.js (both included and compressed in this file)
 */

/* wztooltip.js */
if(typeof tt_Debug=='undefined'){var tt_Debug=true;var tt_Enabled=true;var TagsToTip=true;var config={Above:false,BgColor:'#EEEEEE',BgImg:'',BorderColor:'#BEBEBE',BorderStyle:'solid',BorderWidth:1,CenterMouse:true,ClickClose:false,ClickSticky:false,CloseBtn:true,CloseBtnColors:['','','',''],CloseBtnText:'<a class="fvloggerclose" href="javascript:;"><!----></a>',CopyContent:true,Delay:400,Duration:0,Exclusive:false,FadeIn:100,FadeOut:100,FadeInterval:30,Fix:null,FollowMouse:false,FontColor:'#000044',FontFace:'Verdana,Geneva,sans-serif',FontSize:'8pt',FontWeight:'normal',Height:0,JumpHorz:false,JumpVert:true,Left:false,OffsetX:14,OffsetY:8,Opacity:100,Padding:3,Shadow:false,ShadowColor:'#C0C0C0',ShadowWidth:5,Sticky:true,TextAlign:'left',Title:'',TitleAlign:'left',TitleBgColor:'',TitleFontColor:'#333333',TitleFontFace:'',TitleFontSize:'',TitlePadding:2,Width:0};function Tip(){tt_Tip(arguments,null);}function TagToTip(){var t2t=tt_GetElt(arguments[0]);if(t2t)tt_Tip(arguments,t2t);}function UnTip(){tt_OpReHref();if(tt_aV[DURATION]<0&&(tt_iState&0x2)){tt_tDurt.Timer("tt_HideInit()",-tt_aV[DURATION],true);}else if(!(tt_aV[STICKY]&&(tt_iState&0x2)))tt_HideInit();}var tt_aElt=new Array(10),tt_aV=new Array(),tt_sContent,tt_t2t,tt_t2tDad,tt_musX,tt_musY,tt_over,tt_x,tt_y,tt_w,tt_h;function tt_Extension(){tt_ExtCmdEnum();tt_aExt[tt_aExt.length]=this;return this;}function tt_SetTipPos(x,y){var css=tt_aElt[0].style;tt_x=x;tt_y=y;css.left=x+"px";css.top=y+"px";if(tt_ie56){var ifrm=tt_aElt[tt_aElt.length-1];if(ifrm){ifrm.style.left=css.left;ifrm.style.top=css.top;}}}function tt_HideInit(){if(tt_iState){tt_ExtCallFncs(0,"HideInit");tt_iState&=~(0x4|0x8);if(tt_flagOpa&&tt_aV[FADEOUT]){tt_tFade.EndTimer();if(tt_opa){var n=Math.round(tt_aV[FADEOUT]/(tt_aV[FADEINTERVAL]*(tt_aV[OPACITY]/tt_opa)));tt_Fade(tt_opa,tt_opa,0,n);return;}}tt_tHide.Timer("tt_Hide();",1,false);}}function tt_Hide(){if(tt_db&&tt_iState){tt_OpReHref();if(tt_iState&0x2){tt_aElt[0].style.visibility="hidden";tt_ExtCallFncs(0,"Hide");}tt_tShow.EndTimer();tt_tHide.EndTimer();tt_tDurt.EndTimer();tt_tFade.EndTimer();if(!tt_op&&!tt_ie){tt_tWaitMov.EndTimer();tt_bWait=false;}if(tt_aV[CLICKCLOSE]||tt_aV[CLICKSTICKY])tt_RemEvtFnc(document,"mouseup",tt_OnLClick);tt_ExtCallFncs(0,"Kill");if(tt_t2t&&!tt_aV[COPYCONTENT])tt_UnEl2Tip();tt_iState=0;tt_over=null;tt_ResetMainDiv();if(tt_aElt[tt_aElt.length-1])tt_aElt[tt_aElt.length-1].style.display="none";}}function tt_GetElt(id){return(document.getElementById?document.getElementById(id):document.all?document.all[id]:null);}function tt_GetDivW(el){return(el?(el.offsetWidth||el.style.pixelWidth||0):0);}function tt_GetDivH(el){return(el?(el.offsetHeight||el.style.pixelHeight||0):0);}function tt_GetScrollX(){return(window.pageXOffset||(tt_db?(tt_db.scrollLeft||0):0));}function tt_GetScrollY(){return(window.pageYOffset||(tt_db?(tt_db.scrollTop||0):0));}function tt_GetClientW(){return tt_GetWndCliSiz("Width");}function tt_GetClientH(){return tt_GetWndCliSiz("Height");}function tt_GetEvtX(e){return(e?((typeof(e.pageX)!=tt_u)?e.pageX:(e.clientX+tt_GetScrollX())):0);}function tt_GetEvtY(e){return(e?((typeof(e.pageY)!=tt_u)?e.pageY:(e.clientY+tt_GetScrollY())):0);}function tt_AddEvtFnc(el,sEvt,PFnc){if(el){if(el.addEventListener){el.addEventListener(sEvt,PFnc,false);}else{el.attachEvent("on"+sEvt,PFnc);}}}function tt_RemEvtFnc(el,sEvt,PFnc){if(el){if(el.removeEventListener){el.removeEventListener(sEvt,PFnc,false);}else{el.detachEvent("on"+sEvt,PFnc);}}}function tt_GetDad(el){return(el.parentNode||el.parentElement||el.offsetParent);}function tt_MovDomNode(el,dadFrom,dadTo){if(dadFrom)dadFrom.removeChild(el);if(dadTo)dadTo.appendChild(el);}var tt_aExt=new Array(),tt_db,tt_op,tt_ie,tt_ie56,tt_bBoxOld,tt_body,tt_ovr_,tt_flagOpa,tt_maxPosX,tt_maxPosY,tt_iState=0,tt_opa,tt_bJmpVert,tt_bJmpHorz,tt_elDeHref,tt_tShow=new Number(0),tt_tHide=new Number(0),tt_tDurt=new Number(0),tt_tFade=new Number(0),tt_tWaitMov=new Number(0),tt_bWait=false,tt_u="undefined";function tt_Init(){tt_MkCmdEnum();if(!tt_Browser()||!tt_MkMainDiv())return;tt_IsW3cBox();tt_OpaSupport();tt_AddEvtFnc(document,"mousemove",tt_Move);if(TagsToTip||tt_Debug)tt_SetOnloadFnc();tt_AddEvtFnc(window,"unload",tt_Hide);}function tt_MkCmdEnum(){var n=0;for(var i in config)eval("window."+i.toString().toUpperCase()+" = "+n++);tt_aV.length=n;}function tt_Browser(){var n,nv,n6,w3c;n=navigator.userAgent.toLowerCase(),nv=navigator.appVersion;tt_op=(document.defaultView&&typeof(eval("w"+"indow"+"."+"o"+"p"+"er"+"a"))!=tt_u);tt_ie=n.indexOf("msie")!=-1&&document.all&&!tt_op;if(tt_ie){var ieOld=(!document.compatMode||document.compatMode=="BackCompat");tt_db=!ieOld?document.documentElement:(document.body||null);if(tt_db)tt_ie56=parseFloat(nv.substring(nv.indexOf("MSIE")+5))>=5.5&&typeof document.body.style.maxHeight==tt_u;}else{tt_db=document.documentElement||document.body||(document.getElementsByTagName?document.getElementsByTagName("body")[0]:null);if(!tt_op){n6=document.defaultView&&typeof document.defaultView.getComputedStyle!=tt_u;w3c=!n6&&document.getElementById;}}tt_body=(document.getElementsByTagName?document.getElementsByTagName("body")[0]:(document.body||null));if(tt_ie||n6||tt_op||w3c){if(tt_body&&tt_db){if(document.attachEvent||document.addEventListener)return true;}else{tt_Err("wz_tooltip.js must be included INSIDE the body section,"+" immediately after the opening <body> tag.",false);}}tt_db=null;return false;}function tt_MkMainDiv(){if(tt_body.insertAdjacentHTML){tt_body.insertAdjacentHTML("afterBegin",tt_MkMainDivHtm());}else if(typeof tt_body.innerHTML!=tt_u&&document.createElement&&tt_body.appendChild)tt_body.appendChild(tt_MkMainDivDom());if(window.tt_GetMainDivRefs&&tt_GetMainDivRefs())return true;tt_db=null;return false;}function tt_MkMainDivHtm(){return('<div id="WzTtDiV"></div>'+(tt_ie56?('<iframe id="WzTtIfRm" src="javascript:false" scrolling="no" frameborder="0" style="filter:Alpha(opacity=0);position:absolute;top:0px;left:0px;display:none;"></iframe>'):''));}function tt_MkMainDivDom(){var el=document.createElement("div");if(el)el.id="WzTtDiV";return el;}function tt_GetMainDivRefs(){tt_aElt[0]=tt_GetElt("WzTtDiV");if(tt_ie56&&tt_aElt[0]){tt_aElt[tt_aElt.length-1]=tt_GetElt("WzTtIfRm");if(!tt_aElt[tt_aElt.length-1])tt_aElt[0]=null;}if(tt_aElt[0]){var css=tt_aElt[0].style;css.visibility="hidden";css.position="absolute";css.overflow="hidden";return true;}return false;}function tt_ResetMainDiv(){tt_SetTipPos(0,0);tt_aElt[0].innerHTML="";tt_aElt[0].style.width="0px";tt_h=0;}function tt_IsW3cBox(){var css=tt_aElt[0].style;css.padding="10px";css.width="40px";tt_bBoxOld=(tt_GetDivW(tt_aElt[0])==40);css.padding="0px";tt_ResetMainDiv();}function tt_OpaSupport(){var css=tt_body.style;tt_flagOpa=(typeof(css.KhtmlOpacity)!=tt_u)?2:(typeof(css.KHTMLOpacity)!=tt_u)?3:(typeof(css.MozOpacity)!=tt_u)?4:(typeof(css.opacity)!=tt_u)?5:(typeof(css.filter)!=tt_u)?1:0;}function tt_SetOnloadFnc(){tt_AddEvtFnc(document,"DOMContentLoaded",tt_HideSrcTags);tt_AddEvtFnc(window,"load",tt_HideSrcTags);if(tt_body.attachEvent)tt_body.attachEvent("onreadystatechange",function(){if(tt_body.readyState=="complete")tt_HideSrcTags();});if(/WebKit|KHTML/i.test(navigator.userAgent)){var t=setInterval(function(){if(/loaded|complete/.test(document.readyState)){clearInterval(t);tt_HideSrcTags();}},10);}}function tt_HideSrcTags(){if(!window.tt_HideSrcTags||window.tt_HideSrcTags.done)return;window.tt_HideSrcTags.done=true;if(!tt_HideSrcTagsRecurs(tt_body))tt_Err("There are HTML elements to be converted to tooltips.\nIf you"+" want these HTML elements to be automatically hidden, you"+" must edit wz_tooltip.js, and set TagsToTip in the global"+" tooltip configuration to true.",true);}function tt_HideSrcTagsRecurs(dad){var ovr,asT2t;var a=dad.childNodes||dad.children||null;for(var i=a?a.length:0;i;){--i;if(!tt_HideSrcTagsRecurs(a[i]))return false;ovr=a[i].getAttribute?(a[i].getAttribute("onmouseover")||a[i].getAttribute("onclick")):(typeof a[i].onmouseover=="function")?(a[i].onmouseover||a[i].onclick):null;if(ovr){asT2t=ovr.toString().match(/TagToTip\s*\(\s*'[^'.]+'\s*[\),]/);if(asT2t&&asT2t.length){if(!tt_HideSrcTag(asT2t[0]))return false;}}}return true;}function tt_HideSrcTag(sT2t){var id,el;id=sT2t.replace(/.+'([^'.]+)'.+/,"$1");el=tt_GetElt(id);if(el){if(tt_Debug&&!TagsToTip){return false;}else{el.style.display="none";}}else tt_Err("Invalid ID\n'"+id+"'\npassed to TagToTip()."+" There exists no HTML element with that ID.",true);return true;}function tt_Tip(arg,t2t){if(!tt_db||(tt_iState&0x8))return;if(tt_iState)tt_Hide();if(!tt_Enabled)return;tt_t2t=t2t;if(!tt_ReadCmds(arg))return;tt_iState=0x1|0x4;tt_AdaptConfig1();tt_MkTipContent(arg);tt_MkTipSubDivs();tt_FormatTip();tt_bJmpVert=false;tt_bJmpHorz=false;tt_maxPosX=tt_GetClientW()+tt_GetScrollX()-tt_w-1;tt_maxPosY=tt_GetClientH()+tt_GetScrollY()-tt_h-1;tt_AdaptConfig2();tt_OverInit();tt_ShowInit();tt_Move();}function tt_ReadCmds(a){var i;i=0;for(var j in config)tt_aV[i++]=config[j];if(a.length&1){for(i=a.length-1;i>0;i-=2)tt_aV[a[i-1]]=a[i];return true;}tt_Err("Incorrect call of Tip() or TagToTip().\n"+"Each command must be followed by a value.",true);return false;}function tt_AdaptConfig1(){tt_ExtCallFncs(0,"LoadConfig");if(!tt_aV[TITLEBGCOLOR].length)tt_aV[TITLEBGCOLOR]=tt_aV[BORDERCOLOR];if(!tt_aV[TITLEFONTCOLOR].length)tt_aV[TITLEFONTCOLOR]=tt_aV[BGCOLOR];if(!tt_aV[TITLEFONTFACE].length)tt_aV[TITLEFONTFACE]=tt_aV[FONTFACE];if(!tt_aV[TITLEFONTSIZE].length)tt_aV[TITLEFONTSIZE]=tt_aV[FONTSIZE];if(tt_aV[CLOSEBTN]){if(!tt_aV[CLOSEBTNCOLORS])tt_aV[CLOSEBTNCOLORS]=new Array("","","","");for(var i=4;i;){--i;if(!tt_aV[CLOSEBTNCOLORS][i].length)tt_aV[CLOSEBTNCOLORS][i]=(i&1)?tt_aV[TITLEFONTCOLOR]:tt_aV[TITLEBGCOLOR];}if(!tt_aV[TITLE].length)tt_aV[TITLE]=" ";}if(tt_aV[OPACITY]==100&&typeof tt_aElt[0].style.MozOpacity!=tt_u&&!Array.every)tt_aV[OPACITY]=99;if(tt_aV[FADEIN]&&tt_flagOpa&&tt_aV[DELAY]>100)tt_aV[DELAY]=Math.max(tt_aV[DELAY]-tt_aV[FADEIN],100);}function tt_AdaptConfig2(){if(tt_aV[CENTERMOUSE]){tt_aV[OFFSETX]-=((tt_w-(tt_aV[SHADOW]?tt_aV[SHADOWWIDTH]:0))>>1);tt_aV[JUMPHORZ]=false;}}function tt_MkTipContent(a){if(tt_t2t){if(tt_aV[COPYCONTENT]){tt_sContent=tt_t2t.innerHTML;}else tt_sContent="";}else tt_sContent=a[0];tt_ExtCallFncs(0,"CreateContentString");}function tt_MkTipSubDivs(){var sCss='position:relative;margin:0px;padding:0px;border-width:0px;left:0px;top:0px;line-height:normal;width:auto;',sTbTrTd=' cellspacing="0" cellpadding="0" border="0" style="'+sCss+'"><tbody style="'+sCss+'"><tr><td ';tt_aElt[0].style.width=tt_GetClientW()+"px";tt_aElt[0].innerHTML=(''+(tt_aV[TITLE].length?('<div id="WzTiTl" style="position:relative;z-index:1;">'+'<table id="WzTiTlTb"'+sTbTrTd+'id="WzTiTlI" style="'+sCss+'">'+tt_aV[TITLE]+'</td>'+(tt_aV[CLOSEBTN]?('<td align="right" style="'+sCss+'text-align:right;">'+'<span id="WzClOsE" style="position:relative;left:2px;padding-left:2px;padding-right:2px;'+'cursor:'+(tt_ie?'hand':'pointer')+';" onmouseover="tt_OnCloseBtnOver(1)" onmouseout="tt_OnCloseBtnOver(0)" onclick="tt_HideInit()">'+tt_aV[CLOSEBTNTEXT]+'</span></td>'):'')+'</tr></tbody></table></div>'):'')+'<div id="WzBoDy" style="position:relative;z-index:0;">'+'<table'+sTbTrTd+'id="WzBoDyI" style="'+sCss+'">'+tt_sContent+'</td></tr></tbody></table></div>'+(tt_aV[SHADOW]?('<div id="WzTtShDwR" style="position:absolute;overflow:hidden;"></div>'+'<div id="WzTtShDwB" style="position:relative;overflow:hidden;"></div>'):''));tt_GetSubDivRefs();if(tt_t2t&&!tt_aV[COPYCONTENT])tt_El2Tip();tt_ExtCallFncs(0,"SubDivsCreated");}function tt_GetSubDivRefs(){var aId=new Array("WzTiTl","WzTiTlTb","WzTiTlI","WzClOsE","WzBoDy","WzBoDyI","WzTtShDwB","WzTtShDwR");for(var i=aId.length;i;--i)tt_aElt[i]=tt_GetElt(aId[i-1]);}function tt_FormatTip(){var css,w,h,pad=tt_aV[PADDING],padT,wBrd=tt_aV[BORDERWIDTH],iOffY,iOffSh,iAdd=(pad+wBrd)<<1;if(tt_aV[TITLE].length){padT=tt_aV[TITLEPADDING];css=tt_aElt[1].style;css.background=tt_aV[TITLEBGCOLOR];css.paddingTop=css.paddingBottom=padT+"px";css.paddingLeft=css.paddingRight=(padT+2)+"px";css=tt_aElt[3].style;css.color=tt_aV[TITLEFONTCOLOR];if(tt_aV[WIDTH]==-1)css.whiteSpace="nowrap";css.fontFamily=tt_aV[TITLEFONTFACE];css.fontSize=tt_aV[TITLEFONTSIZE];css.fontWeight="bold";css.textAlign=tt_aV[TITLEALIGN];if(tt_aElt[4]){css=tt_aElt[4].style;css.background=tt_aV[CLOSEBTNCOLORS][0];css.color=tt_aV[CLOSEBTNCOLORS][1];css.fontFamily=tt_aV[TITLEFONTFACE];css.fontSize=tt_aV[TITLEFONTSIZE];css.fontWeight="bold";}if(tt_aV[WIDTH]>0){tt_w=tt_aV[WIDTH];}else{tt_w=tt_GetDivW(tt_aElt[3])+tt_GetDivW(tt_aElt[4]);if(tt_aElt[4])tt_w+=pad;if(tt_aV[WIDTH]<-1&&tt_w>-tt_aV[WIDTH])tt_w=-tt_aV[WIDTH];}iOffY=-wBrd;}else{tt_w=0;iOffY=0;}css=tt_aElt[5].style;css.top=iOffY+"px";if(wBrd){css.borderColor=tt_aV[BORDERCOLOR];css.borderStyle=tt_aV[BORDERSTYLE];css.borderWidth=wBrd+"px";}if(tt_aV[BGCOLOR].length)css.background=tt_aV[BGCOLOR];if(tt_aV[BGIMG].length)css.backgroundImage="url("+tt_aV[BGIMG]+")";css.padding=pad+"px";css.textAlign=tt_aV[TEXTALIGN];if(tt_aV[HEIGHT]){css.overflow="auto";if(tt_aV[HEIGHT]>0){css.height=(tt_aV[HEIGHT]+iAdd)+"px";}else tt_h=iAdd-tt_aV[HEIGHT];}css=tt_aElt[6].style;css.color=tt_aV[FONTCOLOR];css.fontFamily=tt_aV[FONTFACE];css.fontSize=tt_aV[FONTSIZE];css.fontWeight=tt_aV[FONTWEIGHT];css.textAlign=tt_aV[TEXTALIGN];if(tt_aV[WIDTH]>0){w=tt_aV[WIDTH];}else if(tt_aV[WIDTH]==-1&&tt_w){w=tt_w;}else{w=tt_GetDivW(tt_aElt[6]);if(tt_aV[WIDTH]<-1&&w>-tt_aV[WIDTH])w=-tt_aV[WIDTH];}if(w>tt_w)tt_w=w;tt_w+=iAdd;if(tt_aV[SHADOW]){tt_w+=tt_aV[SHADOWWIDTH];iOffSh=Math.floor((tt_aV[SHADOWWIDTH]*4)/3);css=tt_aElt[7].style;css.top=iOffY+"px";css.left=iOffSh+"px";css.width=(tt_w-iOffSh-tt_aV[SHADOWWIDTH])+"px";css.height=tt_aV[SHADOWWIDTH]+"px";css.background=tt_aV[SHADOWCOLOR];css=tt_aElt[8].style;css.top=iOffSh+"px";css.left=(tt_w-tt_aV[SHADOWWIDTH])+"px";css.width=tt_aV[SHADOWWIDTH]+"px";css.background=tt_aV[SHADOWCOLOR];}else iOffSh=0;tt_SetTipOpa(tt_aV[FADEIN]?0:tt_aV[OPACITY]);tt_FixSize(iOffY,iOffSh);}function tt_FixSize(iOffY,iOffSh){var wIn,wOut,h,add,pad=tt_aV[PADDING],wBrd=tt_aV[BORDERWIDTH],i;tt_aElt[0].style.width=tt_w+"px";tt_aElt[0].style.pixelWidth=tt_w;wOut=tt_w-((tt_aV[SHADOW])?tt_aV[SHADOWWIDTH]:0);wIn=wOut;if(!tt_bBoxOld)wIn-=(pad+wBrd)<<1;tt_aElt[5].style.width=wIn+"px";if(tt_aElt[1]){wIn=wOut-((tt_aV[TITLEPADDING]+2)<<1);if(!tt_bBoxOld)wOut=wIn;tt_aElt[1].style.width=wOut+"px";tt_aElt[2].style.width=wIn+"px";}if(tt_h){h=tt_GetDivH(tt_aElt[5]);if(h>tt_h){if(!tt_bBoxOld)tt_h-=(pad+wBrd)<<1;tt_aElt[5].style.height=tt_h+"px";}}tt_h=tt_GetDivH(tt_aElt[0])+iOffY;if(tt_aElt[8])tt_aElt[8].style.height=(tt_h-iOffSh)+"px";i=tt_aElt.length-1;if(tt_aElt[i]){tt_aElt[i].style.width=tt_w+"px";tt_aElt[i].style.height=tt_h+"px";}}function tt_DeAlt(el){var aKid;if(el){if(el.alt)el.alt="";if(el.title)el.title="";aKid=el.childNodes||el.children||null;if(aKid){for(var i=aKid.length;i;)tt_DeAlt(aKid[--i]);}}}function tt_OpDeHref(el){if(!tt_op)return;if(tt_elDeHref)tt_OpReHref();while(el){if(el.hasAttribute&&el.hasAttribute("href")){el.t_href=el.getAttribute("href");el.t_stats=window.status;el.removeAttribute("href");el.style.cursor="hand";tt_AddEvtFnc(el,"mousedown",tt_OpReHref);window.status=el.t_href;tt_elDeHref=el;break;}el=tt_GetDad(el);}}function tt_OpReHref(){if(tt_elDeHref){tt_elDeHref.setAttribute("href",tt_elDeHref.t_href);tt_RemEvtFnc(tt_elDeHref,"mousedown",tt_OpReHref);window.status=tt_elDeHref.t_stats;tt_elDeHref=null;}}function tt_El2Tip(){var css=tt_t2t.style;tt_t2t.t_cp=css.position;tt_t2t.t_cl=css.left;tt_t2t.t_ct=css.top;tt_t2t.t_cd=css.display;tt_t2tDad=tt_GetDad(tt_t2t);tt_MovDomNode(tt_t2t,tt_t2tDad,tt_aElt[6]);css.display="block";css.position="static";css.left=css.top=css.marginLeft=css.marginTop="0px";}function tt_UnEl2Tip(){var css=tt_t2t.style;css.display=tt_t2t.t_cd;tt_MovDomNode(tt_t2t,tt_GetDad(tt_t2t),tt_t2tDad);css.position=tt_t2t.t_cp;css.left=tt_t2t.t_cl;css.top=tt_t2t.t_ct;tt_t2tDad=null;}function tt_OverInit(){if(window.event){tt_over=window.event.target||window.event.srcElement;}else tt_over=tt_ovr_;tt_DeAlt(tt_over);tt_OpDeHref(tt_over);}function tt_ShowInit(){tt_tShow.Timer("tt_Show()",tt_aV[DELAY],true);if(tt_aV[CLICKCLOSE]||tt_aV[CLICKSTICKY])tt_AddEvtFnc(document,"mouseup",tt_OnLClick);}function tt_Show(){var css=tt_aElt[0].style;css.zIndex=Math.max((window.dd&&dd.z)?(dd.z+2):0,1010);if(tt_aV[STICKY]||!tt_aV[FOLLOWMOUSE])tt_iState&=~0x4;if(tt_aV[EXCLUSIVE])tt_iState|=0x8;if(tt_aV[DURATION]>0)tt_tDurt.Timer("tt_HideInit()",tt_aV[DURATION],true);tt_ExtCallFncs(0,"Show");css.visibility="visible";tt_iState|=0x2;if(tt_aV[FADEIN])tt_Fade(0,0,tt_aV[OPACITY],Math.round(tt_aV[FADEIN]/tt_aV[FADEINTERVAL]));tt_ShowIfrm();}function tt_ShowIfrm(){if(tt_ie56){var ifrm=tt_aElt[tt_aElt.length-1];if(ifrm){var css=ifrm.style;css.zIndex=tt_aElt[0].style.zIndex-1;css.display="block";}}}function tt_Move(e){if(e)tt_ovr_=e.target||e.srcElement;e=e||window.event;if(e){tt_musX=tt_GetEvtX(e);tt_musY=tt_GetEvtY(e);}if(tt_iState&0x4){if(!tt_op&&!tt_ie){if(tt_bWait)return;tt_bWait=true;tt_tWaitMov.Timer("tt_bWait = false;",1,true);}if(tt_aV[FIX]){tt_iState&=~0x4;tt_PosFix();}else if(!tt_ExtCallFncs(e,"MoveBefore"))tt_SetTipPos(tt_Pos(0),tt_Pos(1));tt_ExtCallFncs([tt_musX,tt_musY],"MoveAfter")}}function tt_Pos(iDim){var iX,bJmpMod,cmdAlt,cmdOff,cx,iMax,iScrl,iMus,bJmp;if(iDim){bJmpMod=tt_aV[JUMPVERT];cmdAlt=ABOVE;cmdOff=OFFSETY;cx=tt_h;iMax=tt_maxPosY;iScrl=tt_GetScrollY();iMus=tt_musY;bJmp=tt_bJmpVert;}else{bJmpMod=tt_aV[JUMPHORZ];cmdAlt=LEFT;cmdOff=OFFSETX;cx=tt_w;iMax=tt_maxPosX;iScrl=tt_GetScrollX();iMus=tt_musX;bJmp=tt_bJmpHorz;}if(bJmpMod){if(tt_aV[cmdAlt]&&(!bJmp||tt_CalcPosAlt(iDim)>=iScrl+16)){iX=tt_PosAlt(iDim);}else if(!tt_aV[cmdAlt]&&bJmp&&tt_CalcPosDef(iDim)>iMax-16){iX=tt_PosAlt(iDim);}else iX=tt_PosDef(iDim);}else{iX=iMus;if(tt_aV[cmdAlt]){iX-=cx+tt_aV[cmdOff]-(tt_aV[SHADOW]?tt_aV[SHADOWWIDTH]:0);}else iX+=tt_aV[cmdOff];}if(iX>iMax)iX=bJmpMod?tt_PosAlt(iDim):iMax;if(iX<iScrl)iX=bJmpMod?tt_PosDef(iDim):iScrl;return iX;}function tt_PosDef(iDim){if(iDim){tt_bJmpVert=tt_aV[ABOVE];}else tt_bJmpHorz=tt_aV[LEFT];return tt_CalcPosDef(iDim);}function tt_PosAlt(iDim){if(iDim){tt_bJmpVert=!tt_aV[ABOVE];}else tt_bJmpHorz=!tt_aV[LEFT];return tt_CalcPosAlt(iDim);}function tt_CalcPosDef(iDim){return iDim?(tt_musY+tt_aV[OFFSETY]):(tt_musX+tt_aV[OFFSETX]);}function tt_CalcPosAlt(iDim){var cmdOff=iDim?OFFSETY:OFFSETX;var dx=tt_aV[cmdOff]-(tt_aV[SHADOW]?tt_aV[SHADOWWIDTH]:0);if(tt_aV[cmdOff]>0&&dx<=0)dx=1;return((iDim?(tt_musY-tt_h):(tt_musX-tt_w))-dx);}function tt_PosFix(){var iX,iY;if(typeof(tt_aV[FIX][0])=="number"){iX=tt_aV[FIX][0];iY=tt_aV[FIX][1];}else{if(typeof(tt_aV[FIX][0])=="string"){el=tt_GetElt(tt_aV[FIX][0]);}else el=tt_aV[FIX][0];iX=tt_aV[FIX][1];iY=tt_aV[FIX][2];if(!tt_aV[ABOVE]&&el)iY+=tt_GetDivH(el);for(;el;el=el.offsetParent){iX+=el.offsetLeft||0;iY+=el.offsetTop||0;}}if(tt_aV[ABOVE])iY-=tt_h;tt_SetTipPos(iX,iY);}function tt_Fade(a,now,z,n){if(n){now+=Math.round((z-now)/n);if((z>a)?(now>=z):(now<=z)){now=z;}else tt_tFade.Timer("tt_Fade("+a+","+now+","+z+","+(n-1)+")",tt_aV[FADEINTERVAL],true);}now?tt_SetTipOpa(now):tt_Hide();}function tt_SetTipOpa(opa){tt_SetOpa(tt_aElt[5],opa);if(tt_aElt[1])tt_SetOpa(tt_aElt[1],opa);if(tt_aV[SHADOW]){opa=Math.round(opa*0.8);tt_SetOpa(tt_aElt[7],opa);tt_SetOpa(tt_aElt[8],opa);}}function tt_OnCloseBtnOver(iOver){var css=tt_aElt[4].style;iOver<<=1;css.background=tt_aV[CLOSEBTNCOLORS][iOver];css.color=tt_aV[CLOSEBTNCOLORS][iOver+1];}function tt_OnLClick(e){e=e||window.event;if(!((e.button&&e.button&2)||(e.which&&e.which==3))){if(tt_aV[CLICKSTICKY]&&(tt_iState&0x4)){tt_aV[STICKY]=true;tt_iState&=~0x4;}else if(tt_aV[CLICKCLOSE])tt_HideInit();}}function tt_Int(x){var y;return(isNaN(y=parseInt(x))?0:y);}Number.prototype.Timer=function(s,iT,bUrge){if(!this.value||bUrge)this.value=window.setTimeout(s,iT);};Number.prototype.EndTimer=function(){if(this.value){window.clearTimeout(this.value);this.value=0;}};function tt_GetWndCliSiz(s){var db,y=window["inner"+s],sC="client"+s,sN="number";if(typeof y==sN){var y2;return(((db=document.body)&&typeof(y2=db[sC])==sN&&y2&&y2<=y)?y2:((db=document.documentElement)&&typeof(y2=db[sC])==sN&&y2&&y2<=y)?y2:y);}return(((db=document.documentElement)&&(y=db[sC]))?y:document.body[sC]);}function tt_SetOpa(el,opa){var css=el.style;tt_opa=opa;if(tt_flagOpa==1){if(opa<100){if(typeof(el.filtNo)==tt_u)el.filtNo=css.filter;var bVis=css.visibility!="hidden";css.zoom="100%";if(!bVis)css.visibility="visible";css.filter="alpha(opacity="+opa+")";if(!bVis)css.visibility="hidden";}else if(typeof(el.filtNo)!=tt_u)css.filter=el.filtNo;}else{opa/=100.0;switch(tt_flagOpa){case 2:css.KhtmlOpacity=opa;break;case 3:css.KHTMLOpacity=opa;break;case 4:css.MozOpacity=opa;break;case 5:css.opacity=opa;break;}}}function tt_Err(sErr,bIfDebug){if(tt_Debug||!bIfDebug)alert("Tooltip Script Error Message:\n\n"+sErr);}function tt_ExtCmdEnum(){var s;for(var i in config){s="window."+i.toString().toUpperCase();if(eval("typeof("+s+") == tt_u")){eval(s+" = "+tt_aV.length);tt_aV[tt_aV.length]=null;}}}function tt_ExtCallFncs(arg,sFnc){var b=false;for(var i=tt_aExt.length;i;){--i;var fnc=tt_aExt[i]["On"+sFnc];if(fnc&&fnc(arg))b=true;}return b;}tt_Init();}

/* json2.js */
if(!this.JSON){this.JSON={};}(function(){function f(n){return n<10?'0'+n:n;}if(typeof Date.prototype.toJSON!=='function'){Date.prototype.toJSON=function(key){return isFinite(this.valueOf())?this.getUTCFullYear()+'-'+f(this.getUTCMonth()+1)+'-'+f(this.getUTCDate())+'T'+f(this.getUTCHours())+':'+f(this.getUTCMinutes())+':'+f(this.getUTCSeconds())+'Z':null;};String.prototype.toJSON=Number.prototype.toJSON=Boolean.prototype.toJSON=function(key){return this.valueOf();};}var cx=/[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,escapable=/[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,gap,indent,meta={'\b':'\\b','\t':'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"':'\\"','\\':'\\\\'},rep;function quote(string){escapable.lastIndex=0;return escapable.test(string)?'"'+string.replace(escapable,function(a){var c=meta[a];return typeof c==='string'?c:'\\u'+('0000'+a.charCodeAt(0).toString(16)).slice(-4);})+'"':'"'+string+'"';}function str(key,holder){var i,k,v,length,mind=gap,partial,value=holder[key];if(value&&typeof value==='object'&&typeof value.toJSON==='function'){value=value.toJSON(key);}if(typeof rep==='function'){value=rep.call(holder,key,value);}switch(typeof value){case'string':return quote(value);case'number':return isFinite(value)?String(value):'null';case'boolean':case'null':return String(value);case'object':if(!value){return'null';}gap+=indent;partial=[];if(Object.prototype.toString.apply(value)==='[object Array]'){length=value.length;for(i=0;i<length;i+=1){partial[i]=str(i,value)||'null';}v=partial.length===0?'[]':gap?'[\n'+gap+partial.join(',\n'+gap)+'\n'+mind+']':'['+partial.join(',')+']';gap=mind;return v;}if(rep&&typeof rep==='object'){length=rep.length;for(i=0;i<length;i+=1){k=rep[i];if(typeof k==='string'){v=str(k,value);if(v){partial.push(quote(k)+(gap?': ':':')+v);}}}}else{for(k in value){if(Object.hasOwnProperty.call(value,k)){v=str(k,value);if(v){partial.push(quote(k)+(gap?': ':':')+v);}}}}v=partial.length===0?'{}':gap?'{\n'+gap+partial.join(',\n'+gap)+'\n'+mind+'}':'{'+partial.join(',')+'}';gap=mind;return v;}}if(typeof JSON.stringify!=='function'){JSON.stringify=function(value,replacer,space){var i;gap='';indent='';if(typeof space==='number'){for(i=0;i<space;i+=1){indent+=' ';}}else if(typeof space==='string'){indent=space;}rep=replacer;if(replacer&&typeof replacer!=='function'&&(typeof replacer!=='object'||typeof replacer.length!=='number')){throw new Error('JSON.stringify');}return str('',{'':value});};}if(typeof JSON.parse!=='function'){JSON.parse=function(text,reviver){var j;function walk(holder,key){var k,v,value=holder[key];if(value&&typeof value==='object'){for(k in value){if(Object.hasOwnProperty.call(value,k)){v=walk(value,k);if(v!==undefined){value[k]=v;}else{delete value[k];}}}}return reviver.call(holder,key,value);}text=String(text);cx.lastIndex=0;if(cx.test(text)){text=text.replace(cx,function(a){return'\\u'+('0000'+a.charCodeAt(0).toString(16)).slice(-4);});}if(/^[\],:{}\s]*$/.test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,'@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,']').replace(/(?:^|:|,)(?:\s*\[)+/g,''))){j=eval('('+text+')');return typeof reviver==='function'?walk({'':j},''):j;}throw new SyntaxError('JSON.parse');};}}());

// version number
var FVLOGGER_VERSION = '2.0';

// turn logging on or off;
var FVL_LOG_ON = true;

// all logging statements that whose level is greater than or equal to FVL_DEFAULT_LOG_LEVEL will be processed;
// all others will be ignored
var FVL_DEFAULT_LOG_LEVEL = FVL_DEBUG;

// the id of the node that will have the logging statements appended to it
var FVL_LOG_ID = 'fvlogger';

// the element that should be wrapped around the log messages
var FVL_LOG_ELEMENT = 'p';

/* the code that follows is */

// constants for logging levels
var FVL_DEBUG = 0;
var FVL_INFO = 1;
var FVL_WARN = 2;
var FVL_ERROR = 3;
var FVL_FATAL = 4;

// the css classes that will be applied to the logging elements
var FVL_LOG_CLASSES = new Array("debug","info","warn","error","fatal");

/* */

// retrieves the element whose id is equal to FVL_LOG_ID
function getLogger(id) {
	if (arguments.length == 0) {id = FVL_LOG_ID;}
	return document.getElementById(id);
}

function showDebug() {FVL_showMessages(FVL_DEBUG);}
function showInfo() {FVL_showMessages(FVL_INFO);}
function showWarn() {FVL_showMessages(FVL_WARN);}
function showError() {FVL_showMessages(FVL_ERROR);}
function showFatal() {FVL_showMessages(FVL_FATAL);}
function showAll() {FVL_showMessages();}

// removes all logging information from the logging element
function eraseLog(ask) {
	var debug = getLogger();
	if (! debug) {return false;}

	if (ask && ! confirm("Are you sure you wish to erase the log?")) {
		return false;
	}

	var ps = debug.getElementsByTagName(FVL_LOG_ELEMENT);
	var length = ps.length;
	for (var i = 0; i < length; i++) {debug.removeChild(ps[length - i - 1]);}
	return true;
}

function debug() {FVL_log(arguments, FVL_DEBUG);}
function warn()  {FVL_log(arguments, FVL_WARN);}
function info()  {FVL_log(arguments, FVL_INFO);}
function error() {FVL_log(arguments, FVL_ERROR);}
//function fatal(message) { FVL_log("" + message, FVL_FATAL);}

function windowError(message, url, line) {
	FVL_log('Error on line ' + line + ' of document ' + url + ': ' + message, FVL_FATAL);
	return true; //
}

// only override the window's error handler if we logging is turned on
if (FVL_LOG_ON) {
	window.onerror = windowError;
}

// 
function FVL_showMessages(level, hideOthers) {

//	alert('showing ' + level);

	var showAll = false;
	// if no level has been specified, use the default
	if (arguments.length == 0) {level = FVL_DEFAULT_LOG_LEVEL;showAll = true;}
	if (arguments.length < 2) {hideOthers = true;}

	// retrieve the element and current statements
	var debug = getLogger();
	if (! debug) {return false;}
	var ps = debug.getElementsByTagName("p");
	if (ps.length == 0) {return true;}

	// get the number of nodes in the list
	var l = ps.length; 

	// get the class name for the specified level
	var lookup = FVL_LOG_CLASSES[level]; 

	// loop through all logging statements/<p> elements...
	for (var i = l - 1; i >= 0; i--) {

		// hide all elements by default, if specified
		if (hideOthers) {hide(ps[i]);} 

		// get the class name for this <p>
		var c = getNodeClass(ps[i]);
//		alert(c);
//		alert("Node #" + i + "'s class is:" + c);
		if (c && c.indexOf(lookup) > -1 || showAll) {show(ps[i]);} 
	}
	
	// return default value
	return false;
}

function htmlspecialchars(html){
	var div = document.createElement('DIV');
	var text = document.createTextNode(html);
	div.appendChild(text);
	return div.innerHTML;
}

function domtohtml(dom){
	var div = document.createElement('DIV');
	div.appendChild(dom);
	return div.innerHTML;
}

function ucfirst(str){
	str += '';
	var f = str.charAt(0).toUpperCase();
	return f + str.substr(1);
}

// returns html node
sid=0;
function FVL_Inspect(obj){
	var node; sid++;
	if(obj===null){
		node=document.createElement('SPAN');
		node.className='null box';
		node.innerHTML='null';
	}else{
		switch(typeof obj){
			case 'number':
				node=document.createElement('SPAN');
				node.className='number box';
				node.innerHTML=obj.toString();
				break;
			case 'object':
				node=document.createElement('A');
				var type=(obj.toString()==='[object Object]') ? 'object' : 'array';
				node.className=type+' box';
				if(type=='array' && obj.length==0){ // if empty array
					node.innerHTML='(empty array)';
					node.className+=' empty';
				}else if(false){ // if empty object
					node.innerHTML='(empty object)';
					node.className+=' empty';
				}else{ // if not empty
					node.innerHTML=type+' &raquo;'; // maybe show object overview instead?
					node.setAttribute('id','span'+sid);
					node.setAttribute('href','javascript:;');
					node.setAttribute('onclick','FVL_Tip("span'+sid+'",FVL_Inspector('+JSON.stringify(obj)+'),"'+ucfirst(type)+'&nbsp;Inspector");');
				}
				break;
			default:
				node=document.createElement('SPAN');
				node.className=(typeof obj)+' box';
				if(typeof obj=='string' && obj.length==0){
					node.innerHTML='(empty string)';
					node.className+=' empty';
				}else node.innerHTML=htmlspecialchars(obj.toString());
				break;
		}
	}
	return node;
}

function FVL_Inspector(obj){
	if(typeof obj=='undefined')return 'undefined';
	var html='';
	for(var p in obj)
		html+='<tr><td valign="top">'+htmlspecialchars(p)+'</td><td valign="top">'+domtohtml(FVL_Inspect(obj[p]))+'</td></tr>';
	return '<table>'+html+'</table>';
}

function FVL_Tip(sender,html,title){
	var tdc=document.getElementById('WzBoDyI');
	if (tdc) tdc.innerHTML=html;
	    else Tip(html,TITLE,title,FIX,[sender,0,8]);
	document.getElementById('WzTtDiV').style.width='';
	document.getElementById('WzTiTl').style.width='';
	document.getElementById('WzBoDy').style.width='';
	document.getElementById('WzTiTlTb').style.width='100%';
}

// appends a statement to the logging element if the threshold level is exceeded
function FVL_log(message, level) {

	// autoraise hotfix
	if(typeof message=='string')message=[message];

	// check to make sure logging is turned on
	if (! FVL_LOG_ON) {return false;} 

	// retrieve the infrastructure
	if (arguments.length == 1) {level = FVL_INFO;}
	if (level < FVL_DEFAULT_LOG_LEVEL) {return false;}
	var div = getLogger();
	if (! div) {return false;}

	// append the statement
	var p = document.createElement(FVL_LOG_ELEMENT);

	// this is a hack work around a bug in ie
	if (p.getAttributeNode("class")) {
		for (var i = 0; i < p.attributes.length; i++) {
			if (p.attributes[i].name.toUpperCase() == 'CLASS') {
				p.attributes[i].value = FVL_LOG_CLASSES[level];
			}
		}
	} else {
		p.setAttribute("class", FVL_LOG_CLASSES[level]);
	}
	for(var n=0; n<message.length; n++){
		var node=FVL_Inspect(message[n]);
		if(node.getAttribute('onclick')!=null)
			node.setAttribute('onclick','tt_Hide();'+node.getAttribute('onclick'));
		p.appendChild(node);
	}
	
	div.appendChild(p);
	return true;
}

// show a node
function show(target) {
	target.style.display = "";
	return true;
}

// hide a node
function hide(target) {
	target.style.display = "none";
	return true;
}

// returns the class attribute of a node
function getNodeClass(obj) {
	var result = false;

	if (obj.getAttributeNode("class"))
		result = obj.attributes.getNamedItem("class").value;
	
	return result;
}