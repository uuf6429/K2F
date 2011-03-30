<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php');
	
	class kmapsViewCategories {
		public static function view(){
			CmsHost::cms()->adminlist_begin(KeenMaps::icons('categories'),'Manage Categories','allowadd',array(),ClassMethod(__CLASS__,'actions'));
			$cats=new kmCategories();
			$cats->load();
			CmsHost::cms()->adminlist(
				$cats->rows,'id',
				array('id'=>'','icon'=>'','published'=>'Published','ticked'=>'Ticked','name'=>'Category Name','description'=>'Description','fields'=>'Fields'),
				array('multiselect','allowadd'),
				array('Edit','Publish','Unpublish'),
				ClassMethod(__CLASS__,'cells'),
				'No categories yet',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
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
				function kmUpdateIco(){
					document.getElementById('km-icon-img').src=document.getElementById('km-icon').value;
					if(document.getElementById('km-icon').value=='')kmBadIco();
				}
				function kmBadIco(){
					document.getElementById('km-icon-img').src=<?php echo @json_encode(KeenMaps::url().'img/fnf16.png'); ?>;
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
					$cat=new kmCategory(count($checked)==0 ? 0 : (int)$checked[0]); $cat->load();
					CmsHost::cms()->popup_begin(($cat->id>0?'Update':'Add').' Category','',380,180,ClassMethod(__CLASS__,'actions'));
					?><input type="hidden" name="id" value="<?php echo $cat->id; ?>"/><p>
						<span style="width:180px;display:inline-block;">Enter Category Name:</span>
						<input type="text" id="km-name" name="name" value="<?php echo Security::snohtml($cat->name); ?>" style="vertical-align:middle;width:300px;"/>
					</p><p>
						<span style="width:180px;display:inline-block;">Enter Category Icon:</span>
						<input type="text" id="km-icon" name="icon" value="<?php echo Security::snohtml($cat->icon); ?>" style="vertical-align:middle;width:270px;" onkeyup="kmUpdateIco();" onclick="kmUpdateIco();" onchange="kmUpdateIco();"/>
						<img src="<?php echo Security::snohtml($cat->icon=='' ? KeenMaps::url().'img/fnf16.png' : $cat->icon); ?>" alt="" height="16" onerror="kmBadIco();" id="km-icon-img" style="vertical-align:middle; margin-left:2px;"/>
					</p><p>
						<label for="km-published" style="display:inline-block; width:200px; margin-right:100px;">
							<input type="checkbox" id="km-published" name="published"<?php if($cat->published)echo ' checked="checked"'; ?>/> Publish Category
						</label>
						<label for="km-ticked" style="display:inline-block; width:200px;">
							<input type="checkbox" id="km-ticked" name="ticked"<?php if($cat->ticked)echo ' checked="checked"'; ?>/> Ticked by Default
						</label>
					</p>&nbsp;<div>
						Short Category Description:<?php
						CmsHost::cms()->wysiwyg('description',$cat->description,630,260);
					?></div>&nbsp;<table>
						<tr>
							<td valign="top" width="180">Add (Optional) Fields:</td>
							<td>
								<div id="kmmt<?php echo $cat->id; ?>"><?php
									if(!count($cat->fields))echo self::_kmFldAdd();
									foreach($cat->fields as $n=>$v)echo self::_kmFldAdd($n,$v);
								?></div><div>
									<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Names</small>
									<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Values</small>
								</div>&nbsp;<br/>
								<input type="button" value="Add Field" onclick="jQuery('#kmmt<?php echo $cat->id; ?>').append(<?php
									echo Security::snohtml(@json_encode(self::_kmFldAdd())); ?>); kmFieldsCheck();"/>
								<small id="kmFldNotUniqueWarn" style="display:none;color:red;">Error: Field names must be unique.</small>
							</td>
						</tr>
					</table><br/><?php
					CmsHost::cms()->popup_button(($cat->id>0?'Update':'Add').' Category','do_save','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'do_save':
					$cat=new kmCategory((int)$_REQUEST['id']); $cat->load();
					CmsHost::cms()->popup_begin(($type->id>0?'Update':'Add').' Category','',380,180,ClassMethod(__CLASS__,'actions'));
					$cat->name=$_REQUEST['name'];
					$cat->icon=$_REQUEST['icon'];
					$cat->published=isset($_REQUEST['published']);
					$cat->ticked=isset($_REQUEST['ticked']);
					$cat->description=$_REQUEST['description'];
					$cat->fields=array();
					if(isset($_REQUEST['fields']))
						foreach($_REQUEST['fields'] as $i=>$f)
							if($f!='')$cat->field($f,$_REQUEST['values'][$i]);
					if($cat->save()){
						if((int)$_REQUEST['id']>0){
							?><p>The category has been updated!</p><?php
						}else{
							?><p>The new category has been added!</p><?php
						}
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
			if($colid=='icon')
				return ($cell!='' ? '<img src="'.Security::snohtml($cell).'" alt="" width="16"/>' : '');
			if($colid=='published')
				return '<a href="javascript:;" onclick="" style="margin-left:24px;"><img src="'.KeenMaps::url().'img/'.($cell ? 'ena' : 'dis').'16.png" alt="" width="16" height="16"/></a>';
			if($colid=='ticked')
				return '<a href="javascript:;" onclick="" style="margin-left:14px;"><img src="'.KeenMaps::url().'img/'.($cell ? 'ena' : 'dis').'16.png" alt="" width="16" height="16"/></a>';
			if($colid=='name')
				return CmsHost::fire_action($id,Security::snohtml($cell),'edit');
			if($colid=='description'){
				$cell=strip_tags($cell);
				$cell=strlen($cell)>50 ? substr($cell,0,50).'...'  : $cell;
				if($cell=='')$cell='<i>None</i>';
				return $cell;
			}
			if($colid=='fields'){
				return count($cell) ? Security::snohtml(implode(', ',array_keys($cell))) : '<i>(none)</i>';
			}
			return Security::snohtml(''.$cell);
		}
	}
	// register ajax/api calls
	Ajax::register('kmapsViewCategories','actions',CmsHost::fsig_action());
	
?>