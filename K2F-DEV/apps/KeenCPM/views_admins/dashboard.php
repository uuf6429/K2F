<?php defined('K2F') or die;

	uses('core/events.php','core/apps.php','core/security.php');

	function wp_frm_begin($title){
		?><div class="postbox " id="dashboard_recent_comments"><div title="Click to toggle" class="handlediv">
		<br></div><h3 style="cursor:default;"><span><?php echo $title; ?></span></h3><div class="inside"><p><?php
	}
	function wp_frm_end(){
		?></p></div></div><?php
	}

	class kcmViewDashboard {
		public static function _kcmHideModelWarning($mid){
			$model=new kcmModel($mid);
			if(!$model->load())return array('success'=>false,'reason'=>'Failed loading model #'.$mid.'.');
			$model->field('Ignore Stock','on');
			if(!$model->save())return array('success'=>false,'reason'=>'Failed updating model #'.$mid.'.');
			return array('success'=>true);
		}
		public static function _kcmHideServiceWarning($eid){
			$event=new kcmEvent($eid);
			if(!$event->load())return array('success'=>false,'reason'=>'Failed loading event #'.$eid.'.');
			$event->field('Ignore Servicing','on');
			if(!$event->save())return array('success'=>false,'reason'=>'Failed updating event #'.$eid.'.');
			return array('success'=>true);
		}
		public static function on_head(){
			?><style type="text/css">
				.kcm64btn {
					padding: 0 16px;
				}
				.kcm64btn a {
					background:url('<?php echo KeenCPM::url(); ?>img/icon-btnbg-64.png') no-repeat top;
					display: inline-block;
					width: 64px;
					height: 64px;
					/* opacity */
					-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=60)";
					filter: alpha(opacity=60);
					-moz-opacity:       0.60;
					-khtml-opacity:     0.60;
					opacity:            0.60;
				}
				.kcm64btn a:hover {
					background:url('<?php echo KeenCPM::url(); ?>img/icon-btnbg-64.png') no-repeat bottom;
					/* opacity */
					-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=100)";
					filter: alpha(opacity=100);
					-moz-opacity:       1;
					-khtml-opacity:     1;
					opacity:            1;
				}
			</style><script type="text/javascript">
				<?php if(isset($_REQUEST['auto'])){ ?>
					jQuery(document).ready(function(){
						window.setTimeout(function(){<?php
							if(!isset($_REQUEST['tbl']))$_REQUEST['tbl']=1;
							if(isset($_REQUEST['ids']) && is_array($_REQUEST['ids']))
								foreach($_REQUEST['ids'] as $id)
									echo TAB.'jQuery("#k2frow1-'.(int)$id.' input:checkbox").attr("checked",true);'.CRLF;
							?>k2f_apply(<?php echo @json_encode($_REQUEST['auto']); ?>,'k2f-al-<?php echo (int)$_REQUEST['tbl']; ?>');
						},200);
					});
				<?php } ?>
			</script><?php
		}
		public static function dashboard(){
			CmsHost::cms()->adminlist_begin(KeenCPM::icons('main'),'Dashboard');
			$dtfmt=CmsHost::cms()->config_get('date-format');
			// get stock warnings
			$quota=(int)CmsHost::cms()->config_get('kcm-stock-warn');
			$model=new kcmModel(); $models=new kcmModels(); $models->load(); $models=$models->rows;
			foreach($models as $i=>$model) // decide whether to show warning for model or not
				if($model->instock()>$quota || ($ign=trim(strtolower($model->field('Ignore Stock')))) && ($ign=='yes' || $ign=='on'))
					unset($models[$i]);
			// get service warnings
			$serviceStart=strtotime(CmsHost::cms()->config_get('kcm-notice-min'));
			$serviceEnd=strtotime(CmsHost::cms()->config_get('kcm-notice-max'));
			$services=kcmServices::load(); $service=new kcmService();
			foreach($services as $i=>$service) // decide whether to show warning for model or not
				if(($ign=trim(strtolower($service->lastEvent()->field('Ignore Servicing')))) && ($ign=='yes' || $ign=='on'))
					unset($services[$i]);
			// display dashboard
			if(CFG::get('CMS_HOST')=='wordpress'){
				//// WORDPRESS BEGIN ////
				?><script type="text/javascript">
					function kcmHideModelWarning(mid){
						if(confirm('Are you sure you don\'t want to see any more warnings?\n(you can enable them back by setting "ignore stock" to "off" in model fields)')){
							var url=location.href+'&mid='+mid+<?php echo @json_encode(Ajax::url(__CLASS__,'_kcmHideModelWarning','&')); ?>;
							jQuery.getJSON(url,function(result){
								if(result.success){
									jQuery('#stockWarn'+mid).remove();
									if(jQuery('#stockWarnings tbody tr').length==0)
										jQuery('#stockWarnings tbody').append('<tr><td align="center" colspan="3">&nbsp;<br/>Amount of Stock seems ok.<br/>&nbsp;</td></tr>');
								}else{
									alert('Request aborted due to a fatal server error:\n'+result.reason);
								}
							});
						}
					}
					function kcmHideServiceWarning(eid){
						if(confirm('Are you sure you don\'t want to see any more warnings?\n(you can enable them back by setting "ignore servicing" to "off" in event fields)')){
							var url=location.href+'&eid='+eid+<?php echo @json_encode(Ajax::url(__CLASS__,'_kcmHideServiceWarning','&')); ?>;
							jQuery.getJSON(url,function(result){
								if(result.success){
									jQuery('#serviceWarn'+eid).remove();
									if(jQuery('#serviceWarnings tbody tr').length==0)
										jQuery('#serviceWarnings tbody').append('<tr><td align="center" colspan="5">&nbsp;<br/>No immediate servicing required.<br/>&nbsp;</td></tr>');
								}else{
									alert('Request aborted due to a fatal server error:\n'+result.reason);
								}
							});
						}
					}
				</script><div id="dashboard-widgets-wrap">
					<div id="dashboard-widgets" class="metabox-holder">
						<table width="100%"><tr><td width="49%" valign="top">
							<?php wp_frm_begin('Common Shortcuts'); ?>
								<div class="kcm64btn">
									<a href="?page=k2f_1&auto=new" title="Add Client"><img src="<?php echo KeenCPM::url(); ?>img/icon-client-add-64.png" alt="Add Client" width="64" height="64"/></a>
									<a href="?page=k2f_3&auto=new" title="Add Model"><img src="<?php echo KeenCPM::url(); ?>img/icon-model-add-64.png" alt="Add Model" width="64" height="64"/></a>
									<a href="?page=k2f_4&auto=new" title="Add Stock"><img src="<?php echo KeenCPM::url(); ?>img/icon-stock-add-64.png" alt="Add Stock" width="64" height="64"/></a>
									<a href="?page=k2f_6&auto=new" title="Add Event"><img src="<?php echo KeenCPM::url(); ?>img/icon-event-add-64.png" alt="Add Event" width="64" height="64"/></a>
								</div>&nbsp;
							<?php wp_frm_end(); ?>
						</td><td><!----></td><td width="50%" valign="top">
							<?php wp_frm_begin('Stock Warnings'); ?>
							<div style="padding:0 12px;">
								<table width="100%" cellpadding="0" cellspacing="4" id="stockWarnings">
									<thead><tr>
										<td><b>Model Category &amp; Name</b></td>
										<td><b>Currently in Stock</b></td>
										<td><b>Stock Actions</b></td>
									</tr></thead><tbody><?php
										foreach($models as $model){
											$instk=max($model->instock(),0);
											$color=kcmPercentToHealth(kcmRangeToPercent(0,$quota+2,$instk));
											?><tr id="stockWarn<?php echo $model->id; ?>">
												<td>
													<?php echo Security::snohtml($model->type()->name).' &raquo; '.Security::snohtml($model->name); ?>
												</td><td>
													<span style="color:<?php echo $color; ?>;"><?php echo $instk; ?> item(s)</span>
												</td><td>
													<a href="?page=k2f_4&auto=new" target="_blank">Add Stock</a> | <a href="javascript:kcmHideModelWarning(<?php echo $model->id; ?>);">Hide Warning</a>
												</td>
											</tr><?php
										}
										if(!count($models))
											echo '<tr><td align="center" colspan="3">&nbsp;<br/>Amount of Stock seems ok.<br/>&nbsp;</td></tr>';
									?></tbody>
								</table>
							</div>&nbsp;
							<?php wp_frm_end(); ?>
						</td></tr></table>
						<?php wp_frm_begin('Overdue / Upcoming Servicing'); ?>
							<div style="padding: 0pt 12px;">
								<table width="100%" cellpadding="0" cellspacing="4" id="serviceWarnings">
									<thead><tr>
										<td><b>Service Date</b></td>
										<td><b>Client Details</b></td>
										<td><b>Relevant Product</b></td>
										<td><b>Servicing History</b></td>
										<td><b>Servicing Actions</b></td>
									</tr></thead><tbody><?php
										$when=array(-2=>'%d days remaining',-1=>'yesterday',0=>'today',1=>'tomorrow',2=>'%d days due');
										$events=new kcmEvents(); $event=new kcmEvent();
										foreach($services as $service){
											$color=kcmPercentToHealth(kcmRangeToPercent($serviceStart,$serviceEnd,$service->date));
											$next=$service->lastEvent()->nextService(); // date of next event
											?><tr id="serviceWarn<?php echo $service->lastEvent()->id; ?>">
												<td>
													<a style="color:<?php echo $color; ?>;" href="?page=k2f_7&auto=edit&ids[]=<?php echo (int)$service->event; ?>"><?php
														$dif=round((time()-$next)/(60*60*24));
														echo date($dtfmt=='' ? 'd M Y' : $dtfmt,$next).' ';
														echo '('.str_replace('%d',abs($dif),$when[max(min($dif,2),-2)]).')';
													?></a>
												</td><td>
													<?php echo Security::snohtml($service->client()->name.' '.$service->client()->surname); ?>
												</td><td>
													<?php echo Security::snohtml($service->model()->name); ?>
												</td><td><?php
													$events->load('`id`='.$service->event.' OR `sid`='.$service->event.' ORDER BY `time` ASC');
													$links=array();
													foreach($events->rows as $event)
														$links[]='<a href="?page=k2f_10&amp;auto=edit&amp;ids[]='.$event->id.'" target="_blank">'.date('d M Y',$event->time).'</a>';
													echo implode(', ',$links);
												?></td><td>
													<a href="?page=k2f_10&amp;auto=new&amp;sid=<?php echo $service->event; ?>" target="_blank">Serviced</a> | <a href="javascript:kcmHideServiceWarning(<?php echo $service->event; ?>);">Hide Warning</a>
												</td>
											</tr><?php
										}
										if(!count($services))
											echo '<tr><td align="center" colspan="5">&nbsp;<br/>No immediate servicing required.<br/>&nbsp;</td></tr>';
									?></tbody>
								</table>
							</div>&nbsp;
						<?php wp_frm_end(); ?>
					</div>
				</div><?php
				//// WORDPRESS END ////
			}else{
				echo 'Unsupported CMS.';
			}
			CmsHost::cms()->adminlist_end();
		}
	}
	Events::add('on_head',ClassMethod('kcmViewDashboard','on_head'));
	// register ajax/api calls
	Ajax::register('kcmViewDashboard','_kcmHideModelWarning',array('mid'=>'integer'));
	Ajax::register('kcmViewDashboard','_kcmHideServiceWarning',array('eid'=>'integer'));

?>