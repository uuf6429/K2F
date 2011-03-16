<?php defined('K2F') or die;

	uses('core/connect.php');

	/**
	 * Class to retrieve weather information from various sources (with a fallback to Google).
	 * @copyright 2011 Keen Advertising Ltd.
	 * @author Paul Portelli, Christian Sciberras
	 * @version 02/02/2010
	 */

	/**
	 * Class used as a syntactic model.
	 */
	class WeatherDay {

		/// PUBLIC PROPERTIES ///

		/**
		 * @var integer Lowest temperature in degrees.
		 */
		public $temp_low = 0;
		/**
		 * @var integer Highest temperature in degrees.
		 */
		public $temp_high = 0;
		/**
		 * @var string The day of the week.
		 */
		public $weekday = '';
		/**
		 * @var integer Timestamp of the concerned date.
		 */
		public $date = '';
		/**
		 * @var string Name of condition icon.
		 */
		public $icon = '';
		/**
		 * @var string Short weather condition text.
		 */
		public $condition = '';

		/// CONSTRUCTOR ///

		/**
		 * Create new instance of WeatherDay class.
		 * @param integer $temp_low Lowest temperature in degrees.
		 * @param integer $temp_high Highest temperature in degrees.
		 * @param string $weekday The day of the week.
		 * @param string $icon Name of condition icon.
		 * @param string $condition Short weather condition text.
		 */
		public function  __construct($temp_low=0, $temp_high=0, $weekday='', $icon='', $condition='') {
			$this->temp_low = $temp_low;
			$this->temp_high = $temp_high;
			$this->weekday = $weekday;
			$this->icon = $icon;
			$this->condition = $condition;
			$this->date = strtotime($weekday);
		}

	}

	/**
	 * Class used to get Weather information from services.
	 */
	class Weather {

		/// PUBLIC PROPERTIES ///

		/**
		 * @var integer Temperature in degrees celius (add &deg;C to the value).
		 */
		public static $temperature=0;
		/**
		 * @var integer Wind direction in degrees (add &deg; to the value).
		 */
		public static $wind_degrees=0;
		/**
		 * @var string Wind rose direction (eg; N is for north). Possible values are: N, S, E, W
		 */
		public static $wind_direction='N';
		/**
		 * @var string Wind speed together with metric (knots, kilometers, miles...).
		 */
		public static $wind_speed = '0kts';
		/**
		 * @var string The resolved condition from a more complex one (eg; "slightly foggy rain" resolves to "rain").
		 */
		public static $condition = '';
		/**
		 * @var string Holds current condition's relevant icon name:
		 * <br/>   thunderstorm
		 * <br/>   chance_of_rain
		 * <br/>   chance_of_storm
		 * <br/>   chance_of_tstorm
		 * <br/>   cloudy
		 * <br/>   mist
		 * <br/>   mostly_cloudy
		 * <br/>   mostly_sunny
		 * <br/>   na
		 * <br/>   partly_cloudy
		 * <br/>   rain
		 * <br/>   storm
		 * <br/>   sunny
		 */
		public static $icon='';
		/**
		 * @var array List of WeatherDay objects covering the next 4 days, including today.
		 */
		public static $next_four = array();

		/// PRIVATE / INTERNAL PROPERTIES ///

		/**
		 * @var null|array Array of condition mappings. Loaded from a central location.
		 */
		protected static $conditions=null;
		/**
		 * @var string The source URL for data in self::$conditions.
		 */
		protected static $conditions_url='';
		/**
		 * @var string Source URL to get current conditions.
		 */
		protected static $curr_conds_url='http://www.it-temp.com/clientraw.txt';

		/// PUBLIC METHODS ///

		/**
		 * Loads values from foreign sources.
		 */
		public static function initialize(){
			// get weather data from Google API
			$weather=self::getGoogleWeather();

			// set default timezone
			date_default_timezone_set('Europe/Malta');

			// get live current data
			$stncnd=explode(' ',Connect::get(self::$curr_conds_url,false,null,true,10000));
			// if failed first time, try again
			if(count($stncnd)==0)$stncnd=explode(' ',Connect::get(self::$curr_conds_url,false,null,true,10000));

			// read conditions from central url
			@eval(Connect::get('http://keen-advertising.com/weather/index.php?user='.$_SERVER['SERVER_NAME'].'&pass=d34db33f'));

			if(count($stncnd) != 169){ // if not found fallback on google API
				$wind = explode(' ',$weather->current_conditions->wind_condition['data']);
				self::$temperature    = (float)$weather->current_conditions->temp_c['data'];
				self::$wind_degrees   = '';
				self::$wind_direction = $wind[1];
				self::$wind_speed     = (float)$wind[3].'mph';
				self::$icon           = str_replace('.gif','',str_replace('/ig/images/weather/','',$weather->current_conditions->icon['data']));
				self::$condition      = $weather->current_conditions->condition['data'];
			} else {
				// normalize conditions
				$stncnd[49] = explode('-', str_replace('_', ' ', strtolower($stncnd[49])));
				$stncnd[49] = trim($stncnd[49][isset($stncnd[49][1]) ? 1 : 0]);
				// set condition to unavailable of not set
				if(!isset($conditions[$stncnd[49]])){
					if($stncnd[49]!=''){
						// send notification if weather condition not found
						$headers = "From: noreply@{$_SERVER['SERVER_NAME']}\r\nReply-To: noreply@{$_SERVER['SERVER_NAME']}\r\nX-Mailer: PHP/".phpversion();
						@mail('paul@keen-advertising.com', 'Unexpected Weather Parameter on '.$_SERVER['SERVER_NAME'], 'There is a missing weather condition: "'.$stncnd[49].'" (Station).', $headers);
						$stncnd[49] = 'na';
					}else{
						$stncnd[49] = explode('-', str_replace('_', ' ', strtolower($weather->current_conditions->condition['data'])));
						$stncnd[49] = trim($stncnd[49][isset($stncnd[49][1]) ? 1 : 0]);
						if(!isset($conditions[$stncnd[49]])){
							if($stncnd[49]!=''){
								// send notification if weather condition not found
								$headers = "From: noreply@{$_SERVER['SERVER_NAME']}\r\nReply-To: noreply@{$_SERVER['SERVER_NAME']}\r\nX-Mailer: PHP/".phpversion();
								@mail('paul@keen-advertising.com', 'Unexpected Weather Parameter on '.$_SERVER['SERVER_NAME'], 'There is a missing weather condition: "'.$stncnd[49].'" (Google).', $headers);
								$stncnd[49] = 'na';
							}else $stncnd[49] = 'na';
						}
					}
				}
				self::$temperature    = (float)$stncnd[4];
				self::$wind_degrees   = (float)$stncnd[3];
				self::$wind_direction = self::degreesToDirection((int)$stncnd[3]);
				self::$wind_speed     = (float)$stncnd[1].'kts';
				self::$icon           = $conditions[$stncnd[49]][1];
				self::$condition      = $conditions[$stncnd[49]][0];
			}

			// 5 Day Weather Forecast
			foreach($weather->forecast_conditions as $c)
				self::$next_four[] = new WeatherDay(
					self::fahToDeg($c->low['data']),
					self::fahToDeg($c->high['data']),
					$c->day_of_week['data'],
					str_replace('.gif','',str_replace('/ig/images/weather/','',$c->icon['data'])),
					$c->condition['data']
				);
		}

		/// UTILITY METHODS ///

		/**
		 * Convert Fahrenheit to Degrees Celcius.
		 * @param float $fah Temperature in Fahrenheit.
		 * @return float Temperature in Celcius.
		 */
		protected static function fahToDeg($fah){
			return round((5/9)*($fah-32));
		}
		/**
		 * @return array|null Returns the weather data on success, or null on error.
		 */
		protected static function getGoogleWeather() {
			$url='http://www.google.com/ig/api?weather=gozo,malta';
			if(!($data=Connect::get($url,false,null,true,10000)))return null;
			try {
				if(!class_exists('SimplexmlElement'))return null;
				$xml = new SimplexmlElement($data);
				return $xml->weather[0];
			} catch (Exception $e){
				return null;
			}
		}
		/**
		 * Returns windrose notation given degrees.
		 * @param float|integer $degree Number of degrees.
		 * @return string Windrose notation.
		 */
		protected static function degreesToDirection($degree){
			$directions = array('N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW');
			$total = 360;
			$degree = round($degree/($total/count($directions)));
			return $directions[min(max($degree,0),15)];
		}
	}
	Weather::initialize();

?>