<?php defined('K2F') or die;

	uses('core/cms.php','core/security.php','core/ajax.php');

	// load logic/data classes
	require_once(CFG::get('ABS_K2F').'apps/CvcWiki/classes.php');

	class CvcWiki extends Application {
		public $name='Covac Wiki';
		public $description='Small Wiki management system.';
		public $version='0.0.0.1';
		public $date='30 Dec 2010';
		public $tags='covac wiki collaborative document management information editing';
		public static function path(){
			return CFG::get('ABS_K2F').'apps/'.__CLASS__.'/';
		}
		public static function url(){
			return CFG::get('REL_K2F').'apps/'.__CLASS__.'/';
		}
		public function mainicon() {
			return self::icons('main');
		}
		public function on_admin_menu(){
			// create main and submenu items
			$menu=CmsHost::cms()->admin_add_menu('Covac Wiki','Covac Wiki Management.',self::icons('main'),ClassMethod('CvcWiki','instructions'));
			CmsHost::cms()->admin_add_submenu($menu,'Instructions','How to use Covac Wiki.',null,ClassMethod('CvcWiki','instructions'));
			CmsHost::cms()->admin_add_submenu($menu,'Manage Wiki','Covac Wiki Management.',null,ClassMethod('CvcWiki','manage'));
		}
		/**
		 * Returns an array of icons or a specified AppIcon.
		 * @param string $icon (optional) The icon name to query.
		 * @return AppIcon|array Array of icons or an AppIcon.
		 */
		public static function icons($icon=null){
			// generate a couple of icons
			$url=self::instance()->url();
			$icons=array(
				'main'=>new AppIcon(
					$url.'img/icon16.png',
					$url.'img/icon32.png',
					$url.'img/icon48.png',
					$url.'img/icon64.png',
					$url.'img/icon128.png'
				)
			);
			return $icon ? $icons[$icon] : $icons;
		}
		public static function manage(){
			$defsearch='Search...';
			if(isset($_REQUEST['save'])){
				// save submitted changes
				$page=new cwWikiPage((int)$_REQUEST['cw-id']);
				$page->load();
				// STUPID STUPID STUPID WORDPRESS BUG
				if(CFG::get('CMS_HOST')=='wordpress'){
					$_GET     = array_map( 'stripslashes_deep', $_GET );
					$_POST    = array_map( 'stripslashes_deep', $_POST );
					$_REQUEST = array_map( 'stripslashes_deep', $_REQUEST );
				}
				// /STUPID STUPID STUPID WORDPRESS BUG
				if($_REQUEST['cw-title']==''.(int)$_REQUEST['cw-title'])
					die('Fatal error: page title is incorrect.');
				$page->title=$_REQUEST['cw-title'];
				$page->data_orig=$_REQUEST['cw-content'];
				$page->retired=isset($_REQUEST['cw-retired']) && $_REQUEST['cw-retired']=='yes';
				$page->access=(int)$_REQUEST['cw-access'];
				if(!$page->save())
					die('Fatal error: failed saving changes.');
			}
			?><div class="wrap"><?php
				/* Some WP eyecandy */ if(CFG::get('CMS_HOST')=='wordpress')echo '<div class="icon32" id="icon-edit-pages"><br></div><h2>Manage Wiki Pages</h2>';
				?><script type="text/javascript">
					var cwajax=null; var cworig='';
					function cwSearchCancel(){
						if(cwajax && cwajax.readyState!=0){
							cwajax.abort();
							cwajax=null;
						}
						jQuery('#cw-search-cls').hide();
						jQuery('#cw-search-ldr').hide();
						jQuery('#cw-search-list').html(cworig);
					}
					function cwSearchSend(){
						cwSearchCancel();
						jQuery('#cw-search-cls').hide();
						jQuery('#cw-search-ldr').show();
						var url=location.href+<?php echo @json_encode(Ajax::url('CvcWiki','ajax_search','&')); ?>;
						var arg={
							'query': jQuery('#cw-search-txt').val(),
							'mode': jQuery('#cw-search-content:checked').length ? 'content' : 'title'
						};
						cwajax=jQuery.post(url,arg,function(data){
							jQuery('#cw-search-ldr').hide();
							jQuery('#cw-search-cls').show();
							jQuery('#cw-search-list').html(data);
						},'json');
					}
					function cwAjaxEdit(wiki){
						jQuery('#cw-editor').slideUp();
						var url=location.href+<?php echo @json_encode(Ajax::url('CvcWiki','ajax_edit','&')); ?>+'&wiki='+encodeURIComponent(wiki);
						jQuery.getJSON(url,function(data){
							jQuery('#cw-editor').html(data);
							jQuery('#cw-editor').slideDown();
						});
					}
					function cwPermaRefresh(){
						if(cwajax && cwajax.readyState!=0){
							cwajax.abort();
							cwajax=null;
						}
// TODO: ..jQuery('#cw-title').css();
					}
					jQuery(document).ready(function(){
						jQuery('#cw-search-txt').blur(function(){
							if(jQuery('#cw-search-txt').val()==''){
								jQuery('#cw-search-txt').val('<?php echo $defsearch; ?>').css('font-style','italic');
								cwSearchCancel();
							}
						}).focus(function(){
							if(jQuery('#cw-search-txt').val()=='<?php echo $defsearch; ?>'){
								jQuery('#cw-search-txt').val('').css('font-style',null);
							}
						}).keyup(cwSearchSend);
						jQuery('#cw-search-cls').click(function(){
							cwSearchCancel();
							jQuery('#cw-search-txt').val('');
							jQuery('#cw-search-txt').blur();
						});
						jQuery('#cw-search-title').click(function(){
							if(jQuery('#cw-search-txt').val()!=<?php echo @json_encode($defsearch); ?>)cwSearchSend();
						});
						jQuery('#cw-search-content').click(function(){
							if(jQuery('#cw-search-txt').val()!=<?php echo @json_encode($defsearch); ?>)cwSearchSend();
						});
						cworig=jQuery('#cw-search-list').html();
					});
				</script><table width="100%">
					<tr><td width="230" valign="top">
						<table class="widefat" style="width: 218px;">
							<tr><td>
								<div id="cw-search-box">
									<div style="position:relative; height:24px;">
										<input type="text" value="<?php echo $defsearch; ?>" id="cw-search-txt" style="position:absolute; left:0; top:0; width:200px; padding-right:22px; font-style:italic; height:22px;"/>
										<input type="image" src="<?php echo self::instance()->url(); ?>/img/cancel16.png" id="cw-search-cls" style="position:absolute; left:180px; top:4px; margin:0; padding:0; display:none; border:none;"/>
										<img alt="" src="<?php echo self::instance()->url(); ?>/img/refresh16.gif" id="cw-search-ldr" style="position:absolute; left:180px; top:4px; margin:0; padding:0; display:none;"/>
									</div><div>
										<label>Search in:</label>
										<label for="cw-search-title">
											<input type="radio" name="cw-search-in" id="cw-search-title" checked/>Title
										</label>
										<label for="cw-search-content">
											<input type="radio" name="cw-search-in" id="cw-search-content"/>Content
										</label>
									</div>
								</div>
							</td></tr><tr><td>
								<div id="cw-search-list">
									<?php echo self::ajax_search(); ?>
								</div>
							</td></tr>
						</table>
					</td><td valign="top">
						<div id="cw-editor">
							<?php echo self::ajax_edit(); ?>
						</div>
					</td></tr>
				</table>
			</div><?php
		}
		/**
		 * Performs an ajax search for wiki pages.
		 * @param string $query The text to search for.
		 * @param string $mode Where to search (title vs contents).
		 * @return string Page links.
		 */
		public static function ajax_search($query='',$mode='title'){
			if($mode!='title')$mode='data_orig'; // failsafe
			$pages=new cwWikiPages();
			if(trim($query)!=''){
				$pages->load('`'.Security::escape($mode).'` LIKE "%'.Security::escape($query).'%"');
			}else $pages->load();
			ob_start();
			self::_pages_to_html($pages->rows);
			return ob_get_clean();
		}
		/**
		 * Generates a list of HTML links to page editors.
		 * @param array $pages Pages to link to.
		 */
		protected static function _pages_to_html($pages){
			$page=new cwWikiPage(); $mp=1;
			foreach($pages as $page){
				$name=$page->name();
				?><a href="?<?php echo str_replace('ajax','',Ajax::url('CvcWiki','edit','&'));
					?>&wiki=<?php echo $page->id; ?>" onclick="cwAjaxEdit(<?php echo $page->id;
					?>); return false;" style="white-space: nowrap; overflow: hidden;"><?php echo $page->title==''
						? ($mp==1 ? '<b><i>Primary Main Page</i></b>' : '<i>Secondary Main Page ('.cwOrdinal($mp).')</i>')
						: Security::snohtml($page->title);
				?></a><br/><?php
				if($page->title=='')$mp++;
			}
		}
		/**
		 * Ajax call to show page editor.
		 * @param string|integer $wiki Page title name or page id.
		 * @return string Page editor HTML.
		 */
		public static function ajax_edit($wiki='Untitled Wiki Page'){
			$page=getWikiPage($wiki,true);
			if($page->id<1 && $wiki!=''.(int)$wiki)
				$page->title=$wiki; // neat trick to inherit requested topic name
			ob_start();
			self::_page_edit($page);
			return ob_get_clean();
		}
		/**
		 * Show page editor for page.
		 * @param cwWikiPage $page The page to edit.
		 */
		protected static function _page_edit($page){
			?><form action="" method="post">
				<input type="hidden" name="cw-id" value="<?php echo (int)$page->id; ?>"/>
				<input type="hidden" name="save" value="1"/>
				<table width="100%" cellpadding="0" cellspacing="0">
					<tr>
						<td valign="top" style="margin:0; padding:0;">
							<table class="widefat" style="width:100%;">
								<tr><td>
									<label for="cw-title">
										<input type="text" id="cw-title" name="cw-title" value="<?php echo Security::snohtml($page->title); ?>" onkeyup="cwPermaRefresh();" autocomplete="off" tabindex="1" maxlength="200" style="box-sizing:border-box; -moz-box-sizing:border-box; -ms-box-sizing:border-box; -webkit-box-sizing:border-box; -o-box-sizing:border-box; -khtml-box-sizing:border-box; font-size:27px; padding:3px 4px; width:100%; margin-bottom: 12px;" />
									</label>
									<textarea cols="0" rows="0" name="cw-content" tabindex="2" style="box-sizing:border-box; -moz-box-sizing:border-box; -ms-box-sizing:border-box; -webkit-box-sizing:border-box; -o-box-sizing:border-box; -khtml-box-sizing:border-box; width:100%; height:360px; overflow:scroll; min-width:440px;"><?php echo Security::snohtml($page->data_orig); ?></textarea>
								</td></tr>
							</table>
						</td><td width="12" style="margin:0; padding:0;">&nbsp;</td><td width="300" valign="top" style="vertical-align:top; margin:0; padding:0;">
							<table class="widefat" style="width:100%;">
								<?php if($page->created_uid>0){ ?>
									<tr><td valign="top">
										Created:
									</td><td>
										<b><?php echo date('j M, Y \@ H:i',$page->created); ?></b>
										<br/>by <b><?php echo Security::snohtml(CmsHost::cms()->user_username($page->created_uid)); ?></b>
									</td></tr>
								<?php } ?>
								<?php if($page->updated_uid>0){ ?>
									<tr><td valign="top">
										Updated:
									</td><td>
										<b><?php echo date('j M, Y \@ H:i',$page->updated); ?></b>
										<br/>by <b><?php echo Security::snohtml(CmsHost::cms()->user_username($page->updated_uid)); ?></b>
									</td></tr>
								<?php } ?>
								<tr><td valign="middle" style="vertical-align:middle;">
									Access Mode:
								</td><td>
									<select name="cw-access" style="width:140px;">
										<?php ob_start(); ?>
										<option value="0">Unclassified</option>
										<option value="1">Confidential</option>
										<option value="2">Secret</option>
										<option value="3">Top Secret</option>
										<?php echo str_replace('="'.$page->access.'">','="'.$page->access.'" selected="selected">',ob_get_clean()); ?>
									</select>
								</td></tr>
								<tr><td id="major-publishing-actions" align="center" colspan="2" nowrap="nowrap">
									<label for="cw-retired" style="margin-right:20px;">
										<input type="checkbox" name="cw-retired" style="min-width:inherit;" value="yes" id="cw-retired"<?php if($page->retired)echo ' checked="checked"'; ?>/>
										<span  style="text-decoration:underline; color:red;">Retire Wiki Page</span>
									</label><input type="submit" class="button-primary" value="Save Changes" style="margin-left:24px;"/>
								</td></tr>
							</table>&nbsp;<table class="widefat" style="width:100%;">
								<tr><td>
									<b>Revisions</b>
									<div style="font-size:11px;"><?php
										$revs=new cwWikiPageRevs();
										$rev=new cwWikiPageRev();
										$revs->load($page);
										foreach($revs->rows as $rev){
											?><div>
<!-- BIG TODO: urls must be different for backend!! -->
												<?php echo date('j M, Y \a\t H:i',$rev->created); ?> (<?php echo strlen(@json_encode($rev->data)); ?> bytes)
												<a href="<?php echo Security::snohtml(self::_redirect('revision',$page->name(),'revid='.$rev->id.'&revaction=compareto')); ?>"><img src="<?php echo CvcWiki::url(); ?>img/compare16.png" alt="Compare Revision" width="16" height="16"/></a>
												<a href="<?php echo Security::snohtml(self::_redirect('revision',$page->name(),'revid='.$rev->id.'&revaction=revertto')); ?>"><img src="<?php echo CvcWiki::url(); ?>img/revert16.png" alt="Restore Revision" width="16" height="16"/></a>
												<a href="<?php echo Security::snohtml(self::_redirect('revision',$page->name(),'revid='.$rev->id.'&revaction=viewcode')); ?>"><img src="<?php echo CvcWiki::url(); ?>img/original16.png" alt="View Revision Code" width="16" height="16"/></a>
												<a href="<?php echo Security::snohtml(self::_redirect('revision',$page->name(),'revid='.$rev->id.'&revaction=viewhtml')); ?>"><img src="<?php echo CvcWiki::url(); ?>img/generated16.png" alt="View Revision Page" width="16" height="16"/></a>
											</div><?php
										}
										if(!count($revs->rows))
											echo '<div><i>None yet.</i></div>';
									?></div>
								</td></tr>
							</table>
						</td>
					</tr>
				</table>
				&nbsp;
			</form><?php
		}
		public static function instructions(){
			function cw_hf($php){
				return str_replace(array(
					'<span style="color: #0000BB">&lt;?php&nbsp;</span><span style="color: #FF8000">/*CWHF*/&nbsp;</span>',
					'<span style="color: #FF8000">/*CWHF*/&nbsp;</span><span style="color: #0000BB">?&gt;</span>'),
					'',cw_hl('<?php /*CWHF*/ '.$php.' /*CWHF*/ ?>'));
			}
			function cw_hl($php){
				return cw_cd(str_replace(array('<code>','</code>'),'',highlight_string($php,true)));
			}
			function cw_cd($htm){
				return '<code style="border-left: 2px solid #555; padding-left: 8px; display:block;">'.$htm.'</code>';
			}
			?><div class="wrap"><?php
				/* Some WP eyecandy */ if(CFG::get('CMS_HOST')=='wordpress')echo '<div class="icon32" id="icon-index"><br></div><h2>Instructions</h2>';

				?><h3>Introduction</h3>
					CwWiki is a small and minimal Wiki system for K2F. It aims at being easily customizable, user-friendly and intuitive.<br/>
					How could it be customizable if it doesn't follow your link structure?<br/>
					For this reason, there is no fixed way to view the wiki, unless you do so. See installation section for more info.
				<p>&nbsp;</p>
				
				<h3>Wiki Markup</h3>
					The Wiki markup used is modeled after the <a href="http://en.wikipedia.org/wiki/Creole_%28markup%29">Creole Markup Standard</a>, using the engine from <a href="http://simplewiki.org/">simplewiki.org</a>.<br/>
					If you need help on the Wiki markup, please refer to this page: <a href="http://simplewiki.org/examples">http://simplewiki.org/examples</a>.
				<p>&nbsp;</p>
				
				<h3>Access Modes</h3>
					CwWiki Wiki pages can be set to any of the following access modes:
					<ol>
						<li><b>Unclassified</b> - (Default) Anyone can view page, any logged-in user can edit page.</li>
						<li><b>Confidential</b> - Only registered users can view page, any logged-in user can edit page.</li>
						<li><b>Secret</b>       - Only (logged-in) administrators can view or edit page.</li>
						<li><b>Top Secret</b>   - Only (logged-in) page owner (creator) can view or edit page.</li>
					</ol>
					Notes: Setting a page to <code>Top Secret</code> to a page you don't own is like shooting at your own feet; you'll be loosing access. Same thing if you're a normal user and change a page access mode to <code>Secret</code>.
					A page can also be <code>retired</code>. Afterwords, it can only be recovered according to it's access mode, ie, only the page creator can restore a <code>retired</code> <code>Top Secret</code> page.
				<p>&nbsp;</p>

				<h3>API Details</h3>
					In order to be flexible, CwWiki has a little API system:
					<div style="border-left: 2px solid #AAA; padding-left: 8px; margin-bottom: 16px;">
						<?php echo cw_hf('CvcWiki::wiki_show( $wiki="" );'); ?>
						Show Wiki page in HTML format. Default is an empty string. An empty string will cause the main wiki page to show up.<br/>
						<code><b>$wiki</b></code> (<i>string</i> or <i>integer</i>) - Either the page title or page id<b>*</b>.
					</div>
					<div style="border-left: 2px solid #AAA; padding-left: 8px; margin-bottom: 16px;">
						<?php echo cw_hf('CvcWiki::wiki_show( $wiki="" );'); ?>
						Show Wiki page Editor for the page in question. Default is an empty string. An empty string will cause the main wiki page to be edited.<br/>
						<b>Important:</b> Not using this function does not mean content won't be editable. This function just specifies the editor ought to show up instead of page contents.<br/>
						<code><b>$wiki</b></code> (<i>string</i> or <i>integer</i>) - Either the page title or page id<b>*</b>.
					</div>
					<b>*</b> It may not be obvious, but, Wiki Page names cannot be made up of numbers only as well as start with internal names (like <code>search:</code> or <code>categories:</code>).
				<p>&nbsp;</p>

				<h3>Installation</h3>
					Setting up CwWiki depends on your CMS of choice. The only required action is to throw in some code where the wiki page is to be render (or edited).
					<br/>For your convenience, we will show you how to do this in WordPress:
					<ol>
						<li>
							Determine where you want the wiki to be accessible from. For this example, we asume this to be <a href="/wiki/?" target="_blank">http://yoursite.com/wiki/?</a>.
						</li><li>
							Create a file named wiki.php in your theme folder with the following contents:<b>*</b><?php
							echo cw_hl(implode(CRLF,array(
								'<?php',
								'/*',
								' Template Name: CwWiki',
								'*/',
								'	get_header();',
								'	?><div id="container">',
								'		<div id="content" role="main"><?php',
								'			$wiki=str_replace(\'/wiki/?\',\'\',$_SERVER[\'REQUEST_URI\']);',
								'			CvcWiki::wiki_show($wiki);',
								'		?></div><!-- #content -->',
								'	</div><!-- #container --><?php',
								'	get_sidebar();',
								'	get_footer();',
								'?>'
							)));
							?><b>*</b> Note that the contents may depend on how your template looks like. It is reccomended to see an existing theme file to get a good idea.
						</li><li>
							Create a WordPress page to show up at your desired location and select CwWiki from the list of Templates (<i>Templates</i> section under <i>Page Attributes</i> on your right).
						</li><li>
							Go try it out and enjoy it!
						</li>
					</ol>
				<p>&nbsp;</p>

			</div><?php
		}
		protected static $ORIGREDIR='';
		/**
		 * Returns a Wiki URL with some specific changes.
		 * @param string $action The action to take on Wiki. Leaving it empty would show the Wiki page (eg: 'search').
		 * @param string $wiki The target Wiki page name or id.
		 * @param string $params Any additional parameters to pass via GET (eg: 'a=1&b=N').
		 * @return string The final constructed URL. You may need to run snohtml() over it tho.
		 */
		protected static function _redirect($action='',$wiki='',$params=''){
			if($action!='')$action.=':';
			$requri=urldecode($_SERVER['REQUEST_URI']);
			$result=str_replace(self::$ORIGREDIR,$action.$wiki,$requri);
			if($result==$requri)$result=$requri.$action;
			if($params!='')$result.=(strpos($result,'?')!==false ? '&' : '?').$params;
			return $result;
		}
		public static function wiki_show($name=''){
			self::$ORIGREDIR=$name;
			$mode=strtolower(substr($name,0,strpos($name,':')));
			switch($mode){
				case 'revision':
////////////////////////////////////////////////////////////////////////////////////////////////////
// IMPORTANT WANRING!!!! We don't want to disclose revisions of restricted material, so WATCH IT!!!!
////////////////////////////////////////////////////////////////////////////////////////////////////
					$rev=new cwWikiPageRev((int)$_REQUEST['revid']);
					$rev->load();
					if(($page=$rev->current())){
						switch($_REQUEST['revaction']){
							case 'revertto':
								if($rev->restore()){
									?>The Wiki page, <a href="<?php echo Security::snohtml(str_replace('revision:'.$page->title.'/?revid='.$rev->id.'&revaction=revertto',$page->title,$_SERVER['REQUEST_URI'])); ?>"><?php
									echo Security::snohtml($page->title=='' ? 'Main Page' : $page->title); ?></a>, has been reverted to <?php
									echo date('M jS',$rev->created); ?>.<?php
								}else{
									?>Error: restoration failed. Please
										<a href="<?php echo Security::snohtml($_SERVER['HTTP_REFERER']); ?>" onclick="history.back();">go back</a> or
										<a href="<?php echo Security::snohtml($_SERVER['REQUEST_URI']); ?>" onclick="location.reload(true);">try again</a>.
									<?php
								}
								break;
							case 'compareto':
								$body=htmlDiff(Security::snohtml($rev->data('data_orig')),Security::snohtml($rev->current()->data_orig));
								?><h2><?php
									echo htmlDiff(
											Security::snohtml($rev->data('title')=='' ? 'Main Page' : $rev->data('title')),
											Security::snohtml($rev->current()->title=='' ? 'Main Page' : $rev->current()->title)
										);
									?><small>[<?php
										echo substr_count($body,'<ins>').' addition(s) and '.substr_count($body,'<del>').' deletion(s)';
									?>]</small>
								</h2><?php
								echo '<pre class="text">'.$body.'</pre>';
								break;
							case 'viewcode':
								?><h2><?php
									echo Security::snohtml($rev->data('title')=='' ? 'Main Page' : $rev->data('title'));
								?></h2><?php
								// TODO: Syntax highlight revision code
								echo '<pre class="text">'.Security::snohtml($rev->data('data_orig')).'</pre>';
								break;
							case 'viewhtml':
								?><h2><?php
									echo Security::snohtml($rev->data('title')=='' ? 'Main Page' : $rev->data('title'));
								?></h2><?php
								echo $rev->data('data_html');
								break;
						}
					}else{
						echo 'Access denied.';
					}
					break;
				case 'search':
					break;
				case 'linkto':
					break;
				case 'save':
					if(!CmsHost::cms()->is_guest()){
						$page=getWikiPage(substr($name,strlen($mode)+1),true);
						// STUPID STUPID STUPID WORDPRESS BUG
						if(CFG::get('CMS_HOST')=='wordpress'){
							$_GET     = array_map( 'stripslashes_deep', $_GET );
							$_POST    = array_map( 'stripslashes_deep', $_POST );
							$_REQUEST = array_map( 'stripslashes_deep', $_REQUEST );
						}
						// /STUPID STUPID STUPID WORDPRESS BUG
						if($_REQUEST['cw-title']==''.(int)$_REQUEST['cw-title'])
							die('Fatal error: page title is incorrect.');
						$page->title=$_REQUEST['cw-title'];
						$page->data_orig=$_REQUEST['cw-content'];
						$page->retired=isset($_REQUEST['cw-retired']) && $_REQUEST['cw-retired']=='yes';
						$page->access=''.(int)$_REQUEST['cw-access'];
						?><script type="text/javascript">
							<?php if(!$page->save()){ ?>alert('A fatal error occured while saving, Wiki Page changes where not saved!');<?php } ?>
							if(!confirm('Do you want to continue editing this Wiki Page?'))
								location.href=location.href.replace(<?php echo @json_encode($name); ?>,encodeURIComponent(<?php echo @json_encode($_REQUEST['cw-title']); ?>));
						</script><?php
					}
					// when saving, simply show existing interface, javascript will take care of the rest...
				case 'edit':
					if(!CmsHost::cms()->is_guest()){ // ensure user is logged in
						$name=trim(substr($name,strlen($mode)+1),'/');
						$page=getWikiPage($name,true,$name!=''.(int)$name);
						if($page->title=='' && $name!=''.(int)$name)$page->title=cwWikiNiceName($name);
						ob_start();
						self::_page_edit($page);
						echo str_replace(' action=""',' action="'.
								Security::snohtml(str_replace($mode.':'.$name,'save:'.$name,$_SERVER['REQUEST_URI']))
							.'"',ob_get_clean());
						break;
					}
					// if user is not logged in, don't break switch and fll back to default
				default:
					if(!($page=getWikiPage($name,false,true)) || $page->id<1 || $page->retired){ // some form of failure in loading
						?><h2>The requested page could not be found.</h2>
						<ul>
							<li>If you want, you can <?php if(CmsHost::cms()->is_guest())echo '<a href="'.Security::snohtml(CmsHost::cms()->login_url($_SERVER['REQUEST_URI'])).'">log in</a> and'; ?> <a href="<?php echo Security::snohtml(self::_redirect('edit',$name)); ?>">create the <?php echo Security::snohtml(cwWikiNiceName($name)); ?> page</a>.</li>
							<li><a href="<?php echo Security::snohtml(self::_redirect('search',$name)); ?>">Search for "<?php echo Security::snohtml(cwWikiNiceName($name)); ?>" in existing pages</a>.</li>
							<li><a href="<?php echo Security::snohtml(self::_redirect('linkto',$name)); ?>">Find pages linking to this name.</a></li>
						</ul>
						<h3>Other reasons this message may be displayed:</h3>
						<ul>
							<li>Please check and correct spelling, alternatively, try different textual variations.</li>
							<li>The page may have been reserved to certain people only, please check your access rights.</li>
							<li>The page may have been retired (deleted) for some reason (you have to recreate the page for more info).</li>
						</ul><?php
					}else{ // loading wiki page success!
						?><h2><?php
							echo Security::snohtml($page->title=='' ? 'Main Page' : $page->title);
							$access=array('Unclassified','Confidential','Secret','Top Secret');
							?><span class="cw-access-<?php echo $page->access; ?>">
								<span>(<i><?php echo isset($access[$page->access]) ? $access[$page->access] : 'Public'; ?>)</i></span>
							</span><?php
						?></h2>
						<a href="<?php echo Security::snohtml(self::_redirect('edit',$name)); ?>">Edit Page</a><?php
						echo $page->data_html;
					}
			}
		}
		public static function wiki_edit($name=''){
			echo Security::snohtml('Edit: '.$name);
		}
		public static function router($segments){
			if(strtolower($segments[0])=='wiki'){
				array_shift($segments);
				self::wiki_show(implode('/',$segments));
			}
		}
	}
	Applications::register('CvcWiki');
	Ajax::register('CvcWiki','ajax_search',array('query'=>'string','mode'=>'string'));
	Ajax::register('CvcWiki','ajax_edit',array('wiki'=>'string'));

	// TODO: Use configuration instead!
	CmsHost::cms()->rewrite_url('(wiki)/.*$',ClassMethod('CvcWiki','router'),true);
	
?>