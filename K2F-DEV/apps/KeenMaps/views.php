<?php defined('K2F') or die;

	// load views for backend/admin only
	if(CmsHost::cms()->is_admin())
		foreach(glob(CFG::get('ABS_K2F').'apps/KeenMaps/views_admins/*.php') as $file)
			require_once($file);

	// load views for frontend/logged-in only
	if(CmsHost::cms()->is_client())
		foreach(glob(CFG::get('ABS_K2F').'apps/KeenMaps/views_clients/*.php') as $file)
			require_once($file);

	// load views for frontend/non-logged-in
	if(CmsHost::cms()->is_guest())
		foreach(glob(CFG::get('ABS_K2F').'apps/KeenMaps/views_guests/*.php') as $file)
			require_once($file);

?>