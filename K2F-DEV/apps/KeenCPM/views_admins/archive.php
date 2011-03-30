<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php');

	class kcmViewArchive {
		public static function manage(){
			CmsHost::cms()->adminlist_begin(KeenCPM::icons('archive'),'Manage Archived Data',array(),array(),ClassMethod(__CLASS__,'actions'));
			$archives=new kcmArchives();
			$archives->load();
			CmsHost::cms()->adminlist(
				$archives->rows,'id',
				array('date'=>'Date & Time','desc'=>'Description'),
				array('multiselect'),
				array('Restore','Delete'),
				ClassMethod(__CLASS__,'cells'),
				'No archived items yet',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
		}
		public static function actions($table,$action,$checked){
			switch($action){
				case 'restore':
					CmsHost::cms()->popup_begin('Restore Archive(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Restoring requested item(s):</p><ul><?php
					foreach($checked as $id){
						$archive=new kcmArchive($id);
						?><li><?php
							echo 'Restoring item #'.$archive->id.': ';
							echo ($archive->restore() ? 'done' : 'failed').'.';
						?></li><?php
					}
					?></ul><?php
					CmsHost::cms()->popup_button('Close','refresh','button');
					CmsHost::cms()->popup_end();
					break;
				case 'delete':
					CmsHost::cms()->popup_begin('Delete Archive(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>
						<font style="color:#E00;">Warning! You are about to delete the following items <b>permanently</b>.</font>
					</p><ul><?php
					foreach($checked as $id){
						$archive=new kcmArchive($id);
						$archive->load();
						$dtfmt=CmsHost::cms()->config_get('date-format');
						?><li><?php
							echo '#'.$archive->id.'&nbsp;&nbsp;[';
							echo date($dtfmt=='' ? 'd M Y' : $dtfmt,$archive->date).']&nbsp;&nbsp;-&nbsp;&nbsp;';
							echo $archive->desc=='' ? '<i>no description</i>' : Security::snohtml($archive->desc);
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
					CmsHost::cms()->popup_begin('Delete Archive(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Deleting requested item(s):</p><ul><?php
					foreach($checked as $id){
						$archive=new kcmArchive($id);
						?><li><?php
							echo 'Deleting item #'.$archive->id.': ';
							echo ($archive->delete() ? 'done' : 'failed').'.';
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
			if($colid=='date')return date($dtfmt,(int)$cell);
			return Security::snohtml($cell);
		}
	}
	// register ajax/api calls
	Ajax::register('kcmViewArchive','actions',CmsHost::fsig_action());

?>