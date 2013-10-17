<?php if (!defined('YII_PATH')) {
	exit('No direct script access allowed!');
}

/**
 * CTFunctions class
 *
 * @author    Ramin Farmani <ramin.farmani@gmail.com>
 * @link      http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license   http://www.thankyoumenu.com/license/
 */
class CTFunctions extends CApplicationComponent
{
	/**
	 * @var array|boolean the functions used to serialize and unserialize cached data. Defaults to null, meaning
	 * using the default PHP `serialize()` and `unserialize()` functions. If you want to use some more efficient
	 * serializer (e.g. {@link http://pecl.php.net/package/igbinary igbinary}), you may configure this property with
	 * a two-element array. The first element specifies the serialization function, and the second the deserialization
	 * function. If this property is set false, data will be directly sent to and retrieved from the underlying
	 * cache component without any serialization or deserialization. You should not turn off serialization if
	 * you are using {@link CCacheDependency cache dependency}, because it relies on data serialization.
	 */
	public $defaultSerializer;
	/**
	 * @var string the name of the hashing algorithm to be used by {@link computeHMAC}.
	 * See {@link http://php.net/manual/en/function.hash-algos.php hash-algos} for the list of possible
	 * hash algorithms. Note that if you are using PHP 5.1.1 or below, you can only use 'sha1' or 'md5'.
	 *
	 * Defaults to 'md5', meaning using md5 hash algorithm.
	 * @since 1.1.3
	 */
	public $defaultHashAlgorithm = 'md5';
	/**
	 * @var array the behaviors that should be attached to this component.
	 * The behaviors will be attached to the component when {@link init} is called.
	 * Please refer to {@link CModel::behaviors} on how to specify the value of this property.
	 */
	public $behaviors = array();

	private $_initialized = false;

	/**
	 * Initializes the application component.
	 * This method is required by {@link IApplicationComponent} and is invoked by application.
	 * If you override this method, make sure to call the parent implementation
	 * so that the application component can be marked as initialized.
	 */
	public function init()
	{

	}

	/**
	 * hash Functionality
	 *
	 * @param string  $str    source string
	 * @param boolean $outPut row output or not default value is null
	 *
	 * @return string hash
	 */
	public function hash($str, $outPut = null)
	{
		switch ($this->defaultHashAlgorithm) {
		case 'md5':
			return md5($str, $outPut);
		case 'sha1':
			return sha1($str, $outPut);
		}
		return $str;
	}

	/**
	 * encrypt Functionality
	 *
	 * @param string $string source string
	 * @param string $salt   source salt string
	 *
	 * @return string crypt
	 */
	public function encrypt($string, $salt = '')
	{
		$salt = empty($salt) ? Randomness::blowfishSalt() : $salt;
		return crypt($string, $salt);
	}

	/**
	 * serialize Functionality
	 *
	 * @param mixed $value
	 *
	 * @return string serialized value
	 */
	public function serializer($value)
	{
		if(function_exists(igbinary_serialize))
			return igbinary_serialize($value);
		else
			return serialize($value);
	}

	/**
	 * unserialize Functionality
	 *
	 * @param string $value
	 *
	 * @return mixed unserialized value
	 */
	public function unserializer($value)
	{
		switch ($this->defaultSerializer) {
		case 'php':
			return unserialize($value);
		case 'igbinary':
			return igbinary_serialize($value);
		default:
			return serialize($value);
		}
	}

	/**
	 * getGPSByLocation
	 *
	 * @param string $address
	 *
	 * @return array $response
	 */
	public function getGPSByAddressMapQuest($address)
	{
		$location = str_replace(" ", "+", $address);
		$APIKey = Yii::app()->params['mapquest.key'];

		$url = "http://www.mapquestapi.com/geocoding/v1/address?key=$APIKey&inFormat=kvp&outFormat=json&location=$location&maxResults=1";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = json_decode(curl_exec($ch), true);

		if(!empty($response['results'][0]['locations'][0]))
			$response = $response['results'][0]['locations'][0];
		else
			return null;

		switch(substr($response['geocodeQualityCode'],0,3)) {
		case 'P1A':
		case 'P1B':
		case 'L1A':
		case 'L1B':
			$accurate = true;
			break;
		default:
			$accurate = false;
		}

		$result['country'] = $response['adminArea1'];
		$result['state'] = $response['adminArea3'];
		$result['county'] = $response['adminArea4'];
		$result['city'] = $response['adminArea5'];
		$result['street'] = $response['street'];
		$result['address'] = $response['street'] . ', ' . $response['adminArea4'] . ', ' . $response['adminArea3'] . ', ' . $response['adminArea1'];
		$result['postal_code'] = $response['postalCode'];
		$result['gps']['latitude'] = $response['latLng']['lat'];
		$result['gps']['longitude'] = $response['latLng']['lng'];
		$result['location_type'] = $response['geocodeQuality'];
		$result['map'] = $response['mapUrl'];
		$result['accurate'] = $accurate;

		return $result;

	}

