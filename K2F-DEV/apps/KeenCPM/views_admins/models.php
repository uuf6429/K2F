<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php','core/ajax.php','libs/swfupload/swfupload.php');

	class kcmViewModels {
		public static function manage(){
			CmsHost::cms()->adminlist_begin(KeenCPM::icons('models'),'Manage Models','allowadd',array(),ClassMethod(__CLASS__,'actions'));
			$models=new kcmModels();
			$models->load('1 ORDER BY created DESC');
			CmsHost::cms()->adminlist(
				$models->rows,'id',
				array('name'=>'Name','serial'=>'Model Number','type'=>'Type','fields'=>'Fields','created'=>'Created','soldcount'=>'Units Sold'),
				array('multiselect','allowadd'),
				array('Edit','Archive','Delete'),
				ClassMethod(__CLASS__,'cells'),
				'No models yet',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
			SwfUpload::init();  // force init now, coz it's too late for later on
			?><script type="text/javascript">
				function basename(path, suffix){
					// http://kevin.vanzonneveld.net
					// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
					// +   improved by: Ash Searle (http://hexmen.com/blog/)
					// +   improved by: Lincoln Ramsay
					// +   improved by: djmix
					// *     example 1: basename('/www/site/home.htm', '.htm');
					// *     returns 1: 'home'
					// *     example 2: basename('ecra.php?p=1');
					// *     returns 2: 'ecra.php?p=1'
					var b=path.replace(/^.*[\/\\]/g,'');
					if(typeof(suffix)=='string' && b.substr(b.length-suffix.length) == suffix)
						b=b.substr(0,b.length-suffix.length);
					return b;
				}
				function str_replace(search,replace,subject){
					while(subject.indexOf(search)!=-1)
						subject=subject.replace(search,replace);
					return subject;
				}
				function kcmFieldsCheck(){
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
					w ? jQuery('#kcmFldNotUniqueWarn').show() : jQuery('#kcmFldNotUniqueWarn').hide();
				}
				function kcmFilesCheck(){
					var n={}; var e=jQuery('input[name="file-names[\\]"]');
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
					w ? jQuery('#kcmFileNotUniqueWarn').show() : jQuery('#kcmFileNotUniqueWarn').hide();
				}
				function kcmTypeChanged(url,model,type){
					jQuery('#kcmmt'+model).fadeOut('fast',function(){
						jQuery('#kcmmt'+model).html('<div align="center" style="padding:24px 0 32px 0;">loading...</div>');
						jQuery('#kcmmt'+model).fadeIn('fast',function(){
							jQuery.get(url+'&type='+type+'&getfields',function(data){
								jQuery('#kcmmt'+model).fadeOut('fast',function(){
// TODO: Maybe merge data instead of replace?
									jQuery('#kcmmt'+model).html(data);
									jQuery('#kcmmt'+model).fadeIn('fast');
								});
							});
						});
					});
				}
				var oji=null;
				function kcmSerialChanged(model,serial){
					if(oji)oji.abort();
					var url=location.href+<?php echo @json_encode(Ajax::url(__CLASS__,'_kcmMdlSerialExists','&')); ?>;
					oji=jQuery.getJSON(url+'&model='+(model*1)+'&serial='+encodeURIComponent(serial),function(data){
						jQuery('#serial').css('border-color',data ? 'red' :null);
						data ? jQuery('#kcmMdlNotUniqueWarn').show() : jQuery('#kcmMdlNotUniqueWarn').hide();
						oji=null;
					});
				}
				var kcm_up_defimg=<?php echo @json_encode(KeenCPM::url().'img/thumb-empty.gif'); ?>;
				function kcm_images_mkid(){ // returns the next unused image id
					for(var i=0; i<10000; i++)
						if(!document.getElementById('kcm-image'+i))
							return i;
				}
				function kcm_images_add(url){ // add image item given image url and returns id
					var id=kcm_images_mkid(); var eid='kcm-image'+id;
					var div=document.createElement('DIV'); div.id=eid;
					div.setAttribute('style','display:inline-block; width:148px; overflow:hidden; margin:4px; position:relative;');
					div.setAttribute('align','center');
					div.innerHTML='<div style="padding:4px;border:1px solid #EEEEEE;"><div style="overflow:hidden;"><img height="112" alt="" id="'+eid+'-img"/></div></div>'
						+'<div id="'+eid+'-prg" align="left" style="position:absolute; left:8px; right:8px; top:50px; height:10px; border:1px solid #666; padding:1px; display:none; background:#FFF; box-shadow:0 0 4px #FFF; -moz-box-shadow:0 0 4px #FFF; -o-box-shadow:0 0 4px #FFF; -webkit-box-shadow:0 0 4px #FFF; -khtml-box-shadow:0 0 4px #FFF; -ms-box-shadow:0 0 4px #FFF;">'
							+'<div id="'+eid+'-per" style="disaplay:inline-block; height:10px; width:0%; background:#257DA6;"><!----></div>'
						+'</div>   <input name="images[]" id="'+eid+'-url" type="hidden"/>'
						+'<a href="javascript:;" onclick="kcm_images_movu('+id+')" title="Move Left" style="display:inline-block;"><img src="<?php echo KeenCPM::url(); ?>img/left16.png" width="16" height="16" alt="" style="vertical-align:middle;"/></a> '
						+(<?php ob_start(); kcmUploader::button('{%ID%}',20); echo @json_encode(ob_get_clean()); ?>).replace('{%ID%}',id)+' '
						+'<a href="javascript:;" onclick="kcm_images_furl('+id+')" title="Set from URL" style="display:inline-block;"><img src="<?php echo KeenCPM::url(); ?>img/www16.png" width="16" height="16" alt="" style="vertical-align:middle;"/></a> '
						+'<a href="javascript:;" onclick="kcm_images_dele('+id+')" title="Delete" style="display:inline-block;"><img src="<?php echo KeenCPM::url(); ?>img/delete16.png" width="16" height="16" alt="" style="vertical-align:middle;"/></a> '
						+'<a href="javascript:;" onclick="kcm_images_movd('+id+')" title="Move Right" style="display:inline-block;"><img src="<?php echo KeenCPM::url(); ?>img/right16.png" width="16" height="16" alt="" style="vertical-align:middle;"/></a>';
					document.getElementById('kcm-images').appendChild(div);
					document.getElementById(eid+'-img').src=url=='' ? kcm_up_defimg : url;
					document.getElementById(eid+'-url').value=url;
					return id;
				}
				function kcm_images_furl(id){ // set image url given it's id
					var url=document.getElementById('kcm-image'+id+'-url').value;
					if((url=prompt('',url=='' ? 'http://' : url))!==null){
						document.getElementById('kcm-image'+id+'-img').src=url=='' ? kcm_up_defimg : url;
						document.getElementById('kcm-image'+id+'-url').value=url;
					}
				}
				function kcm_images_dele(id){ // remove image given it's id
					document.getElementById('kcm-images').removeChild(document.getElementById('kcm-image'+id));
				}
				function kcm_images_movu(id){ // move image one place down [p,c] [c] c.append(p)
					var c=document.getElementById('kcm-image'+id);
					var p=c.previousSibling;
					if(p && c)document.getElementById('kcm-images').insertBefore(c,p);
				}
				function kcm_images_movd(id){ // move image one place up [c,n] [c] n.append(c)
					var c=document.getElementById('kcm-image'+id);
					var n=c.nextSibling;
					if(n && c)document.getElementById('kcm-images').insertBefore(n,c);
				}
				function kcm_thumb_update_start(file,id){
					if(id==-1){ // thumbnail uploader
						document.getElementById('kcm_thum_prog').innerHTML='0%';
					}else if(id==-2){ // gallery uploader
					}else{ // gallery image uploader (use id)
						var eid='kcm-image'+id;
						document.getElementById(eid+'-prg').style.display='block';
						document.getElementById(eid+'-per').style.width='0%';
					}
				}
				function kcm_thumb_update_progrs(file,done,total,id){
					var percent=Math.round(total==0 ? 100 : (done/total*100));
					if(id==-1){ // thumbnail uploader
						document.getElementById('kcm_thum_prog').innerHTML=percent+'%';
					}else if(id==-2){ // gallery uploader
					}else{ // gallery image uploader (use id)
						var eid='kcm-image'+id;
						document.getElementById(eid+'-per').style.width=percent+'%';
					}
				}
				function kcm_thumb_update_finish(file,data,ok,id){
					if(ok){ data=eval('('+data+')');
						if(id==-1){ // thumbnail uploader
							document.getElementById('kcm_thum_prog').innerHTML='100%';
							document.getElementById('thumbnail-inp').value=data.url;
							document.getElementById('thumbnail-img').src=data.url;
							document.getElementById('thumbnail-fnm').title=data.url;
							document.getElementById('thumbnail-fnm').innerHTML=data.file;
							document.getElementById('thumbnail-fsz').innerHTML=data.size;
						}else if(id==-2){ // gallery uploader
						}else{ // gallery image uploader (use id)
							var eid='kcm-image'+id;
							document.getElementById(eid+'-img').src=data.url;
							document.getElementById(eid+'-url').value=data.url;
							setTimeout(function(){ document.getElementById(eid+'-prg').style.display='none'; }, 500);
							document.getElementById(eid+'-per').style.width='100%';
						}
					}
				}
				function kcm_select_add_option(select_id,value,text){
					var optn=jQuery('<option></option>');
					optn.text(text); optn.attr('value',value);
					jQuery('#'+select_id).append(optn);
				}
				function kcm_group_change(value){
					kcm_groups=eval(jQuery('#kcm_grps').val());
					if(value!=''){
						if(value=='{{create}}'){
							var ngrp=prompt('Please enter the new group\'s name:','New Group');
							if(ngrp){
								// create group if it doesn't exist yet
								if(jQuery('#group option[value="'+ngrp+'"]').length==0)
									kcm_select_add_option('group',ngrp,ngrp);
								// select the group
								jQuery('#group').val(ngrp);
							}else{
								// revert to none
								jQuery('#group').val('');
							}
						}
						// clear subgroups (except first two items)
						jQuery.each(jQuery('#subgroup option'),function(i,v){ if(i>1)jQuery(v).remove(); });
						// update subgroup
						var grp=jQuery('#group').val();
						if(typeof kcm_groups[grp]!='undefined')
							for(var i=0; i<kcm_groups[grp].length; i++)
								kcm_select_add_option('subgroup',kcm_groups[grp][i],kcm_groups[grp][i]);
						// select 'none' in subgroup
						jQuery('#subgroup').val('');
					}
				}
				function kcm_subgroup_change(value){
					kcm_groups=eval(jQuery('#kcm_grps').val());
					if(value!=''){
						if(value=='{{create}}'){
							var nsgp=prompt('Please enter the new subgroup\'s name:','New Subgroup');
							if(nsgp){
								// create subgroup if it doesn't exist yet
								if(jQuery('#subgroup option[value="'+nsgp+'"]').length==0)
									kcm_select_add_option('subgroup',nsgp,nsgp);
								// select the subgroup
								jQuery('#subgroup').val(nsgp);
							}else{
								// revert to none
								jQuery('#subgroup').val('');
							}
						}
					}
				}
				function kcm_files_furl(id){ // set file url given it's id
					var url=document.getElementById('kcmFileAdd'+id+'-url').value;
					if((url=prompt('',url=='' ? 'http://' : url))!==null){
						document.getElementById('kcmFileAdd'+id+'-txt').innerHTML=url=='' ? '<i>(none)</i>' : '<a href="'+url+'" target="_blank">'+basename(url)+'</a>';
						document.getElementById('kcmFileAdd'+id+'-url').value=url;
					}
				}
				function kcm_files_dele(id){ // remove file given it's id
					jQuery('#kcmFileAdd'+id).remove();
					kcmFilesCheck();
				}
				function kcm_file_update_start(file,id){
					document.getElementById('kcmFileAdd'+id+'-txt').innerHTML='(0%)';
				}
				function kcm_file_update_progrs(file,done,total,id){
					var percent=Math.round(total==0 ? 100 : (done/total*100));
					document.getElementById('kcmFileAdd'+id+'-txt').innerHTML='('+percent+'%)';
				}
				function kcm_file_update_finish(file,data,ok,id){
					if(ok){
						data=eval('('+data+')');
						document.getElementById('kcmFileAdd'+id+'-txt').innerHTML='<a href="'+data.url+'">'+basename(data.url)+'</a>';
						document.getElementById('kcmFileAdd'+id+'-url').value=data.url;
					}
				}
			</script><?php
		}
		public static function _kcmMdlSerialExists($model,$serial){
			$models=new kcmModels();
			$models->load('`id`!='.(int)$model.' AND `serial`="'.Security::escape($serial).'"');
			return $models->count()!=0;
		}
		public static function _kcmCatFldAdd($name='',$value=''){
			static $counter=0; $counter++;
			return '<div id="kcmCatFldAdd'.$counter.'">'
					.'<input type="text" name="fields[]" value="'.Security::snohtml($name).'" style="width:148px;" onkeyup="kcmFieldsCheck();"/>'
					.'<input type="text" name="values[]" value="'.Security::snohtml($value).'" style="width:148px;" onkeyup="kcmFieldsCheck();"/> '
					.'<a href="javascript:;" onclick="jQuery(\'#kcmCatFldAdd'.$counter.'\').remove();kcmFieldsCheck();">'
						.'<small>delete</small></a>'
				.'</div>';
		}
		public static function _kcmFilesAdd($name='',$url='',$id=null){
			static $counter=0; $counter++; if(!$id)$id=$counter; ob_start(); kcmUploader::button2($id,20); $upload_button=ob_get_clean();
			return '<div id="kcmFileAdd'.$id.'" style="position: relative;">'
					.' <input type="text" name="file-names[]" value="'.Security::snohtml($name).'" style="width:120px;" onkeyup="kcmFilesCheck();"/>'
					.' <div align="center" style="position:absolute; left:120px; right:0; top:3px;">'
						.' <input type="hidden" name="file-links[]" value="'.Security::snohtml($url).'" id="kcmFileAdd'.$id.'-url"/> '
						.' <span id="kcmFileAdd'.$id.'-txt">'.($url=='' ? '<i>(none)</i>' : '<a href="'.Security::snohtml($url).'" target="_blank">'.Security::snohtml(basename($url)).'</a>').'</span>'
						.' '.$upload_button
						.' <a href="javascript:;" onclick="kcm_files_furl('.$id.')" title="Set from URL" style="display: inline-block;"><img width="16" height="16" style="vertical-align: middle;" alt="" src="'. KeenCPM::url().'img/www16.png" border="0"></a>'
						.' <a href="javascript:;" onclick="kcm_files_dele('.$id.')" title="Delete" style="display: inline-block;"><img width="16" height="16" style="vertical-align: middle;" alt="" src="'. KeenCPM::url().'img/delete16.png" border="0"></a>'
					.' </div>'
				.'</div>';
		}
		public static function actions($table,$action,$checked){
			switch($action){
				case 'new': case 'edit':
					// initialize some variables
					if(isset($_REQUEST['getfields'])){
						$type=new kcmModelType((int)$_REQUEST['type']);
						$type->load();
						if(!count($type->fields))echo self::_kcmCatFldAdd();
						foreach($type->fields as $n=>$v)echo self::_kcmCatFldAdd($n,$v);
						die;
					}
					$model=new kcmModel(count($checked)==0 ? 0 : (int)$checked[0]); $model->load();
					$hint='Each model has unique model number, but each product has a different barcode!';
					CmsHost::cms()->popup_begin(($model->id>0?'Update':'Add').' Model',$hint,380,180,ClassMethod(__CLASS__,'actions'));
					$models=new kcmModels(); $models->load(); $groups=array();
					foreach($models->rows as $row)if($row->group && $row->group!=''){
						if(!isset($groups[$row->group]))$groups[$row->group]=array();
						if($row->subgroup && $row->subgroup!='')$groups[$row->group][]=$row->subgroup;
					}
					foreach($groups as $group=>$subgroup)array_unique($groups[$group]);
					// write the form
					?><input type="hidden" name="id" value="<?php echo $model->id; ?>"/><p>
						<span style="width:180px;display:inline-block;">Enter Model Name:</span>
						<input type="text" id="name" name="name" value="<?php echo Security::snohtml($model->name); ?>" style="vertical-align:middle;width:300px;"/>
						<br><small>The model name is just a short description, like "ipod 4gb green".</small>
					</p><p>
						<span style="width:180px;display:inline-block;">Enter Model Number:</span>
						<input type="text" id="serial" name="serial" onkeyup="kcmSerialChanged(<?php echo (int)$model->id.',this.value'; ?>);" value="<?php echo Security::snohtml($model->serial); ?>" style="vertical-align:middle;width:300px;"/>
						<small id="kcmMdlNotUniqueWarn" style="display:none;color:red;margin-left:185px;">Error: Model serial number is already in use.</small>
						<br><small>You can find this near the product&rsquo;s barcode.</small>
					</p><p>
						<span style="width:180px;display:inline-block;">Select Model Type:</span>
						<select id="type" name="type" style="vertical-align:middle;width:300px;" onchange='kcmTypeChanged(<?php echo @json_encode($_SERVER['REQUEST_URI']).','.$model->id.',this.value'; ?>);'><?php
							$types=new kcmModelTypes(); $types->load(); $type=new kcmModelType();
							foreach($types->rows as $type)
								echo '<option value="'.$type->id.'"'.($model->type==$type->id ? ' selected' : '').'>'.Security::snohtml($type->name).'</option>';
						?></select>
					</p><table>
						<tr>
							<td valign="top" width="180">Add (Optional) Fields:</td>
							<td>
								<div id="kcmmt<?php echo $model->id; ?>"><?php
									if(!count($model->fields))echo self::_kcmCatFldAdd();
									foreach($model->fields as $n=>$v)echo self::_kcmCatFldAdd($n,$v);
								?></div><div>
									<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Names</small>
									<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Values</small>
								</div>&nbsp;<br/>
								<input type="button" value="Add Field" onclick="jQuery('#kcmmt<?php echo $model->id; ?>').append(<?php
									echo Security::snohtml(@json_encode(self::_kcmCatFldAdd())); ?>); kcmFieldsCheck();"/>
								<small id="kcmFldNotUniqueWarn" style="display:none;color:red;">Error: Field names must be unique.</small>
							</td>
						</tr>
					</table>
					<hr style="border: none; border-bottom: 1px solid #DFDFDF;"/><p>
						<label for="published">
							<input type="checkbox" id="published" name="published" value="published"<?php if($model->published)echo ' checked="checked"'; ?> onclick="this.checked ? jQuery('#ifpublished').slideDown() : jQuery('#ifpublished').slideUp();"/>
							Show Model on Products Page
						</label>
					</p><input type="hidden" id="kcm_grps" value="(<?php echo Security::snohtml(@json_encode($groups)); ?>)"/>
					<div id="ifpublished"<?php if(!$model->published)echo ' style="display:none;"'; ?>>
						<p>
							<span style="width:140px;display:inline-block;">Product Group:</span>
							<select name="group" id="group" onchange="kcm_group_change(this.value);" style="width:300px;">
								<option value="" style="font-style:italic;">No Group</option>
								<option value="{{create}}" style="font-weight:bold;">Create New Group</option><?php
									foreach($groups as $group=>$subgroup)
										echo '<option value="'.Security::snohtml($group).'"'.($group==$model->group ? ' selected="selected"' : '').'>'.Security::snohtml($group).'</option>';
								?>
							</select>
						</p><p>
							<span style="width:140px;display:inline-block;">Product Subgroup:</span>
							<select name="subgroup" id="subgroup" onchange="kcm_subgroup_change(this.value);" style="width:300px;">
								<option value="" style="font-style:italic;">No Subgroup</option>
								<option value="{{create}}" style="font-weight:bold;">Create New Subgroup</option><?php
								if(isset($groups[$model->group]))foreach($groups[$model->group] as $subgroup)
									echo '<option value="'.Security::snohtml($subgroup).'"'.($subgroup==$model->subgroup ? ' selected="selected"' : '').'>'.Security::snohtml($subgroup).'</option>';
								?>
							</select>
						</p><table>
							<tr>
								<td valign="top" width="140">File Downloads:</td>
								<td>
									<div id="kcmmf<?php echo $model->id; ?>"><?php
										if(!count($model->files))echo self::_kcmFilesAdd();
										foreach($model->files as $n=>$v)echo self::_kcmFilesAdd($n,$v);
									?></div><div>
										<small style="display:inline-block;width:120px;text-align:center;">&uarr; File Name / Type</small>
										<small style="display:inline-block;width:270px;text-align:center;">&uarr; Manage File / Link</small>
									</div>&nbsp;<br/>
									<input type="button" value="Add File" onclick='jQuery("#kcmmf<?php echo $model->id; ?>").append(str_replace("{%ID%}",Math.round(Math.random()*10000000),<?php
										echo Security::snohtml(@json_encode(self::_kcmFilesAdd('','','{%ID%}'))); ?>)); kcmFilesCheck();'/>
									<small id="kcmFileNotUniqueWarn" style="display:none;color:red;">Error: File names must be unique.</small>
								</td>
							</tr><tr><td>&nbsp;</td></tr><tr>
								<td valign="top">Image Gallery:</td>
								<td>
									<div id="kcm-images" style="margin-bottom:8px;"></div>
									<input type="button" onclick="kcm_images_add('');" value="Add Image" class="button-secondary action">
									<script type="text/javascript"><?php foreach($model->images as $url)echo 'kcm_images_add('.@json_encode($url).');'.CRLF; ?></script>
								</td>
							</tr>
						</table><p>
							<span style="width:180px;display:inline-block;"></span>
						</p>
						<div>Product Description:</div><?php
						CmsHost::cms()->wysiwyg('description',$model->description,630,300);
					?></div><?php
					CmsHost::cms()->popup_button(($model->id>0?'Update':'Add').' Model','do_save','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'do_save':
					$model=new kcmModel((int)$_REQUEST['id']); $model->load();
					CmsHost::cms()->popup_begin(($model->id>0?'Update':'Add').' Model','',380,180,ClassMethod(__CLASS__,'actions'));
					if($model->id<1)$model->created=time();
					$model->name=$_REQUEST['name'];
					$model->serial=$_REQUEST['serial'];
					$model->type=(int)$_REQUEST['type'];
					$model->fields=array();
					if(isset($_REQUEST['fields']))
						foreach($_REQUEST['fields'] as $i=>$f)
							if($f!='')$model->field($f,$_REQUEST['values'][$i]);
					$model->published=isset($_REQUEST['published']) && $_REQUEST['published']=='published';
					$model->group=$_REQUEST['group'];
					$model->subgroup=$_REQUEST['subgroup'];
					$model->files=array();
					if(isset($_REQUEST['file-names']))
						foreach($_REQUEST['file-names'] as $i=>$name)
							if($name!='' && $_REQUEST['file-links'][$i]!='')
								$model->files[$name]=$_REQUEST['file-links'][$i];
					$model->images=array();
					if(isset($_REQUEST['images']))
						foreach($_REQUEST['images'] as $image)
							if($image!='')
								$model->images[]=$image;
					$model->description=$_REQUEST['description'];
					if($model->save()){
						if((int)$_REQUEST['id']>0){
							?><p>The model has been updated!</p><?php
						}else{
							?><p>The new model has been added!</p><?php
						}
					}else{
						?><p>Fatal: Could not save changes to database.</p><?php
					}
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'delete':
					CmsHost::cms()->popup_begin('Delete Model','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>
						Warning! You are about to delete the following items <b>permanently</b>.
					</p><ul><?php
					foreach($checked as $id){
						$model=new kcmModel($id);
						$model->load();
						$dtfmt=CmsHost::cms()->config_get('date-format');
						?><li><?php
							echo '#'.$model->id.'&nbsp;&nbsp;[';
							echo date($dtfmt=='' ? 'd M Y' : $dtfmt,$model->created).']&nbsp;&nbsp;-&nbsp;&nbsp;';
							echo $model->name=='' ? '<i>unknown model</i>' : Security::snohtml($model->name);
							echo ' ('.($model->serial=='' ? '<i>no model number</i>' : Security::snohtml($model->serial)).')';
						?></li><?php
					}
					?></ul><p>
						Are you really sure you want this?
					</p><?php
					CmsHost::cms()->popup_button('Yes','do_delete','critical');
					CmsHost::cms()->popup_button('Cancel','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'do_delete':
					CmsHost::cms()->popup_begin('Delete Model','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Deleting requested item(s):</p><ul><?php
					foreach($checked as $id){
						$model=new kcmModel($id);
						?><li><?php
							echo 'Deleting item #'.$model->id.': ';
							echo ($model->delete() ? 'done' : 'failed').'.';
						?></li><?php
					}
					?></ul><?php
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'archive':
					CmsHost::cms()->popup_begin('Archiving Model(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Archiving requested item(s):</p><ul><?php
					foreach($checked as $id){
						$model=new kcmModel($id); $model->load();
						?><li><?php
							echo 'Archiving item #'.$model->id.': ';
							echo (kcmArchives::archive($model,'Model '.Security::snohtml($model->name).' ('.$model->serial.').')
								? 'done' : 'failed').'.';
						?></li><?php
					}
					?></ul><?php
					CmsHost::cms()->popup_button('Close','refresh','button');
					CmsHost::cms()->popup_end();
					break;
			}
			die;
		}
		public static function cells($id,$row,$colid,$cell){
			$dtfmt=CmsHost::cms()->config_get('date-format');
			if($colid=='name')return CmsHost::fire_action($id,Security::snohtml($row->name),'edit');
			if($colid=='created')return date($dtfmt=='' ? 'd M Y' : $dtfmt,$cell);
			if($colid=='type'){
				$type=new kcmModelType($cell);
				$type->load();
				return Security::snohtml($type->name);
			}
			if($colid=='fields'){
				$cell=implode(', ',array_keys($cell));
				return $cell=='' ? '<i>(none)</i>' : Security::snohtml($cell);
			}
			if($colid=='soldcount'){
				$sold=$row->sold();
				return $sold==0 ? '<i>(none)</i>' : ($sold==1 ? 'A unit' : $sold.' units');
			}
			return Security::snohtml($cell);
		}
	}
	// register ajax/api calls
	Ajax::register('kcmViewModels','actions',CmsHost::fsig_action());
	Ajax::register('kcmViewModels','_kcmMdlSerialExists',array('model'=>'integer','serial'=>'string'));

?>