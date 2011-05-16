<?php defined('K2F') or die;

	uses('core/events.php','exts/jswidget.php','core/ajax.php','core/security.php');

	/**
	 * A class to ease interaction with Google Maps API.
	 *
	 * http://code.google.com/apis/maps/documentation/javascript/reference.html
	 */
	class Google_Map extends jsWidget {

		/// STATIC FUNCTIONALITY AND CONFIG ///

		public static $USING_SENSOR=false;
		public static $USED_LIBRARIES=array();
		private static $INITTED=false;
		public static function init($force=false){
			if(!self::$INITTED || $force){
				$args=array(
					'sensor'=>self::$USING_SENSOR ? 'true' :'false',
					'libraries'=>implode(',',self::$USED_LIBRARIES)
				);
				foreach($args as $k=>$v)$args[$k]=urlencode($k).'='.urlencode($v); $args=implode('&amp;',array_values($args));
				echo '<script type="text/javascript" src="http://maps.google.com/maps/api/js?'.Security::snohtml($args).'"></script>';
				echo '<script type="text/javascript" src="http://google-maps-utility-library-v3.googlecode.com/svn/tags/infobox/1.1.2/src/infobox_packed.js"></script>';
				echo '<script type="text/javascript">window.kgm={};window.kgmm={};window.kgmt={};window.kgmp={};</script>';
				self::$INITTED=true;
			}
		}

		/// CLASS CONSTANTS ///

		/**
		 * Displays the normal, default 2D tiles of Google Maps.
		 */
		const TYPE_ROADMAP   = 'google.maps.MapTypeId.ROADMAP';
		/**
		 * Displays satellite photographic tiles.
		 */
		const TYPE_SATELLITE = 'google.maps.MapTypeId.SATELLITE';
		/**
		 * Displays a mix of photographic tiles and a tile layer for prominent features (roads, city names).
		 */
		const TYPE_HYBRID    = 'google.maps.MapTypeId.HYBRID';
		/**
		 * Displays physical relief tiles for displaying elevation and water features (mountains, rivers, etc.).
		 */
		const TYPE_TERRAIN   = 'google.maps.MapTypeId.TERRAIN';

		/**
		 * This event is fired when the viewport bounds have changed.
		 */
		const EVENT_CHANGED_BOUNDS     = '"bounds_changed"';
		/**
		 * This event is fired when the map center property changes.
		 */
		const EVENT_CHANGED_CENTER     = '"center_changed"';
		/**
		 * This event is fired when the user clicks on the map (but not when they click on a marker or infowindow).
		 */
		const EVENT_CLICK              = '"click"';
		/**
		 * This event is fired when the user double-clicks on the map. Note that the click event will also fire, right before this one.
		 */
		const EVENT_CLICK_DOUBLE       = '"dblclick"';
		/**
		 * This event is repeatedly fired while the user drags the map.
		 */
		const EVENT_DRAG               = '"drag"';
		/**
		 * This event is fired when the user stops dragging the map.
		 */
		const EVENT_DRAG_END           = '"dragend"';
		/**
		 * This event is fired when the user starts dragging the map.
		 */
		const EVENT_DRAG_START         = '"dragstart"';
		/**
		 * 	This event is fired when the map becomes idle after panning or zooming.
		 */
		const EVENT_IDLE               = '"idle"';
		/**
		 * This event is fired when the mapTypeId property changes.
		 */
		const EVENT_CHANGED_MAP_TYPE   = '"maptypeid_changed"';
		/**
		 * This event is fired whenever the user's mouse moves over the map container.
		 */
		const EVENT_MOUSE_MOVE         = '"mousemove"';
		/**
		 * This event is fired when the user's mouse exits the map container.
		 */
		const EVENT_MOUSE_OUT          = '"mouseout"';
		/**
		 * This event is fired when the user's mouse enters the map container.
		 */
		const EVENT_MOUSE_OVER         = '"mouseover"';
		/**
		 * 	This event is fired when the projection has changed.
		 */
		const EVENT_CHANGED_PROJECTION = '"projection_changed"';
		/**
		 * 	Developers should trigger this event on the map when the div changes size: google.maps.event.trigger(map, 'resize') .
		 */
		const EVENT_RESIZE             = '"resize"';
		/**
		 * This event is fired when the DOM contextmenu event is fired on the map container.
		 */
		const EVENT_CLICK_RIGHT        = '"rightclick"';
		/**
		 * This event is fired when the visible tiles have finished loading.
		 */
		const EVENT_LOADED_TILES       = '"tilesloaded"';
		/**
		 * This event is fired when the map zoom property changes.
		 */
		const EVENT_CHANGED_ZOOM       = '"zoom_changed"';
		/**
		 * Remove all of the default UI.
		 */
		const UI_DISABLED = '"false"';
		/**
		 * Enable all of the default UI.
		 */
		const UI_ENABLED  = '"true"';
		
		const POS_BOTTOM_CENTER = 'google.maps.ControlPosition.BOTTOM_CENTER';
		const POS_BOTTOM_LEFT   = 'google.maps.ControlPosition.BOTTOM_LEFT';
		const POS_BOTTOM_RIGHT  = 'google.maps.ControlPosition.BOTTOM_RIGHT';
		const POS_LEFT_BOTTOM   = 'google.maps.ControlPosition.LEFT_BOTTOM';
		const POS_LEFT_CENTER   = 'google.maps.ControlPosition.LEFT_CENTER';
		const POS_LEFT_TOP      = 'google.maps.ControlPosition.LEFT_TOP';
		const POS_RIGHT_BOTTOM  = 'google.maps.ControlPosition.RIGHT_BOTTOM';
		const POS_RIGHT_CENTER  = 'google.maps.ControlPosition.RIGHT_CENTER';
		const POS_RIGHT_TOP     = 'google.maps.ControlPosition.RIGHT_TOP';
		const POS_TOP_CENTER    = 'google.maps.ControlPosition.TOP_CENTER';
		const POS_TOP_LEFT      = 'google.maps.ControlPosition.TOP_LEFT';
		const POS_TOP_RIGHT     = 'google.maps.ControlPosition.TOP_RIGHT';

		/**
		 * Uses the default map type control. The control which DEFAULT maps to will vary according to window size and other factors. It may change in future versions of the API.
		 */
		const STYLE_MAP_DEFAULT        = 'google.maps.MapTypeControlStyle.DEFAULT';
		/**
		 * A dropdown menu for the screen realestate conscious.
		 */
		const STYLE_MAP_DROPDOWN_MENU  = 'google.maps.MapTypeControlStyle.DROPDOWN_MENU';
		/**
		 * The standard horizontal radio buttons bar.
		 */
		const STYLE_MAP_HORIZONTAL_BAR = 'google.maps.MapTypeControlStyle.HORIZONTAL_BAR';
		
		/**
		 * The default zoom control. The control which DEFAULT maps to will vary according to map size and other factors. It may change in future versions of the API.
		 */
		const STYLE_ZOOM_DEFAULT = 'google.maps.ZoomControlStyle.DEFAULT';
		/**
		 * The larger control, with the zoom slider in addition to +/- buttons.
		 */
		const STYLE_ZOOM_LARGE   = 'google.maps.ZoomControlStyle.LARGE';
		/**
		 * A small control with buttons to zoom in and out.
		 */
		const STYLE_ZOOM_SMALL   = 'google.maps.ZoomControlStyle.SMALL';

		/// PROTECTED PROPERTIES ///

		/**
		 * @var string Map width in CSS format. If integer, it is converted to pixels when map is rendered.
		 */
		protected $width='100px';
		/**
		 * @var string Map width in CSS format. If integer, it is converted to pixels when map is rendered.
		 */
		protected $height='100px';
		/**
		 * @var null|array Array of lat/lon for initial location.
		 */
		protected $initialloc=null;
		/**
		 * @var string The type of tiles to be displayed in map (see TYPE_* constants).
		 */
		protected $type=self::TYPE_ROADMAP;
		/**
		 * @var integer The zoom level, 0 is completely zoomed out and 18 street-level.
		 */
		protected $zoom=8;
		/**
		 * @var array Array of options.
		 */
		protected $options=array();
		/**
		 * @var boolean Whether to show crosshair at the center of the page or not.
		 */
		protected $crosshair='false';
		
		/// PUBLIC FUNCTIONS ///

		/**
		 * Constructs a new map instance.
		 * @param string $name Map name/id - ensure it is unique.
		 */
		public function __construct($name){
			self::init();
			$this->name=$name;
		}
		/**
		 * Used to set location of map.
		 * @param string $latitude Latitude position (string because too big for float).
		 * @param string $longitude Longitude position (string because too big for float).
		 */
		public function set_location($latitude,$longitude){
			$this->initialloc=array($latitude,$longitude);
			if($this->rendered)
				echo $this->mtd('setCenter',array('new google.maps.LatLng('.$this->initialloc[0].','.$this->initialloc[1].')')).';'.CRLF;
		}
		/**
		 * Set map widget size.
		 * @param string $width Map width in CSS format.
		 * @param string $height Map height in CSS format.
		 */
		public function set_size($width=null,$height=null){
			if(is_int($width))$width.='px';
			if(is_int($height))$height.='px';
			if($width!==null)$this->width=$width;
			if($height!==null)$this->height=$height;
			if($this->rendered)
				echo 'var div='.$this->mtd('getDiv').';'.CRLF.'div.setAttribute("style",'
					.@json_encode('width: '.$this->width.'; height: '.$this->height.';').');'.CRLF
					.'var div='.$this->mtd('getDiv').'.parentNode;'.CRLF.'div.setAttribute("style",'
					.@json_encode('width: '.$this->width.'; height: '.$this->height.';').');'.CRLF;
		}
		/**
		 * Set map tiles type.
		 * @param string $type Value of any MAP_* constant.
		 */
		public function set_type($type=self::TYPE_ROADMAP){
			$this->type=$type;
			if($this->rendered)
				echo $this->mtd('setMapTypeId',array($type)).';'.CRLF;
		}
		/**
		 * Set map zoom level.
		 * @var integer $zoom The zoom level, 0 is completely zoomed out and 18 street-level.
		 */
		public function set_zoom($zoom=8){
			$this->zoom=max(min((int)$zoom,20),0);
			if($this->rendered)
				echo $this->mtd('setZoom',array($this->zoom)).';'.CRLF;
		}
		/**
		 * Sets the background color (which is visible while tiles are loading).
		 * @param string $color Color in CSS format, make sure to <b>add double quotes if needed!</b>
		 */
		public function set_bgcolor($color='"#E5E3DF"'){
			$this->options['backgroundColor']=$color;
			if($this->rendered)
				echo $this->mtd('getDiv').'.style.backgroundColor='.$color.';'.CRLF;
				//echo $this->mtd('setOptions',array('{"backgroundColor":'.$color.'}'));
		}
		/**
		 * Enables or disables the default ui.
		 * @param string $enabled Javascript code that must evaluate to true or false.
		 */
		public function set_defaultui($enabled='true'){
			$enabled=self::b2s($enabled);
			$this->options['disableDefaultUI']=$enabled;
			if($this->rendered)
				echo $this->mtd('setOptions',array('{"disableDefaultUI":'.$enabled.'}')).';'.CRLF;
		}
		/**
		 * Enables or disables the "double click to zoom" behavior (defaults to enabled).
		 * @param string Javascript code that must evaluate to true or false.
		 */
		public function set_dblclick_zoom($enabled='true'){
			$enabled=self::b2s($enabled);
			$this->options['disableDoubleClickZoom']=$enabled;
			if($this->rendered)
				echo $this->mtd('setOptions',array('{"disableDoubleClickZoom":'.$enabled.'}')).';'.CRLF;
		}
		/**
		 * Enables or disables the "drag to pan" behavior (defaults to enabled).
		 * @param string $enabled Javascript code that must evaluate to true or false.
		 */
		public function set_draggable($enabled='true'){
			$enabled=self::b2s($enabled);
			$this->options['draggable']=$enabled;
			if($this->rendered)
				echo $this->mtd('setOptions',array('{"draggable":'.$enabled.'}')).';'.CRLF;
		}
		/**
		 * Enables or disables the crosshair at the center of the page (defaults to disabled).
		 * @param string $enabled Javascript code that must evaluate to true or false.
		 */
		public function set_crosshair($enabled='false'){
			$enabled=self::b2s($enabled);
			$this->crosshair=$enabled;
			if($this->rendered)
				echo 'document.getElementById('.@json_encode($this->name.'-crosshair').').style.display='.$enabled.' ? "block" : "none";';
		}
		/**
		 * Show/hide and set options for maptype control.
		 * @param string $enabled Javascript code that must evaluate to true or false.
		 * @param string|array $mapTypes An array of map types (see TYPE_* constants).
		 * @param string $position Control position (see POS_* constants).
		 * @param string $style Control style (see STYLE_MAP_* constants).
		 */
		public function set_ctrl_maptype($enabled=true,$mapTypes=array(self::TYPE_HYBRID,self::TYPE_ROADMAP,self::TYPE_SATELLITE,self::TYPE_TERRAIN),$position=self::POS_TOP_RIGHT,$style=self::STYLE_MAP_DEFAULT){
			$enabled=self::b2s($enabled);
			$this->options['mapTypeControl']=$enabled;
			$this->options['mapTypeControlOptions']=array('mapTypes'=>$mapTypes,'position'=>$position,'style'=>$style);
			if($this->rendered)
				echo $this->mtd('setOptions',array(self::a2o(array('mapTypeControl'=>$enabled,'mapTypeControlOptions'=>$this->options['mapTypeControlOptions'])))).';'.CRLF;
		}
		/**
		 * Show/hide and set options for panning control.
		 * @param string $enabled Javascript code that must evaluate to true or false.
		 * @param string $position Control position (see POS_* constants).
		 */
		public function set_ctrl_pan($enabled,$position=self::POS_TOP_LEFT){
			$enabled=self::b2s($enabled);
			$this->options['panControl']=$enabled;
			$this->options['panControlOptions']=array('position'=>$position);
			if($this->rendered)
				echo $this->mtd('setOptions',array(self::a2o(array('panControl'=>$enabled,'panControlOptions'=>$this->options['panControlOptions'])))).';'.CRLF;
		}
		/**
		 * Show/hide and set options for scale control.
		 * @param string $enabled Javascript code that must evaluate to true or false.
		 * @param string $position Control position (see POS_* constants).
		 */
		public function set_ctrl_scale($enabled,$position=self::POS_BOTTOM_LEFT){
			$enabled=self::b2s($enabled);
			$this->options['scaleControl']=$enabled;
			$this->options['scaleControlOptions']=array('position'=>$position /*, there's also style, but it is unused */);
			if($this->rendered)
				echo $this->mtd('setOptions',array(self::a2o(array('scaleControl'=>$enabled,'scaleControlOptions'=>$this->options['scaleControlOptions'])))).';'.CRLF;
		}
		/**
		 * Show/hide and set options for streetview control.
		 * @param string $enabled Javascript code that must evaluate to true or false.
		 * @param string $position Control position (see POS_* constants).
		 */
		public function set_ctrl_streetview($enabled,$position='""'){
			$enabled=self::b2s($enabled);
			$this->options['streetViewControl']=$enabled;
			$this->options['streetViewControlOptions']=array('position'=>$position /*, there's also style, but it is unused */);
			if($this->rendered)
				echo $this->mtd('setOptions',array(self::a2o(array('streetViewControl'=>$enabled,'streetViewControlOptions'=>$this->options['streetViewControlOptions'])))).';'.CRLF;
		}
		/**
		 * Show/hide and set options for zoom control.
		 * @param string $enabled Javascript code that must evaluate to true or false.
		 * @param string $position Control position (see POS_* constants).
		 * @param string $style Control style (see STYLE_ZOOM_* constants).
		 */
		public function set_ctrl_zoom($enabled,$position=self::POS_TOP_LEFT,$style=self::STYLE_ZOOM_DEFAULT){
			$enabled=self::b2s($enabled);
			$this->options['zoomControl']=$enabled;
			$this->options['zoomControlOptions']=array('position'=>$position,'style'=>$style);
			if($this->rendered)
				echo $this->mtd('setOptions',array(self::a2o(array('zoomControl'=>$enabled,'zoomControlOptions'=>$this->options['zoomControlOptions'])))).';'.CRLF;
		}

		/// POST-RENDER MAP MANAGEMENT ///

		/**
		 * Returns javascript code which can be used to get the map's current center-latitude.
		 * <br><b>Must be called after map was rendered!</b>
		 * @return string Javascript code which returns map center-latitude.
		 */
		public function get_center_lat(){
			echo $this->mtd('getCenter').'.lat()';
		}
		/**
		 * Returns javascript code which can be used to get the map's current center-longitude.
		 * <br><b>Must be called after map was rendered!</b>
		 * @return string Javascript code which returns map center-longitude.
		 */
		public function get_center_lon(){
			echo $this->mtd('getCenter').'.lng()';
		}
		/**
		 * Returns javascript code which can be used to get the map's current zoom level.
		 * <br><b>Must be called after map was rendered!</b>
		 * @return string Javascript code which returns map zoom.
		 */
		public function get_zoom(){
			echo $this->mtd('getZoom');
		}
		/**
		 * Pan (move) map viewport to a lat/long position.
		 * <br><b>Must be called after map was rendered!</b>
		 * @param string $x Latitude position.
		 * @param string $y Longitude position.
		 */
		public function pan_to($x,$y){
			echo $this->mtd('panBy',array('map.setCenter(new google.maps.LatLng('.$x.','.$y.'))')).';'.CRLF;
		}
		/**
		 * Pan (move) map viewport by an amount of pixels.
		 * <br><b>Must be called after map was rendered!</b>
		 * @param string $x Horizontal offset (negative=east positive=west).
		 * @param string $y Vertical offset (negative=north positive=south).
		 */
		public function pan_by($x,$y){
			echo $this->mtd('panBy',array($x,$y)).';'.CRLF;
		}
		/**
		 * Returns code to discover map object.
		 * @return string Javascript code.
		 */
		public function name(){
			return 'window.kgm['.@json_encode($this->name).']';
		}
		/**
		 * Sets the event handler for an event (removes any existing handler!).
		 * <br>You can leave the second parameter empty to remove current handler instead of setting a new one.
		 * @param string $event The event name (see EVENT_* constants).
		 * @param string $handler Either name of javascript function to call, or an anonymous function.
		 */
		public function handle($event,$handler=null){
			if($handler===null){
				unset($this->events[$event]);
			}else{
				$this->events[$event]=$handler;
			}
			if($this->rendered)
				echo 'google.maps.event.clearListeners('.$this->name().','.$event.');'.($handler===null ? ''
					: CRLF.'google.maps.event.addListener('.$this->name().','.$event.','.$handler.');');
		}
		/**
		 * Creates a new map marker for this map.
		 * @param string $name Marker name (used to identify marker).
		 * @return Google_Marker The newly created marker object.
		 */
		public function add_marker($name){
			return new Google_Marker($this,$name);
		}

		/// RENDER FUNCTION ///

		/**
		 * Send map code directly to browser.
		 * @param string $mode (Optional) The output mode. See jsWidget::AS_* constants.
		 * @param string $extrahtml Some additional html to add.
		 */
		public function render($mode=self::AS_HTML,$extrahtml=''){
			if($this->rendered)trigger_error('Map has already been rendered');
			$onresize=@json_encode('google.maps.event.trigger('.$this->name().',"resize")');
			$options=$this->options+array('mapTypeId'=>$this->type,'zoom'=>$this->zoom);
			ob_start();
			?><div id="<?php echo Security::snohtml($this->name); ?>-wrap" style="width:<?php echo $this->width; ?>; height:<?php echo $this->height; ?>; position:relative;"><?php
				?><div id="<?php echo Security::snohtml($this->name); ?>" style="width:<?php echo $this->width; ?>; height:<?php echo $this->height; ?>;"><!----></div><?php
				?><div id="<?php echo Security::snohtml($this->name); ?>-crosshair" style="display:none; position:absolute; width:32px; height:32px; left:50%; top:50%; margin-top:-16px; margin-left:-16px; background:url('<?php echo Ajax::url('Resource_Crosshair','render'); ?>') no-repeat;"><!----></div><?php
				echo $extrahtml;
			?></div><?php
			echo $mode==self::AS_HTML ? ob_get_clean() : 'document.write('.@json_encode(ob_get_clean()).');';
			if($mode==self::AS_HTML){ ?><script type="text/javascript"><?php } ?>
				var opt=<?php echo self::a2o($options); ?>;
				var div=document.getElementById(<?php echo @json_encode($this->name); ?>);
				var map=new google.maps.Map(div,opt);
				<?php if($this->initialloc)echo 'map.setCenter(new google.maps.LatLng('.$this->initialloc[0].','.$this->initialloc[1].'));'.CRLF; ?>
				div.onresize=<?php echo $onresize; ?>; // as per google specs (!)
				window.setTimeout(function(){
					google.maps.event.trigger(<?php echo $this->name(); ?>,"resize");
					<?php if($this->initialloc)echo 'map.setCenter(new google.maps.LatLng('.$this->initialloc[0].','.$this->initialloc[1].'));'.CRLF; ?>
				},200);
				document.getElementById(<?php echo @json_encode($this->name.'-crosshair'); ?>).style.display=<?php echo $this->crosshair; ?> ? "block" : "none"
				<?php echo $this->name(); ?>=map;
				<?php foreach($this->events as $event=>$func)echo 'google.maps.event.addListener(map,'.$event.','.$func.');'.CRLF; ?>
			<?php if($mode==self::AS_HTML){ ?></script><?php }
			$this->rendered=true;
		}
	}

	class Google_Marker extends jsWidget {
		/**
		 * This event is fired when the marker's animation property changes.
		 */
		const EVENT_CHANGED_ANIMATION = '"animation_changed"';
		/**
		 * This event is fired when the marker icon was clicked.
		 */
		const EVENT_CLICK = '"click"';
		/**
		 * This event is fired when the marker's clickable property changes.
		 */
		const EVENT_CHANGED_CLICKABLE = '"clickable_changed"';
		/**
		 * This event is fired when the marker's cursor property changes.
		 */
		const EVENT_CHANGED_CURSOR = '"cursor_changed"';
		/**
		 * This event is fired when the marker icon was double clicked.
		 */
		const EVENT_CLICK_DOUBLE = '"dblclick"';
		/**
		 * This event is repeatedly fired while the user drags the marker.
		 */
		const EVENT_DRAG = '"drag"';
		/**
		 * This event is fired when the user stops dragging the marker.
		 */
		const EVENT_DRAG_END = '"dragend"';
		/**
		 * This event is fired when the marker's draggable property changes.
		 */
		const EVENT_CHANGED_DRAGGABLE = '"draggable_changed"';
		/**
		 * This event is fired when the user starts dragging the marker.
		 */
		const EVENT_DRAG_START = '"dragstart"';
		/**
		 * This event is fired when the marker's flat property changes.
		 */
		const EVENT_CHANGED_FLAT = '"flat_changed"';
		/**
		 * This event is fired when the marker icon property changes.
		 */
		const EVENT_CHANGED_ICON = '"icon_changed"';
		/**
		 * This event is fired when the DOM mousedown event is fired on the marker icon.
		 */
		const EVENT_MOUSE_DOWN = '"mousedown"';
		/**
		 * This event is fired when the mouse leaves the area of the marker icon.
		 */
		const EVENT_MOUSE_OUT = '"mouseout"';
		/**
		 * This event is fired when the mouse enters the area of the marker icon.
		 */
		const EVENT_MOUSE_OVER = '"mouseover"';
		/**
		 * This event is fired for the DOM mouseup on the marker.
		 */
		const EVENT_MOUSE_UP = '"mouseup"';
		/**
		 * This event is fired when the marker position property changes.
		 */
		const EVENT_CHANGED_POSITION = '"position_changed"';
		/**
		 * This event is fired when the marker is right clicked on.
		 */
		const EVENT_CLICK_RIGHT = '"rightclick"';
		/**
		 * This event is fired when the marker's shadow property changes.
		 */
		const EVENT_CHANGED_SHADOW = '"shadow_changed"';
		/**
		 * This event is fired when the marker's shape property changes.
		 */
		const EVENT_CHANGED_SHAPE = '"shape_changed"';
		/**
		 * This event is fired when the marker title property changes.
		 */
		const EVENT_CHANGED_TITLE = '"title_changed"';
		/**
		 * This event is fired when the marker's visible property changes.
		 */
		const EVENT_CHANGED_VISIBLE = '"visible_changed"';
		/**
		 * This event is fired when the marker's zIndex property changes.
		 */
		const EVENT_CHANGED_ZINDEX = '"zindex_changed"';
		/**
		 * Bouncing animation.
		 */
		const ANIM_BOUNCE = 'google.maps.Animation.BOUNCE';
		/**
		 * Drop animation.
		 */
		const ANIM_DROP   = 'google.maps.Animation.DROP';
		/**
		 * Disable any animations.
		 */
		const ANIM_NONE   = 'null';
		/**
		 * @var array Array holding current widget options.
		 */
		protected $options=array();
		/**
		 * @var Google_Map Parent map object.
		 */
		protected $map=null;
		/**
		 * @var array|null Initial location.
		 */
		protected $initialloc=null;
		/**
		 * @var string|null Initial animation.
		 */
		protected $initialani=null;
		/**
		 * Construct a new marker instance.
		 * @param Google_Map $map The parent map object.
		 * @param string $name Marker name.
		 */
		public function __construct($map,$name){
			$this->map=$map;
			$this->name=$name;
		}
		/**
		 * Returns code to discover marker object.
		 * @return string Javascript code.
		 */
		public function name(){
			return 'window.kgmm['.@json_encode($this->name).']';
		}
		/**
		 * Sets the event handler for an event (removes any existing handler!).
		 * <br>You can leave the second parameter empty to remove current handler instead of setting a new one.
		 * @param string $event The event name (see EVENT_* constants).
		 * @param string $handler Either name of javascript function to call, or an anonymous function.
		 */
		public function handle($event,$handler=null){
			if($handler===null){
				unset($this->events[$event]);
			}else{
				$this->events[$event]=$handler;
			}
			if($this->rendered)
				echo 'google.maps.event.clearListeners('.$this->name().','.$event.');'.($handler===null ? ''
					: CRLF.'google.maps.event.addListener('.$this->name().','.$event.','.$handler.');');
		}
		/**
		 * Used to set location of marker.
		 * @param string $latitude Latitude position (string because too big for float).
		 * @param string $longitude Longitude position (string because too big for float).
		 */
		public function set_location($latitude,$longitude){
			$this->initialloc=array($latitude,$longitude);
			if($this->rendered)
				echo $this->mtd('setPosition',array('new google.maps.LatLng('.$this->initialloc[0].','.$this->initialloc[1].')')).';'.CRLF;
		}
		/**
		 * Set the current animation.
		 * @param string $mode Animation type (see ANIM_* constants).
		 */
		public function set_animation($mode){
			$this->initialani=$mode;
			if($this->rendered)
				echo $this->mtd('setAnimation',array($mode)).';'.CRLF;
		}
		/**
		 * Set whether marker is clickable or not.
		 * @param string $enabled Javascript code which must evaluate to a boolean value.
		 */
		public function set_clickable($enabled){
			$enabled=self::b2s($enabled);
			$this->options['clickable']=$enabled;
			if($this->rendered)
				echo $this->mtd('setClickable',array($enabled)).';'.CRLF;
		}
		/**
		 * Set the cursor when mouse moves over marker.
		 * @param string $cursor Either a CSS cursor name or URL to cursor (probably in CSS url() format)
		 */
		public function set_cursor($cursor){
			$this->options['cursor']=$cursor;
			if($this->rendered)
				echo $this->mtd('setCursor',array($cursor)).';'.CRLF;
		}
		/**
		 * Sets whether marker can be dragged around or not.
		 * @param string $enabled Javascript string that must evaluate to boolean.
		 */
		public function set_draggable($enabled){
			$enabled=self::b2s($enabled);
			$this->options['draggable']=$enabled;
			if($this->rendered)
				echo $this->mtd('setDraggable',array($enabled)).';'.CRLF;
		}
		/**
		 * Sets whether this marker has a shadow or not.
		 * @param string|mixed $enabled Whether to shadow (false) or not (true).
		 */
		public function set_flat($enabled){
			$enabled=self::b2s($enabled);
			$this->options['flat']=$enabled;
			if($this->rendered)
				echo $this->mtd('setFlat',array($enabled)).';'.CRLF;
		}
		/**
		 * Set the icon for the marker.
		 * @param string $icon UNKNOWN
		 */
		public function set_icon($icon){
			$this->options['icon']=$icon;
			if($this->rendered)
				echo $this->mtd('setIcon',array($icon)).';'.CRLF;
		}
		/**
		 * Set the map for the marker.
		 * @param Google_Map $map The map instance.
		 */
		public function set_map($map){
			$this->map=$map;
			if($this->rendered)
				echo $this->mtd('setMap',array($map->name())).';'.CRLF;
		}
		/**
		 * Set the shadow for this marker.
		 * @param stirng $shadow UNKNOWN
		 */
		public function set_shadow($shadow){
			$this->options['shadow']=$shadow;
			if($this->rendered)
				echo $this->mtd('setShadow',array($shadow)).';'.CRLF;
		}
		/**
		 * The marker's current shape.
		 * @param string $shape UNKNOWN
		 */
		public function set_shape($shape){
			$this->options['shape']=$shape;
			if($this->rendered)
				echo $this->mtd('setShape',array($shape)).';'.CRLF;
		}
		/**
		 * Set the marker's title (tooltip text).
		 * @param string $title The title text to set.
		 */
		public function set_title($title){
			$this->options['title']=$title;
			if($this->rendered)
				echo $this->mtd('setTitle',array($title)).';'.CRLF;
		}
		/**
		 * Set whether marker is visible or not.
		 * @param string $enabled Javascript code which must evaluate to boolean.
		 */
		public function set_visible($enabled){
			$enabled=self::b2s($enabled);
			$this->options['visible']=$enabled;
			if($this->rendered)
				echo $this->mtd('setVisible',array($enabled)).';'.CRLF;
		}
		/**
		 * Set the zIndex for the marker.
		 * @param integer|string $zindex Javascript code that must evaluate to an integer.
		 */
		public function set_zindex($zindex){
			$this->options['zIndex']=$zindex;
			if($this->rendered)
				echo $this->mtd('setZIndex',array($zindex)).';'.CRLF;
		}
		/**
		 * Set the icon for the marker.
		 * <br><b>Must be called after map was rendered!</b>
		 * @return string|object $icon Icon name or object with icon details (url:string[, size:Size][, origin:Point][, anchor:Point][, scaledSize:Size]).
		 */
		public function get_icon(){
			echo $this->mtd('getIcon');
		}
		/**
		 * Creates a new map tooltip for this marker.
		 * @param string $name Tooltip name (used to identify tooltip).
		 * @return Google_Tooltip The newly created tooltip object.
		 */
		public function add_tooltip($name){
			return new Google_Tooltip($this->map,$this,$name);
		}
		/**
		 * Send marker code directly to browser.
		 * @param string $mode (Optional) The output mode. See jsWidget::AS_* constants.
		 */
		public function render($mode=self::AS_HTML){
			$options=$this->options;
			if($this->map)$options['map']=$this->map->name();
			if($this->initialloc)$options['position']='new google.maps.LatLng('.$this->initialloc[0].','.$this->initialloc[1].')';
			if($mode==self::AS_HTML){ ?><script type="text/javascript"><?php } ?>
				<?php echo $this->name(); ?> = new google.maps.Marker(<?php echo self::a2o($options); ?>);
				<?php if($this->initialani)$this->mtd('setAnimation',array($this->initialani)); ?>
				<?php foreach($this->events as $event=>$func)echo 'google.maps.event.addListener('.$this->name().','.$event.','.$func.');'; ?>
			<?php if($mode==self::AS_HTML){ ?></script><?php }
			$this->rendered=true;
		}
	}

	class Google_Tooltip extends jsWidget {
		/**
		 * This event is fired when the close button was clicked.
		 */
		const EVENT_CLICK_CLOSE = '"closeclick"';
		/**
		 * This event is fired when the content property changes.
		 */
		const EVENT_CHANGED_CONTENT = '"content_changed"';
		/**
		 * This event is fired when the <div> containing the InfoWindow's content is attached to the DOM.
		 * You may wish to monitor this event if you are building out your info window content dynamically.
		 */
		const EVENT_DOMREADY = '"domready"';
		/**
		 * This event is fired when the position property changes.
		 */
		const EVENT_CHANGED_POSITION = '"position_changed"';
		/**
		 * This event is fired when the InfoWindow's zIndex changes.
		 */
		const EVENT_CHANGED_ZINDEX = '"zindex_changed"';
		/**
		 * @var array Array holding current widget options.
		 */
		protected $options=array();
		/**
		 * @var Google_Map Parent map object.
		 */
		protected $map=null;
		/**
		 * @var Google_Marker Positioning marker object.
		 */
		protected $anchor=null;
		/**
		 * Construct a new tooltip (aka infoWindow) instance.
		 * @param Google_Map $map The parent map object.
		 * @param Google_Marker $anchor The marker object (used for positioning).
		 * @param string $name Tooltip name.
		 */
		public function __construct($map,$anchor,$name){
			$this->map=$map;
			$this->anchor=$anchor;
			$this->name=$name;
		}
		/**
		 * Returns code to discover tooltip object.
		 * @return string Javascript code.
		 */
		public function name(){
			return 'window.kgmt['.@json_encode($this->name).']';
		}
		/**
		 * Sets the event handler for an event (removes any existing handler!).
		 * <br>You can leave the second parameter empty to remove current handler instead of setting a new one.
		 * @param string $event The event name (see EVENT_* constants).
		 * @param string $handler Either name of javascript function to call, or an anonymous function.
		 */
		public function handle($event,$handler=null){
			if($handler===null){
				unset($this->events[$event]);
			}else{
				$this->events[$event]=$handler;
			}
			if($this->rendered)
				echo 'google.maps.event.clearListeners('.$this->name().','.$event.');'.($handler===null ? ''
					: CRLF.'google.maps.event.addListener('.$this->name().','.$event.','.$handler.');');
		}
		/**
		 * Shows the tooltip window.
		 * <br><b>Must be called after tooltip was rendered!</b>
		 */
		public function open(){
			echo $this->mtd('open',array($this->map->name(),$this->anchor->name())).';'.CRLF;
		}
		/**
		 * Hides the tooltip window.
		 * <br><b>Must be called after tooltip was rendered!</b>
		 */
		public function close(){
			echo $this->mtd('close').';'.CRLF;
		}
		/**
		 * Returns JS code which, when executed, it returns this tooltip's html content.
		 * <br>Usually, you'd want to execute this after the tooltip got rendered.
		 * @return string Javascript code which, when executed, returns the tooltip's html content.
		 */
		public function get_content(){
			echo $this->mtd('getContent');
		}
		/**
		 * Sets the content of the tooltip. Javascript code is expected, so json_encode() the argument if you want to pass html directly.
		 * @param string $content Javascript code which, when executed, will result in HTML content for the tooltip.
		 */
		public function set_content($content){
			$this->options['content']=$content;
			if($this->rendered)
				echo $this->mtd('setContent',array($content)).';'.CRLF;
		}
// TODO: set_map() and set_anchor()
		/**
		 * Send tooltip code directly to browser.
		 * @param string $mode (Optional) The output mode. See jsWidget::AS_* constants.
		 */
		public function render($mode=self::AS_HTML){
			$options=$this->options;
			if($mode==self::AS_HTML){ ?><script type="text/javascript"><?php } ?>
				<?php echo $this->name(); ?> = new google.maps.InfoWindow(<?php echo self::a2o($options); ?>);
				<?php foreach($this->events as $event=>$func)echo 'google.maps.event.addListener('.$this->name().','.$event.','.$func.');'; ?>
			<?php if($mode==self::AS_HTML){ ?></script><?php }
			$this->rendered=true;
		}
	}

	/**
	 * A 32x32 crosshair icon used in center of map (when enabled).
	 */
	class Resource_Crosshair {
		public static function render(){
			header('Content-type: image/png');
			die(base64_decode('
				iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAABGdBTUEAALGPC/xhBQAAAAFzUkdC
				AdnJLH8AAAAgY0hSTQAAeiYAAICDAAD6AAAAgOoAAHUwAADqYQAAOpkAABdwrM0AoAAAAAlwSFlz
				AAALEgAACxIB0t1+/AAAAAd0SU1FB9YFBxU0Kb7x6FsAAAA0dEVYdEZpbGUgTmFtZQBDOlxFcmlj
				XEVyaWMnc3R1ZmZccG9sYXJcMS40XENyb3NzaGFpci5wbme5Gz3KAAAAGHRFWHRTb2Z0d2FyZQBB
				ZG9iZSBQaG90b3Nob3A0y45nAAAAIXRFWHRXcml0ZXIAU3VwZXJQTkcgYnkgQnJlbmRhbiBCb2xs
				ZXN0+/bRAAACVUlEQVR4nN2Xy44SQRSGv7YaRsERE9m4m0QiJgSfibXzLD4CCe/BW4yB6Av0gpiQ
				yYDD0E2Xi6pDVbc0FpeZif5JLyp1+8/tP9URYYiA3Bu/teMMSIENoEMO0rq4LA4k8EI2R1EE0LKX
				3su5dnwwDiLgoY2x/NZenD02gag0bgEPwBpY7ph/dAINjFfqgHoOAjWMy5UlcjSBcmyfHKEeAEi1
				1jnG6hyT+UGldy4CP5IkSYFLjPs3HpGTCESYUEgs5fNRB75Np1MNvKZYdsrOl6G9L68iG1NUuDJS
				4DtwA8zm8/kCeA98AT4CHzAJWYW2JbumQjFj+FMetyZonSdJkk0mE2az2c/xeHwzHA4/N5vNl91u
				91e/38+VUjv3WsVsY9RyIUdSEqzYW7wLCniDEZ6r0WikBoPBJcbqV/y9it4Bd/bS1H4FxJjG0rJs
				WxiREbfmOKm9bjQaTTv3FZc7MSYHajhdwF52AazYoxeSA8LwwS4UN4nLNsCnTqeztJbLQdIRoShM
				eOdJ7CuTMMPE6RaTLCKvQkCS9KrX62nP8hyXXLLGt3Jj5+/tl1URSL0Ny5IVQkIBtXp9W201XEzv
				MG7OKJawH74y0QIBKQ3/gF06UB6Ld1aYLF+X1gTrgB/nUAhxcBauDti/xbHNSOFCdVJDO3azL9sn
				4dnbcSgBDQXFlAo4uSOGtmONUco2Rl4vcG/C4Cf5qQREsKTuU/YIzLkJiOotcJIr/wVr9rf0sxEQ
				xUxxUix/RkcTOKSMpAdI+fkqFxyC8tsjqnqMPBX+GR34fwn8BuAE42grR95xAAAAAElFTkSuQmCC
			'));
		}
	}
	Ajax::register('Resource_Crosshair','render');

?>