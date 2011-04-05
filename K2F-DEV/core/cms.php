<?php defined('K2F') or die;

	uses('core/security.php');

	/**
	 * A class which abstracts CMS functionality.<br>
	 * <b>NB:</b> In order to use your own CMS interface (let's name it myCms):<br>
	 *   <b>1.</b> Create file name cms.mycms.php in exts folder.<br>
	 *   <b>2.</b> Create a class in that file named CmsHost_myCms.<br>
	 *   <b>3.</b> Make sure your class extends 'CmsHost_Base' and that you override all non-static class methods.<br>
	 *   <b>4.</b> Modify the value of CMS_HOST in config.php and set it to 'myCms' (defaults to 'none').<br>
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 01/11/2010
	 */
	class CmsHost {
		/**
		 * @var CmsHost_Base The CMS object to issue calls to.
		 */
		private static $cms=null;
		/**
		 * Initializes CMS system.<br>
		 * <b>Important:</b> Do not call this directly yourself!
		 */
		public static function _init(){
			$ch=CFG::get('CMS_HOST');
			if($ch!='none' && $ch!='')
				uses('exts/cms.'.$ch.'.php');
			$cms='CmsHost_'.(($ch=='none' || $ch=='') ? 'Base' : $ch);
			if(class_exists($cms)){
				self::$cms=new $cms();
			} else xlog('Error: Unsupported CMS host type "'.Security::snohtml($ch).'".');
		}
		/**
		 * Returns current CMS interface.
		 * @return CmsHost_Base CmsHost manager.
		 */
		public static function cms(){
			return self::$cms;
		}
		/**
		 * Returns the function signature (fsig) for CMS actions.<br>
		 * Parameters are as follows:
		 * <br>table id (integer) - Starting from 1, this is the nth adminlist table.
		 * <br>action (string) - This is the desired action initially specified by the developer (BUT LOWERCASE).
		 * <br>item ids (array) - An array of the affected items' id.
		 * @return array Function parameters (fsig).
		 */
		public static function fsig_action(){
			return array('k2f-table'=>'integer','k2f-action'=>'string','k2f-checked'=>'array');
		}
		/**
		 * Writes a link which when clicked will show a popup after firing the desired action.
		 * @param integer $id Row id (which matches database).
		 * @param string $html Some html code displayed inside the link.
		 * @param string$action The desired action (edit, delete, etc).
		 */
		public static function fire_action($id,$html,$action){              //           .- speaking of paranoid ;-)
			return '<a href="javascript:;" style="white-space:nowrap;" onclick="k2f_edit(this,'.(int)$id.','.Security::snohtml(@json_encode($action)).');">'.$html.'</a>';
		}
		/**
		 * Maps a URL to a URL structure to get a specific part of the URL.
		 * @param string $structure The URL's model structure.
		 * @param string $url The real URL to read parts from.
		 * @param array $parts The parts the URL is made up of.
		 * @param string $part The part to retrieve from the URL.
		 * @param mixed $default Value returned when matching fails (defaults to null).
		 * @return mixed Either the URL part or the $default value (if matching failed).
		 * @example <code>
		 *            // echoes '45'
		 *            echo url('/events/%venue%/%id%-%name%/','/events/Malta/45-Event/',array('venue','name'),'id');
		 *          </code>
		 */
		public static function struct_parse($structure,$url,$parts=array(),$part='',$default=null){
			$vars=array();
			foreach($parts as $k)$vars['%'.$k.'%']='[^/]+';
			$vars['%'.$part.'%']='([^/]+?)';
			$reg='('.str_replace(array_keys($vars),array_values($vars),preg_quote(trim($structure,'/'))).')/?$';
			return preg_match('#'.$reg.'#',$url,$matches) ? array_pop($matches) : $default;
		}
		/**
		 * Generates a regular expression given URL structure and variable parts.
		 * <br/>Works best with cms->rewrite_url(<regex>);
		 * @param string $structure The URL's model structure.
		 * @param array $parts The parts the URL is made up of.
		 * @return string The generated regular expression, without a starting and ending hash (#).
		 */
		public static function struct_apply($structure,$parts=array()){
			$vars=array();
			foreach($parts as $k)$vars['%'.$k.'%']='[^/]+';
			return '('.str_replace(array_keys($vars),array_values($vars),preg_quote(trim($structure,'/'))).')/?$';
		}
	}
	
	/**
	 * Base class for different CMS systems.
	 */
	class CmsHost_Base {
		/**
		 * Renders an adminlist view given rows, columns and actions.
		 * @param array $rows Array of row objects, the keys are what the system works on, use real ids even if they are strings.
		 * @param string $colid The column name to be used as a unique key (it must exist as a property of each object in $rows).
		 * @param array $columns Array of strings/columns (key is the row property and value is the html).
		 * @param array $options Array of desired options:<br>
		 *     multiselect  - Each row can be selected at the same time (checkbox).
		 *     singleselect - Only one row can be selected by itself (radiobutton).
		 *     allowadd     - Whether to show button "Add New" next to the title or not.
		 *     nopopup:xyz  - Ensure page content of action xyz is not shown in a popup (useful for huge pages).
		 * @param array $actions Array of supported action strings.
		 * @param string $handler A function used to preprocess cells before rendering. It is defined as follows:
		 *     $html = handler( $id, $row, $colid, $cell );
		 *     $html - string, the returned HTML, ensure it is secure!
		 *     $id - mixed, the key for $row in array $rows
		 *     $row - object, the current row object
		 *     $colid - mixed, the key for the current column in array $columns
		 *     $cell - mixed, the current cell data
		 * @param string $emptymsg Some HTML shown in place of table if $rows is empty.
		 * @todo Change both action foreach loops to write html that actually does something!!
		 */
		public function adminlist($rows=array(),$colid='id',$columns=array(),$options=array(),$actions=array(),$handler='',$emptymsg='No items found'){
			if(count($rows)==0){
				echo '<div>'.$emptymsg.'</div>';
				return;
			}
			?><div><?php
				foreach($actions as $action)
					echo '<input type="button" value="'.Security::snohtml(ucwords($action)).'"/>';
			?></div><table>
				<thead>
					<tr><?php
						if(in_array('multiselect',$options))echo '<th><input type="checkbox"></th>';
						if(in_array('singleselect',$options))echo '<th><input type="radio"></th>';
						foreach($columns as $key=>$columns)echo '<th>'.Security::snohtml($column).'</th>';
					?></tr>
				</thead><tfoot>
					<tr><?php
						if(in_array('multiselect',$options))echo '<th><input type="checkbox"></th>';
						if(in_array('singleselect',$options))echo '<th><input type="radio"></th>';
						foreach($columns as $key=>$columns)echo '<th>'.Security::snohtml($column).'</th>';
					?></tr>
				</tfoot><tbody>
					<tr><?php
						foreach($rows as $id=>$row){
							if(in_array('singleselect',$options))
								echo '<td><input type="radio" value="'.Security::snohtml($id).'" name="checked[]"/></td>';
							if(in_array('multiselect',$options))
								echo '<td><input type="checkbox" value="'.Security::snohtml($id).'" name="checked[]"/></td>';
							foreach($columns as $colid=>$colname)
								echo '<td>'.(
									function_exists($handler)
										? call_user_func($handler,$id,$row,$colid,isset($row->$colid) ? $row->$colid : null)
										: Security::snohtml(isset($row->$colid) ? $row->$colid : 'null')
								).'</td>';
						}
					?></tr>
				</tbody>
			</table><div><?php
				foreach($actions as $action)
					echo '<input type="button" value="'.Security::snohtml(ucwords($action)).'"/>';
			?></div><?php
		}
		protected static $menucounter=0;
		/**
		 * Adds a new main menu item (visible in backend only).
		 * @param string $name The item's name.
		 * @param string $text A short description of the item, may be used a sub-title or hint.
		 * @param AppIcon $icons A set of multi-sized icons to represent menu item.
		 * @param string|array $handler Function/object/class called when this menu item is clicked.
		 * @return mixed Newly created menu item's handle. Used to create sub menu items.
		 */
		public function admin_add_menu($name,$text,$icons,$handler){
			return ++self::$menucounter;
		}
		/**
		 * Adds a new sub item to a menu (visible in backend only).
		 * @param mixed $parent Handle created by parent menu.
		 * @param string $name The item's name.
		 * @param string $text A short description of the item, may be used a sub-title or hint.
		 * @param AppIcon $icons A set of multi-sized icons to represent menu item.
		 * @param string|array $handler Function/object/class called when this menu item is clicked.
		 * @return mixed Newly created menu item's handle.
		 */
		public function admin_add_submenu($parent,$name,$text,$icons,$handler){
			return ++self::$menucounter;
		}
		/**
		 * Adds a new main menu item (visible in frontend only).
		 * @param string $name The item's name.
		 * @param string $text A short description of the item, may be used a sub-title or hint.
		 * @param AppIcon $icons A set of multi-sized icons to represent menu item.
		 * @param string|array $handler Function/object/class called when this menu item is clicked.
		 * @return mixed Newly created menu item's handle. Used to create sub menu items.
		 */
		public function client_add_menu($name,$text,$icons,$handler){
			return ++self::$menucounter;
		}
		/**
		 * Adds a new sub item to a menu (visible in frontend only).
		 * @param mixed $parent Handle created by parent menu.
		 * @param string $name The item's name.
		 * @param string $text A short description of the item, may be used a sub-title or hint.
		 * @param AppIcon $icons A set of multi-sized icons to represent menu item.
		 * @param string|array $handler Function/object/class called when this menu item is clicked.
		 * @return mixed Newly created menu item's handle.
		 */
		public function client_add_submenu($parent,$name,$text,$icons,$handler){
			return ++self::$menucounter;
		}
		/**
		 * Adds a new main menu item (visible in frontend only).
		 * @param string $name The item's name.
		 * @param string $text A short description of the item, may be used a sub-title or hint.
		 * @param AppIcon $icons A set of multi-sized icons to represent menu item.
		 * @param string|array $handler Function/object/class called when this menu item is clicked.
		 * @return mixed Newly created menu item's handle. Used to create sub menu items.
		 */
		public function guest_add_menu($name,$text,$icons,$handler){
			return ++self::$menucounter;
		}
		/**
		 * Adds a new sub item to a menu (visible in frontend only).
		 * @param mixed $parent Handle created by parent menu.
		 * @param string $name The item's name.
		 * @param string $text A short description of the item, may be used a sub-title or hint.
		 * @param AppIcon $icons A set of multi-sized icons to represent menu item.
		 * @param string|array $handler Function/object/class called when this menu item is clicked.
		 * @return mixed Newly created menu item's handle.
		 */
		public function guest_add_submenu($parent,$name,$text,$icons,$handler){
			return ++self::$menucounter;
		}
		/**
		 * Constructs a URL to menu page (with get arguments).
		 * @param mixed $menu Menu item handle.
		 * @param array $args List of argument to pass to menu page.
		 * @return string The menu page URL.
		 */
		public function url_to_menu($menu,$args=array()){
			return '';
		}
		/**
		 * Returns the value of $key or an empty string.
		 * @param string $key The setting to get the value from.
		 * @return string The setting's value or an empty string.
		 */
		public function config_get($key){
			return ++self::$menucounter;
		}
		/**
		 * Sets the value of a setting.
		 * @param string $key The key to be created/updated/overwritten.
		 * @param string $value The new value to set.
		 */
		public function config_set($key,$value){

		}
		/**
		 * Renders a a title for the page.
		 * @param AppIcon $icon Icon set associated to title.
		 * @param string $title Title's text (keep it short & sweet).
		 * @param array $options Array of desired options: allowadd
		 * @param array $actions Array of supported action strings.
		 * @param array $callback Array of class and method used as a callback for handling actions.
		 * @todo Change the foreach loop to make buttons actually do something!!
		 */
		public function adminlist_begin($icon,$title,$options=array(),$actions=array(),$callback=array()){
			if(!is_array($options))$options=array($options);
			if(!is_array($actions))$actions=array($actions);
			if(in_array('allowadd',$options))array_unshift($actions,'add');
			?><div class="page">
				<table width="100%"><tr>
					<td width="52"><img src="<?php echo Security::snohtml($icon->_32); ?>" width="32" height="32" alt=""></td>
					<td><h2><?php echo Security::snohtml($title); ?></h2></td>
					<td align="right"><?php foreach($actions as $action)echo '<input type="button" value="'.Security::snohtml($action).'">'; ?></td>
				</tr></table><?php
		}
		/**
		 * Finalizes page rendering.
		 */
		public function adminlist_end(){
			?></div><?php
		}
		/**
		 * Creates necessary formatting for a popup.
		 * @param string $title Title shown in the popup (as HTML!).
		 * @param string $hint Shows some text associated with the popup (eg, hint).
		 * @param integer $width If larger than zero, it will be used as popup's width.
		 * @param integer $height If larger than zero, it will be used as popup's height.
		 * @param array $callback A ClassMethod whcih will be used to handle popup actions.
		 */
		public function popup_begin($title,$hint,$width=0,$height=0,$callback=array()){

		}
		/**
		 * Creates popup button. Popup buttons perform an action and close the popup.
		 * @param string $text The text inside the button (eg: "Add" or "Update").
		 * @param string $action Value passed to your callback function's $action (eg: "updated").
		 * @param string $type The type of button to render, a value from the following:
		 * <br> button   - a normal button, medium attention
		 * <br> primary  - a normal button, high positive attention
		 * <br> critical - a normal button, high negative attention
		 * <br> link     - a textual link, low attention
		 */
		public function popup_button($text,$action,$type){

		}
		/**
		 * Finishes necessary formatting for a popup.
		 */
		public function popup_end(){

		}
		/**
		 * Returns whether the current user is an admin or not.
		 * @return boolean Whether it is or not.
		 */
		public function is_admin(){
			return false;
		}
		/**
		 * Returns whether the current user is logged in or not.
		 * @return boolean Whether it is or not.
		 */
		public function is_client(){
			return false;
		}
		/**
		 * Returns whether the current user is not an admin/client or not.
		 * @return boolean Whether it is or not.
		 */
		public function is_guest(){
			return true;
		}
		/**
		 * Returns the currently logged in user's id.
		 * @return integer Currently logged in user's id or 0 on error.
		 */
		public function user_id(){
			return 0;
		}
		/**
		 * Returns username given user id.
		 * @param integer $id Target user's id.
		 * @return string User's (login) username.
		 */
		public function user_username($id){
			return '';
		}
		/**
		 * Generates a wysiwyg editor, prefilled with content.
		 * @param string $name Editor name, use it as $_REQUEST[$name] later on to retrieve the modified content.
		 * @param string $html The editor's initial content, as html.
		 * @param string $width Editor's width in number of pixels.
		 * @param string $height Editor's height in number of pixels.
		 */
		public function wysiwyg($name,$html,$width,$height){       //   .- toolbar id
			?><div id="<?php echo Security::snohtml('k2f-retb-'.$name.'-1'); ?>"><!----></div>
			<textarea cols="" rows="" name="<?php echo Security::snohtml($name);
				?>" id="<?php echo Security::snohtml('k2f-reed-'.$name);
				?>" style="width:<?php echo (int)$width; ?>px; height:<?php echo (int)$height; ?>px;"><?php
				echo Security::snohtml($html);
			?></textarea><?php
		}
		/**
		 * Creates a button inside a particular wysiwyg editor.
		 * @param string $name Editor name where the separator will be inserted.
		 * @param string $icon Full URL to an appropriate icon. The current CMS is passed to the icon, so that
		 *     one can discriminate against them ;-) (create icons based on CMS theme). Eg: '?cms=wordpress'
		 * @param string $title Alternative text / button hint/title for the button.
		 * @param integer $toolbar Toolbar/row number. Starts with "1" as topmost toolbar.
		 * @param string $onclick JS function name for onclick event. First parameter is the button element.
		 * @param string $onmouseover JS function name for onmouseout event. First parameter is the button element.
		 * @param string $onmouseout JS function for onmouseover event. First parameter is the button element.
		 */
		public function wysiwyg_button($name,$icon,$title='',$toolbar=1,$onclick=null,$onmouseover=null,$onmouseout=null){
			$icon=(strpos($icon,'?')===false ? $icon.'?' : $icon.'&').'cms=none';
			?><script type="text/javascript">
				(function(){
					var tb=document.getElementById(<?php echo @json_encode('k2f-retb-'.$name.'-'.$toolbar); ?>);
					var im=document.createElement('IMG');
					im.src=<?php @json_encode($icon); ?>;
					<?php if($onclick){     ?>im.setAttribute('onclick',<?php echo @json_encode($onclick); ?>+'(this);');<?php         } ?>
					<?php if($onmouseover){ ?>im.setAttribute('onmouseover',<?php echo @json_encode($onmouseover); ?>+'(this);');<?php } ?>
					<?php if($onmouseout){  ?>im.setAttribute('onmouseout',<?php echo @json_encode($onmouseout); ?>+'(this);');<?php   } ?>
					tb.appendChild(im);
				})();
			</script><?php
		}
		/**
		 * Creates a button inside a particular wysiwyg editor.
		 * @param string $name Editor name where the separator will be inserted.
		 * @param integer $toolbar Toolbar/row number. Starts with "1" as topmost toolbar.
		 */
		public function wysiwyg_separator($name,$toolbar=1){
			?><script type="text/javascript">
				(function(){
					var tb=document.getElementById(<?php echo @json_encode('k2f-retb-'.$name.'-'.$toolbar); ?>);
					var tx=document.createTextNode('&nbsp;');
					tb.appendChild(tx);
				})();
			</script><?php
		}
		/**
		 * Begins writing popup HTML for WYSIWYG buttons.
		 * @param string $title Popup title.
		 */
		public function wysiwyg_popup_begin($name,$title){
			?><html><head><title><?php echo Security::snohtml($title); ?></title></head><body><?php
		}
		/**
		 * Adds a button to WYSIWYG popup.
		 * @param string $value Button text.
		 * @param string $onclick Javascript code to be executed when button is clicked.
		 */
		public function wysiwyg_popup_button($value,$onclick){
			static $buttons='';
			$buttons.='<input type="button" value="'.Security::snohtml($value).'" onclick="'.Security::snohtml($onclick).'"/>';
		}
		/**
		 * Finishes writing content of WYSIWYG popup.
		 */
		public function wysiwyg_popup_end(){
			?><p><?php echo $buttons; ?></p></body></html><?php
		}
		/**
		 * Given the original WYSIWYG html, this function returns an array of pages.
		 * @param string $content HTML content as read from WYSIWYG.
		 * @return array Array of pages (with at least one string).
		 */
		public function wysiwyg_paginate($content){
			return array($content);
		}
		/**
		 * Rewrites a URL to a different one if it matches an expression.
		 * @param string $search A regular expression to match URL with.
		 * @param ClassMethod $replace A ClassMethod to call if $search matches the current URL.
		 *                             <b>Important:</b> The first parameter contains an array of segments (ie: the URI broken down from delimiters).
		 * @param boolean $important If true, this rule will be executed before other non-important rules.
		 * @example <code>
		 *            class Test {
		 *              public static function route($segments){
		 *                switch($segments}{
		 *                  case 'categories':
		 *                    // ...
		 *                    break;
		 *                  case 'products':
		 *                    // ...
		 *                    break;
		 *                  default:
		 *                    // ...?
		 *                }
		 *              }
		 *            }
		 *            CmsHost::cms()->rewrit_url('(categories|products)/.*$',ClassMethod('Test','route'),true);
		 *          </code>
		 */
		public function rewrite_url($search,$replace,$important=false){

		}
		/**
		 * @return boolean Returns whether permalinks are enabled or not.
		 */
		public function rewrite_enabled(){
			return false;
		}
		/**
		 * Returns a full (publicly accessible) URL which is when accessed, calls
		 */
		public function write_url($handler,$arguments){
			foreach($arguments as $n=>$v)$arguments[$n]='&'.urlencode($n).'='.urlencode($v);
			return 'index.php?cls='.urlencode($handler[0]).'&mtd='.urlencode($handler[1]).implode('',$arguments);
		}
		/**
		 * Returns a CMS-specific upload directory.
		 * @return string Absolute path including last path delimiter.
		 */
		public function upload_dir(){
			return '/uploads/';
		}
		/**
		 * Returns a CMS-specific upload public url.
		 * @return string Absolute url including last path delimiter.
		 */
		public function upload_url(){
			return CFG::get('REL_K2F').'uploads/';
		}
		/**
		 * Returns a CMS-specific url to login page.
		 * @param string $redirect (Optional) Absolute URL to the page the user ends on after logging in.
		 * @return string Absolute URL to login page or an empty string on failure.
		 */
		public function login_url($redirect=''){
			return '';
		}
		/**
		 * Returns a CMS-specific url to logout page.
		 * @param string $redirect (Optional) Absolute URL to the page the user ends on after logging out.
		 * @return string Absolute URL to logout page or an empty string on failure.
		 */
		public function logout_url($redirect=''){
			return '';
		}
		/**
		 * Returns a CMS-specific url to registration page.
		 * @param string $redirect (Optional) Absolute URL to the page the user ends on after registration.
		 * @return string Absolute URL to registration page or an empty string on failure.
		 */
		public function register_url($redirect=''){
			return '';
		}
	}
	
	// Initialize CMS system automatically.
	CmsHost::_init();

?>