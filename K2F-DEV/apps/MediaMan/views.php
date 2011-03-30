<?php defined('K2F') or die;

	uses('core/security.php','core/classutils.php');

	class kmmMainView {
		public static function ViewCats(){
			CmsHost::cms()->adminlist_begin(MediaMan::icons('categories'),'Manage Media Categories','allowadd',array(),ClassMethod(__CLASS__,'ActionsCategories'));
			$cats=new kmmCategories();
			$cats->load();
			CmsHost::cms()->adminlist(
				$cats->rows,'id',
				array('id'=>'','icon'=>'','name'=>'Name','items'=>'Media Items','size'=>'Total Size'),
				array('multiselect','allowadd'),
				array('Edit'),
				ClassMethod(__CLASS__,'cells'),
				'No categories yet',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
		}
		public static function ActionsCategories($table,$action,$checked){

		}
		public static function ViewItems(){

		}
		public static function ViewCode(){
			?><h3>Introduction</h3>
					MediaMan is a small and minimal media management system for K2F. It aims at being easily customizable, user-friendly and intuitive to both end-users as well as developers.<br/>
					To allow full customization, an API is disclosed to the developer. See installation section for more info.
				<p>&nbsp;</p>

			<h3>Definitions</h3>
				<ul>
					<li><b>Category</b> A list of related media types, such as "Advertisements".</li>
					<li><b>Item</b> A single media item in a specific category.</li>
					<li><b>Type</b> A media item's type, such is image, video, link etc...</li>
					<li><b>View</b> Some code which renders a set of items (given a category) in a specific way (such as a banner format).</li>
				</ul>
				<p>&nbsp;</p>

			<h3>API Details</h3>
					In order to be flexible, MediaMan has a little API system:
					<div style="border-left: 2px solid #AAA; padding-left: 8px; margin-bottom: 16px;">
						<pre>MediaMan::render( <span style="color:brown;">$view</span> = <span style="color:red;">''</span> , <span style="color:brown;">$category</span> = <span style="color:purple;">0</span> );</pre>
						Render a specific category viewer at the specified location.<br/>
						<div><code><b>$view</b></code> (<i>string</i>) - The name of the class used to render category.</div>
						<div><code><b>$category</b></code> (<i>integer</i>) - The database id of the category to render.</div>
					</div>
				<p>&nbsp;</p>

			<h3>API Generator</h3>
				In order to make life easier, this will help you out using MediaMan's API.<br/>
				View: <select id="kmmView"><?php
					foreach(get_class_grandchildren('kmmView') as $class)
						echo '<option value="'.Security::snohtml($class).'">'.Security::snohtml(get_class_prop($class,'name')).'</option>';
				?></select>
				<?php $dyn='(<span style="color:blue;">int</span>)<span style="color:brown;">$_REQUEST</span>[<span style="color:red;">\'category\'</span>]'; ?>
				Category: <select id="kmmCategory">
					<option value="<?php echo Security::snohtml($dyn); ?>">Dynamic (from $_REQUEST)</option><?php
					$categories=new kmmCategories(); $categories->load(); $category=new kmmCategory();
					foreach($categories->rows as $category)
						echo '<option value="'.(int)$category->id.'">'.Security::snohtml($category->name).'</option>';
				?></select>
				<input type="button" value="Generate" onclick="kmmGenerate();"/>
				<pre id="kmmCode" style="background:#EFEFEF; padding:4px;"><!----></pre>
				<script type="text/javascript">
					function kmmGenerate(){
						var v=document.getElementById('kmmView').value;
						var c=document.getElementById('kmmCategory').value;
						document.getElementById('kmmCode').innerHTML=[
							'<b>&lt;?php</b>',
							'	<span style="color:blue;">echo defined</span>(<span style="color:red;">\'K2F\'</span>) ? MediaMan::render(<span style="color:red;">\''+v+'\'</span>,'+c+') : <span style="color:red;">\'K2F not loaded.\'</span>;',
							'<b>?&gt;</b>'
						].join('\n');
					}
				</script><?php
		}
	}
	// register ajax/api calls
	Ajax::register('kmmMainView','ActionsCategories',CmsHost::fsig_action());

?>