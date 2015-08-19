<?php
/*
 ******************************************************************************
 *
 * Copyright (C) 2013 T Dispatch Ltd
 *
 * Licensed under the GPL License, Version 3.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 ******************************************************************************
*/

if (!isset($_SESSION)) {
    @session_start();
}


// Check for the required json and curl extensions, the TDispatch APIs PHP
// won't function without them.
if (!function_exists('curl_init')) {
    throw new Exception('TDispatch PHP API Client requires the CURL PHP extension');
}

if (!function_exists('json_decode')) {
    throw new Exception('TDispatch PHP API Client requires the JSON PHP extension');
}

if (!function_exists('http_build_query')) {
    throw new Exception('TDispatch PHP API Client requires http_build_query()');
}

date_default_timezone_set('UTC');

// hack around with the include paths a bit so the library 'just works'
set_include_path(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . PATH_SEPARATOR . get_include_path());

require_once 'config.php';

class TripThru {

    public $api_key;
    public $api_cliente_id;
    public $api_secret;
	public $partnerAccessToken;
    public $getHomeUrl;
    public $getRelativeHomeUrl;
	public $apiUrl;
    public $debug;
    public $resetPasswordCallbackPage;
	public $partnerUrl;
    public $lastErrorMsg;
    public $lastErrorCode;
	public $partnerId;

    /* API FUNCTIONS */
    function __construct() {

        $apiConfig = array();
        $this->api_key = ConfigTT::getFleetApiKey();
        $this->api_cliente_id = ConfigTT::getApiClientId();
        $this->api_secret = ConfigTT::getApiSecret();
		
        $this->getHomeUrl = ConfigTT::getHomeUrl();
        $this->getRelativeHomeUrl = ConfigTT::getRelativeHomeUrl();
        $this->debug = ConfigTT::isDebug();
        $this->resetPasswordCallbackPage = ConfigTT::getResetPasswordCallbackPage();
        $this->partnerUrl = ConfigTT::getPartnerBaseUrl();
		$this->partnerAccessToken = ConfigTT::getPartnerAccessToken();
		$this->partnerId = ConfigTT::getPartnerId();
    }

    public function getToken() {
        if (isset($_SESSION[$this->partnerId]['access']["access_token"])) {
            return $_SESSION[$this->partnerId]['access']["access_token"];
        }
    }

    public function getApiKey() {
        return $this->api_key;
    }

    public function getClientId() {
        return $this->api_cliente_id;
    }
	
    public function getHomeUrl() {
        return $this->getHomeUrl;
    }

    public function getRelativeHomeUrl(){
        return $this->getRelativeHomeUrl;
    }

    /*
     * api_info()
     * Returns basic information about the current API session.      *
     * @return (object) json object
     */

    public function api_info() {
        if ($this->api) {
            return $this->api;
        }
        $api = new API();
        $info = $api->API_getInfo($this);
        $this->api = $info;
        return $info;
    }

    /* END - API FUNCTIONS */



    /* ACCOUNT FUNCTIONS */

    /*
     * Account_login()
     * o login for user
     * @param $user email for user
     * @param $password password for user
     * @return (bool) true or false, if authenticated or not
     */

    public function Account_login($user, $password) {
		if($user == 'passenger@tripthru.com' && $password == 'tripthru'){
			$_SESSION[$this->partnerId]['trips'] = array();
			$_SESSION[$this->partnerId]['access']["access_token"] = $this->partnerAccessToken;
			return true;
		}
    }

    /*
     * Account_logout()
     * do logout and unset all session vars
     */

    public function Account_logout() {
        session_start();
        session_unset();
        session_destroy();
    }

    /*
     * Account_checkLogin()
     * Method to check if user is authenticated
     * @return (bool) true or false
     */

    public function Account_checkLogin() {
        if (isset($_SESSION[$this->partnerId]['access']["access_token"])) {
            return true;
        }
        return false;
    }

    /* END - ACCOUNT FUNCTIONS */

