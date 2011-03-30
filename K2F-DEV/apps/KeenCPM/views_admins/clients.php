<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php','core/ajax.php');

	class kcmViewClients {
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
						jQuery(this).css('border-color',n[s]>1 ? 'red' : '');
						if(n[s]>1)w=true;
					});
					w ? jQuery('#kcmFldNotUniqueWarn').show() : jQuery('#kcmFldNotUniqueWarn').hide();
				}
				function kcmClientNameChanged(id){
					var data={"id": id, "name": jQuery('#name').val(), "surname": jQuery('#surname').val() };
					jQuery.getJSON(<?php echo @json_encode(Ajax::url(__CLASS__,'_kcmCheckUniqueClientName')); ?>,data,function(notok){
						jQuery('#name').css('border-color',!notok ? '#E0BB20' : null);
						jQuery('#surname').css('border-color',!notok ? '#E0BB20' : null);
						jQuery('#kcmClnNotUniqueWarn').css('color','#E0BB20');
						notok ? jQuery('#kcmClnNotUniqueWarn').hide() : jQuery('#kcmClnNotUniqueWarn').show();
					})
				}
			</script><?php
			CmsHost::cms()->adminlist_begin(KeenCPM::icons('clients'),'Manage Clients','allowadd',array(),ClassMethod(__CLASS__,'actions'));
			$clients=new kcmClients();
			$clients->load('1 ORDER BY created DESC');
			CmsHost::cms()->adminlist(
				$clients->rows,'id',
				array('pin'=>'PIN','name+surname'=>'Name','address+town+country'=>'Address','landline+mobile+email'=>'Contact','notes'=>'Notes'),
				array('multiselect','allowadd'),
				array('Edit','Archive','Delete'),
				ClassMethod(__CLASS__,'cells'),
				'No current clients found',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
		}
		/**
		 * Checks whether a name+surname combination exists or not.
		 * @param integer $id Id of the current row, in order to not check itself!
		 * @param string $name Client name.
		 * @param string $surname Client surname.
		 * @return boolean True if the name+surname is unique, false otherwise.
		 */
		public static function _kcmCheckUniqueClientName($id,$name,$surname){
			$clients=new kcmClients();
			$clients->load('`id`!='.(int)$id.' AND `name`="'.Security::escape($name).'" AND `surname`="'.Security::escape($surname).'"');
			return $clients->count()==0;
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
					$client=new kcmClient(count($checked)==0 ? 0 : (int)$checked[0]); $client->load();
					CmsHost::cms()->popup_begin(($client->id>0?'Update':'Add').' Client','',380,180,ClassMethod(__CLASS__,'actions'));
					if($client->country=='' && ($client->country=CmsHost::cms()->config_get('country'))=='')$client->country='Malta';
					?><input type="hidden" name="id" value="<?php echo $client->id; ?>"/><p>
						<span style="width:112px;display:inline-block;">Client's Name:</span>
						<input id="name" type="text" name="name" value="<?php echo Security::snohtml($client->name); ?>" style="vertical-align:middle;width:144px;margin-right:48px;" onkeyup="kcmClientNameChanged(<?php echo (int)$client->id; ?>);">
						<span style="width:112px;display:inline-block;">And Surname:</span>
						<input id="surname" type="text" name="surname" value="<?php echo Security::snohtml($client->surname); ?>" style="vertical-align:middle;width:144px;" onkeyup="kcmClientNameChanged(<?php echo (int)$client->id; ?>);">
						<small id="kcmClnNotUniqueWarn" style="display:none;">Warning: The same name and surname are already in use.</small>
					</p><hr style="border: none; border-bottom: 1px solid #DFDFDF;"/><p>
						<span style="width:112px;display:inline-block;">Street Address:</span>
						<input id="address" type="text" name="address" value="<?php echo Security::snohtml($client->address); ?>" style="vertical-align:middle;width:456px;">
					</p><p>
						<span style="width:112px;display:inline-block;">Town:</span>
						<input id="town" type="text" name="town" value="<?php echo Security::snohtml($client->town); ?>" style="vertical-align:middle;width:144px;margin-right:48px;">
						<span style="width:112px;display:inline-block;">Country:</span>
						<select id="country" name="country" style="vertical-align:middle;width:144px;"><?php
							foreach($GLOBALS['KCM_COUNTRIES'] as $country){
								?><option value="<?php echo Security::snohtml($country); ?>"<?php if($country==$client->country)echo 'selected'; ?>><?php
									echo Security::snohtml($country);
								?></option><?php
							}
						?></select>
					</p><hr style="border: none; border-bottom: 1px solid #DFDFDF;"/><p>
						<span style="width:112px;display:inline-block;">Email Address:</span>
						<input id="email" type="text" name="email" value="<?php echo Security::snohtml($client->email); ?>" style="vertical-align:middle;width:312px;">
					</p><p>
						<span style="width:112px;display:inline-block;">Telephone:</span>
						<input id="landline" type="text" name="landline" value="<?php echo Security::snohtml($client->landline); ?>" style="vertical-align:middle;width:144px;margin-right:48px;">
						<span style="width:112px;display:inline-block;">Mobile Phone:</span>
						<input id="mobile" type="text" name="mobile" value="<?php echo Security::snohtml($client->mobile); ?>" style="vertical-align:middle;width:144px;">
					</p><hr style="border: none; border-bottom: 1px solid #DFDFDF;"/><p>
						<span style="width:112px;display:inline-block;">Additional Notes:</span>
						<textarea id="notes" name="notes" style="width:456px;height:80px;vertical-align:top;" cols="" rows=""><?php echo Security::snohtml($client->notes); ?></textarea>
					</p><hr style="border: none; border-bottom: 1px solid #DFDFDF;"/><table>
						<tr>
							<td valign="top" width="180">Add (Optional) Fields:</td>
							<td>
								<div id="kcmmt<?php echo $client->id; ?>"><?php
									if(!count($client->fields))echo self::_kcmCatFldAdd();
									foreach($client->fields as $n=>$v)echo self::_kcmCatFldAdd($n,$v);
								?></div><div>
									<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Names</small>
									<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Values</small>
								</div>&nbsp;<br/>
								<input type="button" value="Add Field" onclick="jQuery('#kcmmt<?php echo $client->id; ?>').append(<?php
									echo Security::snohtml(@json_encode(self::_kcmCatFldAdd())); ?>); kcmFieldsCheck();"/>
								<small id="kcmFldNotUniqueWarn" style="display:none;color:red;">Error: Field names must be unique.</small>
							</td>
						</tr>
					</table><?php
					CmsHost::cms()->popup_button(($client->id>0?'Update':'Add').' Client','do_save','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'do_save':
					$client=new kcmClient((int)$_REQUEST['id']); $client->load();
					CmsHost::cms()->popup_begin(($client->id>0?'Edit':'Add').' Client','',380,180,ClassMethod(__CLASS__,'actions'));
					if($client->id<1)$client->created=time();
					if($client->pin=='')$client->pin=Security::genToken('0-9,a-z,A-Z',4,true,99999999);
					$client->cookie=''; // security failsafe
					$client->address=$_REQUEST['address'];
					$client->country=$_REQUEST['country'];
					$client->landline=$_REQUEST['landline'];
					$client->mobile=$_REQUEST['mobile'];
					$client->name=$_REQUEST['name'];
					$client->notes=$_REQUEST['notes'];
					$client->surname=$_REQUEST['surname'];
					$client->town=$_REQUEST['town'];
					$client->email=$_REQUEST['email'];
					$client->fields=array();
					if(isset($_REQUEST['fields']))
						foreach($_REQUEST['fields'] as $i=>$f)
							if($f!='')$client->field($f,$_REQUEST['values'][$i]);
					if($client->save()){
						if((int)$_REQUEST['id']>0){
							?><p>Client's details have been updated!</p><?php
						}else{
							?><p>The new client has been added!</p><?php
						}
					}else{
						?><p>Fatal: Could not save changes to database.</p><?php
					}
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'delete':
					CmsHost::cms()->popup_begin('Delete Client(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>
						Warning! You are about to delete the following items <b>permanently</b>.
					</p><ul><?php
					foreach($checked as $id){
						$client=new kcmClient($id);
						$client->load();
						?><li><?php
							echo '#'.$client->id.'&nbsp;&nbsp;';
							echo Security::snohtml($client->name.' '.$client->surname);
							echo ' ('.Security::snohtml($client->town).')';
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
					CmsHost::cms()->popup_begin('Delete Client(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Deleting requested item(s):</p><ul><?php
					foreach($checked as $id){
						$client=new kcmClient($id);
						?><li><?php
							echo 'Deleting item #'.$client->id.': ';
							echo ($client->delete() ? 'done' : 'failed').'.';
						?></li><?php
					}
					?></ul><?php
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'archive':
					CmsHost::cms()->popup_begin('Archiving User(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Archiving requested item(s):</p><ul><?php
					foreach($checked as $id){
						$client=new kcmClient($id); $client->load();
						?><li><?php
							echo 'Archiving item #'.$client->id.': ';
							echo (kcmArchives::archive($client,'Client account "'.Security::snohtml($client->name).'" (#'.$client->id.').')
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
			if($colid=='pin')return $id.$cell;
			if($colid=='name+surname')return CmsHost::fire_action($id,Security::snohtml($row->name.' '.$row->surname),'edit');
			if($colid=='address+town+country')return Security::snohtml(implode(', ',array_filled($row->address,$row->town,$row->country)));
			if($colid=='landline+mobile+email')return Security::snohtml(trim($row->landline.' / '.$row->mobile,' /'))
				.($row->email=='' ? ' <i>(no email address)</i>'
					: ' (<a href="mailto:'.Security::snohtml($row->email).'">'.Security::snohtml($row->email).'</a>)');
			if($colid=='notes')return $cell=='' ? '<i>(no notes)</i>' : '<div style="min-width:200px;">'.Security::snohtml($cell).'</div>';
			return Security::snohtml($cell);
		}
	}
	// register ajax/api calls
	Ajax::register('kcmViewClients','actions',CmsHost::fsig_action());
	Ajax::register('kcmViewClients','_kcmCheckUniqueClientName',array('id'=>'integer','name'=>'string','surname'=>'string'));

?>