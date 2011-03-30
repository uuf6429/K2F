<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php','core/ajax.php');

	class kssManageSettings {
		public static function view(){
			// update changes
			if(isset($_REQUEST['save'])){
				CmsHost::cms()->config_set('ksimpleshop-currency',$_REQUEST['currency']);
				CmsHost::cms()->config_set('ksimpleshop-paypal-username',$_REQUEST['paypal_username']);
			}
			
			// get variables
			$currency=CmsHost::cms()->config_get('ksimpleshop-currency');
			$paypal_username=CmsHost::cms()->config_get('ksimpleshop-paypal-username');
			
			// show form
			CmsHost::cms()->adminlist_begin(KSimpleShop::icons('settings'),'KSimple Shop Settings',array(),array(),ClassMethod(__CLASS__,'view'));
			?><form action="" method="post">
				<h3>General Settings</h3>
				<p>
					<label>Currency:</label>
					<select name="currency">
						<option value="">- Select -</option>
						<?php foreach($GLOBALS['kssCurrencies'] as $item) {
							list($cur,$sym,$desc) = $item;
							?><option <?php if($currency == $cur) echo 'selected="selected"'; ?> value="<?php echo $cur; ?>"><?php echo $desc.' ('.$sym.')'; ?></option><?php 	
						} ?>
					</select>
				</p><p>
					<label>Paypal Username:</label>
					<input type="text" name="paypal_username" value="<?php echo Security::snohtml($paypal_username); ?>" />
					<small>e.g. test@test.com</small>
				</p><p>
					<input name="save" type="submit" class="button-primary" value="Update"/>
				</p>
			</form><?php
			CmsHost::cms()->adminlist_end();
		}
		
	}

?>