	public function Dispatch($passenger, $trip_id, $pickup_time, $pickup_location, $dropoff_location, $partnerId){
		$url = $this->partnerUrl . 'trip';
		$access_token = $this->partnerAccessToken;
		
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$data = array(
			'customer' => $passenger,
      'pickup_time' => $pickup_time,
      'pickup_location' => $pickup_location,
      'dropoff_location' => $dropoff_location,
			'luggage' => 0,
			'payment_method_code' => 'cash',
			'id' => $trip_id,
      'network' => array('id' => $partnerId)
    );
		
		$headr = array();
		$headr[] = 'Content-type: application/json';
		
    curl_setopt($ch, CURLOPT_POST, count($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    $res = json_decode($result, true);
    $info = curl_getinfo($ch);

    curl_close($ch);
    if (!isset($res['result_code']) || $res['result_code'] != 200) {
      $this->setError($res);
      return false;
    }
		
    return $res;
	}
	
	public function Get_quotes($trip_id, $passenger, $pickup_time, $pickup_location, $dropoff_location, $origin){
		$res = null;
		$url = $this->partnerUrl . 'quote';
		$access_token = $this->partnerAccessToken;
		
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
		$data = array(
			'pickup_time' => $pickup_time,
			'pickup_location' => $pickup_location,
			'dropoff_location' => $dropoff_location,
			'customer_id' => $passenger.name,
		  'passengers' => 1,
			'luggage' => 1,
			'payment_method_code' => 'cash'
		);
		
		$headr = array();
		$headr[] = 'Content-type: application/json';
		
		curl_setopt($ch, CURLOPT_POST, count($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$result = curl_exec($ch);
		$res = json_decode($result, true);
		$info = curl_getinfo($ch);
		
		curl_close($ch);
		if (!isset($res['result_code']) || $res['result_code'] != 200) {
			$this->setError($res);
			return false;
		}
		
		return $res;
		
	}
	
	public function Get_drivers_nearby($location, $radius, $limit, $product_id){
		$res = null;
		$url = $this->partnerUrl . 'drivers';
		$access_token = $this->partnerAccessToken;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$data = array(
				'location' => $location,
				'radius' => $radius,
				'limit' => $limit
		);
		
		$headr = array();
		$headr[] = 'Content-type: application/json';
		
		curl_setopt($ch, CURLOPT_POST, count($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$result = curl_exec($ch);
		$res = json_decode($result, true);
		$info = curl_getinfo($ch);
		
		curl_close($ch);
		if (!isset($res['result_code']) || $res['result_code'] != 200) {
			$this->setError($res);
			return false;
		}
		
		return $res;
	}
	
	public function Get_trip_status($trip_id, $partnerId){
		$url = $this->partnerUrl;
		$access_token = $this->partnerAccessToken;
		
		$data = array(
			"id" => $trip_id
		);

		$url = $url . 'tripstatus/' . $trip_id;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$result = curl_exec($ch);
		$res = json_decode($result, true);
		$info = curl_getinfo($ch);
		curl_close($ch);
		return $res;
	}
	
	public function Vehicles_list(){
		return array(
			array("pk" => "compact", "name" => "compact"),
			array("pk" => "sedan", "name" => "sedan")
		);
	}

    /*  ---  Tratamento de erros ---- */

    //put your code here
    public function setError($result) {
        $this->lastErrorMsg = null;
        $this->lastErrorCode = null;
        if (isset($result['status']) && $result['status'] === 'Failed') {
            if ($this->debug) {
                error_log('ERRO:' . print_r($result, 1));
                error_log('ERRO-TRACE:' . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1));
            }
            if (isset($result['status_code'])) {
                $this->lastErrorCode = $result['status_code'];
            }
            if (isset($result['message']['text'])) {
                $this->lastErrorMsg = $result['message']['text'];
            } else {
            	if (isset($result['message'])) {
                $this->lastErrorMsg = $result['message'];
               }
            }
        }
    }

    /*
     * return last error message
     */

    public function getErrorMessage() {
        return $this->lastErrorMsg;
    }

    /*
     * return last error code
     */

    public function getErrorCode() {
        return $this->lastErrorCode;
    }

}
