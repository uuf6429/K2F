<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php');

	class kcmViewCategories {
		public static function manage(){
			?><script type="text/javascript">
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
						jQuery(this).css('border-color',n[s]>1 ? 'red' :'');
						if(n[s]>1)w=true;
					});
					w ? jQuery('#kcmFldNotUniqueWarn').show() : jQuery('#kcmFldNotUniqueWarn').hide();
				}
			</script><?php
			CmsHost::cms()->adminlist_begin(KeenCPM::icons('categories'),'Manage Product Categories','allowadd',array(),ClassMethod(__CLASS__,'actions'));
			$types=new kcmModelTypes();
			$types->load();
			CmsHost::cms()->adminlist(
				$types->rows,'id',
				array('name'=>'Name','fields'=>'Fields','modelcount'=>'Number of Models','soldcount'=>'Total Sold'),
				array('multiselect','allowadd'),
				array('Edit','Archive','Delete'),
				ClassMethod(__CLASS__,'cells'),
				'No categories yet',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
		}
		public static function _kcmCatFldAdd($name='',$value=''){
			static $counter=0; $counter++;
			return '<div id="kcmCatFldAdd'.$counter.'">'
					.'<input type="text" name="fields[]" value="'.Security::snohtml($name).'" style="width:148px;" onkeyup="kcmFieldsCheck();"/>'
					.'<input type="text" name="values[]" value="'.Security::snohtml($value).'" style="width:148px;" onkeyup="kcmFieldsCheck();"/>'
					.'<a href="javascript:;" onclick="jQuery(\'#kcmCatFldAdd'.$counter.'\').remove();kcmFieldsCheck();">'
						.'<small>delete</small></a>'
				.'</div>';
		}
		public static function actions($table,$action,$checked){
			switch($action){
				case 'new': case 'edit':
					$type=new kcmModelType(count($checked)==0 ? 0 : (int)$checked[0]); $type->load();
					CmsHost::cms()->popup_begin(($type->id>0?'Update':'Add').' Category','',380,180,ClassMethod(__CLASS__,'actions'));
					?><input type="hidden" name="id" value="<?php echo $type->id; ?>"/><p>
						<span style="width:180px;display:inline-block;">Enter Category Name:</span>
						<input type="text" id="name" name="name" value="<?php echo Security::snohtml($type->name); ?>" style="vertical-align:middle;width:300px;"/>
					</p>
					<table>
						<tr>
							<td valign="top" width="180">Add (Optional) Fields:</td>
							<td>
								<div id="kcmmt<?php echo $type->id; ?>"><?php
									if(!count($type->fields))echo self::_kcmCatFldAdd();
									foreach($type->fields as $n=>$v)echo self::_kcmCatFldAdd($n,$v);
								?></div><div>
									<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Names</small>
									<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Values</small>
								</div>&nbsp;<br/>
								<input type="button" value="Add Field" onclick="jQuery('#kcmmt<?php echo $type->id; ?>').append(<?php
									echo Security::snohtml(@json_encode(self::_kcmCatFldAdd())); ?>); kcmFieldsCheck();"/>
								<small id="kcmFldNotUniqueWarn" style="display:none;color:red;">Error: Field names must be unique.</small>
							</td>
						</tr>
					</table><?php
					CmsHost::cms()->popup_button(($type->id>0?'Update':'Add').' Category','do_save','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'do_save':
					$type=new kcmModelType((int)$_REQUEST['id']); $type->load();
					CmsHost::cms()->popup_begin(($type->id>0?'Update':'Add').' Category','',380,180,ClassMethod(__CLASS__,'actions'));
					$type->name=$_REQUEST['name'];
					$type->fields=array();
					if(isset($_REQUEST['fields']))
						foreach($_REQUEST['fields'] as $i=>$f)
							if($f!='')$type->field($f,$_REQUEST['values'][$i]);
					if($type->save()){
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
				case 'delete':
					CmsHost::cms()->popup_begin('Delete Category','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>
						Warning! You are about to delete the following items <b>permanently</b>.
					</p><ul><?php
					foreach($checked as $id){
						$type=new kcmModelType($id);
						$type->load();
						$dtfmt=CmsHost::cms()->config_get('date-format');
						?><li><?php
							echo '#'.$type->id.'&nbsp;&nbsp;-&nbsp;&nbsp;'.
								($type->name=='' ? '<i>unknown category</i>' : Security::snohtml($type->name));
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
					CmsHost::cms()->popup_begin('Delete Categorey','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Deleting requested item(s):</p><ul><?php
					foreach($checked as $id){
						$type=new kcmModelType($id);
						?><li><?php
							echo 'Deleting item #'.$type->id.': ';
							echo ($type->delete() ? 'done' : 'failed').'.';
						?></li><?php
					}
					?></ul><?php
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'archive':
					CmsHost::cms()->popup_begin('Archiving Categories','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Archiving requested item(s):</p><ul><?php
					foreach($checked as $id){
						$type=new kcmModelType($id); $type->load();
						?><li><?php
							echo 'Archiving item #'.$type->id.': ';
							echo (kcmArchives::archive($type,'Model category "'.Security::snohtml($type->name).'" (#'.$type->id.').')
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
			if($colid=='name')return CmsHost::fire_action($id,Security::snohtml($cell),'edit');
			if($colid=='fields'){
				return count($cell) ? Security::snohtml(implode(', ',array_keys($cell))) : '<i>(none)</i>';
			}
			if($colid=='modelcount'){
				$models=$row->models();
				return ($models==1 ? '1 model' : $models.' models');
			}
			if($colid=='soldcount'){
				$models=new kcmModels();
				$models->load('`type`='.$row->id);
				$sold=0; $model=new kcmModel();
				foreach($models->rows as $model)
					$sold+=$model->sold();
				return $sold==0 ? '<i>(none)</i>' : ($sold==1 ? 'A unit' : $sold.' units');
			}
			return Security::snohtml($cell);
		}
	}
	// register ajax/api calls
	Ajax::register('kcmViewCategories','actions',CmsHost::fsig_action());

?>