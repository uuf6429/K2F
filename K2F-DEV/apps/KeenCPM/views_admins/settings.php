<?php defined('K2F') or die;

	uses('core/security.php','core/cms.php');
	
	class kcmViewSettings {
		public static function _kcmCatFldAdd($name='',$value=''){
			static $counter=0; $counter++;
			return '<div id="kcmCatFldAdd'.$counter.'">'
					.'<input type="text" name="fields[]" value="'.Security::snohtml($name).'" style="width:148px;" onkeyup="kcmFieldsCheck();"/>'
					.'<input type="text" name="values[]" value="'.Security::snohtml($value).'" style="width:148px;" onkeyup="kcmFieldsCheck();"/>'
					.'<a href="javascript:;" onclick="jQuery(\'#kcmCatFldAdd'.$counter.'\').remove();kcmFieldsCheck();">'
						.'<small>delete</small></a>'
				.'</div>';
		}
		public static function manage(){
			// JS for field uniqueness warning
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
			</script><?php
			// update changes
			if(isset($_REQUEST['save'])){
				CmsHost::cms()->config_set('date-format',strip_tags($_REQUEST['date-format']));
				CmsHost::cms()->config_set('currency',substr($_REQUEST['currency'],0,3));
				CmsHost::cms()->config_set('kcm-notice-min',strip_tags($_REQUEST['kcm-notice-min']));
				CmsHost::cms()->config_set('kcm-notice-max',strip_tags($_REQUEST['kcm-notice-max']));
				CmsHost::cms()->config_set('kcm-stock-warn',strip_tags($_REQUEST['kcm-stock-warn']));
				$fields=array();
				if(isset($_REQUEST['fields']) && isset($_REQUEST['values']))
					foreach($_REQUEST['fields'] as $i=>$name)
						if($name!='')$fields[$name]=$_REQUEST['values'][$i];
				CmsHost::cms()->config_set('kcm-fields',@json_encode((array)$fields));
			}
			// variables
			$currencies=array(
				'AFN'=>'Afghanistan Afghanis - AFN',
				'ALL'=>'Albania Leke - ALL',
				'DZD'=>'Algeria Dinars - DZD',
				'USD'=>'America (United States) Dollars - USD',
				'ARS'=>'Argentina Pesos - ARS',
				'AUD'=>'Australia Dollars - AUD',
				'ATS'=>'Austria Schillings - ATS*',
				'BSD'=>'Bahamas Dollars - BSD',
				'BHD'=>'Bahrain Dinars - BHD',
				'BDT'=>'Bangladesh Taka - BDT',
				'BBD'=>'Barbados Dollars - BBD',
				'BEF'=>'Belgium Francs - BEF*',
				'BMD'=>'Bermuda Dollars - BMD',
				'BRL'=>'Brazil Reais - BRL',
				'BGN'=>'Bulgaria Leva - BGN',
				'CAD'=>'Canada Dollars - CAD',
				'XOF'=>'CFA BCEAO Francs - XOF',
				'XAF'=>'CFA BEAC Francs - XAF',
				'CLP'=>'Chile Pesos - CLP',
				'CNY'=>'China Yuan Renminbi - CNY',
				'CNY'=>'RMB (China Yuan Renminbi) - CNY',
				'COP'=>'Colombia Pesos - COP',
				'XPF'=>'CFP Francs - XPF',
				'CRC'=>'Costa Rica Colones - CRC',
				'HRK'=>'Croatia Kuna - HRK',
				'CYP'=>'Cyprus Pounds - CYP*',
				'CZK'=>'Czech Republic Koruny - CZK',
				'DKK'=>'Denmark Kroner - DKK',
				'DEM'=>'Deutsche (Germany) Marks - DEM*',
				'DOP'=>'Dominican Republic Pesos - DOP',
				'NLG'=>'Dutch (Netherlands) Guilders - NLG*',
				'XCD'=>'Eastern Caribbean Dollars - XCD',
				'EGP'=>'Egypt Pounds - EGP',
				'EEK'=>'Estonia Krooni - EEK',
				'EUR'=>'Euro - EUR',
				'FJD'=>'Fiji Dollars - FJD',
				'FIM'=>'Finland Markkaa - FIM*',
				'FRF'=>'France Francs - FRF*',
				'DEM'=>'Germany Deutsche Marks - DEM*',
				'XAU'=>'Gold Ounces - XAU',
				'GRD'=>'Greece Drachmae - GRD*',
				'NLG'=>'Holland (Netherlands) Guilders - NLG*',
				'HKD'=>'Hong Kong Dollars - HKD',
				'HUF'=>'Hungary Forint - HUF',
				'ISK'=>'Iceland Kronur - ISK',
				'XDR'=>'IMF Special Drawing Right - XDR',
				'INR'=>'India Rupees - INR',
				'IDR'=>'Indonesia Rupiahs - IDR',
				'IRR'=>'Iran Rials - IRR',
				'IQD'=>'Iraq Dinars - IQD',
				'IEP'=>'Ireland Pounds - IEP*',
				'ILS'=>'Israel New Shekels - ILS',
				'ITL'=>'Italy Lire - ITL*',
				'JMD'=>'Jamaica Dollars - JMD',
				'JPY'=>'Japan Yen - JPY',
				'JOD'=>'Jordan Dinars - JOD',
				'KES'=>'Kenya Shillings - KES',
				'KRW'=>'Korea (South) Won - KRW',
				'KWD'=>'Kuwait Dinars - KWD',
				'LBP'=>'Lebanon Pounds - LBP',
				'LUF'=>'Luxembourg Francs - LUF*',
				'MYR'=>'Malaysia Ringgits - MYR',
				'MTL'=>'Malta Liri - MTL*',
				'MUR'=>'Mauritius Rupees - MUR',
				'MXN'=>'Mexico Pesos - MXN',
				'MAD'=>'Morocco Dirhams - MAD',
				'NLG'=>'Netherlands Guilders - NLG*',
				'NZD'=>'New Zealand Dollars - NZD',
				'NOK'=>'Norway Kroner - NOK',
				'OMR'=>'Oman Rials - OMR',
				'PKR'=>'Pakistan Rupees - PKR',
				'XPD'=>'Palladium Ounces - XPD',
				'PEN'=>'Peru Nuevos Soles - PEN',
				'PHP'=>'Philippines Pesos - PHP',
				'XPT'=>'Platinum Ounces - XPT',
				'PLN'=>'Poland Zlotych - PLN',
				'PTE'=>'Portugal Escudos - PTE*',
				'QAR'=>'Qatar Riyals - QAR',
				'RON'=>'Romania New Lei - RON',
				'ROL'=>'Romania Lei - ROL*',
				'RUB'=>'Russia Rubles - RUB',
				'SAR'=>'Saudi Arabia Riyals - SAR',
				'XAG'=>'Silver Ounces - XAG',
				'SGD'=>'Singapore Dollars - SGD',
				'SKK'=>'Slovakia Koruny - SKK*',
				'SIT'=>'Slovenia Tolars - SIT*',
				'ZAR'=>'South Africa Rand - ZAR',
				'KRW'=>'South Korea Won - KRW',
				'ESP'=>'Spain Pesetas - ESP*',
				'XDR'=>'Special Drawing Rights (IMF) - XDR',
				'LKR'=>'Sri Lanka Rupees - LKR',
				'SDG'=>'Sudan Pounds - SDG',
				'SEK'=>'Sweden Kronor - SEK',
				'CHF'=>'Switzerland Francs - CHF',
				'TWD'=>'Taiwan New Dollars - TWD',
				'THB'=>'Thailand Baht - THB',
				'TTD'=>'Trinidad and Tobago Dollars - TTD',
				'TND'=>'Tunisia Dinars - TND',
				'TRY'=>'Turkey Lira - TRY',
				'AED'=>'United Arab Emirates Dirhams - AED',
				'GBP'=>'United Kingdom Pounds - GBP',
				'USD'=>'United States Dollars - USD',
				'VEB'=>'Venezuela Bolivares - VEB*',
				'VEF'=>'Venezuela Bolivares Fuertes - VEF',
				'VND'=>'Vietnam Dong - VND',
				'ZMK'=>'Zambia Kwacha - ZMK'
			);
			// show form
			CmsHost::cms()->adminlist_begin(KeenCPM::icons('settings'),'Manage KeenCPM Settings');
			?><form action="" method="post">
				<h3>General Settings</h3>
				<p>
					<span style="width:130px;display:inline-block;">Date Format:</span>
					<input type="text" name="date-format" value="<?php echo Security::snohtml(CmsHost::cms()->config_get('date-format')); ?>"/>
					<a href="http://php.net/manual/en/function.date.php" target="_blank">(more info)</a>
				</p><p>
					<span style="width:130px;display:inline-block;">Currency:</span>
					<select name="currency"><?php
						$ccr=CmsHost::cms()->config_get('currency');
						if($ccr=='')$ccr='EUR';
						foreach($currencies as $cur=>$text)
							echo '<option value="'.$cur.'"'.($cur==$ccr?' selected':'').'>'.$text.'</option>';
					?></select><br/><small style="margin-left:134px;display:inline-block;">* Currency not in use any more.</small>
				</p>&nbsp;
				<h3>KeenCPM Settings</h3>
				<p>
					<span style="width:130px;display:inline-block;">Stock Warning:</span>
					<input type="text" name="kcm-stock-warn" value="<?php echo max((int)CmsHost::cms()->config_get('kcm-stock-warn'),1); ?>"/>
					<br/><small style="margin-left:134px;display:inline-block;">A warning is shown when stock falls below this number.</small>
				</p><p>
					<span style="width:160px;display:inline-block;">Servicing Range Start:</span>
					<input style="width:220px;" type="text" name="kcm-notice-min" value="<?php echo Security::snohtml(CmsHost::cms()->config_get('kcm-notice-min')); ?>"/>
					<small>ex: <i>-7 days</i></small>
					<br/>
					<span style="width:160px;display:inline-block;">Servicing Range End:</span>
					<input style="width:220px;" type="text" name="kcm-notice-max" value="<?php echo Security::snohtml(CmsHost::cms()->config_get('kcm-notice-max')); ?>"/>
					<small>ex: <i>+7 days</i></small>
					<br/><small style="margin-left:164px;display:inline-block;">These two control when to show servicing.</small>
				</p><table>
					<tr>
						<td valign="top" width="180">Global (optional) Fields:</td>
						<td>
							<div id="kcm-fields"><?php
								$fields=CmsHost::cms()->config_get('kcm-fields');
								$fields=$fields=='' ? array() : (array)@json_decode($fields);
								if(!count($fields))echo self::_kcmCatFldAdd();
								foreach($fields as $n=>$v)echo self::_kcmCatFldAdd($n,$v);
							?></div><div>
								<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Names</small>
								<small style="display:inline-block;width:148px;text-align:center;">&uarr; Field Values</small>
							</div>&nbsp;<br/>
							<input type="button" value="Add Field" onclick="jQuery('#kcm-fields').append(<?php
								echo Security::snohtml(@json_encode(self::_kcmCatFldAdd())); ?>); kcmFieldsCheck();"/>
							<small id="kcmFldNotUniqueWarn" style="display:none;color:red;">Error: Field names must be unique.</small>
						</td>
					</tr>
				</table><p>
					<input name="save" type="submit" class="button-primary" value="Update"/>
				</p>
			</form><?php
			CmsHost::cms()->adminlist_end();
		}
	}

?>