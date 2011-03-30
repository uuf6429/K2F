<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php','core/ajax.php');

	class kcmViewDocuments {
		public static function fieldsPopup(){
			CmsHost::cms()->wysiwyg_popup_begin('html','Insert Placemarks');
			?><select style="width:100%" size="10" id="placemarks"><?php
			foreach(kcmGetFieldDescs() as $fld)echo '<option value="'.Security::snohtml($fld).'">'.Security::snohtml($fld).'</option>';
			?></select><?php
			CmsHost::cms()->wysiwyg_popup_button('Close','k2f_wysiwyg.close("html",window)');
			CmsHost::cms()->wysiwyg_popup_button('Insert','doInsertPlacemark(document.getElementById("placemarks").value,window)');
			CmsHost::cms()->wysiwyg_popup_end();
			die;
		}
		public static function manage(){
			?><script type="text/javascript">
				function kcmShowFieldsList(){
					k2f_wysiwyg.open('html',location.href+'<?php echo Ajax::url(__CLASS__,'fieldsPopup','&'); ?>',280,206);
				}
				function doInsertPlacemark(value,wnd){
					k2f_wysiwyg.insert("html",'{'+value+'}');
					k2f_wysiwyg.close("html",wnd);
				}
			</script><?php
			CmsHost::cms()->adminlist_begin(KeenCPM::icons('documents'),'Manage Templates',array('allowadd','nopopup:add'),array(),ClassMethod(__CLASS__,'actions'));
			$docs=new kcmDocuments();
			$docs->load();
			CmsHost::cms()->adminlist(
				$docs->rows,'id',
				array('name'=>'Name','type'=>'Type','created'=>'Creation Date','updated'=>'Last Updated'),
				array('multiselect','allowadd','nopopup:add','nopopup:edit'),
				array('Edit','Duplicate','Archive','Delete'),
				ClassMethod(__CLASS__,'cells'),
				'No document templates found',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
		}
		public static function actions($table,$action,$checked){
			switch($action){
				case 'new': case 'edit':
					$doc=new kcmDocument(count($checked)==0 ? 0 : (int)$checked[0]); $doc->load();
					CmsHost::cms()->popup_begin(($doc->id>0?'Update':'Add').' Template','',380,180,ClassMethod(__CLASS__,'actions'));
					?><input type="hidden" name="id" value="<?php echo $doc->id; ?>"/><p>
						<span style="width:120px;display:inline-block;">Template Name:</span>
						<input id="name" name="name" value="<?php echo $doc->name; ?>" style="vertical-align:middle;width:280px;margin-right:48px;">
						<span style="width:48px;display:inline-block;">Type:</span>
						<select id="type" name="type" style="vertical-align:middle;width:144px;"><?php
							echo '<option value="'.kcmDocuments::TYPE_GUARANTEE.'"'.($doc->type==kcmDocuments::TYPE_GUARANTEE?' selected':'').'>Guarantee</option>';
							echo '<option value="'.kcmDocuments::TYPE_QUOTATION.'"'.($doc->type==kcmDocuments::TYPE_QUOTATION?' selected':'').'>Quotation</option>';
							echo '<option value="'.kcmDocuments::TYPE_SERVICE  .'"'.($doc->type==kcmDocuments::TYPE_SERVICE  ?' selected':'').'>Service Letter</option>';
						?></select>
					</p><?php
					CmsHost::cms()->wysiwyg('html',$doc->html,900,600);
					CmsHost::cms()->wysiwyg_separator('html',1);
					CmsHost::cms()->wysiwyg_button('html',KeenCPM::url().'img/fn-btn.gif','Insert Placemark',1,'kcmShowFieldsList');
					CmsHost::cms()->popup_button(($doc->id>0?'Update':'Add').' Template','do_save','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'do_save':
					$doc=new kcmDocument((int)$_REQUEST['id']); $doc->load();
					CmsHost::cms()->popup_begin(($doc->id>0?'Edit':'Add').' Template','',380,180,ClassMethod(__CLASS__,'actions'));
					if($doc->id<1)$doc->created=time();
					$doc->updated=time();
					$doc->name=$_REQUEST['name'];
					$doc->type=(int)$_REQUEST['type'];
					$doc->html=$_REQUEST['html'];
					if($doc->save()){
						if((int)$_REQUEST['id']>0){
							?><p>The document template has been updated!</p><?php
						}else{
							?><p>The new template has been added!</p><?php
						}
					}else{
						?><p>Fatal: Could not save changes to database.</p><?php
					}
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'duplicate':
					CmsHost::cms()->popup_begin('Duplicating Template(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Copying requested item(s):</p><ul><?php
					foreach($checked as $id){
						$doc=new kcmDocument($id);
						$doc->load(); 
						?><li><?php
							echo 'Copying item "'.Security::snohtml($doc->name).'" (#'.$doc->id.'): ';
							$doc->id=0; $doc->name='copy of '.$doc->name;
							echo ($doc->save() ? 'done' : 'failed').'.';
						?></li><?php
					}
					?></ul><?php
					CmsHost::cms()->popup_button('Close','refresh','button');
					CmsHost::cms()->popup_end();
					break;
				case 'delete':
					CmsHost::cms()->popup_begin('Delete Template(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>
						Warning! You are about to delete the following items <b>permanently</b>.
					</p><ul><?php
					foreach($checked as $id){
						$doc=new kcmDocument($id);
						$doc->load();
						$dtfmt=CmsHost::cms()->config_get('date-format');
						?><li><?php
							echo '#'.$doc->id.'&nbsp;&nbsp;[';
							echo date($dtfmt=='' ? 'd M Y' : $dtfmt,$doc->created).' - '.date($dtfmt=='' ? 'd M Y' : $dtfmt,$doc->updated);
							echo ']&nbsp;&nbsp;-&nbsp;&nbsp;';
							echo $doc->name=='' ? '<i>untitled</i>' : Security::snohtml($doc->name);
							echo ' ('.($doc->type=='' ? '<i>unknown type</i>' : Security::snohtml($doc->type)).')';
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
					CmsHost::cms()->popup_begin('Delete Template(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Deleting requested item(s):</p><ul><?php
					foreach($checked as $id){
						$doc=new kcmDocument($id);
						?><li><?php
							echo 'Deleting item #'.$doc->id.': ';
							echo ($doc->delete() ? 'done' : 'failed').'.';
						?></li><?php
					}
					?></ul><?php
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'archive':
					CmsHost::cms()->popup_begin('Archiving Template(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Archiving requested item(s):</p><ul><?php
					foreach($checked as $id){
						$doc=new kcmDocument($id); $doc->load();
						?><li><?php
							echo 'Archiving item #'.$doc->id.': ';
							echo (kcmArchives::archive($doc,'Document template "'.Security::snohtml($doc->name).'" (#'.$doc->id.').')
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
			if($colid=='name')return CmsHost::fire_action($id,$row->name==''?'<i>Untitled</i>':Security::snohtml($row->name),'edit');
			if($colid=='created')return date($dtfmt=='' ? 'd M Y' : $dtfmt,(int)$cell);
			if($colid=='updated')return date($dtfmt=='' ? 'd M Y' : $dtfmt,(int)$cell);
			if($colid=='type')
				switch((int)$cell){
					case kcmDocuments::TYPE_GUARANTEE: return 'Guarantee';
					case kcmDocuments::TYPE_QUOTATION: return 'Quotation';
					case kcmDocuments::TYPE_SERVICE:   return 'Service';
					default: return '(<i>unknown</i>)';
				}
			return Security::snohtml($cell);
		}
	}
	// register ajax/api calls
	Ajax::register('kcmViewDocuments','actions',CmsHost::fsig_action());
	Ajax::register('kcmViewDocuments','fieldsPopup');

?>