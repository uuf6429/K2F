<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php');

	class kmapsViewSettings {
		public static function view(){
			// update changes
			if(isset($_REQUEST['save'])){
				CmsHost::cms()->config_set('km-marker-url',$_REQUEST['km-marker-url']);
				$tok=strtok($_REQUEST['km-marker-types'],CRLF);
				$_REQUEST['km-marker-types']=array();
				while($tok!==false){
					$_REQUEST['km-marker-types'][]=$tok;
					$tok=strtok(CRLF);
				}
				foreach($_REQUEST['km-marker-types'] as $i=>$t)
					if(trim($t)=='')
						unset($_REQUEST['km-marker-types'][$i]);
				$_REQUEST['km-marker-types']=implode(CRLF,array_unique($_REQUEST['km-marker-types']));
				CmsHost::cms()->config_set('km-marker-types',$_REQUEST['km-marker-types']);
			}
			// get variables
			$murl=CmsHost::cms()->config_get('km-marker-url');
			$smrk=CmsHost::cms()->config_get('km-marker-types');
			if($murl=='')$murl='/places/%category%/%place%/';
			// show form
			CmsHost::cms()->adminlist_begin(KeenMaps::icons('settings'),'Manage KeenMaps Settings');
			?><h3>General Settings</h3>
			<p>
				<span style="width:150px;display:inline-block;">Marker Permalink URL:</span>
				<input type="text" name="km-marker-url" value="<?php echo Security::snohtml($murl); ?>" style="width:360px;"/><br/>
				<?php if(!CmsHost::cms()->rewrite_enabled()){ ?>
					<small style="color:red;">You need to enable permalinks for this setting to work!</small><br/>
				<?php } ?>
				<small>
					URL must at least contain <code>%place%</code>.
					Optionally, you can also use <code>%category%</code> as well as any base names.
				</small>
			</p><p>
				<span style="width:150px;display:inline-block;height:120px;">Special Marker Types:</span>
				<textarea cols="0" rows="0" name="km-marker-types" style="width:360px;height:120px;vertical-align:top;"><?php
					echo Security::snohtml($smrk);
				?></textarea><br/>
				<small style="margin-left:158px;">
					Each line of text is a separate marker type.
				</small>
			</p><p>
				<input name="save" type="submit" class="button-primary" value="Update"/>
			</p><?php
			CmsHost::cms()->adminlist_end();
		}
	}
	
?>