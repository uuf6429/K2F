<?php defined('K2F') or die;

	uses('exts/oodb.php','core/cookies.php','libs/swfupload/swfupload.php');

	/// GENERAL FUNCTIONALITY ///

	class kcmCaseInsensitiveArray {
		protected $ref=null;
		/**
		 * Class instance constructor.
		 * @param array $array The array to wrap.
		 */
		public function __construct(&$array){
			$this->ref=&$array;
		}
		/**
		 * Get a specified key's value.
		 * @param string $name Key name.
		 * @param mixed $default Default value (for non-existent keys).
		 * @return mixed The key's value or default value.
		 */
		public function get($name,$default=null){
			$name=strtolower($name);
			foreach($this->ref as $k=>$v)
				if(strtolower($k)==$name)
					return $v;
			return $default;
		}
		/**
		 * Set a specified key's value.
		 * @param string $name Key name.
		 * @param mixed $value Key's new value.
		 */
		public function set($name,$value){
			$this->rem($name);
			$this->ref[$name]=$value;
		}
		/**
		 * Remove key given its name.
		 * @param string $name The key's name.
		 */
		public function rem($name){
			$name=strtolower($name);
			foreach($this->ref as $k=>$v)
				if(strtolower($k)==$name)
					unset($this->ref[$k]);
		}
	}

	/**
	 * Returns a case insensitive object wrapper for array.
	 * @param array $array Original array.
	 * @return kcmCaseInsensitiveArray The wrapper object.
	 */
	function kcmCiArray(&$array){
		return new kcmCaseInsensitiveArray($array);
	}

	class kcmModels extends DatabaseRows {
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kcm_models';
		}
	}

	/// SYSTEM LOGIC OBJECTS ///

	class kcmClient extends DatabaseRow {
		/**
		 * @var integer Timestamp of when client was created.
		 */
		public $created=0;
		/**
		 * @var string Full client name.
		 */
		public $name='';
		/**
		 * @var string Full client surname.
		 */
		public $surname='';
		/**
		 * @var string Full client landline (telephone) number.
		 */
		public $landline='';
		/**
		 * @var string Full client mobile (cellphone) number.
		 */
		public $mobile='';
		/**
		 * @var string Client address (multiline).
		 */
		public $address='';
		/**
		 * @var string Client town.
		 */
		public $town='';
		/**
		 * @var string Client's country name.
		 */
		public $country='';
		/**
		 * @var string Notes on client.
		 */
		public $notes='';
		/**
		 * @var string Client's email address.
		 */
		public $email='';
		/**
		 * @var array List of client-specific details (eg: "Address 2"=>"Middle of Nowhere").
		 */
		public $fields=array();
		/**
		 * @var string Client's PIN number used to access account details.
		 */
		public $pin='';
		/**
		 * @var string A random string which when set and matches client cookie, means the user is logged in.
		 */
		public $cookie='';
		/**
		 * Gets and/or sets a field's value.
		 * @param string $name The field value to set/get.
		 * @param mixed $value (Optioanl) Used to overwrite field's value with it.
		 * @return mixed The value of the field (if $value is set, the older value is returned).
		 */
		public function field($name,$value='%some^rand&const$val'){
			$flds=kcmCiArray($this->fields);
			$val=$flds->get($name);
			if($value!='%some^rand&const$val')$flds->set($name,$value);
			return $val;
		}
		/**
		 * @return string Table name for storing instances.
		 */
		public function table(){
			return 'kcm_clients';
		}
		/**
		 * Returns the client token (client id + client pin).
		 * @return string The client token.
		 */
		public function token(){
			return $this->id.$this->pin;
		}
		/**
		 * Returns a chronological (new to old) list of events related to client.
		 * @return array List of kcmEvent objects.
		 */
		public function events(){
			$events=new kcmEvents();
			$events->load('`cid`='.(int)$this->id);
			return $events->rows;
		}
	}

	class kcmClients extends DatabaseRows {
		public function table(){
			return 'kcm_clients';
		}
		/**
		 * Parses the user token (id+pin) into its respective parts.
		 * @param string $token The user(-supplied) token to parse.
		 * @return array Array of (0=>id, 1=>pin, 'id'=>id, 'pin'=>pin)
		 */
		public static function token_parse($token){
			$i=substr($token,0,-4);
			$p=substr($token,-4,4);
			return array($i,$p,'id'=>$i,'pin'=>$p);
		}
		/**
		 * Returns the client for the particular token.
		 * @param string $token The user(-supplied) token.
		 * @return kcmClient|null The loaded client object or null on error.
		 */
		public static function token_load($token){
			list($id,$pin)=self::token_parse($token);
			if($pin=='')return null;
			$client=new kcmClient((int)$id);
			return ($client->load() && $client->pin==$pin) ? $client : null;
		}
		/**
		 * Verifies the user token.
		 * @param string $token The user(-supplied) token.
		 * @return boolean True if token is ok, false otherwise.
		 */
		public static function token_verify($token){
			return self::token_load($token)!==null;
		}
		/**
		 * @var null|kcmClient|false Cached logged in client object or null if not logged in (default is false).
		 */
		protected static $login=false;
		/**
		 * Logs user in (sets cookies).
		 * @param string $token The user(-supplied) token.
		 */
		public static function login($token){
			if(($u=self::token_load($token))){
				list($i,$p)=self::token_parse($token);
				$c=md5(time()).'|'.time().'|'.mt_rand();
				Cookies::set('kcmt',$token,/*strtotime('+1 hour')*/0);
				Cookies::set('kcmc',$c,/*strtotime('+1 hour')*/0);
				$u->cookie=$c;
				$u->save();
				// invalidate cache
				self::$login=false;
			}
		}
		/**
		 * Returns whether user is logged in or not.
		 * @return kcmClient|null Logged in client object or null if none logged in.
		 */
		public static function loggedin(){
			if(self::$login===false){
				$t=Cookies::get('kcmt','');
				$c=Cookies::get('kcmc','');
				list($i,$p)=self::token_parse($t);
				self::$login = ($t!='' && $c!='' && ($u=self::token_load($t)) && $u->cookie!='' && $u->cookie==$c) ? $u : null;
			}
			return self::$login;
		}
		/**
		 * Logs user out (clears cookies and cookie property).
		 */
		public static function logout(){
			if(($u=self::loggedin())){
				Cookies::set('kcmt','');
				Cookies::set('kcmc','');
				$u->cookie='';
				$u->save();
				// invalidate cache
				self::$login=false;
			}
		}
	}

	class kcmStock extends DatabaseRow {
		/**
		 * @var integer Date this stock was recieved on.
		 */
		public $date=0;
		/**
		 * @var integer Model ID which relates to the stock.
		 */
		public $model=0;
		/**
		 * @var integer The amount of stock bought.
		 */
		public $amount=0;
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kcm_stock';
		}
		/**
		 * Returns object instance of stock model.
		 * @return kcmModel Model object or null on error.
		 */
		public function model(){
			$model=new kcmModel($this->model);
			$model->load();
			return $model;
		}
	}

	class kcmStocks extends DatabaseRows {
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kcm_stock';
		}
	}

	class kcmDocument extends DatabaseRow {
		/**
		 * @var string Document name (this is purely admin cosmetic).
		 */
		public $name='';
		/**
		 * @var integer Document type (from kcmDocuments::TYPE_).
		 */
		public $type=0;
		/**
		 * @var string The document's HTML code.
		 */
		public $html='';
		/**
		 * @var integer Timestamp this document was created on.
		 */
		public $created=0;
		/**
		 * @var integer Timestamp this document was last updated on.
		 */
		public $updated=0;
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kcm_documents';
		}
	}

	class kcmDocuments extends DatabaseRows {
		/**
		 * Document is a guarantee printout.
		 */
		const TYPE_GUARANTEE = 0;
		/**
		 * Document is a letter to alert user over pending service.
		 */
		const TYPE_SERVICE   = 1;
		/**
		 * Document is a product quotation.
		 */
		const TYPE_QUOTATION = 2;
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kcm_documents';
		}
	}

	class kcmModel extends DatabaseRow {
		/**
		 * @var string A short descriptive name of the product.
		 */
		public $name='';
		/**
		 * @var string Serial number unique to the model.
		 */
		public $serial='';
		/**
		 * @var integer The model's category.
		 */
		public $type=0;
		/**
		 * @var array List of model-specific details (eg: "Color"=>"green").
		 */
		public $fields=array();
		/**
		 * @var integer Timestamp of when it was created.
		 */
		public $created=0;
		/**
		 * @var string Short HTML description of the product.
		 */
		public $description='';
		/**
		 * @var array List of image URLs used in product gallery.
		 */
		public $images=array();
		/**
		 * @var array List of name=>url items each representing a downloadable file.
		 */
		public $files=array();
		/**
		 * @var string The primary group name for this product (eg: "Residential")
		 */
		public $group='';
		/**
		 * @var string The secondary group name for this product (eg: "DC Inverter")
		 */
		public $subgroup='';
		/**
		 * @var boolean If true, model is shown on website, if false, it's not.
		 */
		public $published=false;
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kcm_models';
		}
		/**
		 * Returns this model's category object.
		 * @return kcmModelType Model category object.
		 */
		public function type(){
			$type=new kcmModelType($this->type);
			$type->load();
			return $type;
		}
		/**
		 * Returns the amount of units sold so far.
		 * @return integer The number of units sold.
		 */
		public function sold(){
			$events=new kcmEvents();
			$events->load('mid='.(int)$this->id.' && type='.kcmEvents::TYPE_SALE);
			return $events->count();
		}
		/**
		 * Returns the total number of units bought by the shop (total stock).
		 * @return integer Total units bought.
		 */
		public function bought(){
			$stock=new kcmStocks();
			$stock->load('model='.$this->id);
			$count=0; $row=new kcmStock();
			foreach($stock->rows as $row)
				$count+=$row->amount;
			return $count;
		}
		/**
		 * Returns the amount of unsold units.
		 * @return integer The number of units in stock.
		 */
		public function instock(){
			return $this->bought()-$this->sold();
		}
		/**
		 * Gets and/or sets a field's value.
		 * @param string $name The field value to set/get.
		 * @param mixed $value (Optioanl) Used to overwrite field's value with it.
		 * @return mixed The value of the field (if $value is set, the older value is returned).
		 */
		public function field($name,$value='%some^rand&const$val'){
			$flds=kcmCiArray($this->fields);
			$val=$flds->get($name);
			if($value!='%some^rand&const$val')$flds->set($name,$value);
			return $val;
		}
	}

	class kcmModelType extends DatabaseRow {
		/**
		 * @var string Model category's name.
		 */
		public $name='';
		/**
		 * @var array Array of custom fields which are inherited by model.
		 *            <br>These fields can be overriden in model!
		 */
		public $fields=array();
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kcm_types';
		}
		/**
		 * Gets and/or sets a field's value.
		 * @param string $name The field value to set/get.
		 * @param mixed $value (Optioanl) Used to overwrite field's value with it.
		 * @return mixed The value of the field (if $value is set, the older value is returned).
		 */
		public function field($name,$value='%some^rand&const$val'){
			$flds=kcmCiArray($this->fields);
			$val=$flds->get($name);
			if($value!='%some^rand&const$val')$flds->set($name,$value);
			return $val;
		}
		/**
		 * @return integer Number of models under this category.
		 */
		public function models(){
			$models=new kcmModels();
			$models->load('`type`='.$this->id);
			return $models->count();
		}
	}

	class kcmModelTypes extends DatabaseRows {
		/**
		 * @return string Table holding instance data.
		 */
		public function table(){
			return 'kcm_types';
		}
	}

	class kcmEvent extends DatabaseRow {
		/**
		 * @var integer Client ID.
		 */
		public $cid=-1;
		/**
		 * @var integer Model ID.
		 */
		public $mid=-1;
		/**
		 * @var integer ID of document.
		 */
		public $did=0;
		/**
		 * @var integer ID of parent sale event (if any).
		 */
		public $sid=-1;
		/**
		 * @var integer Type of event (see kcmEvents::TYPE_* constants).
		 */
		public $type=kcmEvents::TYPE_SALE;
		/**
		 * @var integer Timestamp of when event happened.
		 */
		public $time=0;
		/**
		 * @var boolean Whether user paid for event or not.
		 */
		public $paid=false;
		/**
		 * @var string Some public notes on event (NO HTML).
		 */
		public $pub_notes='';
		/**
		 * @var string Some private notes on event (NO HTML).
		 */
		public $prv_notes='';
		/**
		 * @var array List of model-specific details (eg: "Color"=>"green").
		 */
		public $fields=array();
		/**
		 * Returns storage table name.
		 * @return string Table name.
		 */
		public function table(){
			return 'kcm_events';
		}
		/**
		 * Returns the relevant parent sale event (if any).
		 * @return kcmEvent|null The event object or null on failure.
		 */
		public function sale(){
			if(!isset($this->_sale)){
				$this->_sale=new kcmEvent($this->sid);
				if(!$this->_sale->load())
					$this->_sale=null;
			}
			return $this->_sale;
		}
		/**
		 * Returns the relevant client.
		 * @return kcmClient Client object.
		 */
		public function client(){
			if(!isset($this->_client)){
				$this->_client=new kcmClient($this->cid);
				if(!$this->_client->load())
					$this->_client=null;
			}
			return $this->_client;
		}
		/**
		 * Returns the relevant model.
		 * @return kcmModel Model object.
		 */
		public function model(){
			if(!isset($this->_model)){
				$this->_model=new kcmModel($this->mid);
				if(!$this->_model->load())
					$this->_model=null;
			}
			return $this->_model;
		}
		/**
		 * Returns the relevant document.
		 * @return kcmDocument Document object.
		 */
		public function document(){
			if(!isset($this->_document)){
				$this->_document=new kcmDocument($this->did);
				if(!$this->_document->load())
					$this->_document=null;
			}
			return $this->_document;
		}
		/**
		 * Gets and/or sets a field's value.
		 * @param string $name The field value to set/get.
		 * @param mixed $value (Optioanl) Used to overwrite field's value with it.
		 * @return mixed The value of the field (if $value is set, the older value is returned).
		 */
		public function field($name,$value='%some^rand&const$val'){
			$flds=kcmCiArray($this->fields);
			$val=$flds->get($name);
			if($value!='%some^rand&const$val')$flds->set($name,$value);
			return $val;
		}
		/**
		 * Returns date (timestamp) of the next service date (could be be in the past!)
		 * @return integer Date of next service.
		 */
		public function nextService(){
			if(!isset($GLOBALS['KCMNXTSRVCDTS']))$GLOBALS['KCMNXTSRVCDTS']=array();
			if(!isset($GLOBALS['KCMNXTSRVCDTS'][$this->id])){
				// get period[service] from: settings,category,model,client,(this sale)event
				//$fields=@json_decode(CmsHost::cms()->config_get('fields'));
				$period='';//CmsHost::cms()->config_get(''); // TODO: fix this...
				if($this->model() && $this->model()->type() && ($v=$this->model()->type()->field('Service'))!='')$period=$v;
				if($this->model() && ($v=$this->model()->field('Service'))!='')$period=$v;
				if($this->client() && ($v=$this->client()->field('Service'))!='')$period=$v;
				if(($v=$this->field('Service'))!='')$period=$v;
				$GLOBALS['KCMNXTSRVCDTS'][$this->id] = (($t=strtotime($period,0)) && $t>1)
					? nearestPeriodToTime($this->time,$period,time()) : 0;
			}
			return $GLOBALS['KCMNXTSRVCDTS'][$this->id];
		}
	}

	class kcmEvents extends DatabaseRows {
		/**
		 * Event type not specified.
		 */
		const TYPE_OTHER    = 0;
		/**
		 * Event type is a product purchase.
		 */
		const TYPE_SALE     = 1;
		/**
		 * Event type is a product install.
		 */
		const TYPE_INSTALL  = 2;
		/**
		 * Event type is a service/checkup.
		 */
		const TYPE_SERVICE  = 3;
		/**
		 * Converts event type (kcmEvents::TYPE_) to document type (kcmDocuments::TYPE_).
		 * @param integer $eventType The kcmEvents::TYPE_* constant.
		 * @return integer The matching kcmDocuments::TYPE_* constant.
		 */
		public static function eventToDocType($eventType){
			switch($eventType){
				case kcmEvents::TYPE_SALE:
					return self::TYPE_SALE;
				case kcmEvents::TYPE_INSTALL:
					return self::TYPE_INSTALL;
				case kcmEvents::TYPE_SERVICE:
					return self::TYPE_SERVICE;
				default:
					return self::TYPE_OTHER;
			}
		}
		public function table(){
			return 'kcm_events';
		}
	}

	class kcmArchive extends DatabaseRow {
		/**
		 * @var integer Timestamp of when archive was created.
		 */
		public $date=0;
		/**
		 * @var string Short description of this archive.
		 */
		public $desc='';
		/**
		 * @var string Original object's class, must extend DatabaseRow.
		 */
		public $class='';
		/**
		 * @var array Array of serialized object properties (this is the actual archive data).
		 */
		public $data=array();
		/**
		 * @return string Returns the storage table for this instance.
		 */
		public function table(){
			return 'kcm_archives';
		}
		/**
		 * Restores this archive object.
		 */
		public function restore(){
			return kcmArchives::restore($this->id);
		}
	}

	class kcmArchives extends DatabaseRows {
		/**
		 * @return string Returns the storage table for this instance.
		 */
		public function table(){
			return 'kcm_archives';
		}
		/**
		 * Archives the specified object (which must be extending DatabaseRow).
		 * @param DatabaseRow $obj The object to archive.
		 * @param string $desc Short description for the archive.
		 */
		public static function archive($obj,$desc){
			$archive=new kcmArchive();
			$archive->date=time();
			$archive->desc=$desc;
			$archive->class=get_class($obj);
			$archive->data=get_object_vars($obj);
			return and_r($archive->save(),$obj->delete());
		}
		/**
		 * Restores a specific archived object.
		 * @param integer $oid The archive row to restore.
		 */
		public static function restore($id){
			$archive=new kcmArchive((int)$id);
			$archive->load();
			$obj=new $archive->class();
			foreach($archive->data as $k=>$v)$obj->$k=$v;
			return and_r($obj->save(),$archive->delete());
		}
	}

	class kcmService { // this is just a modeling class, it ain't stored anywhere.
		/**
		 * @var integer Timestamp of when servicing should be done.
		 */
		public $date=0;
		/**
		 * @var integer Last event related to this new servicing (could be any kind of event).
		 */
		public $event=0;
		/**
		 * Returns the model related to this servicing.
		 * @return kcmModel Model object.
		 */
		public function model(){
			return $this->lastEvent()->model();
		}
		/**
		 * Returns the last event related to this servicing.
		 * @return kcmEvent Event object.
		 */
		public function lastEvent(){
			$event=new kcmEvent($this->event);
			$event->load();
			return $event;
		}
		/**
		 * Returns the client related to this servicing.
		 * @return kcmClient Client object.
		 */
		public function client(){
			return $this->lastEvent()->client();
		}
	}

	class kcmServices { // this is a modeling/loader class, no actual db storage.
		/**
		 * @var null|array Object cache thingy.
		 */
		protected static $services=null;
		/**
		 * Returns a list of service objects matching several criteria (servicing within range and not ignored).
		 * @return array|kcmService An array of service items (if any at all, otherwise an empty array).
		 */
		public static function load(){
			if(!self::$services){
				$serviceStart=strtotime(CmsHost::cms()->config_get('kcm-notice-min'));
				$serviceEnd=strtotime(CmsHost::cms()->config_get('kcm-notice-max'));
				self::$services=array();
				$events=new kcmEvents(); $event=new kcmEvent();
				$events->load('`type`="'.kcmEvents::TYPE_SALE.'"');
				foreach($events->rows as $event)
					if(!($event->nextService()<$serviceStart || $event->nextService()>$serviceEnd)){
						$ign=trim(strtolower($event->field('Ignore Stock')));
						if($ign!='yes' && $ign!='on'){
							$service=new kcmService();
							$service->date=$event->nextservice();
							$service->event=$event->id;
							self::$services[]=$service;
						}
					}
			}
			return self::$services;
		}
	}

	/// FIELD SYSTEM ///

	$GLOBALS['KCMNOTFIELDS']=array('fields');
	$GLOBALS['KCMDATEFIELDS']=array('date','time','creation','created','updated');

	/**
	 * Returns normalized field name.
	 * @param string $name The original field name.
	 * @return string Normalized field name.
	 */
	function kcmGetFieldNorm($name){
		return strtoupper(Security::stoident($name));
	}

	/**
	 * Returns an class's public properties as field names.
	 * @param string $class Target class name.
	 * @return object Object resembling a fields-compatible object.
	 */
	function kcmGetClassProps($class){
		$res=(object)array('fields'=>array());
		foreach((array)new $class() as $n=>$v)
			if(!in_array($n,$GLOBALS['KCMNOTFIELDS']))
				$res->fields[$n]=null;
		return $res;
	}

	/**
	 * Returns a list of fields from various places:<br>
	 *   clients events models types
	 * @param null|DatabaseRow|DatabaseRows $obj (Optional) Used internally to iterate accordingly.
	 * @param string $sfx (Optional) Used internally to provide a field name suffix to fields.
	 * @return array Array of name=>description pairs.
	 */
	function kcmGetFieldDescs($obj=null,$sfx=''){
		if(is_object($obj)){
			$res=array(); if($sfx!='')$sfx.='.';
			if(isset($obj->fields) && is_array($obj->fields))
				foreach($obj->fields as $k=>$v)
					$res[$sfx.kcmGetFieldNorm($k)]=null;
			return $res;
		}elseif(is_array($obj)){
			$res=array();
			foreach($obj as $row)
				foreach(kcmGetFieldDescs($row,$sfx) as $k=>$v)
					$res[$k]=$v;
			return $res;
		}else{
			// intialize variables & data
			$c=new kcmClients();    $c->load();
			$e=new kcmEvents();     $e->load();
			$m=new kcmModels();     $m->load();
			$t=new kcmModelTypes(); $t->load();
			$res=array();
			$sflds=CmsHost::cms()->config_get('kcm-fields');
			$sflds=$sflds=='' ? array() : (array)@json_decode($sflds);
			// add settings fields
			foreach(kcmGetFieldDescs((object)array('fields'=>$sflds)) as $k=>$v)$res[$k]=$v;
			// add object fields
			foreach(kcmGetFieldDescs($c->rows) as $k=>$v)$res[$k]=$v;
			foreach(kcmGetFieldDescs($e->rows) as $k=>$v)$res[$k]=$v;
			foreach(kcmGetFieldDescs($m->rows) as $k=>$v)$res[$k]=$v;
			foreach(kcmGetFieldDescs($t->rows) as $k=>$v)$res[$k]=$v;
			// add object properties
			foreach(kcmGetFieldDescs(kcmGetClassProps('kcmClient'),   'CLIENT')   as $k=>$v)$res[$k]=$v;
			foreach(kcmGetFieldDescs(kcmGetClassProps('kcmEvent'),    'EVENT')    as $k=>$v)$res[$k]=$v;
			foreach(kcmGetFieldDescs(kcmGetClassProps('kcmModel'),    'MODEL')    as $k=>$v)$res[$k]=$v;
			foreach(kcmGetFieldDescs(kcmGetClassProps('kcmModelType'),'CATEGORY') as $k=>$v)$res[$k]=$v;
			// return resulting keys
			return array_keys($res);
		}
	}

	/**
	 * Returns a list of fields from various passed objects.<br>
	 * Each parameter must be an object to get fields from.<br>
	 * Note that any fields in last object will override those in earlier objects.
	 * @example <code>
	 *     $a=(object)array('aa'=>'11','bb'=>'22');
	 *     $b=(object)array('bb'=>'33','cc'=>'44');
	 *     $c=kcmGetFieldValues($a,$b);
	 *     // $c => {aa:11,bb:33,cc:44}
	 * </code>
	 * @return array Array of name=>value pairs.
	 */
	function kcmGetFieldValues(){
		$flds=array();
		$dtfmt=CmsHost::cms()->config_get('date-format'); if($dtfmt=='')$dtfmt='d M Y';
		foreach(func_get_args() as $arg)
			if(is_object($arg)){
				$cls=strtoupper(str_replace('kcm','',get_class($arg)));
				// add from object properties
				foreach((array)$arg as $k=>$v)
					if(!in_array($k,$GLOBALS['KCMNOTFIELDS'])){
						if(in_array($k,$GLOBALS['KCMDATEFIELDS']))$v=date($dtfmt,(int)$v);
						$flds[$cls.'.'.kcmGetFieldNorm($k)]=$v;
					}
				// add from fields property
				if(isset($arg->fields) && is_array($arg->fields))
					foreach($arg->fields as $k=>$v)
						$flds[kcmGetFieldNorm($k)]=$v;
			}
		return $flds;
	}

	/**
	 * Returns percent given a range and a position.
	 * @param float $min Range minimum value.
	 * @param float $max Range maximum value.
	 * @param float $pos Range position.
	 * @return float The percentage position.
	 */
	function kcmRangeToPercent($min,$max,$pos){
		return $max-$min==0 ? 0 : ($pos-$min) / ($max-$min) * 100;
	}

	/**
	 * Returns a red-grean (healthbar) color given percent (where 0 is red).
	 * @param float $pos The position to get it's color.
	 * @return string The color (in css hex format).
	 */
	function kcmPercentToHealth($pos){
		return kcmGetGradColor('#FF0000','#00FF00',round($pos));
	}

	/**
	 * Returns a color between two colors at a certain percentage position.
	 * @param string $col1 The initial color (in css hex).
	 * @param string $col2 The final color (in css hex).
	 * @param integer $pos Percentage (0..100) to find color (where 0 matches $col1 and 100 $col2).
	 * @return string The color (in css hex) of pos between $col1 and $col2.
	 */
	function kcmGetGradColor($mincolor,$maxcolor,$percent){
		$percent = $percent / 100;
		$mincolor = hexdec( substr( $mincolor, 1 ) );
		$maxcolor = hexdec( substr( $maxcolor, 1 ) );
		$r1 = ($mincolor >> 16) & 0xff;
		$g1 = ($mincolor >> 8) & 0xff;
		$b1 = ($mincolor & 0xff);
		$r2 = ($maxcolor >> 16) & 0xff;
		$g2 = ($maxcolor >> 8) & 0xff;
		$b2 = $maxcolor & 0xff;
		$r = $r1 + ($r2 - $r1) * $percent;
		$g = $g1 + ($g2 - $g1) * $percent;
		$b = $b1 + ($b2 - $b1) * $percent;
		$color = ($r << 16) | ($g << 8) | $b;
		return sprintf('#%06x', $color);
	}

	/**
	 * Parses a string similar to strtotime's input (-20 hours 10 minutes +4 seconds).
	 * @param string $timestr The time string to parse.
	 * @param boolean $returnempty Whether to return empty components or not (defaults to true).
	 * @return array An array of parsed components [year:y,month:m...].
	 */
	function parsestrtime($timestr,$returnempty=true){
		$result=array('year'=>0,'month'=>0,'day'=>0,'hour'=>0,'minute'=>0,'millisecond'=>0,'second'=>0);
		$timestr=str_replace(array(NULL,CR,LF,TAB,VTAB),' ',strtolower($timestr));
		while(strpos($timestr,'  ')!==false)
			$timestr=str_replace('  ',' ',strtolower($timestr));
		foreach($result as $name=>$unused)
			$timestr=str_replace(' '.$name,$name,$timestr);
		$lastsign='';
		foreach(explode(' ',$timestr) as $token)
			foreach($result as $name=>$unused)
				if(strpos($token,$name)!==false && strlen($token)){
					$token=str_replace(array($name.'s',$name),'',$token);
					($token[0]!='+' && $token[0]!='-' && $lastsign=='-') ?  $result[$name]-=floatval($token) : $result[$name]+=floatval($token);
					$lastsign=($token[0]=='-' ? '-' : '');
					break;
				}
		if(!$returnempty)foreach($result as $n=>$v)if($v==0)unset($result[$n]);
		return $result;
	}

	/**
	 * Parses an integer timestamp and returns it's components, similar to parsestrtime().
	 * @param integer $timeint The timestamp to parse.
	 * @param boolean $returnempty Whether to return empty components or not (defaults to true).
	 * @return array An array of parsed components [year:y,month:m...].
	 */
	function parseinttime($timeint,$returnempty=true){
		$result=array_combine(array('year','month','day','hour','minute','millisecond','second'),explode(',',date('Y,n,j,G,i,u,s',(int)$timeint)));
		foreach($result as $n=>$v)$result[$n]=(int)$v;
		if(!$returnempty)foreach($result as $n=>$v)if($v==0)unset($result[$n]);
		return $result;
	}

	/**
	 * Returns $prev or $next depending on who's nearest to $curr.
	 * @param integer $prev The previous (lower) value.
	 * @param integer $curr The test value.
	 * @param integer $next The next (higher) value.
	 * @return integer The value of $prev or $next.
	 */
	function nearestToTime($prev,$curr,$next){
		return $curr<(($next-$prev)/2)+$prev ? $prev : $next;
	}

	/**
	 * Returns $timestamp+($period*N) nearest to today.
	 * @param integer $initial Initial timestamp.
	 * @param string $period A string representation of a period (eg: "+2 years").
	 * @param integer $nearto The final time to near to.
	 * @return integer The final calculated timestamp.
	 */
	function nearestPeriodToTime($initial,$period,$nearto){
		$next=$initial; $pperiod=parsestrtime($period,true);
		if($pperiod['year']!=0){ // by years
			while(($next=strtotime($period,$next)) && $next<$nearto)$prev=$next;
			return nearestToTime($prev,$nearto,$next);
		}/* TODO: Optimize function by using "JumpStart" Algorithm specific to the major component.
		if($pperiod['month']!=0){ // by months
			while(($next=strtotime($period,$next)) && $next<$nearto)
				$prev=$next;
			return nearestToTime($prev,$nearto,$next);
		}
		if($pperiod['day']!=0){ // by days
			while(($next=strtotime($period,$next)) && $next<$nearto)
				$prev=$next;
			return nearestToTime($prev,$nearto,$next);
		}
		if($pperiod['hour']!=0){ // by hours
			while(($next=strtotime($period,$next)) && $next<$nearto)
				$prev=$next;
			return nearestToTime($prev,$nearto,$next);
		}
		if($pperiod['minute']!=0){ // by minutes
			while(($next=strtotime($period,$next)) && $next<$nearto)
				$prev=$next;
			return nearestToTime($prev,$nearto,$next);
		}
		if($pperiod['second']!=0){ // by seconds
			while(($next=strtotime($period,$next)) && $next<$nearto)
				$prev=$next;
			return nearestToTime($prev,$nearto,$next);
		}*/
		// no period?! calculate normally but employ a failsafe maximum
		static $MAXITERS=99999999; $iter=0;
		while(($next=strtotime($period,$next)) && $next<$nearto && $iter<$MAXITERS){
			$prev=$next;
			$iter++;
		}
		return nearestToTime($prev,$nearto,$next);
	}

	$GLOBALS['KCM_COUNTRIES']=explode(',','Afghanistan,Albania,Algeria,Andorra,Angola,Antigua and Barbuda,Argentina,Armenia,Australia,Austria,Azerbaijan,Bahamas,Bahrain,Bangladesh,Barbados,Belarus,Belgium,Belize,Benin,Bhutan,Bolivia,Bosnia and Herzegovina,Botswana,Brazil,Brunei,Bulgaria,Burkina Faso,Burundi,Cambodia,Cameroon,Canada,Cape Verde,Central African Republic,Chad,Chile,China,Colombi,Comoros,Congo (Brazzaville),Congo,Costa Rica,Cote d\'Ivoire,Croatia,Cuba,Cyprus,Czech ,Denmark,Djibouti,Dominica,Dominican Republic,East Timor (Timor Timur),Ecuador,Egypt,El Salvador,Equatorial Guinea,Eritrea,Estonia,Ethiopia,Fiji,Finland,France,Gabon,Gambia, The,Georgia,Germany,Ghana,Greece,Grenada,Guatemala,Guinea,Guinea-Bissau,Guyana,Haiti,Honduras,Hungary,Iceland,India,Indonesia,Iran,Iraq,Ireland,Israel,Italy,Jamaica,Japan,Jordan,Kazakhstan,Kenya,Kiribati,Korea, North,Korea, South,Kuwait,Kyrgyzstan,Laos,Latvia,Lebanon,Lesotho,Liberia,Libya,Liechtenstein,Lithuania,Luxembourg,Macedonia,Madagascar,Malawi,Malaysia,Maldives,Mali,Malta,Marshall Islands,Mauritania,Mauritius,Mexico,Micronesia,Moldova,Monaco,Mongolia,Morocco,Mozambique,Myanmar,Namibia,Nauru,Nepa,Netherlands,New Zealand,Nicaragua,Niger,Nigeria,Norway,Oman,Pakistan,Palau,Panama,Papua New Guinea,Paraguay,Peru,Philippines,Poland,Portugal,Qatar,Romania,Russia,Rwanda,Saint Kitts and Nevis,Saint Lucia,Saint Vincent,Samoa,San Marino,Sao Tome and Principe,Saudi Arabia,Senegal,Serbia and Montenegro,Seychelles,Sierra Leone,Singapore,Slovakia,Slovenia,Solomon Islands,Somalia,South Africa,Spain,Sri Lanka,Sudan,Suriname,Swaziland,Sweden,Switzerland,Syria,Taiwan,Tajikistan,Tanzania,Thailand,Togo,Tonga,Trinidad and Tobago,Tunisia,Turkey,Turkmenistan,Tuvalu,Uganda,Ukraine,United Arab Emirates,United Kingdom,United States,Uruguay,Uzbekistan,Vanuatu,Vatican City,Venezuela,Vietnam,Yemen,Zambia,Zimbabwe,Other');

	/**
	 * TODO: Refactor the following code.
	 */

	uses('core/ajax.php','core/apps.php','exts/wkhtmltox.php','core/email.php');

	class kcmAjax {
		/**
		 * Generates the HTML for an event's document.
		 * @param kcmEvent $event The event's database id.
		 * @return string The generated HTML.
		 */
		protected static function ghtm($event){
			$header='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/></head><body>';
			$footer='</body></html>';
			$doc=$event->document();
			if($doc->id>0){
				$html=$doc->html;
				// settings category model client event
				$flds=CmsHost::cms()->config_get('kcm-fields');
				$flds=$flds=='' ? array() : (array)@json_decode($flds);
				$flds=(object)array('fields'=>$flds);
				$flds=kcmGetFieldValues($flds,$event->model()->type(),$event->model(),$event->client(),$event);
				foreach($flds as $fld=>$dat)$html=str_replace('{'.$fld.'}',Security::snohtml($dat),$html);
				return $header.$html.$footer;
			}else return '<b>Fatal error: Document template could not be read from persistent database storage.</b>';
		}
		/**
		 * Converts some HTML to PDF.
		 * @param kcmEvent $event Target event to render.
		 * @return WKPDF The rendered PDF object.
		 */
		protected static function mpdf($event){
			$pdf=new WKPDF();
			$pdf->set_source(WKPDF::SRC_HTML,self::ghtm($event));
			$pdf->set_margins('0px','0px','0px','0px');
			$pdf->render();
			return $pdf;
		}
		public static function down($event){
			$event=new kcmEvent($event);
			$event->load();
			if(kcmClients::loggedin() && ($event->client()->id==kcmClients::loggedin()->id)){
				$pdf=self::mpdf($event);
				switch($event->type){
					case kcmEvents::TYPE_OTHER:   $typ='Other';   break;
					case kcmEvents::TYPE_SALE:    $typ='Sale';    break;
					case kcmEvents::TYPE_INSTALL: $typ='Install'; break;
					case kcmEvents::TYPE_SERVICE: $typ='Service'; break;
					default:                      $typ='Unknown';
				}
				$pdf->output(WKPDF::OUT_DOWNLOAD,Security::filename($event->id.' ['.date('dmy',$event->time).'] - '.$client->name.' '.$client->surname).' ('.$typ.').pdf');
			}else die('Access Denied: Login expired.');
		}
		public static function mail($event){
			$event=new kcmEvent($event);
			$event->load();
			if(($client=kcmClients::loggedin()) && ($client->email!='') && ($event->client()->id==$client->id)){
				$pdf=self::mpdf($event);
				$file='document.pdf';
				$pdf=$pdf->output(WKPDF::OUT_RETURN,$file);
				$from='noreply@'.$_SERVER['SERVER_NAME'];
				$to=$client->email;
				$mn=$event->model()->name=='' ? 'Unknown Model' : $event->model()->name;
				switch($event->type){
					case kcmEvents::TYPE_SALE:    $subject='re: Your Purchase of "'.$mn.'"'; break;
					case kcmEvents::TYPE_INSTALL: $subject='re: Installation of "'.$mn.'"';  break;
					case kcmEvents::TYPE_SERVICE: $subject='re: Servicing for "'.$mn.'"';     break;
					default: $subject='';
				}
				$message=nl2br(Security::snohtml(
					'Dear '.$client->name.($client->surname!=''? ' '.$client->surname : '').','.CRLF.CRLF
					.'Please find your document attached. Don\'t hesitate to ask us if you have any further queries.'
					.CRLF.CRLF.'Sincerely,'.CRLF.$_SERVER['SERVER_NAME']
				));
				$email=new Email($from,$to,$from,$subject,$message,array($file=>$pdf));
				return $email->send()==Email::ST_SUCCESS;
			}else die('Access Denied: Login expired.');
		}
		public static function updt($name,$surname,$address,$town,$country,$email,$landline,$mobile){
			$client=kcmClients::loggedin();
			$client->name=$name;
			$client->surname=$surname;
			$client->address=$address;
			$client->town=$town;
			$client->country=$country;
			$client->email=$email;
			$client->landline=$landline;
			$client->mobile=$mobile;
			return $client->save() ? array('success'=>true) : array('success'=>false,'reason'=>'Failed updating database');
		}
	}
	Ajax::register('kcmAjax','down',array('event'=>'integer'));
	Ajax::register('kcmAjax','mail',array('event'=>'integer'));
	Ajax::register('kcmAjax','updt',array('uname'=>'string',
		'usurname'=>'string','uaddress'=>'string','utown'=>'string',
		'ucountry'=>'string','uemail'=>'string','ulandline'=>'string',
		'umobile'=>'string'));

	/**
	 * Convert file size from bytes to human-readable/compact format.
	 * @param integer $size Original file size in bytes.
	 * @return string Human-readable size.
	 */
	function kcmBytesToHuman($size){
		$type=array('bytes','KB','MB','GB','TB','PB','EB','ZB','YB');
		$i=0;
		while($size>=1024){
			$size/=1024;
			$i++;
		}
		return (ceil($size*100)/100).' '.$type[$i];
	}

	/**
	 * Shortens the filename to a human-readable format.<br/>
	 * The filename BECOMES UNRECOVERABLE. Do not reuse file name!!
	 * @param string $name Original filename, path or url
	 * @param string $maxlength (Optional) maximum length of filename. Use 0 to disable (default is 10).
	 * @param string $rangetext (Optional) text used to denote replaced text (default to three dots).
	 * @return string The shortened filename.
	 */
	function kcmShortFileName($name,$maxlength=10,$rangetext='...'){
		$url=@parse_url($name);
		if(isset($url['scheme']) && isset($url['path']))$url['path']; // uri
		$name=basename($name); // filename
		$xtra=strlen($name)-$maxlength;
		if($maxlength && $xtra>0){
			$left=round($maxlength/2);
			$name=substr_replace($name,$rangetext,$left,strlen($rangetext)-$left*2);
		}
		return $name;
	}

	class kcmUploader {
		/**
		 * Generates an uploader button.
		 * @param integer $id Uploader id.
		 * @param integer $w (Optional) Button width in pixels (defaults to 71).
		 */
		public static function button($id,$w=71){
			$src=Security::snohtml(Ajax::url(__CLASS__,'uploader').'&i=').$id;
			$style='background: url(\''.KeenCPM::url().'img/upload-btn-'.CFG::get('CMS_HOST').'.gif'.'\') no-repeat left -54px; margin-top: 1px; border: none; vertical-align: middle;';
			?><iframe width="<?php echo (int)$w; ?>" height="18" frameborder="none" scrolling="no" src="<?php echo $src; ?>" style="<?php echo $style; ?>"></iframe><?php
		}
		/**
		 * Generates the HTML for the uploader button (iframe), including the real flash uploader widget.
		 */
		public static function uploader(){
			?><!DOCTYPE HTML><html><head><title></title><style type="text/css">body,html{margin:0;padding:0;}</style></head><body>
			<?php SwfUpload::init(); ?>
			<span id="kcm-upload-thumb"><!----></span>
			<script type="text/javascript">
				window.onload=function(){
					try {<?php echo CRLF;
						$url='http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].Ajax::url(__CLASS__,'uploaded');
						$upload=new SwfUpload('swfupload');
						$upload->set_placeholder('"kcm-upload-thumb"');
						$_REQUEST['i'] = isset($_REQUEST['i']) ? (int)$_REQUEST['i'] : 0; // error correction and input filtering
						$upload->handle(SwfUpload::EVENT_UPLOAD_START,    'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.kcm_thumb_update_start.apply( parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_UPLOAD_PROGRESS, 'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.kcm_thumb_update_progrs.apply(parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_UPLOAD_SUCCESS,  'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.kcm_thumb_update_finish.apply(parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_DIALOG_END,      'function(){ this.startUpload(); }');
						$upload->set_url(@json_encode($url));
						$upload->set_max_queue(0);
						$upload->set_button_cursor(SwfUpload::CURSOR_HAND);
						$upload->set_button_action(SwfUpload::ACTION_SELECT_FILE);
						$upload->set_button_size(71,18);
						$upload->set_button_sprite(@json_encode(KeenCPM::url().'img/upload-btn-'.CFG::get('CMS_HOST').'.gif'));
						$upload->set_types('"*.bmp;*.png;*.jpg;*.gif;*.ico"');
						$upload->set_description('"Image Files"');
						$upload->set_post(@json_encode(array('tmpcks'=>@json_encode($_COOKIE))));
						$upload->render(SwfUpload::AS_JS);
					?>swfu.getMovieElement().title='Browse';
					} catch(e) {
						// this is a hack to route back errors to firebug from iframe
						(typeof parent.console!='undefined' ? parent.console : console).exception(e);
					}
				}
			</script></body></html><?php
			die;
		}
		/**
		 * Generates an uploader button.
		 * @param integer $id Uploader id.
		 * @param integer $w (Optional) Button width in pixels (defaults to 71).
		 */
		public static function button2($id,$w=71){
			$src=Security::snohtml(Ajax::url(__CLASS__,'uploader2').'&i=').$id;
			$style='background: url(\''.KeenCPM::url().'img/upload-btn-'.CFG::get('CMS_HOST').'.gif'.'\') no-repeat left -54px; margin-top: 1px; border: none; vertical-align: middle;';
			?><iframe width="<?php echo (int)$w; ?>" height="18" frameborder="none" scrolling="no" src="<?php echo $src; ?>" style="<?php echo $style; ?>"></iframe><?php
		}
		/**
		 * Generates the HTML for the uploader button (iframe), including the real flash uploader widget.
		 */
		public static function uploader2(){
			?><!DOCTYPE HTML><html><head><title></title><style type="text/css">body,html{margin:0;padding:0;}</style></head><body>
			<?php SwfUpload::init(); ?>
			<span id="kcm-upload-thumb"><!----></span>
			<script type="text/javascript">
				window.onload=function(){
					try {<?php echo CRLF;
						$url='http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].Ajax::url(__CLASS__,'uploaded');
						$upload=new SwfUpload('swfupload');
						$upload->set_placeholder('"kcm-upload-thumb"');
						$_REQUEST['i'] = isset($_REQUEST['i']) ? (int)$_REQUEST['i'] : 0; // error correction and input filtering
						$upload->handle(SwfUpload::EVENT_UPLOAD_START,    'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.kcm_file_update_start.apply( parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_UPLOAD_PROGRESS, 'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.kcm_file_update_progrs.apply(parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_UPLOAD_SUCCESS,  'function(){ arguments.push=Array.prototype.push; arguments.push('.$_REQUEST['i'].'); parent.kcm_file_update_finish.apply(parent,arguments); }');
						$upload->handle(SwfUpload::EVENT_DIALOG_END,      'function(){ this.startUpload(); }');
						$upload->set_url(@json_encode($url));
						$upload->set_max_queue(0);
						$upload->set_button_cursor(SwfUpload::CURSOR_HAND);
						$upload->set_button_action(SwfUpload::ACTION_SELECT_FILE);
						$upload->set_button_size(71,18);
						$upload->set_button_sprite(@json_encode(KeenCPM::url().'img/upload-btn-'.CFG::get('CMS_HOST').'.gif'));
						$upload->set_types('"*.pdf;*.xps;*.doc;*.docx;*.txt;"');
						$upload->set_description('"Image Files"');
						$upload->set_post(@json_encode(array('tmpcks'=>@json_encode($_COOKIE))));
						$upload->render(SwfUpload::AS_JS);
					?>swfu.getMovieElement().title='Browse';
					} catch(e) {
						// this is a hack to route back errors to firebug from iframe
						(typeof parent.console!='undefined' ? parent.console : console).exception(e);
					}
				}
			</script></body></html><?php
			die;
		}
		/**
		 * Handle uploaded file.
		 * @return array|boolean Returns info for uploaded file or false on error.
		 */
		public static function uploaded(){
			// do a request to ::authorize() with specified cookies and check if return is true
			$cookies=isset($_POST['tmpcks']) ? (array)@json_decode($_POST['tmpcks']) : null;
			if(!$cookies)return false;
			$url='http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].Ajax::url(__CLASS__,'authorized');
			$resp=Connect::get($url,false,null,false,-1,$cookies);
			if(!@json_decode(trim($resp,'()')))return false;
			// handle the upload(s) and return details as json
			$file=new SwfUploadFile(); 
			foreach(SwfUpload::uploaded() as $file)
				if(file_put_contents(CmsHost::cms()->upload_dir().$file->name,$file->data))
					return array( // only use the last uploaded file
						'url'=>CmsHost::cms()->upload_url().$file->name,
						'file'=>kcmShortFileName($file->name),
						'size'=>'('.kcmBytesToHuman(strlen($file->data)).')'
					);
			return false;
		}
		/**
		 * AJAX method to return whether current user is logged in and an administrator or not.
		 * @return boolean True if logged in user is an administrator, false otherwise.
		 */
		public static function authorized(){
			// this function is mostly used for flash uploader session bug
			// in short, we do an ajax call to this method while providing and verifying cookies.
			return CmsHost::cms()->is_admin();
		}
	}
	// register ajax/api calls
	Ajax::register('kcmUploader','uploader');
	Ajax::register('kcmUploader','uploaded');
	Ajax::register('kcmUploader','uploader2');
	Ajax::register('kcmUploader','uploaded2');
	Ajax::register('kcmUploader','authorized');

?>