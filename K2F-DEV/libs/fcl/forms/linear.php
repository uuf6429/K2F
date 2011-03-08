<?php defined('K2FORMS') or die;

	fcl_uses('forms/base.php');

	class Form_Linear extends Form_Base {
		/**
		 * Renders this form's content directly to output.
		 */
		public function render($action='',$mode='submit',$method='post'){
			$name=Security::snohtml($this->name);
			?><form action="" method="post" name="<?php echo $name; ?>" id="<?php echo $name; ?>"><?php
				foreach($this->data as $item){
					?><div><?php
					if(is_object($item)){ $item->data_editor(); }else{ echo ''.$item; }
					?></div><?php
				}
			?></form><?php
		}
	}

?>