<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php','core/ajax.php');

	class kcmViewStock {
		public static function manage(){
			CmsHost::cms()->adminlist_begin(KeenCPM::icons('stock'),'Manage Stock','allowadd',array(),ClassMethod(__CLASS__,'actions'));
			$stock=new kcmStocks();
			$stock->load('1 ORDER BY date DESC');
			CmsHost::cms()->adminlist(
				$stock->rows,'id',
				array('model'=>'Model Name & Number','date'=>'Date Added','amount'=>'Amount'),
				array('multiselect','allowadd'),
				array('Edit','Archive','Delete'),
				ClassMethod(__CLASS__,'cells'),
				'No existing stock found',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
		}
		public static function actions($table,$action,$checked){
			switch($action){
				case 'new': case 'edit':
					$stock=new kcmStock(count($checked)==0 ? 0 : (int)$checked[0]); $stock->load();
					$hint=($stock->id>0?'':'After adding stock you can cancel it later on if you want to.');
					CmsHost::cms()->popup_begin(($stock->id>0?'Update':'Add').' Stock',$hint,380,180,ClassMethod(__CLASS__,'actions'));
					?><input type="hidden" name="id" value="<?php echo $stock->id; ?>"/><p>
						<span style="width:200px;display:inline-block;">Select Model Type:</span>
						<select id="model" name="model" style="vertical-align:middle;width:144px;"><?php
							$models=new kcmModels(); $models->load(); $model=new kcmModel();
							foreach($models->rows as $model)
								echo '<option value="'.(int)$model->id.'"'.($stock->model==$model->id?' selected':'').'>'
									.Security::snohtml($model->serial.' - '.$model->name).'</option>';
						?></select>
					</p><p>
						<span style="width:200px;display:inline-block;">Amount of Units:</span>
						<input id="amount" name="amount" value="<?php echo $stock->amount; ?>" style="vertical-align:middle;width:144px;">
					</p><?php
					CmsHost::cms()->popup_button(($stock->id>0?'Update':'Add').' Stock','do_save','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'do_save':
					$stock=new kcmStock((int)$_REQUEST['id']); $stock->load();
					CmsHost::cms()->popup_begin(($stock->id>0?'Edit':'Add').' Stock','',380,180,ClassMethod(__CLASS__,'actions'));
					if($stock->id<1)$stock->date=time();
					$stock->amount=(int)$_REQUEST['amount'];
					$stock->model=(int)$_REQUEST['model'];
					if($stock->save()){
						if((int)$_REQUEST['id']>0){
							?><p>The stock has been updated!</p><?php
						}else{
							?><p>The new stock has been added!</p><?php
						}
					}else{
						?><p>Fatal: Could not save changes to database.</p><?php
					}
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'delete':
					CmsHost::cms()->popup_begin('Delete Stock','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>
						Warning! You are about to delete the following items <b>permanently</b>.
					</p><ul><?php
					foreach($checked as $id){
						$stock=new kcmStock($id);
						$stock->load();
						$model=new kcmModel($stock->model);
						$model->load();
						$dtfmt=CmsHost::cms()->config_get('date-format');
						?><li><?php
							echo '#'.$stock->id.'&nbsp;&nbsp;[';
							echo date($dtfmt=='' ? 'd M Y' : $dtfmt,$stock->date).']&nbsp;&nbsp;-&nbsp;&nbsp;';
							echo $stock->amount==1 ? 'A single item of ' : $stock->amount.' items of ';
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
					CmsHost::cms()->popup_begin('Delete Stock','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Deleting requested item(s):</p><ul><?php
					foreach($checked as $id){
						$stock=new kcmStock($id);
						?><li><?php
							echo 'Deleting item #'.$stock->id.': ';
							echo ($stock->delete() ? 'done' : 'failed').'.';
						?></li><?php
					}
					?></ul><?php
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'archive':
					CmsHost::cms()->popup_begin('Archiving Stock','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Archiving requested item(s):</p><ul><?php
					foreach($checked as $id){
						$stock=new kcmStock($id); $stock->load();
						?><li><?php
							echo 'Archiving item #'.$stock->id.': ';
							echo (kcmArchives::archive($stock,'Stock #'.$stock->id.', '.(int)$stock->amount
									.' unit(s) of '.Security::snohtml($stock->model()->name)
									.' ('.Security::snohtml($stock->model()->serial).').')
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
			if($colid=='model'){
				$model=new kcmModel((int)$cell);
				$model->load();
				return CmsHost::fire_action($id,Security::snohtml($model->name).' ('.Security::snohtml($model->serial).')','edit');
			}
			if($colid=='date')return date($dtfmt=='' ? 'd M Y' : $dtfmt,$cell);
			if($colid=='amount')return $cell==0 ? 'No units' : ($cell==1 ? 'A unit' : (int)$cell.' units');
			return Security::snohtml($cell);
		}
	}
	// register ajax/api calls
	Ajax::register('kcmViewStock','actions',CmsHost::fsig_action());

?>