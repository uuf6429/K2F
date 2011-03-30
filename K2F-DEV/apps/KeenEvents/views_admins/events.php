<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php');

	class keEventsView {
		public static function manage(){
			CmsHost::cms()->adminlist_begin(KeenEvents::icons('main'),'Manage Events','allowadd',array(),ClassMethod(__CLASS__,'actions'));
			$event=new keEvents();
			$event->load();
			CmsHost::cms()->adminlist(
				$event->rows,'id',
				array('title'=>'Event Name','venue'=>'Event Venue','time'=>'Time and Duration','repeats'=>'Repetitions','marker'=>'Event Type'),
				array('multiselect','allowadd'),
				array('Edit'),
				ClassMethod(__CLASS__,'cells'),
				'No categories yet',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
		}
		public static function actions($table,$action,$checked){
			switch($action){
				case 'new': case 'edit':
					$event=new keEvent(count($checked)==0 ? 0 : (int)$checked[0]); $event->load();
					if($action=='new'){
						// a small hotfix to use reasonable dates if new
						$event->date_start=time();
						$event->date_end=time();
					}
					CmsHost::cms()->popup_begin(($event->id>0?'Update':'Add').' Event','',380,180,ClassMethod(__CLASS__,'actions'));
					?><input type="hidden" name="id" value="<?php echo $event->id; ?>"/><p>
						<span style="width:90px;display:inline-block;">Event Name:</span>
						<input type="text" name="ke-title" value="<?php echo Security::snohtml($event->title); ?>"/>
						<span style="width:90px;display:inline-block;margin-left:24px;">Event Venue:</span>
						<input type="text" name="ke-venue" value="<?php echo Security::snohtml($event->venue); ?>"/>
					</p><input type="hidden" name="ke-marker" id="ke-marker" value="<?php echo $event->marker; ?>"/>
					<?php if(Applications::installed('KeenMaps')){ ?><p>
						<span style="width:90px;display:inline-block;">Event Type:</span>
						<label for="ke-global" style="margin-right:24px;">
							<input type="radio" name="ke-type" id="ke-global"<?php if($event->national())echo ' checked="checked"'; ?> onclick="if(checked){ document.getElementById('ke-markers').disabled=true; document.getElementById('ke-marker').value='0'; }"/>
							National
						</label>
						<label for="ke-local">
							<input type="radio" name="ke-type" id="ke-local"<?php if(!$event->national())echo ' checked="checked"'; ?> onclick="if(checked){document.getElementById('ke-markers').disabled=false; document.getElementById('ke-marker').value=document.getElementById('ke-markers').value; }"/>
							Local:
						</label>
						<select id="ke-markers" onchange="document.getElementById('ke-marker').value=value;"<?php if($event->national())echo ' disabled="disabled"'; ?> onmousedown="document.getElementById('ke-local').checked=true;"><?php
							$marker=new kmMarker();
							$markers=new kmMarkers();
							$markers->load();
							foreach($markers->rows as $marker)
								echo '<option value="'.$marker->id.'">'.Security::snohtml($marker->title).'</option>';
						?></select>
					</p><?php } ?><p>
						<span style="width:90px;display:inline-block;">Event Starts:</span>
						<input type="text" name="ke-date_start" id="ke-date_start" value="<?php echo date('j M Y',$event->date_start); ?>" onclick="kShowCalendar(this);" onkeyup="kDateChanged();" style="text-transform:uppercase; width:100px; height:28px; vertical-align:bottom;"/>
						<select name="ke-date_start_h" id="ke-date_start_h"><?php $d=(int)date('H',$event->date_start); for($i=0; $i<24; $i++)echo '<option value="'.$i.'"'.($i==$d ? ' selected="selected"' : '').'>'.str_pad($i,2,'0',STR_PAD_LEFT).'</option>'; ?></select>
						<select name="ke-date_start_m" id="ke-date_start_m"><?php $d=(int)date('i',$event->date_start); for($i=0; $i<60; $i++)echo '<option value="'.$i.'"'.($i==$d ? ' selected="selected"' : '').'>'.str_pad($i,2,'0',STR_PAD_LEFT).'</option>'; ?></select>
						<br/>
						<span style="width:90px;display:inline-block;">Event Ends:</span>
						<input type="text" name="ke-date_end"   id="ke-date_end"   value="<?php echo date('j M Y',$event->date_end  ); ?>" onclick="kShowCalendar(this);" onkeyup="kDateChanged();" style="text-transform:uppercase; width:100px; height:28px; vertical-align:bottom;"/>
						<select name="ke-date_end_h" id="ke-date_end_h"><?php $d=(int)date('H',$event->date_end); for($i=0; $i<24; $i++)echo '<option value="'.$i.'"'.($i==$d ? ' selected="selected"' : '').'>'.str_pad($i,2,'0',STR_PAD_LEFT).'</option>'; ?></select>
						<select name="ke-date_end_m" id="ke-date_end_m"><?php $d=(int)date('i',$event->date_end); for($i=0; $i<60; $i++)echo '<option value="'.$i.'"'.($i==$d ? ' selected="selected"' : '').'>'.str_pad($i,2,'0',STR_PAD_LEFT).'</option>'; ?></select>
						<label style="display:inline-block; margin-left:32px; margin-top:-32px;" for="ke-date_day"><?php
							$isday=round(($event->date_end-$event->date_start)/10)==8640; // day
							?><input type="checkbox" name="ke-date_day" id="ke-date_day" value="yes"<?php echo $isday; ?> onclick="if(checked)kDateDay();"/>
							Whole Day
						</label>
						<script type="text/javascript" src="<?php echo KeenEvents::url(); ?>js/phpjs_date.min.js"></script>
						<script type="text/javascript" src="<?php echo KeenEvents::url(); ?>js/jsDatePick.min.1.3.js"></script>
						<script type="text/javascript">
							function kShowCalendar(elem,d,m,y){
								// attach css to document if not there yet
								if(typeof window.kecal=='undefined'){
									var css=document.createElement('link');
									css.setAttribute('rel','stylesheet');
									css.setAttribute('type','text/css');
									css.setAttribute('href','<?php echo KeenEvents::url(); ?>css/jsDatePick_ltr.css');
									document.getElementsByTagName('head')[0].appendChild(css);
									window.kecal=true;
								}
								// attach calendar to element
								g_jsDatePickImagePath='<?php echo KeenEvents::url(); ?>img/';
								new JsDatePick({
									useMode:2,
									target:elem.id,
									dateFormat:'%j %M %Y',
									selectedDate:{day:d,month:m,year:y},
									cellColorScheme:'ocean_blue'
								});
								// remove onclick handler from element
								elem.setAttribute('onclick','');
								// trigger calendar popup
								elem.focus(); elem.focus(); /* fix weird bug */
							}
							function kDateChanged(){
								var s=strtotime(document.getElementById('ke-date_start').value+' '+document.getElementById('ke-date_start_h').value+':'+document.getElementById('ke-date_start_m').value);
								var e=strtotime(document.getElementById('ke-date_end').value+' '+document.getElementById('ke-date_end_h').value+':'+document.getElementById('ke-date_end_m').value);
								document.getElementById('ke-date_day').checked=(Math.round((s-e)/10)==8640);
							}
							function kDateDay(){
								document.getElementById('ke-date_day').checked=true;
								var s=strtotime(document.getElementById('ke-date_start').value+' '+document.getElementById('ke-date_start_h').value+':'+document.getElementById('ke-date_start_m').value);
								s=s+86399; // a day minus a second
								document.getElementById('ke-date_end').value=date('j M Y',s);
								document.getElementById('ke-date_end_h').value=date('H',s)*1;
								document.getElementById('ke-date_end_m').value=date('i',s)*1;
							}
						</script>
					</p><p>
						<span style="width:130px;display:inline-block;">Event Repetitions:</span>
						Event will take place <select name="ke-repeats">
							<option value="">just once.</option>
							<option value="year">every year.</option>
							<option value="month">every month.</option>
						</select>
					</p>
					<div>Short Description:</div><?php
					CmsHost::cms()->wysiwyg('ke-desc_short',$event->desc_short,630,210);
					?><br/><div>Longer Description:</div><?php
					CmsHost::cms()->wysiwyg('ke-desc_long',$event->desc_long,630,420);
					?><br/>&nbsp;<?php
					// rest of popup
					CmsHost::cms()->popup_button(($event->id>0?'Update':'Add').' Event','do_save','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'do_save':
					$event=new keEvent((int)$_REQUEST['id']); $event->load();
					CmsHost::cms()->popup_begin(($event->id>0?'Update':'Add').' Event','',380,180,ClassMethod(__CLASS__,'actions'));
					$event->title=$_REQUEST['ke-title'];
					$event->venue=$_REQUEST['ke-venue'];
					$event->marker=(int)$_REQUEST['ke-marker'];
					$event->date_start=strtotime($_REQUEST['ke-date_start'].' '.$_REQUEST['ke-date_start_h'].':'.$_REQUEST['ke-date_start_m']);
					$event->date_end=strtotime($_REQUEST['ke-date_end'].' '.$_REQUEST['ke-date_end_h'].':'.$_REQUEST['ke-date_end_m']);
					$event->repeats=strip_tags($_REQUEST['ke-repeats']);
					$event->desc_short=$_REQUEST['ke-desc_short'];
					$event->desc_long=$_REQUEST['ke-desc_long'];
					if($event->save()){
						if((int)$_REQUEST['id']>0){
							?><p>The event has been updated!</p><?php
						}else{
							?><p>The new event has been added!</p><?php
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
		public static function cells($id,$row,$colid,$cell){
			if($colid=='title')return CmsHost::fire_action($id,Security::snohtml($cell),'edit');
			if($colid=='time'){
				$difference=abs($row->date_end-$row->date_start);
				$periods=array('sec','min','hour','day','week','month','years','decade');
				$lengths=array('60','60','24','7','4.35','12','10');
				for($j = 0; $difference >= $lengths[$j]; $j++)
					$difference /= $lengths[$j];
				$difference = round($difference);
				if($difference != 1) $periods[$j].= 's';
				return date('d.m.Y',$row->date_start).' &mdash; '.date('d.m.Y',$row->date_end).' ('.$difference.' '.$periods[$j].')';
			}
			if($colid=='repeats')return $row->repeats=='' ? 'No Repetitions' : 'Repeated every <i>'.Security::snohtml($cell).'</i>';
			if($colid=='marker')return $row->national() ? 'National Event' : ($row->marker() ? 'Local Event at <i>'.Security::snohtml($row->marker()->title).'</i>' : '<span style="color:#E00;">Broken Event</span>');
			return Security::snohtml($cell);
		}
	}
	// register ajax/api calls
	Ajax::register('keEventsView','actions',CmsHost::fsig_action());

?>