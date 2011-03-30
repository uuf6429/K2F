<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php','core/ajax.php','exts/google.maps.php','libs/swfupload/swfupload.php');

	class kmrViewRegions {
		public static function view(){
			CmsHost::cms()->adminlist_begin(KeenMapsRegions::icons('main'),'Manage Regions','allowadd',array(),ClassMethod(__CLASS__,'actions'));
			$regions=new kmrRegions();
			$regions->load();
			CmsHost::cms()->adminlist(
				$regions->rows,'id',
				array('number'=>'No.','title'=>'Title','desc'=>'Thumbnail and Short Description','markers'=>'Markers','tabs'=>'Tab Content','view'=>'Viewport'),
				array('multiselect','allowadd'),
				array('Edit'),
				ClassMethod(__CLASS__,'cells'),
				'No regions yet',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
			Google_Map::init(); // force init now, coz it's too late for later on
			SwfUpload::init();  // force init now, coz it's too late for later on
			?><script type="text/javascript">
				function str_replace (search, replace, subject, count) {
					var i = 0, j = 0, temp = '', repl = '', sl = 0, fl = 0,
						f = [].concat(search), r = [].concat(replace), s = subject,
						ra = r instanceof Array, sa = s instanceof Array;
					s = [].concat(s);
					if (count) this.window[count] = 0;
					for (i = 0, sl = s.length; i < sl; i++) {
						if (s[i] === '') continue;
						for (j = 0, fl = f.length; j < fl; j++) {
							temp = s[i] + '';
							repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
							s[i] = (temp).split(f[j]).join(repl);
							if (count && s[i] !== temp)
								this.window[count] += (temp.length - s[i].length) / f[j].length;
						}
					}
					return sa ? s : s[0];
				}
				function kmr_thumb_clear(){
					// TODO: Confirm request and actually delete file through ajax
					document.getElementById('thumb-inp').value='';
					document.getElementById('thumb-img').src=<?php echo @json_encode(KeenMaps::url().'img/thumb-empty.gif'); ?>;
					document.getElementById('thumb-fnm').innerHTML='(no thumbnail)';
					document.getElementById('thumb-fsz').innerHTML='';
				}
				function kmr_thumb_update_start(file,id){
					if(id==-1){ // thumbnail uploader
						document.getElementById('kmr_thum_prog').innerHTML='0%';
					}else if(id==-2){ // gallery uploader
					}else{ // gallery image uploader (use id)
						var eid='kmr-image'+id;
						document.getElementById(eid+'-prg').style.display='block';
						document.getElementById(eid+'-per').style.width='0%';
					}
				}
				function kmr_thumb_update_progrs(file,done,total,id){
					var percent=Math.round(total==0 ? 100 : (done/total*100));
					if(id==-1){ // thumbnail uploader
						document.getElementById('kmr_thum_prog').innerHTML=percent+'%';
					}else if(id==-2){ // gallery uploader
					}else{ // gallery image uploader (use id)
						var eid='kmr-image'+id;
						document.getElementById(eid+'-per').style.width=percent+'%';
					}
				}
				function kmr_thumb_update_finish(file,data,ok,id){
					if(ok){ data=eval('('+data+')');
						if(id==-1){ // thumbnail uploader
							document.getElementById('kmr_thum_prog').innerHTML='100%';
							document.getElementById('thumb-inp').value=data.url;
							document.getElementById('thumb-img').src=data.url;
							document.getElementById('thumb-fnm').title=data.url;
							document.getElementById('thumb-fnm').innerHTML=data.file;
							document.getElementById('thumb-fsz').innerHTML=data.size;
						}else if(id==-2){ // gallery uploader
						}else{ // gallery image uploader (use id)
							var eid='kmr-image'+id;
							document.getElementById(eid+'-img').src=data.url;
							document.getElementById(eid+'-url').value=data.url;
							setTimeout(function(){ document.getElementById(eid+'-prg').style.display='none'; }, 500);
							document.getElementById(eid+'-per').style.width='100%';
						}
					}
				}
				function kmr_view_show(el,id){
					jQuery(el).css('backgroundColor','#EEE');
					for(var i in window.kgmm)window.kgmm[i].setVisible(false);
					window.kgmm['marker'+id].setVisible(true);
					window.kgm["temp"].setCenter(window.kgmm['marker'+id].getPosition());
					window.kgm["temp"].setZoom(15);
				}
				function kmr_view_reset(el){
					jQuery(el).css('backgroundColor','');
				}
				function kmr_tab_add(id){
					// generate unique id
					var id=jQuery('.tab-btns-list a').length;
					while(jQuery('#tab'+id).length && id<10000)id++; // the 10000 is an infloop failsafe
					// append button
					jQuery('.tab-btns-list').append('<span class="tabs-draggable" id="tab'+id+'"><a href="javascript:;" style="font-weight:bold;" onclick="kmr_tab_show('+id+')">Untitled '+id+'</a> | </span>');
					// append editor
					jQuery('.tabs-data').append(str_replace(['---ID---','---NAME---'],[id,'Untitled '+id],JSTABHTMLTMPL,null));
					// show tab
					kmr_tab_show(id);
				}
				function kmr_tab_show(id){
					if(jQuery('.tabs-data .tabs-tab:first')[0].id!='tabs-tab'+id)
						jQuery('.tabs-data .tabs-tab:first').before(jQuery('#tabs-tab'+id));
					jQuery('.tabs-btns a').css('font-weight','');
					jQuery('#tab'+id+' a').css('font-weight','bold');
				}
				var JSWYSIWYGTMPL=<?php
					ob_start();
					CmsHost::cms()->wysiwyg('tabs-html---ID---','',626,390);
					echo @json_encode(ob_get_clean());
				?>;
				var JSTABHTMLTMPL='<div class="tabs-tab" id="tabs-tab---ID---" style="margin-bottom:20px;">'+
						'<input type="hidden" name="tabs[]" value="---ID---"/>'+
						'<label>Name:</label> <input type="text" name="tabs-name---ID---" value="---NAME---" onkeyup="jQuery(\'#tab---ID--- a\').text(value);" style="width:200px"/>'+
						'<a href="javascript:;" style="margin-left:20px; color:red;" onclick="if(confirm(\'Warning!\\n\\nThe contents of this tab will be lost forever.\\n\\nAre you sure you want this?\'))jQuery(\'#tab---ID---, #tabs-tab---ID---\').remove();">Delete This Tab</a>'+
						JSWYSIWYGTMPL+
					'</div>';
			</script><?php
		}
		public static function actions($table,$action,$checked){
			switch($action){
				case 'new': case 'edit':
					$regn=new kmrRegions(); $regn->load();
					$used=array(); $reg=new kmrRegion();
					foreach($regn->rows as $i=>$reg){
						$regn->rows[$i]=$reg->number;
						if($reg->id!=(count($checked)==0 ? 0 : (int)$checked[0]))
							foreach($reg->markers as $id)$used[$id]=null;
					}
					$used=array_keys($used);
					$reg=new kmrRegion(count($checked)==0 ? 0 : (int)$checked[0]); $reg->load();
					$regn=$regn->rows; unset($regn[array_search($reg->number,$regn)]);
					CmsHost::cms()->popup_begin(($reg->id>0?'Update':'Add').' Region','',380,180,ClassMethod(__CLASS__,'actions'));
					$size=$reg->thumb!='' ? '('.bytes_to_human(strlen(Connect::get($reg->thumb))).')' : '';
					?><input type="hidden" name="id" value="<?php echo $reg->id; ?>"/><p>
						<span style="width:100px;display:inline-block;">Region Title:</span>
						<input type="text" id="title" name="title" value="<?php echo Security::snohtml($reg->title); ?>" style="vertical-align:middle;width:290px;"/>
						<span style="width:80px;display:inline-block;margin-left:40px;">Region N<sup><u>o</u></sup>:</span>
						<select id="number" name="number" style="vertical-align:middle;width:100px;"><?php
							for($i=1; $i<=20; $i++)
								if(!in_array($i,$regn))
									echo '<option value="'.$i.'"'.($i==$reg->number ? ' selected="selected"' : '').'>'.$i.'</option>';
						?></select>
					</p><p>
						<span style="width:100px;display:inline-block;">Video URL:</span>
						<input type="text" id="video" name="video" value="<?php echo $reg->video=='' ? 'http://' : Security::snohtml($reg->video); ?>" style="vertical-align:middle;width:520px;"/>
					</p><div style="font: 12px Verdana;">
						<input type="hidden" name="thumb" id="thumb-inp" value="<?php echo Security::snohtml($reg->thumb); ?>"/>
						<span style="width:180px;height:112px;display:inline-block;vertical-align:top;">Select Region Thumbnail:</span>
						<div style="width:112px;height:112px;display:inline-block;vertical-align:top;margin-right:12px;padding:4px;border:1px solid #EEEEEE;" align="center"><div style="overflow:hidden;">
							<img id="thumb-img" src="<?php echo Security::snohtml($reg->thumb=='' ? KeenMapsRegions::url().'img/thumb-empty.gif' : $reg->thumb); ?>" height="112" alt=""/>
						</div></div>
						<span style="width:300px;display:inline-block;vertical-align:top;padding-top:32px;position:relative;">
							<i id="thumb-fnm" title="<?php echo Security::snohtml($reg->thumb); ?>"><?php
								echo Security::snohtml($reg->thumb!='' ? kmrShortFileName($reg->thumb,20) : '(no thumbnail)');
							?></i> <i id="thumb-fsz"><?php echo $size; ?></i> <div><?php kmrUploader::button(-1); ?></div>
							<a href="javascript:;" onclick="kmr_thumb_clear();" style="margin-left: 2px;" title="Clear"><img src="<?php echo KeenMapsRegions::url(); ?>img/clear16.png" width="16" height="16" alt="" style="vertical-align:bottom;margin-right:4px;"/>Clear</a><br/>
							<span id="kmr_thum_prog" style="position:absolute;left:112px;top:56px;"><!----></span>
						</span>
					</div>&nbsp;<div>
						<span>Short Description:</span><br/>
						<?php CmsHost::cms()->wysiwyg('desc',$reg->desc,626,215); ?>
					</div>&nbsp;<div>
						<span>Regional Markers:</span><br/>
						<table width="100%">
							<tr>
								<td>
									<div style="height:180px; overflow-y:scroll;">
										<div style="border-bottom: 1px dotted #AAA; padding-bottom: 8px; margin-bottom: 8px;"><?php
											$markers=new kmMarkers(); $markers->load(); $marker=new kmMarker(); $html='';
											foreach($markers->rows as $marker)
												if($marker->published && $marker->category()->published){
													ob_start();
													?><label for="marker-<?php echo $marker->id; ?>" style="display: block;" onmouseover="kmr_view_show(this,<?php echo (int)$marker->id; ?>);" onmouseout="kmr_view_reset(this);">
														<input type="checkbox" name="markers[]" id="marker-<?php echo $marker->id; ?>"
															   value="<?php echo $marker->id; ?>"<?php if(in_array($marker->id,$reg->markers))echo ' checked="checked"'; ?>/>
														<?php echo Security::snohtml($marker->category()->name).' &raquo; '.Security::snohtml($marker->title); ?>
													</label><?php
													in_array($marker->id,$used) ? $html.=ob_get_clean() : ob_end_flush();
												}
										?></div><?php
										if($html!=''){
											?><a id="markers-show" href="javascript:;" onclick="jQuery('#markers-show').hide();jQuery('#markers-more').slideDown('fast');">Show More Markers &raquo;</a>
											<div id="markers-more" style="display:none;"><?php echo $html; ?></div><?php
										}
									?></div>
								</td><td width="288" align="right"><?php
									$map=new Google_Map('temp');
									$map->set_ctrl_maptype(false);
									$map->set_ctrl_pan(false);
									$map->set_ctrl_scale(false);
									$map->set_ctrl_streetview(false);
									$map->set_ctrl_zoom(true,Google_Map::POS_TOP_LEFT,Google_Map::STYLE_ZOOM_SMALL);
									$map->set_type(Google_Map::TYPE_TERRAIN);
									$map->set_size(280,180);
									$map->set_location('50.90620042364813','3.740681274414084');
									$map->set_zoom(3);
									$map->render(Google_Map::AS_HTML);
									foreach($markers->rows as $marker)
										if($marker->published && $marker->category()->published){
											$mkr=$map->add_marker('marker'.$marker->id);
											$mkr->set_location(
												Security::filename($marker->latitude,'',array('.')),
												Security::filename($marker->longitude,'',array('.'))
											);
											$mkr->set_icon(@json_encode($marker->category()->icon));
											$mkr->set_visible('false');
											$mkr->render(Google_Marker::AS_HTML);
										}
								?></td>
							</tr>
						</table>
					</div>&nbsp;<?php
					CmsHost::cms()->popup_button(($reg->id>0?'Update':'Add').' Region','do_save','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'do_save':
					$reg=new kmrRegion((int)$_REQUEST['id']); $reg->load();
					CmsHost::cms()->popup_begin(($reg->id>0?'Update':'Add').' Region','',380,180,ClassMethod(__CLASS__,'actions'));
					$reg->title=$_REQUEST['title'];
					$reg->number=(int)$_REQUEST['number'];
					$reg->video=$_REQUEST['video']=='http://' ? '' : $_REQUEST['video'];
					$reg->thumb=$_REQUEST['thumb'];
					$reg->desc=$_REQUEST['desc'];
					$reg->markers=array();
					if(isset($_REQUEST['markers']))
						foreach($_REQUEST['markers'] as $id)
							$reg->markers[]=(int)$id;
					if($reg->save()){
						if((int)$_REQUEST['id']>0){
							?><p>The region has been updated!</p><?php
						}else{
							?><p>The new region has been added!</p><?php
						}
					}else{
						?><p>Fatal: Could not save changes to database.</p><?php
					}
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'edit_view':
					$reg=new kmrRegion(count($checked)==0 ? 0 : (int)$checked[0]); $reg->load();
					CmsHost::cms()->popup_begin('Update Viewport for '.Security::snohtml($reg->title),'',380,180,ClassMethod(__CLASS__,'actions'));
					?><input type="hidden" name="id" value="<?php echo $reg->id; ?>"/>
					<div style="font-size:10px;">
						Latitude: <input type="text" readonly="readonly" id="latitude" name="latitude" value="<?php echo Security::snohtml($reg->latitude); ?>" style="width:128px; margin-right: 20px;"/>
						Longitude: <input type="text" readonly="readonly" id="longitude" name="longitude" value="<?php echo Security::snohtml($reg->longitude); ?>" style="width:128px; margin-right: 20px;"/>
						Zoom Level: <input type="text" readonly="readonly" id="zoom" name="zoom" value="<?php echo Security::snohtml($reg->zoom); ?>" style="width:128px;"/>
						<div style="background:#EEE; border:1px solid #EEE; <?php foreach(explode(',',',-moz-,-ms-,-o-,-webkit-,-html-') as $p)echo $p.'border-radius:4px; '; ?>margin:2px 0; padding:5px;">
							Move the map around until the crosshair pinpoints your desired location.
						</div>
					</div><?php
					$map=new Google_Map('location');
					$map->set_ctrl_pan(true);
					$map->set_ctrl_scale(true);
					$map->set_ctrl_streetview(false);
					$map->set_ctrl_zoom(true);
					?><script type="text/javascript">
						function km_update_loc(){
							document.getElementById("latitude").value=<?php $map->get_center_lat(); ?>;
							document.getElementById("longitude").value=<?php $map->get_center_lon(); ?>;
						}
						function km_update_zoom(){
							document.getElementById("zoom").value=<?php $map->get_zoom(); ?>;
						}
					</script><?php
					$map->set_crosshair(true);
					if($reg->latitude && $reg->longitude){
						$map->set_location(Security::filename($reg->latitude,'','.'),Security::filename($reg->longitude,'','.'));
					}else{
						$map->set_location('35.93691894491214','14.397943916015622');
					}
					$map->set_zoom((int)$reg->zoom);
					$map->set_size('625px','350px');
					$map->handle(Google_Map::EVENT_CHANGED_CENTER,'km_update_loc');
					$map->handle(Google_Map::EVENT_CHANGED_ZOOM,'km_update_zoom');
					$map->render(Google_Map::AS_HTML);
					?>&nbsp;<?php
					CmsHost::cms()->popup_button('Save View','save_view','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'save_view':
					$reg=new kmrRegion((int)$_REQUEST['id']); $reg->load();
					CmsHost::cms()->popup_begin('Save Viewport','',380,180,ClassMethod(__CLASS__,'actions'));
					$reg->latitude=$_REQUEST['latitude'];
					$reg->longitude=$_REQUEST['longitude'];
					$reg->zoom=$_REQUEST['zoom'];
					if($reg->save()){
						?><p>The viewport has been updated!</p><?php
					}else{
						?><p>Fatal: Could not save changes to database.</p><?php
					}
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'edit_tabs':
					$reg=new kmrRegion(count($checked)==0 ? 0 : (int)$checked[0]); $reg->load(); $tab=new kmrRegionTab(); $tab->name='Untitled';
					CmsHost::cms()->popup_begin('Update Tabs for '.Security::snohtml($reg->title),'',380,180,ClassMethod(__CLASS__,'actions'));
					if(count($reg->tabs())==0)$reg->tab_add($tab);
					?><input type="hidden" name="id" value="<?php echo $reg->id; ?>"/>
					<div class="tabs-btns" style="border-bottom:1px dotted #AAA; margin-bottom:8px; padding-bottom:4px;">
						<span class="tab-btns-list"><?php
							$first=true;
							foreach($reg->tabs() as $id=>$tab){
								?><span class="tabs-draggable" id="tab<?php echo (int)$id; ?>">
									<a href="javascript:;" style="<?php if($first)echo 'font-weight:bold;'; ?>" onclick="kmr_tab_show(<?php echo (int)$id; ?>)"><?php
										echo Security::snohtml($tab->name);
									?></a> | 
								</span><?php
								$first=false;
							}
						?></span>
						<a href="javascript:;" onclick="kmr_tab_add()"><span>+&nbsp;Add&nbsp;Tab</span></a>
					</div><div class="tabs-data" style="height:600px; overflow:hidden;"><?php
						$first=true;
						foreach($reg->tabs() as $id=>$tab){
							?><div class="tabs-tab" id="tabs-tab<?php echo (int)$id; ?>" style="margin-bottom:20px;">
								<input type="hidden" name="tabs[]" value="<?php echo (int)$id; ?>"/>
								<label>Name:</label> <input type="text" name="tabs-name<?php echo (int)$id; ?>" value="<?php echo Security::snohtml($tab->name); ?>" onkeyup="jQuery('#tab<?php echo (int)$id; ?> a').text(value);" style="width:200px"/>
								<a href="javascript:;" style="margin-left:20px; color:red;" onclick="if(confirm('Warning!\n\nThe contents of this tab will be lost forever.\n\nAre you sure you want this?'))jQuery('#tab<?php echo (int)$id; ?>, #tabs-tab<?php echo (int)$id; ?>').remove();">Delete This Tab</a><?php
								CmsHost::cms()->wysiwyg('tabs-html'.(int)$id,$tab->html,626,590);
							?></div><?php
							$first=false;
						}
					?></div>&nbsp;
					<script type="text/javascript">
						//jQuery('.tabs-draggable').sortable();
						//jQuery('.tabs-draggable').disableSelection();
					</script><?php
					CmsHost::cms()->popup_button('Save Tabs','save_tabs','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'save_tabs':
					$reg=new kmrRegion((int)$_REQUEST['id']); $reg->load();
					CmsHost::cms()->popup_begin('Save Tabs','',380,180,ClassMethod(__CLASS__,'actions'));
					$reg->tabs_clear();
					if(isset($_REQUEST['tabs']))
						foreach($_REQUEST['tabs'] as $id){
							$tab=new kmrRegionTab();
							$tab->id=$id;
							$tab->name=$_REQUEST['tabs-name'.$id];;
							//$tab->icon=$_REQUEST['tabs-icon'][$id];
							$tab->html=$_REQUEST['tabs-html'.$id];
							$reg->tab_add($tab);
						}
					if($reg->save()){
						?><p>The tabs have been updated!</p><?php
					}else{
						?><p>Fatal: Could not save changes to database.</p><?php
					}
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
			}
			die;
		}
		public static function cells($id,$row,$colid,$cell){
			if($colid=='title')return CmsHost::fire_action($id,Security::snohtml($cell),'edit');
			if($colid=='desc')
				return '<span style="overflow:hidden; width:32px; float:left; margin:4px; padding:0;"><img src="'.Security::snohtml($row->thumb).'" height="32"/></span>
						<div style="display:table-cell; height:34px; vertical-align:middle;">'.$cell.'</div>';
			if($colid=='view')
				return CmsHost::fire_action($id,'<img src="'.KeenMapsRegions::url().'img/select16.png" width="16" height="16" alt="" style="vertical-align:middle;"/> Select','edit_view');
			if($colid=='tabs')
				return CmsHost::fire_action($id,'<img src="'.KeenMapsRegions::url().'img/tabs16.png" width="16" height="16" alt="" style="vertical-align:middle;"/> Manage ('.(count($cell)==0 ? '<i>no tabs</i>' : (count($cell)==1 ? 'a tab' : count($cell).' tabs')).')','edit_tabs');
			if($colid=='markers')return ''.count($cell);
			return Security::snohtml(''.$cell);
		}
	}
	// register ajax/api calls
	Ajax::register('kmrViewRegions','actions',CmsHost::fsig_action());

?>