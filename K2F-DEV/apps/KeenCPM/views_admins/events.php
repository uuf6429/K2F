<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php','core/ajax.php','core/events.php','exts/wkhtmltox.php','core/email.php');

	class kcmViewEvents {
		/**
		 * Generates the HTML for an event's document.
		 * @param integer $event_id The event's database id.
		 * @return string The generated HTML.
		 */
		public static function _kcmGenerateHtml($event_id){
			$header='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/></head><body>';
			$footer='</body></html>';
			$event=new kcmEvent((int)$event_id);
			if($event->load()){
				$doc=new kcmDocument($event->did);
				if($doc->load()){
					$html=$doc->html;
					// settings category model client event
					$flds=CmsHost::cms()->config_get('kcm-fields');
					$flds=$flds=='' ? array() : (array)@json_decode($flds);
					$flds=(object)array('fields'=>$flds);
					$flds=kcmGetFieldValues($flds,$event->model()->type(),$event->model(),$event->client(),$event);
					foreach($flds as $fld=>$dat)$html=str_replace('{'.$fld.'}',Security::snohtml($dat),$html);
					return $header.$html.$footer;
				}else return '<b>Fatal error: Document template could not be read from persistent database storage.</b>';
			}else return '<b>Fatal error: Event logistics could not be read from persistent database storage.</b>';
		}
		public static function manage(){
			?><script type="text/javascript">
				function kcmFieldsCheck(){
					var n={}; var e=jQuery('input[name="fields\\[\\]"]');
					e.each(function(){
						var s=jQuery(this).val().toLowerCase();
						if(typeof n[s]=='undefined')n[s]=0;
						n[s]++;
					});
					var w=false;
					e.each(function(){
						var s=jQuery(this).val().toLowerCase();
						jQuery(this).css('border-color',n[s]>1 ? 'red' : '');
						if(n[s]>1)w=true;
					});
					w ? jQuery('#kcmFldNotUniqueWarn').show() : jQuery('#kcmFldNotUniqueWarn').hide();
				}
				function kcmDatePickerRefresh(){
					var y=parseInt(jQuery('#year').val());
					var m=parseInt(jQuery('#month').val());
					var d=parseInt('0'+jQuery('#day').val());
					var n=new Date(y,m,0); // small trick to get days in month
					var s='<option>Day</option>';
					for(var i=1; i<=n.getDate(); i++){
						var l=(i==d) || (d!=0 && i==n.getDate() && d>n.getDate()); // i exists or i truncated to last
						var i2 = i<10 ? '0'+i : i; // pad i when less then 10
						s+='<option value="'+i+'"'+(l ? ' selected' : '')+'>'+i2+'</option>';
					}
					jQuery('#day').html(s);
				}
			</script><?php
			CmsHost::cms()->adminlist_begin(KeenCPM::icons('events'),'Manage Events','allowadd',array(),ClassMethod(__CLASS__,'actions'));
			$events=new kcmEvents();
			$events->load();
			CmsHost::cms()->adminlist(
				$events->rows,'id',
				array('time'=>'Date & Time','paid'=>'Paid','type'=>'Type','cid'=>'Client','mid'=>'Model','notes'=>'Notes','document'=>'Document'),
				array('multiselect','allowadd'),
				array('Edit','Archive','Delete'),
				ClassMethod(__CLASS__,'cells'),
				'No existing events found',
				ClassMethod(__CLASS__,'actions')
			);
			CmsHost::cms()->adminlist_end();
		}
		public static function _kcmCatFldAdd($name='',$value=''){
			static $counter=0; $counter++;
			return '<div id="kcmCatFldAdd'.$counter.'">'
					.'<input type="text" name="fields[]" value="'.Security::snohtml($name).'" style="width:148px;" onkeyup="kcmFieldsCheck();"/>'
					.'<input type="text" name="values[]" value="'.Security::snohtml($value).'" style="width:148px;" onkeyup="kcmFieldsCheck();"/>'
					.'<a href="javascript:;" onclick="jQuery(\'#kcmCatFldAdd'.$counter.'\').remove();kcmFieldsCheck();">'
						.'<small>delete</small></a>'
				.'</div>';
		}
		public static function actions($table,$action,$checked){
			if(!function_exists('kcm_gen_pdf')){
				function kcm_gen_pdf($event_id){
					$pdf=new WKPDF();
					$pdf->set_source(WKPDF::SRC_HTML,kcmViewEvents::_kcmGenerateHtml($event_id));
					$pdf->set_margins('0px','0px','0px','0px');
					$pdf->render();
					return $pdf;
				}
			}
			switch($action){
				case 'doc_preview':
					CmsHost::cms()->popup_begin('Document Preview','');
					?><iframe frameborder="0" width="630" height="330" src="?<?php
						echo Security::snohtml($_SERVER['QUERY_STRING']);
						?>&k2f-action=do_doc_preview"></iframe><?php
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'do_doc_preview':
					$pdf=kcm_gen_pdf($checked[0]);
					$pdf->output(WKPDF::OUT_EMBED,'document.pdf');
					break;
				case 'doc_download':
					CmsHost::cms()->popup_begin('Download Document','');
					$url='?'.Security::snohtml($_SERVER['QUERY_STRING'].'&k2f-action=do_doc_download');
					?><p>
						Your download is ready, please <a href="<?php echo $url; ?>">click here</a> if the download doesn't start.
					</p> <iframe frameborder="0" width="1" height="1" src="<?php echo $url; ?>"></iframe><?php
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'do_doc_download':
					$event=new kcmEvent((int)$checked[0]);
					$event->load();
					$client=$event->client();
					$pdf=kcm_gen_pdf($checked[0]);
					switch($event->type){
						case kcmEvents::TYPE_OTHER:   $typ='Other';   break;
						case kcmEvents::TYPE_SALE:    $typ='Sale';    break;
						case kcmEvents::TYPE_INSTALL: $typ='Install'; break;
						case kcmEvents::TYPE_SERVICE: $typ='Service'; break;
						default:                      $typ='Unknown';
					}
					$pdf->output(WKPDF::OUT_DOWNLOAD,Security::filename($event->id.' ['.date('dmy',$event->time).'] - '.$client->name.' '.$client->surname).' ('.$typ.').pdf');
					die;
				case 'doc_email':
					$event=new kcmEvent(count($checked)==0 ? 0 : (int)$checked[0]); $event->load();
					$client=$event->client();
					CmsHost::cms()->popup_begin('Send Document by Email','Note: The document will be attached to the email.',380,180,ClassMethod(__CLASS__,'actions'));
					$ns=Security::snohtml($client->name.' '.$client->surname);
					if($ns==' ')$ns='<i>FATAL ERROR</i>'; // easter egg ;)
					switch($event->type){
						case kcmEvents::TYPE_SALE:    $sb='re: Your Purchase of "'.$event->model()->name.'"'; break;
						case kcmEvents::TYPE_INSTALL: $sb='re: Installation of "'.$event->model()->name.'"';  break;
						case kcmEvents::TYPE_SERVICE: $sb='re: Servicing of "'.$event->model()->name.'"';     break;
						default: $sb='';
					}
					?><input type="hidden" name="id" value="<?php echo $event->id; ?>"/>
					<p>
						<span style="width:72px;display:inline-block;">From:</span>
						<input type="text" name="from" id="from" style="vertical-align:top;width:482px;" value="<?php
							echo Security::snohtml('noreply@'.$_SERVER['SERVER_NAME']); ?>"/>
					</p><p>
						<span style="width:72px;display:inline-block;">To:</span>
						<input type="text" name="to" id="to" style="vertical-align:top;width:482px;" value="<?php
							echo Security::snohtml($client->email); ?>"/>
					</p><p>
						<span style="width:72px;display:inline-block;">Subject:</span>
						<input type="text" name="subject" id="subject" style="vertical-align:top;width:482px;" value="<?php
							echo Security::snohtml($sb); ?>"/>
					</p><p>
						<span style="width:72px;display:inline-block;">Message:</span>
						<textarea id="message" name="message" cols="" rows="7" style="vertical-align:top;width:482px;"><?php
							echo Security::snohtml(
								'Dear '.strip_tags($ns).','.CRLF.CRLF.'Please find your document attached. Don\'t hesitate to '.
								'ask us if you have any further queries.'.CRLF.CRLF.'Sincerely,'.CRLF.$_SERVER['SERVER_NAME']
							);
						?></textarea>
					</p><?php
					CmsHost::cms()->popup_button('Send Document','do_doc_email','primary');
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'do_doc_email':
					$pdf=kcm_gen_pdf($checked[0]);
					$file='document.pdf';
					$pdf=$pdf->output(WKPDF::OUT_RETURN,$file);
					$from=$_REQUEST['from'];
					$to=$_REQUEST['to'];
					$subject=$_REQUEST['subject'];
					$message=nl2br($_REQUEST['message']);
					$email=new Email($from,$to,$from,$subject,$message,array($file=>$pdf));
					CmsHost::cms()->popup_begin('Send Document by Email','');
					if(($r=$email->send())==Email::ST_SUCCESS){
						echo 'Email has been sent successfully!';
						CmsHost::cms()->popup_button('Close','close','button');
					}else{
						echo 'Email could not be sent: ';
						switch($r){
							case Email::ST_FAIL_GENERIC: echo 'A generic email failure occured.'; break;
							default: echo 'An unknown error occured.'; break;
						}
						//CmsHost::cms()->popup_button('Try Again','','button');
						//CmsHost::cms()->popup_button('Cancel','cancel','link');
						CmsHost::cms()->popup_button('Close','close','button'); // until I figure out a good interface to reload() page
					}
					CmsHost::cms()->popup_end();
					break;
				case 'new': case 'edit': case 'edit_pub_notes': case 'edit_prv_notes':
					$event=new kcmEvent(count($checked)==0 ? 0 : (int)$checked[0]); $event->load();
					$docs=new kcmDocuments(); $docs->load(); $ar=array(); $doc=new kcmDocument();
					foreach($docs->rows as $doc)
						$ar[$doc->type]=array();
					foreach($docs->rows as $doc)
						$ar[$doc->type][$doc->id]=Security::snohtml($doc->name);
					$first=array_values($ar); $first=array_shift($first);
					$docs=isset($ar[kcmEvents::eventToDocType($event->type)]) ? $ar[kcmEvents::eventToDocType($event->type)] : $first;
					CmsHost::cms()->popup_begin(($event->id>0?'Update':'Add').' Event','',380,180,ClassMethod(__CLASS__,'actions'));
					?><input type="hidden" name="id" value="<?php echo $event->id; ?>"/><p>
						<span style="width:70px;display:inline-block;">Type:</span>
						<select id="type" name="type" style="vertical-align:middle;width:210px;margin-right:48px;" onchange="kcmRefreshSale();"><?php
							echo '<option value="'.kcmEvents::TYPE_SALE    .'"'.($event->type==kcmEvents::TYPE_SALE    ?' selected':'').'>Sale</option>';
							echo '<option value="'.kcmEvents::TYPE_INSTALL .'"'.($event->type==kcmEvents::TYPE_INSTALL ?' selected':'').'>Install</option>';
							echo '<option value="'.kcmEvents::TYPE_SERVICE .'"'.($event->type==kcmEvents::TYPE_SERVICE ?' selected':'').'>Service</option>';
							echo '<option value="'.kcmEvents::TYPE_OTHER   .'"'.($event->type==kcmEvents::TYPE_OTHER   ?' selected':'').'>Other</option>';
						?></select>
						<span style="width:70px;display:inline-block;">Client:</span>
						<select id="client" name="client" style="vertical-align:middle;width:210px;" onchange="kcmRefreshSale();"><?php
							$clients=new kcmClients(); $clients->load(); $client=new kcmModel();
							foreach($clients->rows as $client)
								echo '<option value="'.(int)$client->id.'"'.($event->cid==$client->id?' selected':'').'>'
									.Security::snohtml($client->name.' '.$client->surname).'</option>';
						?></select>
					</p><p id="sale-p"<?php if($event->type==kcmEvents::TYPE_SALE)echo ' style="display:none;"'; ?>><?php
						$events=new kcmEvents(); $events->load('`type`='.kcmEvents::TYPE_SALE); $ev=new kcmEvent();
						function _kcmSaleSelect($events,$cid=null,$sid=null){
							$html='';
							foreach($events->rows as $ev)if(!$cid || $ev->cid==$cid)
								$html.='<option value="'.(int)$ev->id.'"'.($sid==$ev->id?' selected':'').'>'
									.Security::snohtml($ev->client()->name.' '.$ev->client()->name.' ('.date('j M Y',$ev->time).')').'</option>';
							return $html;
						}
						?><span style="width:70px;display:inline-block;">Sale:</span>
						<select id="sale" name="sale" style="vertical-align:middle;width:210px;margin-right:48px;" onchange="kcmRefreshEvent();"><?php
							echo _kcmSaleSelect($events,$event->cid>0 ? $event->cid : null,$event->sid);
						?></select><script type="text/javascript">
							var kcmSales=<?php
								$sales=array(0=>_kcmSaleSelect($events));
								foreach($events->rows as $ev){
									if(!isset($sales[$ev->cid]))
										$sales[$ev->cid]=_kcmSaleSelect($events,$ev->cid);
									$old[$ev->id]=$ev;
								}
								echo @json_encode(array('options'=>$sales,'sale'=>$old));
							?>;
							function kcmRefreshSale(){
								if(document.getElementById('type').value!=<?php echo kcmEvents::TYPE_SALE; ?>){
									document.getElementById('sale-p').style.display='block';
									var sid=document.getElementById('sale').value;
									document.getElementById('sale').innerHTML=kcmSales.options[document.getElementById('client').value];
									document.getElementById('sale').value=sid;
								}else{
									document.getElementById('sale-p').style.display='none';
								}
								kcmRefreshEvent();
							}
							function kcmRefreshEvent(){
								<?php if($action=='new'){ ?>
									var id=document.getElementById('sale').value;
									document.getElementById('model').value=kcmSales.sale[id].mid;
									document.getElementById('paid').checked=kcmSales.sale[id].paid;
									document.getElementById('pub_notes').value=kcmSales.sale[id].pub_notes;
									document.getElementById('prv_notes').value=kcmSales.sale[id].prv_notes;
								<?php } ?>
							}
							<?php if($action=='new' && isset($_REQUEST['sid'])){ ?>
								document.getElementById('type').value=<?php echo kcmEvents::TYPE_SERVICE; ?>;
								document.getElementById('sale').value=<?php echo (int)$_REQUEST['sid']; ?>;
								kcmRefreshSale();
							<?php } ?>
						</script>
					</p><p>
						<span style="width:70px;display:inline-block;">Template:</span>
						<select id="document" name="document" style="vertical-align:middle;width:210px;margin-right:48px;">
							<option value="0">No Document</option><?php
							$docs=new kcmDocuments(); $docs->load(); $rows=$docs->rows; $docs=array(); foreach($rows as $doc)$docs[$doc->id]=Security::snohtml($doc->name); // <- this is a temporary fix
							foreach($docs as $id=>$name)
								echo '<option value="'.(int)$id.'"'.($id==$event->did?' selected':'').'>'.$name.'</option>';
						?></select><script type="text/javascript">
							window.kcm_tmpls=<?php echo @json_encode($ar); ?>;
						</script>
						<span style="width:70px;display:inline-block;">Product:</span>
						<select id="model" name="model" style="vertical-align:middle;width:210px;"><?php
							$models=new kcmModels(); $models->load(); $model=new kcmModel();
							foreach($models->rows as $model)
								echo '<option value="'.(int)$model->id.'"'.($event->mid==$model->id?' selected':'').'>'
									.Security::snohtml($model->serial.' - '.$model->name).'</option>';
						?></select>
					</p><hr style="border: none; border-bottom: 1px solid #DFDFDF;"/><p>
						<span style="width:64px;display:inline-block;">Payment:</span>
						<label for="paid" style="vertical-align:middle;width:160px;margin-right:60px;" onmousedown="return false;">
							<input type="checkbox" id="paid" value="paid" name="paid"<?php if($event->paid)echo ' checked="checked"'; ?> style="vertical-align:middle;"/>
							Paid
						</label>

						<span style="width:84px;display:inline-block;">Event Time:</span><?php
						if($event->time<1)$event->time=time();
						$d=(int)date('j',$event->time);
						$t=(int)date('t',$event->time);
						$m=(int)date('m',$event->time);
						$y=(int)date('Y',$event->time);
						$h=(int)date('H',$event->time);
						$n=(int)date('i',$event->time);
						$a=array('January','February','March','April','June','July','August','September','October','November','December');
						?><select id="day" name="day" style="vertical-align:middle;width:60px;">
							<option>Day</option><?php
							for($i=1; $i<=$t; $i++)
								echo '<option value="'.$i.'"'.($i==$d?' selected':'').'>'.str_pad($i.'',2,'0',STR_PAD_LEFT).'</option>';
						?></select><?php
						?><select id="month" name="month" style="vertical-align:middle;width:74px;" onchange="kcmDatePickerRefresh();">
							<option>Month</option><?php
							for($i=1; $i<=12; $i++)
								echo '<option value="'.$i.'"'.($i==$m?' selected':'').'>'.date('M',mktime(0,0,0,$i)).'</option>';
						?></select><?php
						?><select id="year" name="year" style="vertical-align:middle;width:70px;" onchange="kcmDatePickerRefresh();">
							<option>Year</option><?php
							for($i=1990; $i<date('Y')+2; $i++)
								echo '<option value="'.$i.'"'.($i==$y?' selected':'').'>'.$i.'</option>';
						?></select>
						&nbsp;&nbsp;
						<select id="hours" name="hours" style="vertical-align:middle;width:52px;">
							<option>Hrs</option><?php
							for($i=0; $i<24; $i++)
								echo '<option value="'.$i.'"'.($i==$h?' selected':'').'>'.str_pad($i.'',2,'0',STR_PAD_LEFT).'</option>';
						?></select> : 
						<select id="minutes" name="minutes" style="vertical-align:middle;width:52px;">
							<option>Min</option><?php
							for($i=0; $i<60; $i++)
								echo '<option value="'.$i.'"'.($i==$n?' selected':'').'>'.str_pad($i.'',2,'0',STR_PAD_LEFT).'</option>';
						?></select>
					</p><hr style="border: none; border-bottom: 1px solid #DFDFDF;"/><p>
						<span style="width:130px;display:inline-block;">Public Notes:<br/><i>(viewable by client)</i></span>
						<textarea id="pub_notes" name="pub_notes" cols="" rows="" style="vertical-align:top;width:482px;"><?php
							echo Security::snohtml($event->pub_notes);
						?></textarea>
					</p><p>
						<span style="width:130px;display:inline-block;">Private Notes:<br/><i>(admin-only)</i></span>
						<textarea id="prv_notes" name="prv_notes" cols="" rows="" style="vertical-align:top;width:482px;"><?php
							echo Security::snohtml($event->prv_notes);
						?></textarea>
					</p><hr style="border: none; border-bottom: 1px solid #DFDFDF;"/><table>
						<tr>
							<td valign="top" width="180">Add (Optional) Fields:</td>
							<td>
								<div id="kcmmt<?php echo $event->id; ?>"><?php
									if(!count($event->fields))echo self::_kcmCatFldAdd();
									foreach($event->fields as $n=>$v)echo self::_kcmCatFldAdd($n,$v);
								?></div><div>
									<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Names</small>
									<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Values</small>
								</div>&nbsp;<br/>
								<input type="button" value="Add Field" onclick="jQuery('#kcmmt<?php echo $event->id; ?>').append(<?php
									echo Security::snohtml(@json_encode(self::_kcmCatFldAdd())); ?>); kcmFieldsCheck();"/>
								<small id="kcmFldNotUniqueWarn" style="display:none;color:red;">Error: Field names must be unique.</small>
							</td>
						</tr>
					</table><?php
					if($action=='edit_pub_notes' || $action=='edit_prv_notes'){
						?><script type="text/javascript">
							function _tmp_k2f_sel_fld(){
								var fld=<?php echo @json_encode(str_replace('edit_','',$action)); ?>;
								document.getElementById(fld).focus();
								document.getElementById(fld).select();
							}
							setTimeout(_tmp_k2f_sel_fld,500);
						</script><?php
					}
					CmsHost::cms()->popup_button(($event->id>0?'Update':'Add').' Event','do_save','primary');
					CmsHost::cms()->popup_button('Cancel','close','link');
					CmsHost::cms()->popup_end();
					break;
				case 'do_save':
					$event=new kcmEvent((int)$_REQUEST['id']); $event->load();
					CmsHost::cms()->popup_begin(($event->id>0?'Edit':'Add').' Event','',380,180,ClassMethod(__CLASS__,'actions'));
					$event->cid=(int)$_REQUEST['client'];
					$event->mid=(int)$_REQUEST['model'];
					$event->did=(int)$_REQUEST['document'];
					$event->sid=(int)$_REQUEST['sale'];
					$event->type=(int)$_REQUEST['type'];
					$event->pub_notes=$_REQUEST['pub_notes'];
					$event->prv_notes=$_REQUEST['prv_notes'];
					$event->paid=isset($_REQUEST['paid']) && $_REQUEST['paid']=='paid';
					$event->time=mktime((int)$_REQUEST['hours'],(int)$_REQUEST['minutes'],0,(int)$_REQUEST['month'],(int)$_REQUEST['day'],(int)$_REQUEST['year']);
					$event->fields=array();
					if(isset($_REQUEST['fields']))
						foreach($_REQUEST['fields'] as $i=>$f)
							if($f!='')$event->field($f,$_REQUEST['values'][$i]);
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
				case 'delete':
					CmsHost::cms()->popup_begin('Delete Event(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>
						Warning! You are about to delete the following items <b>permanently</b>.
					</p><ul><?php
					foreach($checked as $id){
						$event=new kcmEvent($id);
						$event->load();
						$dtfmt=CmsHost::cms()->config_get('date-format');
						$prod=$event->model();
						$prod=Security::snohtml($prod->name.' ('.$prod->serial.')');
						if($prod=='')$prod='<i>unknown product</i>';
						$clnt=$event->client();
						$clnt=Security::snohtml($clnt->name.' '.$clnt->surname);
						if($clnt=='')$clnt='<i>unnamed client</i>';
						$vwls=array('a','e','i','o','u','h');
						?><li><?php
							echo '#'.$event->id.'&nbsp;&nbsp;[';
							echo date($dtfmt=='' ? 'd M Y' : $dtfmt,$event->time);
							echo ']&nbsp;&nbsp;-&nbsp;&nbsp;';
							switch($event->type){
								case kcmEvents::TYPE_SALE:
									echo $clnt.' purchased '.(in_array(strtolower(strip_tags($prod{0})),$vwls)?'an ':'a ').$prod.'.'; break;
								case kcmEvents::TYPE_INSTALL:
									echo $prod.' installed at '.$clnt.(substr($clnt,-1)=='s'?'\'':'\'s').'.'; break;
								case kcmEvents::TYPE_SERVICE:
									echo $clnt.(substr($clnt,-1)=='s'?'\'':'\'s').' '.$prod.' has been serviced.'; break;
								default:
									echo 'Event related to '.$clnt.(substr($clnt,-1)=='s'?'\'':'\'s').' '.$prod.'.'; break;
							}
						?></li><?php
					}
					?></ul><p>
						Are you really sure you want this?
					</p><?php
					CmsHost::cms()->popup_button('Yes','do_delete','critical');
					CmsHost::cms()->popup_button('Cancel','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'do_delete':
					CmsHost::cms()->popup_begin('Delete Event(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Deleting requested item(s):</p><ul><?php
					foreach($checked as $id){
						$event=new kcmEvent($id);
						?><li><?php
							echo 'Deleting item #'.$event->id.': ';
							echo ($event->delete() ? 'done' : 'failed').'.';
						?></li><?php
					}
					?></ul><?php
					CmsHost::cms()->popup_button('Close','close','button');
					CmsHost::cms()->popup_end();
					break;
				case 'archive':
					CmsHost::cms()->popup_begin('Archiving Event(s)','',380,180,ClassMethod(__CLASS__,'actions'));
					?><p>Archiving requested item(s):</p><ul><?php
					foreach($checked as $id){
						$event=new kcmEvent($id); $event->load();
						?><li><?php
							echo 'Archiving item #'.$event->id.': ';
							$type=array(
								kcmEvents::TYPE_OTHER   =>'Unknown',
								kcmEvents::TYPE_SALE    =>'Sale',
								kcmEvents::TYPE_INSTALL =>'Installation',
								kcmEvents::TYPE_SERVICE =>'Service',
							);
							echo (kcmArchives::archive($event,$type[$event->type].' event (#'.$event->id.') for "'.Security::snohtml($event->client()->name).'".')
								? 'done' : 'failed').'.';
						?></li><?php
					}
					?></ul><?php
					CmsHost::cms()->popup_button('Close','refresh','button');
					CmsHost::cms()->popup_end();
					break;
			}
			die;
		}
		public static function cells($id,$row,$colid,$cell){
			$dtfmt=CmsHost::cms()->config_get('date-format');
			if($colid=='time')return CmsHost::fire_action($id,date($dtfmt=='' ? 'd M Y' : $dtfmt,(int)$cell),'edit');
			if($colid=='type')
				switch($cell){
					case kcmEvents::TYPE_SALE:
						return '<img src="'.KeenCPM::icons('purchase')->_16.'" width="16" height="16" alt="" style="vertical-align:middle;"> Sale';
					case kcmEvents::TYPE_INSTALL:
						return '<img src="'.KeenCPM::icons('install')->_16.'" width="16" height="16" alt="" style="vertical-align:middle;"> Install';
					case kcmEvents::TYPE_SERVICE:
						return '<img src="'.KeenCPM::icons('service')->_16.'" width="16" height="16" alt="" style="vertical-align:middle;"> Service';
					default:
						return '<i>Unspecified</i>';
				}
			if($colid=='cid')return (($b=Security::snohtml($row->client()->name.' '.$row->client()->surname))==' ')?'<i>Unnamed Client</i>':$b;
			if($colid=='mid')return (($b=Security::snohtml($row->model()->name.' ('.$row->model()->serial.')'))==' ()')?'<i>Unknown Model</i>':$b;
			if($colid=='notes'){
				$ico_pub=KeenCPM::icons('public')->_16;
				$ico_prv=KeenCPM::icons('private')->_16;
				$hnt_pub=explode(LF,$row->pub_notes,2);
				$hnt_prv=explode(LF,$row->prv_notes,2);
				$max=40;
				$hnt_pub=strlen($hnt_pub[0])>$max+5 ? substr($hnt_pub[0],0,$max).'...' : $hnt_pub[0];
				$hnt_prv=strlen($hnt_prv[0])>$max+5 ? substr($hnt_prv[0],0,$max).'...' : $hnt_prv[0];
				return '<a href="javascript:;" class="k2fbtn2" style="background-image:url(\''.$ico_pub.'\');" title="'
					.Security::snohtml($hnt_pub).'" onclick="k2f_edit(this,'.(int)$id.',\'edit_pub_notes\');">&nbsp;</a>'
					.'  <a href="javascript:;" class="k2fbtn2" style="background-image:url(\''.$ico_prv.'\');" title="'
					.Security::snohtml($hnt_prv).'" onclick="k2f_edit(this,'.(int)$id.',\'edit_prv_notes\');">&nbsp;</a>';
			}
			if($colid=='document'){
				if($row->did>0){
					switch($row->document()->type){
						case kcmDocuments::TYPE_GUARANTEE: $type='Guarantee'; break;
						case kcmDocuments::TYPE_SERVICE:   $type='Servicing Invoice'; break;
						case kcmDocuments::TYPE_QUOTATION: $type='Quotation'; break;
						default:                      $type='<i>Other / Unknown</i>'; break;
					}
					return '<a href="javascript:;" class="k2fbtn2" style="background-image:url(\''.KeenCPM::icons('preview')->_16
						.'\');" title="Preview PDF" onclick="k2f_edit(this,'.(int)$id.',\'doc_preview\');">&nbsp;</a>'
						.'  <a href="javascript:;" class="k2fbtn2" style="background-image:url(\''.KeenCPM::icons('download')->_16
						.'\');" title="Download PDF" onclick="k2f_edit(this,'.(int)$id.',\'doc_download\');">&nbsp;</a>'
						.'  <a href="javascript:;" class="k2fbtn2" style="background-image:url(\''.KeenCPM::icons('email')->_16
						.'\');" title="Email PDF" onclick="k2f_edit(this,'.(int)$id.',\'doc_email\');">&nbsp;</a>'
						.' '.$type;
				}
				return '<i>None</i>';
			}
			if($colid=='paid')
				return '<img src="'.KeenCPM::icons($cell ? 'yes' : 'no')->_16.'" width="16" height="16" alt="'.($cell ? 'Paid' : 'Not Paid').'" title="'.($cell ? 'Paid' : 'Not Paid').'">';
			return Security::snohtml($cell);
		}
		public static function on_head(){
			?><style type="text/css">
				.k2fbtn {
					width:16px;
					height:16px;
					display:inline-block;
					background-repeat:no-repeat;
					background-position:top left;
				}
				.k2fbtn:hover {
					background-position:bottom left;
				}
				.k2fbtn2 {
					width:16px;
					height:16px;
					display:inline-block;
					background-repeat:no-repeat;
					background-position:top left;
					/* crossbrowser opacity */
					-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=50)";
					filter: alpha(opacity=50);
					-moz-opacity: 0.5;
					-khtml-opacity: 0.5;
					opacity: 0.5;
				}
				.k2fbtn2:hover {
					/* crossbrowser opacity */
					-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=100)";
					filter: alpha(opacity=100);
					-moz-opacity: 1;
					-khtml-opacity: 1;
					opacity: 1;
				}
			</style><?php
		}
	}
	// register ajax/api calls
	Ajax::register('kcmViewEvents','actions',CmsHost::fsig_action());

	// bind to on_head event so we can inject code in <head> tag.
	Events::add('on_head',ClassMethod('kcmViewEvents','on_head'));

?>