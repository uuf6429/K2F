<?php defined('K2F') or die;

	uses('core/cms.php','core/security.php','core/events.php','core/ajax.php');

	if(!defined('WPINC')){
		// define dependencies in case of a disaster
		function add_action(){}
		function wp_enqueue_script(){}
		function wp_enqueue_style(){}
	}

	/**
	 * CMS host interface for wordpress.
	 */
	class CmsHost_wordpress extends CmsHost_Base {
		// set title
		// add menu
		// add submenu
		// render adminlist
		protected static $pagecounter=0;
		protected static $_adminlist_scol='';
		protected static $_adminlist_sord='';
		public function _adminlist_order($a,$b){
			$col=self::$_adminlist_scol;
			if($a->$col==$b->$col)return 0;
			return ($a->$col<$b->$col) ? self::$_adminlist_sord*-1 : self::$_adminlist_sord*1;
		}
		public function adminlist($rows=array(),$colkey='id',$columns=array(),$options=array(),$actions=array(),$handler='',$emptymsg='No items found'){
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
			// perform ordering, if needed
			if(count($rows) && isset($_REQUEST['k2f-scol']) && isset($_REQUEST['k2f-sord']) && isset($rows[0]->{$_REQUEST['k2f-scol']})){
				self::$_adminlist_scol=$_REQUEST['k2f-scol'];
				self::$_adminlist_sord=$_REQUEST['k2f-sord']=='a' ? 1 : -1;
				uasort($rows,array(__CLASS__,'_adminlist_order'));
			}
			// perform pagination, if needed
			$p = isset($_REQUEST['k2f-page']) ? (int)$_REQUEST['k2f-page'] : 0;
			## BEGIN wordpress bs (from: post.php and upload.php) ##
			$media_per_page = (int) get_user_option( 'upload_per_page' );
			if ( empty( $media_per_page ) || $media_per_page < 1 )
				$media_per_page = 20;
			$l = apply_filters( 'upload_per_page', $media_per_page );
			## END wordpress bs ##
			$l = isset($_REQUEST['k2f-limit']) ? (int)$_REQUEST['k2f-limit'] : $l; // fk you wp - this is a serious limitation!
			$t = count($rows);
			$c = $l<=0 ? 0 : ceil($t/$l)-1;
			$p = max(min($p,$c),0);
			if($l>0)$rows = array_splice($rows,$p*$l,$l);

			if(!function_exists('CmsHost_wp_al_cols')){
				/**
				 * Generates columns.
				 * @param array $columns List of columns.
				 * @param array $options List of options.
				 */
				function CmsHost_wp_al_cols($pageid,$columns,$options){
					static $counter=0; $counter++;
					?><tr><?php
						if(in_array('multiselect',$options)){
							?><th class="manage-column check-column" scope="col"><input type="checkbox" id="k2fcbc<?php echo $counter; ?>"/></th><?php
						}
						if(in_array('singleselect',$options)){
							?><th class="manage-column check-column" scope="col">&nbsp;</th><?php
						}
						foreach($columns as $key=>$column){
							$is_ext=is_array($column);
							$is_srt=$is_ext && is_array($column) && isset($column[2]) && $column[2];
							$is_cur=isset($_REQUEST['k2f-scol']) ? $_REQUEST['k2f-scol']==$key : '';
							$mode=($is_cur && isset($_REQUEST['k2f-sord']) && $_REQUEST['k2f-sord']=='a') ? 'ASC' : 'DESC';
							?><th class="manage-column<?php
							  if($is_srt)echo ' sortable '.strtolower($mode);
							  if($is_cur)echo ' sorted';
							  ?>" style="<?php echo $is_ext ? (isset($column[1]) ? $column[1] : '') : ''; ?>" scope="col">
								<input type="hidden" name="colname" value="<?php echo Security::snohtml($key); ?>"/><?php
								// if sortable...
								if($is_srt){
									?><a href="javascript:;" onclick="k2f_toggle_order(this);"><span><?php
								}
								// write contents
								echo Security::snohtml($is_ext ? $column[0] : $column);
								// if sortable...
								if($is_srt){
									?></span><span class="sorting-indicator" style="display: block;"></span></a><?php
								}
							?></th><?php
						}
					?></tr><?php
				}
			}

			if(!function_exists('CmsHost_wp_al_bulk')){
				/**
				 * Generates actions.
				 * @param array $actions List of allowed actions.
				 */
				function CmsHost_wp_al_bulk($actions,$id,$tbl,$options,$istop,$rows,$p,$l,$t,$c){
					if(count($actions)==0)return;
					?><div class="tablenav top">
						<div class="alignleft actions">
							<select name="action" id="k2f-al-ba-<?php echo $id; ?>"
								onchange="jQuery('#k2f-al-bb-<?php echo $id; ?>').attr('disabled',false);">
								<option selected="selected" value="">Bulk Actions</option><?php
									foreach($actions as $action){
										?><option value="<?php echo Security::snohtml(strtolower($action)); ?>"><?php
											echo Security::snohtml(ucwords($action));
										?></option><?php
									}
								?>
							</select>
							<script type="text/javascript">
								jQuery(document).ready(function(){
									jQuery('#k2f-al-ba-<?php echo $id; ?>').val('');
								});
								window['k2f-options-<?php echo $tbl; ?>']=<?php echo @json_encode($options); ?>;
							</script>
							<input type="submit" id="k2f-al-bb-<?php echo $id; ?>" class="button-secondary action"
								value="Apply" onclick="k2f_action('<?php echo $id; ?>','<?php echo $tbl; ?>');" disabled/>
						</div>
						<div class="tablenav-pages">
							<img src="<?php echo Ajax::url('K2F_WP_WYSIWYG_HACK','loader'); ?>" style="display:none; margin:-2px 6px 0 0; vertical-align: middle;" width="16" height="16" alt="Loading..." class="k2f-pgnation-throbber"/>
							<span class="displaying-num"><?php echo $t; ?> items</span>
							<a href="javascript:;" onclick="if(!jQuery(this).is('.disabled'))k2f_pgnation_page(this,0);" title="Go to the first page" class="first-page<?php if($p==0)echo ' disabled'; ?>">&laquo;</a>
							<a href="javascript:;" onclick="if(!jQuery(this).is('.disabled'))k2f_pgnation_page(this,<?php echo $p-1; ?>);" title="Go to the previous page" class="prev-page<?php if($p==0)echo ' disabled'; ?>">&lsaquo;</a>
							<span class="paging-input">
								<?php if($istop){ ?>
									<input type="text" size="1" value="<?php echo $p+1; ?>" name="paged" title="Current page" class="current-page" onkeyup="evant=event||window.event; if(event.keyCode==13)k2f_pgnation_page(this,parseInt(value));"/>
								<?php }else echo $p+1; ?>
								of <span class="total-pages"><?php echo $c+1; ?></span>
							</span>
							<a href="javascript:;" onclick="if(!jQuery(this).is('.disabled'))k2f_pgnation_page(this,<?php echo $p+1; ?>);" title="Go to the next page" class="next-page<?php if($p==$c)echo ' disabled'; ?>">&rsaquo;</a>
							<a href="javascript:;" onclick="if(!jQuery(this).is('.disabled'))k2f_pgnation_page(this,<?php echo $c; ?>);" title="Go to the last page" class="last-page<?php if($p==$c)echo ' disabled'; ?>">&raquo;</a>
						</div>
						<br class="clear"/>
					</div><?php
				}
			}
			
			?><div class="k2f-adminlist" id="k2f-al-<?php echo self::$pagecounter; ?>">
			
			<ul class="subsubsub">
				<li class="all"><a class="current" href="">All <span class="count">(<?php echo $t; ?>)</span></a></li>
				<!-- New API would write stuff here -->
			</ul>

			<p class="search-box">
				<img src="<?php echo Ajax::url('K2F_WP_WYSIWYG_HACK','loader'); ?>" style="display:none; margin:-2px 6px 0 0; vertical-align: middle;" width="16" height="16" alt="Loading..." class="k2f-search-throbber"/>
				<label for="post-search-input" class="screen-reader-text">Search Items:</label>
				<input type="text" value="<?php if(isset($_REQUEST['k2f-search']))echo Security::snohtml($_REQUEST['k2f-search']); ?>" name="s" class="k2f-search" id="post-search-input" onkeyup="evant=event||window.event; if(event.keyCode==13)k2f_search_search(this);"/>
				<input type="button" value="Search Items" class="button" id="search-submit" onclick="k2f_search_search(this);"/>
			</p>
			
			<br class="clear"/><?php

			if(count($rows)>0){
				CmsHost_wp_al_bulk($actions,self::$pagecounter.'-1',self::$pagecounter,$options,true,$rows,$p,$l,$t,$c);
				?><div class="clear"></div>
				<table cellspacing="0" class="widefat">
					<thead><?php CmsHost_wp_al_cols(self::$pagecounter,$columns,$options); ?></thead>
					<tfoot><?php CmsHost_wp_al_cols(self::$pagecounter,$columns,$options); ?></tfoot>
					<tbody><?php
						$n=0;
						foreach($rows as $row){
							?><tr id="<?php echo 'k2frow'.self::$pagecounter.'-'.$row->$colkey; ?>" class="<?php echo $n%2==0 ? 'alternate' : ''; ?>"><?php
								if(in_array('singleselect',$options))
									echo '<th class="check-column" scope="row"><input type="radio" value="'.Security::snohtml($row->$colkey).'" '.($n==0 ? 'checked="checked" ' : '').'name="checked[]" id="k2fcb'.self::$pagecounter.'-'.$n.'"/></th>';
								if(in_array('multiselect',$options))
									echo '<th class="check-column" scope="row"><input type="checkbox" value="'.Security::snohtml($row->$colkey).'" name="checked[]" id="k2fcb'.self::$pagecounter.'-'.$n.'"/></th>';
								foreach($columns as $colid=>$colname){
									$res=(count($handler)>0 && $handler!='')
										? call_user_func($handler,$row->$colkey,$row,$colid,isset($row->$colid) ? $row->$colid : null)
										: Security::snohtml(isset($row->$colid) ? $row->$colid : 'null');
									echo '<td>'.($res=='' ? '&nbsp;' : $res).'</td>';
								}
							?></tr><?php
							$n++;
						}
					?></tbody>
				</table><?php
				CmsHost_wp_al_bulk($actions,self::$pagecounter.'-2',self::$pagecounter,$options,false,$rows,$p,$l,$t,$c);
			}else echo '<p>'.$emptymsg.'</p>';
			
			?></div>&nbsp;<?php
		}
		public static $menu_list=array();
		public function admin_add_menu($name,$text,$icons,$handler){
			return array_push(self::$menu_list,array(null,$name,$text,$icons,$handler))-1;
		}
		public function admin_add_submenu($parent,$name,$text,$icons,$handler){
			return array_push(self::$menu_list,array($parent,$name,$text,$icons,$handler))-1;
		}
		public function client_add_menu($name,$text,$icons,$handler){
			return array_push(self::$menu_list,array(null,$name,$text,$icons,$handler))-1;
		}
		public function client_add_submenu($parent,$name,$text,$icons,$handler){
			return array_push(self::$menu_list,array($parent,$name,$text,$icons,$handler))-1;
		}
		public function guest_add_menu($name,$text,$icons,$handler){
			return array_push(self::$menu_list,array(null,$name,$text,$icons,$handler))-1;
		}
		public function guest_add_submenu($parent,$name,$text,$icons,$handler){
			return array_push(self::$menu_list,array($parent,$name,$text,$icons,$handler))-1;
		}
		public function url_to_menu($menu,$args=array()){
			foreach($args as $k=>$v)$args[$k]='&'.$k.'='.urlencode($v);
									//    v------ wordpress bug hotfix for when subitem relates to parent
			return 'admin.php?page=k2f_'.($menu-1==self::$menu_list[$menu][0] ? self::$menu_list[$menu][0] : $menu).implode('',$args);
		}
		public function config_get($key){
			return get_option('k2f_'.$key);
		}
		public function config_set($key,$value){
			update_option('k2f_'.$key,$value);
		}
		protected static $nocallback=true;
		public function adminlist_begin($icon,$title,$options=array(),$actions=array(),$callback=array()){
			self::$nocallback=!count($callback);
			// fix for shorthand (passing a string instead of array)
			if(!is_array($options))$options=array($options);
			if(!is_array($actions))$actions=array($actions);
			// one-time pass
			if(self::$pagecounter==0){
				if(self::$nocallback)echo '<form method="post" action="">';
				echo '<div class="wrap">';
				?><style type="text/css">
					/* backend bug hotfix */
					#k2f-nopopup #media-upload { display: none; }
				</style><script type="text/javascript">
					var k2f_refresh_ajax=null;
					var k2f_page=0;
					function k2f_toggle_order(el){
						// get current column
						var col=jQuery(el).parents('th:first');
						var head=jQuery(el).parents('table').find('thead th:eq('+col.index()+')');
						var foot=jQuery(el).parents('table').find('tfoot th:eq('+col.index()+')');
						// if already selected and desc, switch to asc
						var ord=col.is('.sorted.asc') ? 'desc' : 'asc';
						// remove "sorted" class and reset ordering of each column
						jQuery(el).parents('table').find('thead th').removeClass('sorted').removeClass('asc').addClass('desc');
						jQuery(el).parents('table').find('tfoot th').removeClass('sorted').removeClass('asc').addClass('desc');
						// add "sorted" and order classes to this column
						head.addClass('sorted').removeClass('desc').addClass(ord);
						foot.addClass('sorted').removeClass('desc').addClass(ord);
						// TODO: show loader
						k2f_reload(el,function(){
							// TODO: hide loader
						});
					}
					function k2f_pgnation_page(el,p){
						k2f_page=p*1;
						jQuery(el).parents('.k2f-adminlist').find('.k2f-pgnation-throbber').show();
						k2f_reload(el,function(){
							jQuery(el).parents('.k2f-adminlist').find('.k2f-pgnation-throbber').hide();
						});
					}
					function k2f_search_search(el){
						jQuery(el).parents('.k2f-adminlist').find('.k2f-search-throbber').show();
						k2f_reload(el,function(){
							jQuery(el).parents('.k2f-adminlist').find('.k2f-search-throbber').hide();
						});
					}
					function k2f_reload(el,ondone){
						// define and compute some variables
						var s=jQuery(el).parents('.k2f-adminlist').find('.k2f-search').val();
						var col=jQuery(el).parents('.k2f-adminlist').find('th.sorted:first');
						var cnm=encodeURIComponent(col.find('input').val());
						var url=location.href.replace('k2f-search','k2f-ign').replace('k2f-page','k2f-ign');
						url+='&k2f-search='+encodeURIComponent(s)+"&k2f-page="+k2f_page; // search & pagination
						url+='&k2f-scol='+cnm+"&k2f-sord="+(col.is('.asc') ? 'a' : 'd');   // sort column & sort order
						// stop existing requests
						if(k2f_refresh_ajax && k2f_refresh_ajax.readyState!=0){
							k2f_refresh_ajax.abort();
							k2f_refresh_ajax=null;
						}
						// run new request
						k2f_refresh_ajax=k2f_refresh(url,function(){
							k2f_refresh_ajax=null;
							// call callback if any
							if(typeof ondone=='function')ondone();
						});
					}
					function k2f_popup(url){
						tb_show('',url.indexOf('?')!=-1 ? url : url+'?');
						jQuery('#TB_window').css('overflow-y','auto'); //  big box hotfix
/* what's this for? *///						tinymce.DOM.setStyle(["TB_overlay","TB_window","TB_load"],"z-index","999999");
						jQuery('#TB_ajaxContent').css('width','');
						jQuery('#TB_ajaxContent').css('height','');
						return false; // in case of links, stop 'em from redirecting (you need to return in the onclick as well)
					}
					function k2f_apply(action,tbl,ids){
						if(typeof ids=='undefined')var ids='';
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
							?>&k2f-table='+(tbl.replace('k2f-al-','')*1)+'&k2f-action='+encodeURIComponent(action);
						jQuery('.k2f-adminlist').hide();
						jQuery.post(url,ids.substring(1),function(data){
							jQuery('#k2f-nopopup').html(data);
							jQuery('#k2f-nopopup').show();
						});
						return false;
					}
					function k2f_action(id,tbl){
						var act=jQuery('#k2f-al-ba-'+id).val();
						var res=window['k2f-options-'+tbl].indexOf('nopopup:'+act)!=-1
							? k2f_applyNP(act,tbl) : k2f_apply(act,tbl);
						jQuery('#k2f-al-ba-'+id).val('');
						jQuery('#k2f-al-bb-'+id).attr('disabled',true);
						return res;
					}
					var k2fajax=null;
					function k2f_submit(elem,action){
						// compute some variables...
						var s=jQuery('.k2f-search').val(); // this is not threadsafe! (multiple tables)
						var url=location.href.replace('k2f-search','k2f-ign').replace('k2f-page','k2f-ign');
						url+='&k2f-search='+encodeURIComponent(s)+"&k2f-page="+k2f_page; // TODO: ADD SORTING BELOW
						// wordpress iframe hack: move html to textarea
						jQuery('iframe.k2f-richedit').each(function(){
							var id=jQuery(this).attr('id').replace('k2f-','');  // id of textarea and richedit
							var sw=jQuery(this)[0].contentWindow;               // window object of iframe
							if(typeof sw.tinyMCE!='undefined'){
								var value=sw.tinyMCE.get(id) ? sw.tinyMCE.get(id).getContent() : sw.document.getElementById(id).value;
								document.getElementById(id).value=value;
							}
						});
						// continue...
						if(action!='refresh' && action!='close' && action!='cancel'){
							var el=jQuery(elem).parents('form');
							if(el.length>0){
								el=el[0];
								// show "loading..." message
								var vars=jQuery(el).serialize();
								jQuery(el).html(
									'<p>Loading, please wait...</p>'+
									'<input type="button" value="Cancel" class="k2f-popup-button button" onclick="k2f_cancel();">'+
									'<br/>&nbsp;'
								);
								// do it! do it! do it!
								k2fajax=jQuery.post(el.action+'&k2f-action='+encodeURIComponent(action),vars,function(data){
									// show resulting message
									jQuery('#TB_ajaxContent').html(data);
									if(jQuery('#TB_ajaxContent').length==0)
										jQuery('#k2f-nopopup').html(data);
									// refresh page (well, parts of it)
									k2f_refresh(url,function(){
										if(action.indexOf('delete')!=-1 || action.indexOf('remove')!=-1){
											jQuery('input[type=checkbox]:checked').attr('checked',false);
										}
									});
								});
							}
						}else{
							jQuery('#k2f-nopopup').hide();
							jQuery('.k2f-adminlist').show();
							if(action=='refresh')k2f_refresh(url);
							tb_remove();
						}
					}
					function k2f_cancel(){
						if(k2fajax && k2fajax.readyState!=0){
							k2fajax.abort();
							k2fajax=null;
						}
						tb_remove();
					}
					function k2f_refresh(url,ondone){
						// this is a bit of a hack:
						// - first, it gets the new content of this page (GET/POST location.href)
						// - converts the returned HTML to DOM using jQuery
						// - replaces all <tbody>s of current page with the new HTML using DOM
						// todo: maybe do this via POST for cases like pagination
						if(typeof url=='undefined')url=location.href;
						return jQuery.post(url,function(data){
							// get list of checked checkboxes with an id
							var cbs=[];
							jQuery('input[type=checkbox]:checked').each(function(){ cbs.push(jQuery(this).attr('id')) });
							// overwrite each adminlist with new content
							jQuery('.k2f-adminlist').each(function(){
								jQuery(this).html(jQuery(data).find('#'+jQuery(this).attr('id')).html());
							});
							// rehook checkboxes using code from wordpress
							k2f_rehookcbs();
							// tick back checkboxes
							for(var i=0; i<cbs.length; i++)if(cbs[i]!='')jQuery('#'+cbs[i]).attr('checked',true);
							// call callback if any
							if(typeof ondone=='function')ondone();
						});
					}
					function k2f_rehookcbs(){
						jQuery("thead, tfoot").find(":checkbox").unbind('click');
						jQuery("thead, tfoot").find(":checkbox").click(function(){
							var checked=jQuery(this).attr("checked") ? "checked" : "";
							jQuery(this).closest("table").children("tbody").filter(":visible")
								.children().children(".check-column").find(":checkbox").attr("checked", function() {
									return jQuery(this).closest("tr").is(":hidden") ? "" : checked;
								});
							jQuery(this).closest("table").children("thead, tfoot").filter(":visible")
								.children().children(".check-column").find(":checkbox").attr("checked", function() {
									return checked;
								})
						});
					}
					function k2f_edit(link,id,action,extra){
						if(typeof extra=='undefined')extra='';
						var tbl=jQuery(link).parents('.k2f-adminlist');
						tbl = tbl.length ? jQuery(tbl[0]).attr('id').replace('k2f-al-','')*1 : 1;
						var url=location.href+'<?php
							echo (count($callback)==2) ? Ajax::url($callback[0],$callback[1],'&') : '&k2f-notajax';
							?>&k2f-table='+tbl+'&k2f-action='+encodeURIComponent(action)+'&k2f-checked[]='+(id*1)+extra;
						if(window['k2f-options-'+tbl].indexOf('nopopup:'+action)!=-1){
							jQuery('.k2f-adminlist').hide();
							jQuery.post(url,function(data){
								jQuery('#k2f-nopopup').html(data);
								jQuery('#k2f-nopopup').show();
							});
							return false;
						}else return k2f_popup(url);
					}
					var k2f_wysiwyg={
						/**
						 * Returns the internal window of a wysiwyg iframe
						 */
						'Window':function(name){
							return jQuery('#k2f-'+name)[0].contentWindow;
						},
						/**
						 * Returns the TinyMCE wysiwyg instance given it's name.
						 */
						'TinyMCE':function(name){
							var sw=k2f_wysiwyg.Window(name);
							return (typeof sw.tinyMCE!='undefined') ? sw.tinyMCE.get(name) : null;
						},
						/**
						 * Sets the content of the wysiwyg editor.
						 */
						'set':function(name,html){
							var sw=k2f_wysiwyg.TinyMCE(name);
							if(sw)sw.setContent(html);
						},
						/**
						 * Gets the content of the wysiwyg editor.
						 */
						'get':function(name){
							var sw=k2f_wysiwyg.TinyMCE(name);
							return sw ? sw.getContent() : '';
						},
						/**
						 * Inserts some HTML at the current cursor position.
						 */
						'insert':function(name,html){
							var sw=k2f_wysiwyg.TinyMCE(name);
							if(sw)sw.execCommand('mceInsertContent',false,html);
						},
						/**
						 * Replaces selected content with new content.
						 */
						'replace':function(name,html){
							var sw=k2f_wysiwyg.TinyMCE(name);
							if(sw)sw.execCommand('mceReplaceContent',false,html);
						},
						/**
						 * Gets the currently selected content.
						 */
						'selected':function(name){
							var sw=k2f_wysiwyg.TinyMCE(name);
							return sw ? sw.selection.getContent() : '';
						},
						/**
						 * Shows a wysiwyg popup thingy.
						 * @param string WYSIWYG name.
						 * @param string URL to popup HTML content.
						 * @param integer Popup width.
						 * @param integer Popup height.
						 * @param boolean Whether popup should be resizable or not.
						 * @param boolean Whether popup should have scrollbars or not.
						 * @param boolean Whether to show an inline popup or not.
						 * @param boolean Whether previous popups should be closed or not.
						 */
						'open':function(name,u,w,h){
							var sw=k2f_wysiwyg.TinyMCE(name);
							if(sw){
								var a={'file':u,'width':w,'height':h,'inline':true,'close_previous':true};
								var b={'window':k2f_wysiwyg.Window(name),'editor_id':sw.editorId};
								sw.windowManager.open(a,b);
							}
						},
						/**
						 * Closes any and all wysiwyg popups.
						 * @param string WYSIWYG name.
						 * @param object Popup windows to close.
						 */
						'close':function(name,popup){
							var sw=k2f_wysiwyg.TinyMCE(name);
							if(sw)sw.windowManager.close(popup);
						}
					}
				</script><?php
			}
			// increase now
			self::$pagecounter++;
			// write a title bar thingy
			?><div class="icon32" style="background:url('<?php echo Security::snohtml($icon->_32); ?>') no-repeat;"><br/></div>
			<h2><?php
				echo Security::snohtml($title);
				if(in_array('allowadd',$options))
					echo '<a class="button add-new-h2" href="javascript:;" onclick="return '.
						(!in_array('nopopup:add',$options) ? 'k2f_apply' : 'k2f_applyNP').
						'(\'new\',\'k2f-al-'.self::$pagecounter.'\');">Add New</a>';
			?></h2><?php
		}
		public function adminlist_end(){
			// one-time pass
			if(self::$pagecounter>0){
				self::$pagecounter=0;
				echo '<div id="k2f-nopopup" style="display:none;"><!----></div></div>';
				if(self::$nocallback)echo '</form>';
			}
		}
		protected static $hints=array();
		public function popup_begin($title,$hint,$width=0,$height=0,$callback=array()){
			self::$hints[]=$hint;
			?><script type="text/javascript">jQuery('#TB_ajaxWindowTitle').html(<?php echo @json_encode($title); ?>);</script>
			<form id="<?php //echo Security::snohtml($id); ?>" class="type-form k2f-adm-frm" action="<?php
				echo '?page='.Security::snohtml(urlencode($_REQUEST['page'])).((count($callback)==2)
						? Ajax::url($callback[0],$callback[1],'&') : '&k2f-notajax').'&k2f-table='.(int)$_REQUEST['k2f-table'];
				?>" method="post" style="display:inline-block; width:630px;">
				<h3 class="media-title"><?php echo Security::snohtml($title); ?></h3><?php
				// reflect checked item[s] as hidden input elements
				if(isset($_REQUEST['k2f-checked']))foreach((array)$_REQUEST['k2f-checked'] as $id)
					echo '<input type="hidden" name="k2f-checked[]" value="'.(int)$id.'"/>';
		}
		protected static $buttons=array();
		public function popup_button($text,$action,$type){
			self::$buttons[]=array($text,$action,$type);
		}
		public function popup_end(){
				?><div align="right"><?php
				foreach(self::$buttons as $i=>$button){
					list($text,$action,$type)=$button;
					switch($type){
						case 'button':
							?><input type="button" value="<?php echo Security::snohtml($text);
							?>" class="k2f-popup-button button" onclick='k2f_submit(this,<?php echo @json_encode($action); ?>);'/><?php
							break;
						case 'primary':
							?><input type="button" value="<?php echo Security::snohtml($text);
							?>" class="k2f-popup-button button-primary" onclick='k2f_submit(this,<?php echo @json_encode($action); ?>);'/><?php
							break;
						case 'critical':
							?><input type="button" value="<?php echo Security::snohtml($text);
							?>" class="k2f-popup-button button-primary" onclick='k2f_submit(this,<?php echo @json_encode($action); ?>);'/><?php
							break;
						case 'link':
							?><a class="k2f-popup-button" href="javascript:;" onclick='k2f_submit(this,<?php echo @json_encode($action); ?>);'><?php
							echo Security::snohtml($text); ?></a><?php
							break;
					}
					if(isset(self::$buttons[$i+1]))echo '&nbsp;&nbsp;';
				}
				?></div><?php
				if(($hint=array_pop(self::$hints))!==null)
					echo $hint=='' ? '&nbsp;' : '<p class="howto">'.Security::snohtml($hint).'</p>';
			?></form><div id="media-upload"><div id="media-items"><!----></div></div><?php
		}
		public function is_admin(){
			return current_user_can('manage_options');
		}
		public function is_client(){
			return !$this->is_admin() && is_user_logged_in();
		}
		public function is_guest(){
			return !is_user_logged_in();
		}
		public function user_id(){
			return wp_get_current_user()->ID;
		}
		public function user_username($id){
			return get_userdata((int)$id)->user_login;
		}
		public function wysiwyg($name,$html,$width,$height){
			?><div style="background:#FFF url('<?php echo Ajax::url('K2F_WP_WYSIWYG_HACK','loader'); ?>') no-repeat center;">
			<textarea cols="" rows="" style="display:none;" name="<?php echo Security::snohtml($name); ?>" id="<?php echo Security::snohtml($name); ?>"><?php
				echo Security::snohtml($html);
			?></textarea><iframe class="k2f-richedit" scrolling="no" id="k2f-<?php echo Security::snohtml($name); ?>" src="<?php
				echo Ajax::url('K2F_WP_WYSIWYG_HACK','handle').'&k2f-n='.Security::snohtml(urlencode($name))
				.'&k2f-w='.Security::snohtml(urlencode($width-2)).'&k2f-h='.Security::snohtml(urlencode($height-48)); ?>" style="<?php
				echo 'width:'.(int)$width.'px; height:'.(int)$height.'px;'; ?>" frameborder="0">Get a real browser.</iframe></div><?php
			// this is a wordpress hotfix to show a pagebreak button (althrough pagebreak works on wordpress, there's no such button)
			$icon=str_replace('wp-admin/','',CFG::get('REL_WWW')).'wp-includes/js/tinymce/plugins/wordpress/img/trans.gif';
			$html=Security::snohtml(@json_encode('<img src="'.$icon.'" class="mceWPnextpage mceItemNoResize" title="Next page..." />'));
			$icon=Ajax::url('K2F_WP_WYSIWYG_HACK','pbreak');
			$this->wysiwyg_button($name,$icon,'Insert Pagebreak (Alt+Shift+P)',1,'k2f_wysiwyg.insert(\''.$name.'\','.$html.');//');
		}
		public function wysiwyg_button($name,$icon,$title='',$toolbar=1,$onclick=null,$onmouseover=null,$onmouseout=null){
			$icon=(strpos($icon,'?')===false ? $icon.'?' : $icon.'&').'cms=wordpress';
			$expr='#'.$name.'_toolbar'.$toolbar.' > tbody td:last';
			$code='<td><a href="javascript:;" title="'.Security::snohtml($title).'" class="mceButton mceButtonEnabled" onmousedown="return false;" onclick="';
			if($onclick)$code.='(parent?parent:window).'.$onclick.'(this); ';
			$code.='return false;" onmouseover="';
			if($onmouseover)$code.='(parent?parent:window).'.$onmouseover.'(this);';
			$code.='" onmouseout="';
			if($onmouseout)$code.='(parent?parent:window).'.$onmouseout.'(this);';
			$code.='"><span class="mceIcon" style="background:url(\''.Security::snohtml($icon).'\') no-repeat;"><!----></span></a></td>';
			?><input type="hidden" class="k2f-custbtns-<?php echo Security::snohtml($name); ?>"
				<?php echo 'k2fexpr="'.Security::snohtml($expr).'"'; ?> value="<?php echo Security::snohtml($code); ?>"/><?php
		}
		public function wysiwyg_separator($name,$toolbar=1){
			$expr='#'.$name.'_toolbar'.$toolbar.' > tbody td:last';
			$code='<td><span class="mceSeparator"><!----></span></td>';
			?><input type="hidden" class="k2f-custbtns-<?php echo Security::snohtml($name); ?>"
				<?php echo 'k2fexpr="'.Security::snohtml($expr).'"'; ?> value="<?php echo Security::snohtml($code); ?>"/><?php
		}
		public function wysiwyg_popup_begin($name,$title){
			?><html xmlns="http://www.w3.org/1999/xhtml">
				<head>
					<title><?php echo Security::snohtml($title); ?></title>
					<link rel="stylesheet" href="http://localhost/K2F/cms/cms-wordpress/wp-includes/js/tinymce/themes/advanced/skins/wp_theme/dialog.css?ver=327-1235"/>
					<style type="text/css">#k2fdlg .panel_wrapper, #k2fdlg div.current { height: auto; }</style>
					<script type="text/javascript">
						for(var i=0; i<1000; i++){
							var el=parent.document.getElementById('mce_'+i+'_title');
							if(el)el.innerHTML=<?php echo @json_encode(Security::snohtml($title)); ?>;
						}
						function cancel(){
							parent.parent.k2f_wysiwyg.TinyMCE(document.getElementById('k2fPopupName').value).windowManager.close(window);
						}
					</script>
				</head><body id="k2fdlg">
					<form action="#">
						<input type="hidden" id="k2fPopupName" value="<?php echo Security::snohtml($name); ?>"/>
						<div class="tabs"><ul><li class="current" id="general_tab">
							<span><a onmousedown="return false;" href="javascript:;"><?php echo Security::snohtml($title); ?></a></span>
						</li></ul></div>
						<div class="panel_wrapper"><div class="panel current" id="general_panel"><?php
		}
		public function wysiwyg_popup_button($value,$onclick){
			$left=array('cancel','close');
			self::$buttons[]='<div style="float:'.(in_array(strtolower($value),$left) ? 'left' : 'right').';">
				<input type="button" onclick="parent.parent.'.Security::snohtml($onclick).'" value="'.Security::snohtml($value).'" class="mceButton"/>
			</div>';
		}
		public function wysiwyg_popup_end(){
						?></div>
						</div><div class="mceActionPanel"><?php echo implode(CRLF,self::$buttons); ?></div>
					</form>
				</body>
			</html><?php
		}
		public function wysiwyg_paginate($content){
			$content=do_shortcode($content);
			// code from: %wordpress\wp-includes\query.php
			$tag='<!--nextpage-->';
			return explode($tag,str_replace(array("\n$tag\n","\n$tag","$tag\n"),$tag,$content));
		}
		public static $WriteRules=array();      // holds a list of accessible urls
		public static $RewriteRules=array();    // holds a list of url rewriter rules
		public static $RewriteRulesImp=array(); // holds a list of (important) url rewriter rules
		public function rewrite_url($search,$replace,$important=false){
			!$important ? self::$RewriteRules[$search]=$replace
				: self::$RewriteRulesImp=array_merge(array($search=>$replace),self::$RewriteRulesImp);
		}
		public function rewrite_enabled(){
			return get_option('permalink_structure')!='';
		}
		public function write_url($handler,$arguments){
			self::$WriteRules[]=$handler;
			foreach($arguments as $n=>$v)$arguments[$n]='&'.urlencode($n).'='.urlencode($v);
			return '/index.php?k2facm='.implode('.',$handler).implode('',$arguments).'&k2fsef';
		}
		public function upload_dir(){
			$uploads=wp_upload_dir();
			return $uploads['path'].DIRECTORY_SEPARATOR;
		}
		public function upload_url(){
			$uploads=wp_upload_dir();
			return $uploads['url'].'/';
		}
		public function login_url($redirect=''){
			return wp_login_url($redirect);
		}
		public function logout_url($redirect=''){
			return wp_logout_url($redirect);
		}
		public function register_url($redirect=''){
			// Note: wordpress does not have a convenient wp_register_url() so
			// we do it ourselves. The code came from wp_register() function.
			// Note2: unfortunately, it doesn't support redirection. Maybe we
			// ought to add it ourselves in the future...
			$link = is_user_logged_in() ? admin_url() : (get_option('users_can_register') ? site_url('wp-login.php?action=register', 'login') : '');
			return apply_filters('register', $link);
		}
	}
	
	class K2F_WP_WYSIWYG_HACK {
		public static function handle($id,$width,$height){
			// hotfix: removes extra CSS in editor
			global $editor_styles;
			$editor_styles=array();
			// hotfix: load needed scripts and styles
			wp_admin_css('css/global');
			wp_admin_css('css/wp-admin');
			wp_admin_css('css/colors');
			wp_admin_css('css/ie');
			wp_admin_css('css/colors-fresh');
			wp_admin_css();
			wp_enqueue_script('common');
			wp_print_scripts('media-upload');
			wp_print_scripts('editor');
			wp_tiny_mce(false,array('theme_advanced_resizing'=>false,'width'=>$width,'height'=>$height));
			?><html>
				<head>
					<?php wp_head(); ?><style type="text/css">
						#editorcontainer textarea { margin:0; width:100%; }
					</style>
				</head><body class="wp-admin" style="min-width:0;">
					<div id="poststuff"><?php the_editor('',$id,$id,true); ?></div>
					<script type="text/javascript">
						window.onload=function(){
							try {
								// hack to get data from parent window textarea
								var ehtm=tinyMCE.get(<?php echo @json_encode($id); ?>);
								var etxt=document.getElementById(<?php echo @json_encode($id); ?>);
								var value=parent.document.getElementById(<?php echo @json_encode($id); ?>).value;
								if(ehtm){
									ehtm.setContent(value);
								}else{
									etxt.value=value;
								}
								// hack to add additional buttons from parent
								var els=parent.jQuery('.k2f-custbtns-'+<?php echo @json_encode($id); ?>);
								for(var i=0; i<els.length; i++)jQuery(els[i].getAttribute('k2fexpr')).before(els[i].value);
								// hack to update wysiwyg whenever textarea changes
								etxt.onchange=function(){
									if(ehtm)ehtm.setContent(etxt.value);
								}
							}catch(e){
								// this is a hack to route back errors to firebug
								if(typeof parent.console!='undefined')parent.console.exception(e);
							}
						}
					</script>
				</body>
			</html><?php die;
		}
		public static function loader(){
			header('Content-type: image/gif');
			die(base64_decode('
				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
				PQScngQ1B9YA2EhSy9bOYhXfwsSQswe2oCshADs=
			'));
		}
		public static function pbreak(){
			header('Content-type: image/gif');
			die(base64_decode('
				R0lGODlhFAAUAMIDAGZmZoKlyMzMzP///////////////////yH5BAEUAAQALAAAAAAUABQAAANH
				SATT/g8oxaBtctYLM+WWt4DdNJKOuKEq4L5wbM50HQTEfec6v++1IC1GdNVWoYHoA6oskZ2UCRqB
				TUmCrECFkmq6mCs4kwAAOw==
			'));
		}
	}
	Ajax::register('K2F_WP_WYSIWYG_HACK','handle',array('k2f-n'=>'string','k2f-w'=>'string','k2f-h'=>'string'));
	Ajax::register('K2F_WP_WYSIWYG_HACK','loader');
	Ajax::register('K2F_WP_WYSIWYG_HACK','pbreak');

	/* Wordpress Hooks */
	function k2f_wp_backend_menus(){
		$can=CmsHost::cms()->is_admin() ? 'manage_options' : (CmsHost::cms()->is_client() ? 'read' : '');
		$main=array(); // bitlist for main plugin page hotfix
		foreach(CmsHost_wordpress::$menu_list as $id=>$item){
			list($pid,$name,$text,$icons,$handler)=$item;
			if($pid===null){ // main-item
				add_menu_page($name,$name,$can,'k2f_'.$id,$handler,$icons ? $icons->_16 : '');
				$main['k2f_'.$id]=implode('.',$handler); // main plugin page hotfix
			}else{ // sub-item
				// the following line is for main plugin page hotfix
				$nid='k2f_'.$id;
				if(isset($main['k2f_'.$pid]) && $main['k2f_'.$pid]==implode('.',$handler)){
					$nid='k2f_'.$pid;
					unset($main['k2f_'.$pid]);
				}
				add_submenu_page('k2f_'.$pid,$name,$name,$can,$nid,$handler,$icons ? $icons->_16 : '');
			}
		}
	}
	function k2f_wp_frontend_menus($pages,$query){
		if(!CmsHost::cms()->is_admin()){
			// TODO: Play with $query to modify our returned pages.
			foreach(CmsHost_wordpress::$menu_list as $id=>$item){
				list($pid,$name,$text,$icons,$handler)=$item;
				$pid+=900000; $id+=900000;
				$page=array(
					'ID' => $id,
					'post_author' => 1,
					'post_date' => date('Y-m-d H:i:s'),
					'post_date_gmt' => date('Y-m-d H:i:s'),
					'post_content' => 'gbvxbv fbbx xbbx', // TODO: if post_id==-id call page content
					'post_title' => $name,
					'post_excerpt' => '',
					'post_status' => 'publish',
					'comment_status' => 'closed',
					'ping_status' => 'closed',
					'post_password' => '',
					'post_name' => sanitize_title($name),
					'to_ping' => '',
					'pinged' => '',
					'post_modified' => date('Y-m-d H:i:s'),
					'post_modified_gmt' => date('Y-m-d H:i:s'),
					'post_content_filtered' => '',
					'post_parent' => $pid,
					'guid' => md5(''.$id),
					'menu_order' => '0',
					'post_type' => 'page',
					'post_mime_type' => '',
					'comment_count' => 0,
					'filter' => 'raw',
					'url' => 'http://google.com/'
				);
				$pages[]=(object)$page;
			}
		}
		return $pages;
	}
	function k2f_wp_admin_head(){
		// this hotfix fixes a bug where when we load the media, "Screen Options" box malfunctions.
		// the second and third lines is a hotfix for table column order icon bug
		?><style type="text/css">
			#screen-meta .hidden { height: auto; width: auto; }
			th span.sorting-indicator { visibility: hidden; }
			th.sorted .sorting-indicator,th:hover span.sorting-indicator { visibility: visible; }
			.k2f-adm-frm label { width: 120px; display: inline-block; }
			.k2f-adm-frm fieldset {
				position: relative;
				padding: 1em;
				border: 1px solid #AAA;
				background: #F8F8F8;
				margin: 8px 0;
				border-radius: 3px;
				-moz-border-radius: 3px;
				-o-border-radius: 3px;
				-webkit-border-radius: 3px;
			}
			.k2f-adm-frm legend {
				color: #000;
				font-weight: bold;
				font-size: 13px;
				padding: 0 4px;		
				cursor: default;
			}
			.k2f-adm-frm .closable legend {			
				cursor: pointer;
			}
			.k2f-adm-frm fieldset input {
				width: 300px;
			}
			.k2f-adm-frm thead th {
				text-align: left;
				font-size: 12px;
			}
		</style><?php
		// backend thickbox path hotfix
		?><script type="text/javascript">
			if(typeof tb_pathToImage!='string')
				var tb_pathToImage="<?php echo CFG::get('REL_WWW'); ?>../wp-includes/js/thickbox/loadingAnimation.gif";
			if(typeof tb_closeImage!='string')
				var tb_closeImage = "<?php echo CFG::get('REL_WWW'); ?>../wp-includes/js/thickbox/tb-close.png";
		</script><?php
		// call event handlers
		Events::call('on_head');
	}
	function k2f_wp_head(){
		// backend thickbox path hotfix
		?><script type="text/javascript">
			if(typeof tb_pathToImage!='string')
				var tb_pathToImage="<?php echo CFG::get('REL_WWW'); ?>wp-includes/js/thickbox/loadingAnimation.gif";
			if(typeof tb_closeImage!='string')
				var tb_closeImage = "<?php echo CFG::get('REL_WWW'); ?>wp-includes/js/thickbox/tb-close.png";
		</script><?php
		// call event handlers
		Events::call('on_head');
	}
	function k2f_wp_init(){
		// run init stuff
		Events::call('on_init');
		// run menu stuff
		if(CmsHost::cms()->is_admin())Events::call('on_admin_menu');
		if(CmsHost::cms()->is_client())Events::call('on_registered_menu');
		if(CmsHost::cms()->is_guest())Events::call('on_guest_menu');
		// code to rewrite wordpress urls
		/*
		global $wp_rewrite;
		foreach(CmsHost_wordpress::$RewriteRules as $rule=>$value)
			$wp_rewrite->add_rule($rule,$value);
		foreach(CmsHost_wordpress::$RewriteRulesImp as $rule=>$value)
			$wp_rewrite->add_rule($rule,$value,'top');
		$wp_rewrite->flush_rules();
		*/
		$rules=array_merge(CmsHost_wordpress::$RewriteRulesImp,CmsHost_wordpress::$RewriteRules);
		$subject=$_SERVER['REQUEST_URI']; // might need to remove root difference path (REL_WEB)
		foreach($rules as $pattern=>$handler)if(is_array($handler)){ // eg: '(wiki)/.*$' => ClassMethod
			$replacement='index.php?k2facm='.implode('.',$handler).'&k2fsef';
			if(($new=preg_replace('#'.$pattern.'#',$replacement,$subject))!==null && $subject!=$new){
				$new=explode('?',$new,2);
				if(isset($new[1])){
					parse_str($new[1],$new);
					foreach($new as $name=>$value){
						$_REQUEST[$name]=$value;
						$_GET[$name]=$value;
					}
				}
				break;
			}
		}
		if(isset($_REQUEST['k2facm'])){
			$content=false;
			$handlers=array_values(CmsHost_wordpress::$RewriteRules)+array_values(CmsHost_wordpress::$RewriteRulesImp)+CmsHost_wordpress::$WriteRules;
			foreach($handlers as $handler)
				if($_REQUEST['k2facm']==implode('.',$handler)){
					if(class_exists($handler[0])){
						$args = !isset($_REQUEST['k2fsef']) ? array()
							: array(explode('/',ltrim(str_replace(CFG::get('REL_WWW'),'',$_SERVER['REQUEST_URI']),'/')));
						ob_start();
						call_user_func_array($handler,$args);
						$content=ob_get_clean();
					}
					break;
				}
			if($content===false)
				$content='Page not found or handler is wrong.';
//			ob_start();
			get_header();
//			echo str_ireplace('<title>','<title>'.CmsHost_wordpress::title().' - ',ob_get_clean());
			echo $content;
			get_footer();
			die;
		}
	}

	/* Wordpress actions */
	add_action('admin_menu','k2f_wp_backend_menus');
	add_action('admin_head','k2f_wp_admin_head');
	add_action('wp_head',   'k2f_wp_head');
	add_action('init',      'k2f_wp_init');
	add_filter('get_pages' ,'k2f_wp_frontend_menus',10,2);

	/* Ensure all Wordpress features are on */
	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	wp_enqueue_style('thickbox');
	// the conditional is a hotfix for the wordpress media uploader style
    if(basename($_SERVER['PHP_SELF'])!='media-upload.php')
		wp_enqueue_style('media'); // see related hotfix in function k2f_wp_head

?>