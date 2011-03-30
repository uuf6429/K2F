<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php','core/ajax.php','exts/google.maps.php','libs/swfupload/swfupload.php');

	class kmapsViewMarkers {
		public static function view(){
			CmsHost::cms()->adminlist_begin(KeenMaps::icons('markers'),'Manage Markers','allowadd',array(),ClassMethod(__CLASS__,'actions'));
			$mkrs=new kmMarkers();
			$mkrs->load();
			CmsHost::cms()->adminlist(
				$mkrs->rows,'id',
				array('title'=>'Marker Title & Alias','images'=>'Images','desc_short'=>'Short Description','fields'=>'Fields','published'=>'Published','location'=>'Location'),
				array('multiselect','allowadd'),
				array('Edit','Publish','Unpublish'),
				ClassMethod(__CLASS__,'cells'),
				'No markers yet',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
			Google_Map::init(); // force init now, coz it's too late for later on
			SwfUpload::init();  // force init now, coz it's too late for later on
			?><script type="text/javascript">
				function kmFieldsCheck(){
					var n={}; var e=jQuery('input[name="fields\\[\\]"]');
					e.each(function(){
						var s=jQuery(this).val().toLowerCase();
						if(typeof n[s]=='undefined')n[s]=0;
						n[s]++;
					});
					var w=false;
					e.each(function(){
						var s=jQuery(this).val().toLowerCase();
						jQuery(this).css('border-color',n[s]>1 ? 'red' : '');
						if(n[s]>1)w=true;
					});
					w ? jQuery('#kmFldNotUniqueWarn').show() : jQuery('#kmFldNotUniqueWarn').hide();
				}
				var km_up_defimg=<?php echo @json_encode(KeenMaps::url().'img/thumb-empty.gif'); ?>;
				function km_images_mkid(){ // returns the next unused image id
					for(var i=0; i<10000; i++)
						if(!document.getElementById('km-image'+i))
							return i;
				}
				function km_images_add(url){ // add image item given image url and returns id
					var id=km_images_mkid(); var eid='km-image'+id;
					var div=document.createElement('DIV'); div.id=eid;
					div.setAttribute('style','display:inline-block; width:148px; overflow:hidden; margin:4px; position:relative;');
					div.setAttribute('align','center');
					div.innerHTML='<div style="padding:4px;border:1px solid #EEEEEE;"><div style="overflow:hidden;"><img height="112" alt="" id="'+eid+'-img"/></div></div>'
						+'<div id="'+eid+'-prg" align="left" style="position:absolute; left:8px; right:8px; top:50px; height:10px; border:1px solid #666; padding:1px; display:none; background:#FFF; box-shadow:0 0 4px #FFF; -moz-box-shadow:0 0 4px #FFF; -o-box-shadow:0 0 4px #FFF; -webkit-box-shadow:0 0 4px #FFF; -khtml-box-shadow:0 0 4px #FFF; -ms-box-shadow:0 0 4px #FFF;">'
							+'<div id="'+eid+'-per" style="disaplay:inline-block; height:10px; width:0%; background:#257DA6;"><!----></div>'
						+'</div>   <input name="images[]" id="'+eid+'-url" type="hidden"/>'
						+'<a href="javascript:;" onclick="km_images_movu('+id+')" title="Move Left" style="display:inline-block;"><img src="<?php echo KeenMaps::url(); ?>img/left16.png" width="16" height="16" alt="" style="vertical-align:middle;"/></a> '
						+(<?php ob_start(); kmUploader::button('{%ID%}',20); echo @json_encode(ob_get_clean()); ?>).replace('{%ID%}',id)+' '
						+'<a href="javascript:;" onclick="km_images_furl('+id+')" title="Set from URL" style="display:inline-block;"><img src="<?php echo KeenMaps::url(); ?>img/www16.png" width="16" height="16" alt="" style="vertical-align:middle;"/></a> '
						+'<a href="javascript:;" onclick="km_images_dele('+id+')" title="Delete" style="display:inline-block;"><img src="<?php echo KeenMaps::url(); ?>img/delete16.png" width="16" height="16" alt="" style="vertical-align:middle;"/></a> '
						+'<a href="javascript:;" onclick="km_images_movd('+id+')" title="Move Right" style="display:inline-block;"><img src="<?php echo KeenMaps::url(); ?>img/right16.png" width="16" height="16" alt="" style="vertical-align:middle;"/></a>';
					document.getElementById('km-images').appendChild(div);
					document.getElementById(eid+'-img').src=url=='' ? km_up_defimg : url;
					document.getElementById(eid+'-url').value=url;
					return id;
				}
				function km_images_furl(id){ // set image url given it's id
					var url=document.getElementById('km-image'+id+'-url').value;
					if((url=prompt('',url=='' ? 'http://' : url))!==null){
						document.getElementById('km-image'+id+'-img').src=url=='' ? km_up_defimg : url;
						document.getElementById('km-image'+id+'-url').value=url;
					}
				}
				function km_images_dele(id){ // remove image given it's id
					document.getElementById('km-images').removeChild(document.getElementById('km-image'+id));
				}
				function km_images_movu(id){ // move image one place down [p,c] [c] c.append(p)
					var c=document.getElementById('km-image'+id);
					var p=c.previousSibling;
					if(p && c)document.getElementById('km-images').insertBefore(c,p);
				}
				function km_images_movd(id){ // move image one place up [c,n] [c] n.append(c)
					var c=document.getElementById('km-image'+id);
					var n=c.nextSibling;
					if(n && c)document.getElementById('km-images').insertBefore(n,c);
				}
				function km_thumb_clear(){
					// TODO: Confirm request and actually delete file through ajax
					document.getElementById('thumbnail-inp').value='';
					document.getElementById('thumbnail-img').src=<?php echo @json_encode(KeenMaps::url().'img/thumb-empty.gif'); ?>;
					document.getElementById('thumbnail-fnm').innerHTML='(no thumbnail)';
					document.getElementById('thumbnail-fsz').innerHTML='';
				}
				function km_thumb_update_start(file,id){
					if(id==-1){ // thumbnail uploader
						document.getElementById('km_thum_prog').innerHTML='0%';
					}else if(id==-2){ // gallery uploader
					}else{ // gallery image uploader (use id)
						var eid='km-image'+id;
						document.getElementById(eid+'-prg').style.display='block';
						document.getElementById(eid+'-per').style.width='0%';
					}
				}
				function km_thumb_update_progrs(file,done,total,id){
					var percent=Math.round(total==0 ? 100 : (done/total*100));
					if(id==-1){ // thumbnail uploader
						document.getElementById('km_thum_prog').innerHTML=percent+'%';
					}else if(id==-2){ // gallery uploader
					}else{ // gallery image uploader (use id)
						var eid='km-image'+id;
						document.getElementById(eid+'-per').style.width=percent+'%';
					}
				}
				function km_thumb_update_finish(file,data,ok,id){
					if(ok){ data=eval('('+data+')');
						if(id==-1){ // thumbnail uploader
							document.getElementById('km_thum_prog').innerHTML='100%';
							document.getElementById('thumbnail-inp').value=data.url;
							document.getElementById('thumbnail-img').src=data.url;
							document.getElementById('thumbnail-fnm').title=data.url;
							document.getElementById('thumbnail-fnm').innerHTML=data.file;
							document.getElementById('thumbnail-fsz').innerHTML=data.size;
						}else if(id==-2){ // gallery uploader
						}else{ // gallery image uploader (use id)
							var eid='km-image'+id;
							document.getElementById(eid+'-img').src=data.url;
							document.getElementById(eid+'-url').value=data.url;
							setTimeout(function(){ document.getElementById(eid+'-prg').style.display='none'; }, 500);
							document.getElementById(eid+'-per').style.width='100%';
						}
					}
				}
			</script><?php
		}
		public static function _kmFldAdd($name='',$value=''){
			static $counter=0; $counter++;
			return '<div id="kmFldAdd'.$counter.'">'
					.'<input type="text" name="fields[]" value="'.Security::snohtml($name).'" style="width:148px;" onkeyup="kmFieldsCheck();"/>'
					.'<input type="text" name="values[]" value="'.Security::snohtml($value).'" style="width:148px;" onkeyup="kmFieldsCheck();"/>'
					.'<a href="javascript:;" onclick="jQuery(\'#kmFldAdd'.$counter.'\').remove();kmFieldsCheck();">'
						.'<small>delete</small></a>'
				.'</div>';
		}
		public static function actions($table,$action,$checked){
			switch($action){
				case 'new': case 'edit':
					$mkr=new kmMarker(count($checked)==0 ? 0 : (int)$checked[0]); $mkr->load();
					CmsHost::cms()->popup_begin(($mkr->id>0?'Update':'Add').' Marker','',380,180,ClassMethod(__CLASS__,'actions'));
					$cts=new kmCategories(); $cts->load(); $cat=new kmCategory();
					$murl=CmsHost::cms()->config_get('km-marker-url');
					if($murl=='')$murl='/places/%category%/%place%/';
					$size=$mkr->thumbnail!='' ? '('.kmBytesToHuman(strlen(Connect::get($mkr->thumbnail))).')' : '';
					?><input type="hidden" name="id" value="<?php echo $mkr->id; ?>"/><p>
						<span style="width:180px;display:inline-block;">Enter Marker Name:</span>
						<input type="text" id="title" name="title" value="<?php echo Security::snohtml($mkr->title); ?>" style="vertical-align:middle;width:300px;"/>
					</p><p>
						<span style="width:180px;display:inline-block;">Select Category:</span>
						<select name="cid" style="width: 300px;">
							<option style="font-style: italic;" value="0"<?php echo $mkr->cid==0 ? ' selected="selected"' : ''; ?>>Uncategorized</option><?php
							foreach($cts->rows as $cat)
								echo '<option value="'.$cat->id.'"'.($mkr->cid==$cat->id ? ' selected="selected"' : '').'>'.Security::snohtml($cat->name).'</option>';
						?></select>
					</p><p>
						<span style="width:180px;display:inline-block;">Select Offer Type:</span>
						<select name="type" style="width: 300px;">
							<option style="font-style: italic;" value="0"<?php echo $mkr->cid==0 ? ' selected="selected"' : ''; ?>>None</option><?php
							foreach(explode(CRLF,CmsHost::cms()->config_get('km-marker-types')) as $type)
								echo '<option value="'.Security::snohtml($type).'"'.($mkr->type==$type ? ' selected="selected"' : '').'>'.Security::snohtml($type).'</option>';
						?></select>
					</p><p>
						<span style="width:180px;display:inline-block;">Enter URL Alias:</span><?php
						$inp='<input type="text" id="alias" name="alias" value="'.Security::snohtml($mkr->alias).'" style="vertical-align:middle;width:100px;"/>';
						?><code><?php echo str_replace(array('%category%','%place%'),array('CategoryName',$inp),Security::snohtml($murl)); ?></code>
					</p><div style="font: 12px Verdana;">
						<input type="hidden" name="thumbnail" id="thumbnail-inp" value="<?php echo Security::snohtml($mkr->thumbnail); ?>"/>
						<span style="width:180px;height:112px;display:inline-block;vertical-align:top;">Select Marker Thumbnail:</span>
						<div style="width:112px;height:112px;display:inline-block;vertical-align:top;margin-right:12px;padding:4px;border:1px solid #EEEEEE;" align="center"><div style="overflow:hidden;">
							<img id="thumbnail-img" src="<?php echo Security::snohtml($mkr->thumbnail=='' ? KeenMaps::url().'img/thumb-empty.gif' : $mkr->thumbnail); ?>" height="112" alt=""/>
						</div></div>
						<span style="width:300px;display:inline-block;vertical-align:top;padding-top:32px;position:relative;">
							<i id="thumbnail-fnm" title="<?php echo Security::snohtml($mkr->thumbnail); ?>"><?php
								echo Security::snohtml($mkr->thumbnail!='' ? kmShortFileName($mkr->thumbnail,20) : '(no thumbnail)');
							?></i> <i id="thumbnail-fsz"><?php echo $size; ?></i> <div><?php kmUploader::button(-1); ?></div>
							<a href="javascript:;" onclick="km_thumb_clear();" style="margin-left: 2px;" title="Clear"><img src="<?php echo KeenMaps::url(); ?>img/clear16.png" width="16" height="16" alt="" style="vertical-align:bottom;margin-right:4px;"/>Clear</a><br/>
							<span id="km_thum_prog" style="position:absolute;left:112px;top:56px;"><!----></span>
						</span>
					</div><p>
						<span>Short Description (marker label):</span><br/>
						<?php CmsHost::cms()->wysiwyg('desc_short',$mkr->desc_short,626,150); ?>
					</p><p>
						<span>Longer Description (marker page):</span><br/>
						<?php CmsHost::cms()->wysiwyg('desc_long',$mkr->desc_long,626,400); ?>
					</p>&nbsp;<div>
						<span style="width:180px;display:inline-block;">Image Gallery:</span>
						<div id="km-images" style="margin-bottom:8px;"></div>
<!-- todo: make this an uploader button -->						<input type="button" onclick="km_images_add('');" value="Add Image" class="button-secondary action">
						<script type="text/javascript"><?php foreach($mkr->images as $url)echo 'km_images_add('.@json_encode($url).');'.CRLF; ?></script>
					</div>&nbsp;<table>
						<tr>
							<td valign="top" width="180">Add (Optional) Fields:</td>
							<td>
								<div id="kmmt<?php echo $mkr->id; ?>"><?php
									if(!count($mkr->fields))echo self::_kmFldAdd();
									foreach($mkr->fields as $n=>$v)echo self::_kmFldAdd($n,$v);
								?></div><div>
									<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Names</small>
									<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Values</small>
								</div>&nbsp;<br/>
								<input type="button" value="Add Field" onclick="jQuery('#kmmt<?php echo $mkr->id; ?>').append(<?php
									echo Security::snohtml(@json_encode(self::_kmFldAdd())); ?>); kmFieldsCheck();"/>
								<small id="kmFldNotUniqueWarn" style="display:none;color:red;">Error: Field names must be unique.</small>
							</td>
						</tr>
					</table><br/><?php
					// title category tags desc_short desc_long thumbnail images fields lon/lat
					CmsHost::cms()->popup_button(($mkr->id>0?'Update':'Add').' Marker','do_save','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'do_save':
					$mkr=new kmMarker((int)$_REQUEST['id']); $mkr->load();
					CmsHost::cms()->popup_begin(($mkr->id>0?'Update':'Add').' Marker','',380,180,ClassMethod(__CLASS__,'actions'));
					$mkr->title=$_REQUEST['title'];
					$mkr->alias=$mkr->unique_alias($_REQUEST['alias']);
					$mkr->cid=(int)$_REQUEST['cid'];
					$mkr->type=$_REQUEST['type'];
					$mkr->desc_long=$_REQUEST['desc_long'];
					$mkr->desc_short=$_REQUEST['desc_short'];
					$mkr->thumbnail=$_REQUEST['thumbnail'];
					$mkr->fields=array();
					if(isset($_REQUEST['fields']))
						foreach($_REQUEST['fields'] as $i=>$f)
							if($f!='')$mkr->field($f,$_REQUEST['values'][$i]);
					if(isset($_REQUEST['images']))
						$mkr->images=array_values((array)$_REQUEST['images']);
					if($mkr->save()){
						if((int)$_REQUEST['id']>0){
							?><p>The marker has been updated!</p><?php
						}else{
							?><p>The new marker has been added!</p><?php
						}
					}else{
						?><p>Fatal: Could not save changes to database.</p><?php
					}
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'edit_loc':
					$mkr=new kmMarker(count($checked)==0 ? 0 : (int)$checked[0]); $mkr->load();
					CmsHost::cms()->popup_begin('Select Location for '.Security::snohtml($mkr->title),'',380,180,ClassMethod(__CLASS__,'actions'));
					?><input type="hidden" name="id" value="<?php echo $mkr->id; ?>"/>
					<div style="font-size:10px;">
						Latitude: <input type="text" readonly="readonly" id="latitude" name="latitude" value="<?php echo Security::snohtml($mkr->latitude); ?>" style="width:128px; margin-right: 20px;"/>
						Longitude: <input type="text" readonly="readonly" id="longitude" name="longitude" value="<?php echo Security::snohtml($mkr->longitude); ?>" style="width:128px;"/>
						<div style="background:#EEE; border:1px solid #EEE; <?php foreach(explode(',',',-moz-,-ms-,-o-,-webkit-,-html-') as $p)echo $p.'border-radius:4px; '; ?>margin:2px 0; padding:5px;">
							Move the map around until the crosshair pinpoints your desired location.
						</div>
					</div><?php
					$map=new Google_Map('location');
					?><script type="text/javascript">
						function km_update_loc(){
							document.getElementById("latitude").value=<?php $map->get_center_lat(); ?>;
							document.getElementById("longitude").value=<?php $map->get_center_lon(); ?>;
						}
					</script><?php
					$map->set_crosshair(true);
					if($mkr->latitude && $mkr->longitude){
						$map->set_location(Security::filename($mkr->latitude,'','.'),Security::filename($mkr->longitude,'','.'));
					}else{
						$map->set_location('35.93691894491214','14.397943916015622');
					}
					$map->set_size('625px','350px');
					$map->handle(Google_Map::EVENT_CHANGED_CENTER,'km_update_loc');
					$map->render(Google_Map::AS_HTML);
					CmsHost::cms()->popup_button('Save Location','do_save_loc','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'do_save_loc':
					$mkr=new kmMarker((int)$_REQUEST['id']); $mkr->load();
					CmsHost::cms()->popup_begin('Save Location','',380,180,ClassMethod(__CLASS__,'actions'));
					$mkr->latitude=$_REQUEST['latitude'];
					$mkr->longitude=$_REQUEST['longitude'];
					if($mkr->save()){
						?><p>The marker location has been updated!</p><?php
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
			if($colid=='title')
				return CmsHost::fire_action($id,Security::snohtml($row->category()->name).'&nbsp;&raquo;<br/>'.Security::snohtml($cell.' ('.$row->alias.')'),'edit');
			if($colid=='images')
				return is_array($cell) && count($cell) ? (count($cell)==1 ? 'an image' : count($cell).'&nbsp;images') : '<i>(none)</i>';
			if($colid=='desc_short')
				return $cell=='' ? '<i>(none)</i>' : $cell; // override security filter
			if($colid=='fields'){
				return count($cell) ? Security::snohtml(implode(', ',array_keys($cell))) : '<i>(none)</i>';
			}
			if($colid=='published')
				return '<a href="javascript:;" onclick="" style="margin-left:24px;"><img src="'.KeenMaps::url().'img/'.($cell ? 'ena' : 'dis').'16.png" alt="" width="16" height="16"/></a>';
			if($colid=='location')
				return CmsHost::fire_action($id,'<img src="'.KeenMaps::url().'img/select16.png" width="16" height="16" alt="" style="vertical-align:middle;"/>&nbsp;Select','edit_loc');
			return Security::snohtml(''.$cell);
		}
	}
	// register ajax/api calls
	Ajax::register('kmapsViewMarkers','actions',CmsHost::fsig_action());

?>