	public function getAddressByGPSMapQuest($lat,$lng)
	{
		$location = $lat.','.$lng;
		$APIKey = Yii::app()->params['mapquest.key'];

		$url = "http://www.mapquestapi.com/geocoding/v1/reverse?key=$APIKey&thumbMaps=true&maxResults=1&location=$location";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = json_decode(curl_exec($ch), true);
		if(!empty($response['results'][0]['locations'][0]))
			$response = $response['results'][0]['locations'][0];
		else
			return null;

		switch(substr($response['geocodeQualityCode'],0,3)) {
		case 'P1A':
			//case 'P1B':
		case 'L1A':
			//case 'L1B':
			$accurate = true;
			break;
		default:
			$accurate = false;
		}

		$result['country'] = $response['adminArea1'];
		$result['state'] = $response['adminArea3'];
		$result['county'] = $response['adminArea4'];
		$result['city'] = $response['adminArea5'];
		$result['street'] = $response['street'];
		$result['address'] = $response['street'] . ', ' . $response['adminArea4'] . ', ' . $response['adminArea3'] . ', ' . $response['adminArea1'];
		$result['postal_code'] = $response['postalCode'];
		$result['gps']['latitude'] = $response['latLng']['lat'];
		$result['gps']['longitude'] = $response['latLng']['lng'];
		$result['location_type'] = $response['geocodeQuality'];
		$result['map'] = $response['mapUrl'];
		$result['accurate'] = $accurate;

		return $result;

	}

	/**
	 * getGPSByLocation
	 *
	 * @param string $address
	 *
	 * @return array $response
	 */
	public function getGPSByAddressGeocodeFarm($address)
	{
		$location = str_replace(" ", "+", $address);
		$APIKey = Yii::app()->params['gecodefarm.key'];

		$url = "http://www.geocodefarm.com/api/forward/json/$APIKey/$location/";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = json_decode(curl_exec($ch), true);

		if($response['geocoding_results']['STATUS']['status'] == 'SUCCESS') {
			$address = $response['geocoding_results']['ADDRESS']['address_returned'];
			$gps = $response['geocoding_results']['COORDINATES'];
			$accurate = $response['geocoding_results']['ADDRESS']['accuracy'];
			if($accurate == 'VERY ACCURATE' || $accurate == 'GOOD ACCURACY')
				$accurate = true;
			else
				$accurate = false;

		}else
			return null;

		$result['address'] = $address;
		$result['gps'] = $gps;
		$result['accurate'] = $accurate;

		return $result;
	}

	public function getAddressByGPSGeocodeFarm($lat,$lng)
	{
		$APIKey = Yii::app()->params['gecodefarm.key'];

		$url = "http://www.geocodefarm.com/api/reverse/json/$APIKey/$lat/$lng/";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = json_decode(curl_exec($ch), true);

		if($response['geocoding_results']['STATUS']['status'] == 'SUCCESS') {
			$address = $response['geocoding_results']['ADDRESS']['address'];
			$gps = $response['geocoding_results']['COORDINATES'];
			$accurate = $response['geocoding_results']['ADDRESS']['accuracy'];
			if($accurate == 'VERY ACCURATE' || $accurate == 'GOOD ACCURACY')
				$accurate = true;
			else
				$accurate = false;

		}else
			return null;

		$result['address'] = $address;
		$result['gps'] = $gps;
		$result['accurate'] = $accurate;

		return $result;
	}
	/**
	 * getGPSByLocation
	 *
	 * @param string $address
	 *
	 * @return array $response
	 */
	public function getGPSByAddress($address)
	{
		return $this->getGPSByAddressGeocodeFarm($address);
	}

	public function getAddressByGPS($lat,$lng)
	{
		return $this->getAddressByGPSGeocodeFarm($lat,$lng);
	}

	public function generatePassword($length = 9)
	{
		$vowels = 'aeuy';
		$consonants = 'bdghjmnpqrstvzBDGHJLMNPQRSTVWXZAEUY23456789!@#$%^&*()-+?';
		$password = '';
		$alt = time() % 2;
		for ($i = 0; $i < $length; $i++) {
			if ($alt == 1) {
				$password .= $consonants[(rand() % strlen($consonants))];
				$alt = 0;
			} else {
				$password .= $vowels[(rand() % strlen($vowels))];
				$alt = 1;
			}
		}
		return $password;
	}

	public function getServerLoad() {

		$serverload = array();

		// DIRECTORY_SEPARATOR checks if running windows
		if(DIRECTORY_SEPARATOR != '\\')
		{
			if(function_exists("sys_getloadavg"))
			{
				// sys_getloadavg() will return an array with [0] being load within the last minute.
				$serverload = sys_getloadavg();
				$serverload[0] = round($serverload[0], 1);
			} else if(@file_exists("/proc/loadavg") && $load = @file_get_contents("/proc/loadavg")) {
				$serverload = explode(" ", $load);
				$serverload[0] = round($serverload[0], 1);
			}

			if(!is_numeric($serverload[0])) {
				if(@ini_get('safe_mode') == 'On') {
					return array();
				}

				// Suhosin likes to throw a warning if exec is disabled then die - weird
				if($func_blacklist = @ini_get('suhosin.executor.func.blacklist')) {
					if(strpos(",".$func_blacklist.",", 'exec') !== false) {
						return array();
					}
				}
				// PHP disabled functions?
				if($func_blacklist = @ini_get('disable_functions')) {
					if(strpos(",".$func_blacklist.",", 'exec') !== false) {
						return array();
					}
				}

				$load = @exec("uptime");
				$load = explode("load average: ", $load);
				$serverload = explode(",", $load[1]);
				if(!is_array($serverload)) {
					return array();
				}
			}
		} else {
			return array();
		}

		$returnload = trim($serverload[0]);

		return $returnload;
	}

