<?php defined('K2F') or die;

	uses('core/cms.php');

	xlog('Error: Drupal currently conflicts with our class naming (eg; it has it\'s own Database class). We need to rewrite K2F to make use of more unique global names.');
	
	/**
	 * CMS host interface for drupal.
	 */
	class CmsHost_drupal extends CmsHost_Base {

	}

?>