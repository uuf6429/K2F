<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php');
	
	class keSettingsView {
		public static function manage(){
			// update changes
			if(isset($_REQUEST['save'])){
				CmsHost::cms()->config_set('ke-event-url',$_REQUEST['ke-event-url']);
				CmsHost::cms()->config_set('ke-event-view',$_REQUEST['ke-event-view']);
			}
			// get variables
			$murl=CmsHost::cms()->config_get('ke-event-url');
			if($murl=='')$murl='/events/%venue%/%title%/';
			$view=CmsHost::cms()->config_get('ke-event-view');
			if($view=='')$view=CFG::get('ABS_WWW');
			// show form
			CmsHost::cms()->adminlist_begin(KeenEvents::icons('settings'),'Manage KeenEvents Settings');
			?><form action="" method="post">
				<h3>General Settings</h3>
				<p>
					<span style="width:150px;display:inline-block;">Event Permalink URL:</span>
					<input type="text" name="ke-event-url" value="<?php echo Security::snohtml($murl); ?>" style="width:360px;"/><br/>
					<?php if(!CmsHost::cms()->rewrite_enabled()){ ?>
						<small style="color:red;">You need to enable permalinks for this setting to work!</small>
					<?php } ?><br/>
					<small>URL must at least contain <code>%title%</code> or <code>%id%</code>.
						Optionally, you can also use <?php
							$vars=get_object_vars(new keEvent());
							unset($vars['id']); unset($vars['title']);
							foreach($vars as $name=>$unused)$vars[$name]='<code>%'.$name.'%</code>';
							$last=array_pop($vars);
							echo count($vars)==0 ? $last : implode(', ',$vars).' and '.$last;
						?>.</small>
				</p><p>
					<span style="width:150px;display:inline-block;">Event Theme File:</span>
					<input type="text" name="ke-event-view" value="<?php echo Security::snohtml($view); ?>" style="width:360px;"/><br/>
					<?php if(!CmsHost::cms()->rewrite_enabled()){ ?>
						<small style="color:red;">You need to enable permalinks for this setting to work!</small><br/>
					<?php } ?>
					<small>This theme file can make use of object <code>$event</code> (of class <code>kmeEvent</code>) to render the event.</small>
				</p><p>
					<input name="save" type="submit" class="button-primary" value="Update"/>
				</p>
			</form><?php
			CmsHost::cms()->adminlist_end();
		}
	}

?>