	/*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
	/*::                                                                         :*/
	/*::  This routine calculates the distance between two points (given the     :*/
	/*::  latitude/longitude of those points). It is being used to calculate     :*/
	/*::  the distance between two locations using GeoDataSource(TM) Products    :*/
	/*::                     													 :*/
	/*::  Definitions:                                                           :*/
	/*::    South latitudes are negative, east longitudes are positive           :*/
	/*::                                                                         :*/
	/*::  Passed to function:                                                    :*/
	/*::    lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees)  :*/
	/*::    lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees)  :*/
	/*::    unit = the unit you desire for results                               :*/
	/*::           where: 'M' is statute miles                                   :*/
	/*::                  'K' is kilometers (default)                            :*/
	/*::                  'N' is nautical miles                                  :*/
	/*::  Worldwide cities and other features databases with latitude longitude  :*/
	/*::  are available at http://www.geodatasource.com                          :*/
	/*::                                                                         :*/
	/*::  For enquiries, please contact sales@geodatasource.com                  :*/
	/*::                                                                         :*/
	/*::  Official Web site: http://www.geodatasource.com                        :*/
	/*::                                                                         :*/
	/*::         GeoDataSource.com (C) All Rights Reserved 2013		   		     :*/
	/*::                                                                         :*/
	/*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
	public function distance($lat1, $lon1, $lat2, $lon2, $unit) {

		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		$unit = strtoupper($unit);

		if ($unit == "K") {
			return ($miles * 1.609344);
		} else if ($unit == "N") {
			return ($miles * 0.8684);
		} else {
			return $miles;
		}
	}

	public function squareAroundGPS($latitude, $longitude, $distance) {

	}

	// Modified from:
	// http://www.sitepoint.com/forums/showthread.php?656315-adding-distance-gps-coordinates-get-bounding-box
	/**
	 * bearing is 0 = north, 180 = south, 90 = east, 270 = west
	 * $topRight = boundingBox($lat, $lng, 45, $d);
	 * $bottomRight = boundingBox($lat, $lng, 135, $d);
	 * $bottomLeft = boundingBox($lat, $lng, 225, $d);
	 * $topLeft = boundingBox($lat, $lng, 315, $d);
	 *
	 */
	function boundingBox($latitude, $longitude, $distance, $distanceUnit = "km", $returnArray = true) {

		if ($distanceUnit == "m") {
			// Distance is in miles.
			$radius = 3963.1676;
		} else {
			// distance is in km.
			$radius = 6378.1;
		}

		// bearings
		$due_north = 0;
		$due_south = 180;
		$due_east = 90;
		$due_west = 270;

		// convert latitude and longitude into radians
		$lat_r = deg2rad($latitude);
		$lon_r = deg2rad($longitude);

		// find the northmost, southmost, eastmost and westmost corners $distance_in_miles away
		// original formula from
		// http://www.movable-type.co.uk/scripts/latlong.html

		$northmost  = asin(sin($lat_r) * cos($distance/$radius) + cos($lat_r) * sin ($distance/$radius) * cos($due_north));
		$southmost  = asin(sin($lat_r) * cos($distance/$radius) + cos($lat_r) * sin ($distance/$radius) * cos($due_south));

		$eastmost = $lon_r + atan2(sin($due_east)*sin($distance/$radius)*cos($lat_r),cos($distance/$radius)-sin($lat_r)*sin($lat_r));
		$westmost = $lon_r + atan2(sin($due_west)*sin($distance/$radius)*cos($lat_r),cos($distance/$radius)-sin($lat_r)*sin($lat_r));


		$northmost = rad2deg($northmost);
		$southmost = rad2deg($southmost);
		$eastmost = rad2deg($eastmost);
		$westmost = rad2deg($westmost);

		// sort the lat and long so that we can use them for a between query
		if ($northmost > $southmost) {
			$lat1 = $southmost;
			$lat2 = $northmost;

		} else {
			$lat1 = $northmost;
			$lat2 = $southmost;
		}


		if ($eastmost > $westmost) {
			$lon1 = $westmost;
			$lon2 = $eastmost;

		} else {
			$lon1 = $eastmost;
			$lon2 = $westmost;
		}

		return array($lat1,$lat2,$lon1,$lon2);

	}

	function suitableByte($size)
	{
		$unit=array('b','kb','mb','gb','tb','pb');
		return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
	}
}