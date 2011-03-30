<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php','core/ajax.php','libs/swfupload/swfupload.php');

	class kssManageProducts {
		public static function view(){
			CmsHost::cms()->adminlist_begin(KSimpleShop::icons('main'),'Manage Products','allowadd',array(),ClassMethod(__CLASS__,'actions'));
			$products=new kssProducts();
			$products->load();
			CmsHost::cms()->adminlist(
				$products->rows,'id',
				array(
					'thumb'=>array('Product Image','width:80px;text-align:center;'),
					'name'=>array('Product Name','width:300px;'),
					'description'=>'Description',
					'published'=>array('Published','width:30px;text-align:center;')
				),
				array('multiselect','allowadd'),
				array('Edit'),
				ClassMethod(__CLASS__,'cells'),
				'No products yet...',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
			SwfUpload::init();  // force init now, coz it's too late for later on
			?><script type="text/javascript">
				var kss_up_defimg=<?php echo @json_encode(KSimpleShop::url().'img/thumb-empty.gif'); ?>;
				function kss_images_mkid(){ // returns the next unused image id
					for(var i=0; i<10000; i++)
						if(!document.getElementById('kss-image'+i))
							return i;
				}
				function kss_images_add(url){ // add image item given image url and returns id
					var id=kss_images_mkid(); var eid='kss-image'+id;
					var div=document.createElement('DIV'); div.id=eid;
					div.setAttribute('style','display:inline-block; width:148px; overflow:hidden; margin:4px; position:relative;');
					div.setAttribute('align','center');
					div.innerHTML='<div style="padding:4px;border:1px solid #EEEEEE;"><div style="overflow:hidden;"><img height="112" alt="" id="'+eid+'-img"/></div></div>'
						+'<div id="'+eid+'-prg" align="left" style="position:absolute; left:8px; right:8px; top:50px; height:10px; border:1px solid #666; padding:1px; display:none; background:#FFF; box-shadow:0 0 4px #FFF; -moz-box-shadow:0 0 4px #FFF; -o-box-shadow:0 0 4px #FFF; -webkit-box-shadow:0 0 4px #FFF; -khtml-box-shadow:0 0 4px #FFF; -ms-box-shadow:0 0 4px #FFF;">'
							+'<div id="'+eid+'-per" style="disaplay:inline-block; height:10px; width:0%; background:#257DA6;"><!----></div>'
						+'</div>   <input name="images[]" id="'+eid+'-url" type="hidden"/>'
						+'<a href="javascript:;" onclick="kss_images_movu('+id+')" title="Move Left" style="display:inline-block;"><img src="<?php echo KSimpleShop::url(); ?>img/left16.png" width="16" height="16" alt="" style="vertical-align:middle;"/></a> '
						+(<?php ob_start(); kssUploader::button('{%ID%}',20); echo @json_encode(ob_get_clean()); ?>).replace('{%ID%}',id)+' '
						+'<a href="javascript:;" onclick="kss_images_furl('+id+')" title="Set from URL" style="display:inline-block;"><img src="<?php echo KSimpleShop::url(); ?>img/www16.png" width="16" height="16" alt="" style="vertical-align:middle;"/></a> '
						+'<a href="javascript:;" onclick="kss_images_dele('+id+')" title="Delete" style="display:inline-block;"><img src="<?php echo KSimpleShop::url(); ?>img/delete16.png" width="16" height="16" alt="" style="vertical-align:middle;"/></a> '
						+'<a href="javascript:;" onclick="kss_images_movd('+id+')" title="Move Right" style="display:inline-block;"><img src="<?php echo KSimpleShop::url(); ?>img/right16.png" width="16" height="16" alt="" style="vertical-align:middle;"/></a>';
					document.getElementById('kss-images').appendChild(div);
					document.getElementById(eid+'-img').src=url=='' ? kss_up_defimg : url;
					document.getElementById(eid+'-url').value=url;
					return id;
				}
				function kss_images_furl(id){ // set image url given it's id
					var url=document.getElementById('kss-image'+id+'-url').value;
					if((url=prompt('',url=='' ? 'http://' : url))!==null){
						document.getElementById('kss-image'+id+'-img').src=url=='' ? kss_up_defimg : url;
						document.getElementById('kss-image'+id+'-url').value=url;
					}
				}
				function kss_images_dele(id){ // remove image given it's id
					document.getElementById('kss-images').removeChild(document.getElementById('kss-image'+id));
				}
				function kss_images_movu(id){ // move image one place down [p,c] [c] c.append(p)
					var c=document.getElementById('kss-image'+id);
					var p=c.previousSibling;
					if(p && c)document.getElementById('kss-images').insertBefore(c,p);
				}
				function kss_images_movd(id){ // move image one place up [c,n] [c] n.append(c)
					var c=document.getElementById('kss-image'+id);
					var n=c.nextSibling;
					if(n && c)document.getElementById('kss-images').insertBefore(n,c);
				}
				function kss_thumb_update_start(file,id){
					if(id==-1){ // thumbnail uploader
						document.getElementById('kss_thum_prog').innerHTML='0%';
					}else if(id==-2){ // gallery uploader
					}else{ // gallery image uploader (use id)
						var eid='kss-image'+id;
						document.getElementById(eid+'-prg').style.display='block';
						document.getElementById(eid+'-per').style.width='0%';
					}
				}
				function kss_thumb_update_progrs(file,done,total,id){
					var percent=Math.round(total==0 ? 100 : (done/total*100));
					if(id==-1){ // thumbnail uploader
						document.getElementById('kss_thum_prog').innerHTML=percent+'%';
					}else if(id==-2){ // gallery uploader
					}else{ // gallery image uploader (use id)
						var eid='kss-image'+id;
						document.getElementById(eid+'-per').style.width=percent+'%';
					}
				}
				function kss_thumb_update_finish(file,data,ok,id){
					if(ok){ data=eval('('+data+')');
						if(id==-1){ // thumbnail uploader
							document.getElementById('kss_thum_prog').innerHTML='100%';
							document.getElementById('thumbnail-inp').value=data.url;
							document.getElementById('thumbnail-img').src=data.url;
							document.getElementById('thumbnail-fnm').title=data.url;
							document.getElementById('thumbnail-fnm').innerHTML=data.file;
							document.getElementById('thumbnail-fsz').innerHTML=data.size;
						}else if(id==-2){ // gallery uploader
						}else{ // gallery image uploader (use id)
							var eid='kss-image'+id;
							document.getElementById(eid+'-img').src=data.url;
							document.getElementById(eid+'-url').value=data.url;
							setTimeout(function(){ document.getElementById(eid+'-prg').style.display='none'; }, 500);
							document.getElementById(eid+'-per').style.width='100%';
						}
					}
				}
			</script><?php
		}
		
		public static function cells($id,$row,$colid,$cell){
			if($colid=='thumb')return (count($row->images) ? '<div align="center"><img src="'.$row->images[0].'" width="50">' : '').'</div>';
			if($colid=='name')return CmsHost::fire_action($id,Security::snohtml($cell),'edit');
			if($colid=='published')return '<div align="center">'.($cell ? '<img src="'.KSimpleShop::url().'img/ena16.png" alt="Published" />' : '<img src="'.KSimpleShop::url().'img/dis16.png" alt="Not Published" />').'</div>';
			return Security::snohtml($cell);
		}
		
		public static function actions($table,$action,$checked){
			switch($action){
				case 'new': case 'edit':
					$product=new kssProduct(count($checked)==0 ? 0 : (int)$checked[0]);
					$product->load();
					CmsHost::cms()->popup_begin(($product->id>0?'Update':'Add').' Product','',380,180,ClassMethod(__CLASS__,'actions'));
					?><input type="hidden" name="id" value="<?php echo $product->id; ?>"/>
					<table width="100%" cellspacing="10">
						<tr>
							<td width="100" valign="top"><label>Product Name:</label></td>
							<td valign="top"><input type="text" name="name" value="<?php echo $product->name; ?>" /> <small>e.g. Long sleeve shirt</small></td>
						</tr>
						<tr>
							<td width="100" valign="top"><label>Product Price:</label></td>
							<td valign="top"><input type="text" name="price" value="<?php echo $product->price; ?>" /> <small>e.g. 50.00</small></td>
						</tr>
						<tr>
							<td width="100" valign="top"><label>Product Description:</label></td>
							<td valign="top">
								<textarea name="description" style="width:450px; height:250px" cols="0" rows="0"><?php echo Security::snohtml($product->description); ?></textarea>
							</td>
						</tr>
						<tr>
							<td width="100" valign="top"><label>Product Images:</label></td>
							<td valign="top">
								<div id="kss-images" style="margin-bottom:8px;"></div>
								<input type="button" onclick="kss_images_add('');" value="Add Image" class="button-secondary action">
								<script type="text/javascript"><?php foreach($product->images as $url)echo 'kss_images_add('.@json_encode($url).');'.CRLF; ?></script>
							</td>
						</tr>
						<tr>
							<td width="100" valign="top"><label>Available Sizes:</label></td>
							<td valign="top"><?php
								$sizes = array(
									kssProducts::SIZE_XS,
									kssProducts::SIZE_S,
									kssProducts::SIZE_M,
									kssProducts::SIZE_L,
									kssProducts::SIZE_XL,
									kssProducts::SIZE_XXL
								);
								
								foreach($sizes as $i=>$size){ ?>
									<label for="size-<?php echo $i; ?>">
										<input type="checkbox" name="size[]" value="<?php echo $size; ?>"
											   id="size-<?php echo $i; ?>" <?php if(in_array($size, $product->sizes)) echo 'checked="checked"'; ?> />
										<?php echo $size; ?>
									</label>&nbsp;&nbsp;&nbsp;
								<?php }
							?></td>
						</tr>
						<tr>
							<td width="100" valign="top"><label>Published:</label></td>
							<td valign="top">
								<input type="checkbox" name="published" value="published" <?php if($product->published) echo 'checked="checked"'; ?> />
							</td>
						</tr>
					</table>
					&nbsp;<?php
					CmsHost::cms()->popup_button(($product->id>0?'Update':'Add').' Product','save','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'save':
					$product = new kssProduct((int)$_REQUEST['id']);
					$product->load();
					
					CmsHost::cms()->popup_begin(($product->id>0?'Update':'Add').' Product','',380,180,ClassMethod(__CLASS__,'actions'));
					$product->name = $_REQUEST['name'];
					$product->price = (float)$_REQUEST['price'];
					$product->description = $_REQUEST['description'];
					
					$product->images = array();
					if(isset($_REQUEST['images'])) $product->images = $_REQUEST['images'];
					
					$product->sizes = array();
					if(isset($_REQUEST['size'])) $product->sizes = $_REQUEST['size'];
					
					$product->published = isset($_REQUEST['published']) && $_REQUEST['published'] == 'published';
					
					
					if($product->save()){
						if((int)$_REQUEST['id']>0){
							?><p>The product has been updated!</p><?php
						}else{
							?><p>The new product has been added!</p><?php
						}
					}else{
						?><p>Fatal: Could not save changes to database.</p><?php
					}
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
			}
			die;
		}
	}
	// register ajax/api calls
	Ajax::register('kssManageProducts','actions',CmsHost::fsig_action());

?>