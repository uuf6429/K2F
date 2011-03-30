<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php');

	class kmapsViewMaps {
		public static function manage(){
		}
		public static function actions($table,$action,$checked){
		}
		public static function cells($id,$row,$colid,$cell){
		}
	}
	// register ajax/api calls
	Ajax::register('kmapsViewMaps','actions',CmsHost::fsig_action());

?>