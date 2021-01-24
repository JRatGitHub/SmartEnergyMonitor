<?php
	class Growatt_Inverter extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			//Properties
			$this->RegisterPropertyString ('Username','');
			$this->RegisterPropertyString ('Password','');

			//timers		
			$this->RegisterTimer('Interval',10, 'GROWATT_retrieve_growatt_data($id);');
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
		}


		protected function RegisterTimer($ident, $interval, $script) {
			$id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		
			if ($id && IPS_GetEvent($id)['EventType'] <> 1) {
			  IPS_DeleteEvent($id);
			  $id = 0;
			}
		
			if (!$id) {
			  $id = IPS_CreateEvent(1);
			  IPS_SetParent($id, $this->InstanceID);
			  IPS_SetIdent($id, $ident);
			}
		
			IPS_SetName($id, $ident);
			IPS_SetHidden($id, true);
			IPS_SetEventScript($id, "\$id = \$_IPS['TARGET'];\n$script;");
		
			if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type");
		
			if (!($interval > 0)) {
			  IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);
			  IPS_SetEventActive($id, false);
			} else {
			  IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $interval);
			  IPS_SetEventActive($id, true);
			}
		  }

		public function retrieve_growatt_data(){
		//	define('USERNAME', '*****');		// The username or email address of the account.
		//	define('PASSWORD', '*****');// The Password of the account
			$pw =  md5($this->ReadPropertyString('Password'));					// No Need to double md5
								//replace leading 0 by c for Growatt
			for ($i = 0; $i < strlen($pw); $i=$i+2){
				if ($pw[$i]=='0')
        		{
                   $pw=substr_replace($pw,'c',$i,1);
	        	}
			}
	
			define('USER_AGENT', 'Dalvik/2.1.0 (Linux; U; Android 9; ONEPLUS A6003 Build/PKQ1.180716.001)');	// Set a user agent. 
			define('COOKIE_FILE','/home/pi/symcon/scripts/pass2php/growatt.cookie');							// Where our cookie information will be stored (need to be writable!)
			define('HEADER',array('Content-Type: application/x-www-form-urlencoded;charset=UTF-8'));
			define('LOGIN_FORM_URL', 'http://server-api.growatt.com/newLoginAPI.do');							// URL of the login form.
			define('LOGIN_ACTION_URL', 'http://server-api.growatt.com/newLoginAPI.do');							// Login action URL. Sometimes, this is the same URL as the login form.
			define('DOMOTICZDEVICE', '****');																	// 'idx_here' For Watt / Daily Return																												
			$continue=true;

		$postValues = array(
			'userName' 	=> $this->ReadPropertyString('Username'),
		//	'userName'	=> USERNAME,
			'password'	=> $pw
		);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, LOGIN_ACTION_URL);						// Set the URL that we want to send our POST request to. In this case, it's the action URL of the login form.
		curl_setopt($curl, CURLOPT_POST, true);									// Tell cURL that we want to carry out a POST request.
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postValues));	// Set our post fields / date (from the array above). 
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);						// We don't want any HTTPS errors.
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);						// We don't want any HTTPS errors.
		curl_setopt($curl, CURLOPT_COOKIEJAR, COOKIE_FILE);						// Where our cookie details are saved. 
																				// This is typically required for authentication, as the session ID is usually saved in the cookie file.
		curl_setopt($curl, CURLOPT_HTTPHEADER, HEADER);
		curl_setopt($curl, CURLOPT_COOKIEFILE, COOKIE_FILE); 
		curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);						// Sets the user agent. Some websites will attempt to block bot user agents. //Hence the reason I gave it a Chrome user agent.
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);						// Tells cURL to return the output once the request has been executed.
		curl_setopt($curl, CURLOPT_REFERER, LOGIN_FORM_URL);					// Allows us to set the referer header. In this particular case, 
																				// we are fooling the server into thinking that we were referred by the login form.
		curl_setopt ($curl, CURLOPT_SSLVERSION, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);						// Do we want to follow any redirects?
		$result=curl_exec($curl);												// Execute the login request.

		if(curl_errno($curl)){
			switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
 				case 200:   $continue=true;# OK
 	    			$this->lg('Growatt inverter: Login: Expected HTTP code: ', $http_code);
        			break;
				case 302:   $continue=true;# OK
 	    			$this->lg('Growatt inverter: Login: Expected HTTP code: ', $http_code);
        			break;        				
        		default:    $continue=false;
        			$this->lg('Growatt inverter: Login: Unexpected HTTP code: ', $http_code);
			}
		}
		curl_close($curl);

		if (file_exists(COOKIE_FILE)) $this->lg ('Cookie File: '.COOKIE_FILE.' exists!'); else $this->lg ('Cookie File: '.COOKIE_FILE.' does NOT exist!');
		if (is_writable(COOKIE_FILE)) $this->lg ('Cookie File: '.COOKIE_FILE.' is writable!'); else $this->lg ('Cookie File: '.COOKIE_FILE.' NOT writable!');

		if ($continue) {
			$curl = curl_init();
			$url='http://server-api.growatt.com/newPlantAPI.do?action=getUserCenterEnertyData';
			curl_setopt($curl, CURLOPT_URL, $url);								
			curl_setopt($curl, CURLOPT_COOKIEJAR, COOKIE_FILE);					
			curl_setopt($curl, CURLOPT_COOKIEFILE, COOKIE_FILE); 
			curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);					
			curl_setopt($curl, CURLOPT_POSTFIELDS, "language=1" );
			curl_setopt($curl, CURLOPT_HTTPHEADER, HEADER);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);						
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);						
			curl_setopt ($curl, CURLOPT_SSLVERSION, 1);
			$result = curl_exec($curl);											

			if(curl_errno($curl)){
				switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
 				case 200:   $continue=true;# OK
 	    			$this->lg('Growatt inverter: Data: Expected HTTP code: ', $http_code);
        			break;
				case 302:   $continue=true;# OK
 	    			$this->lg('Growatt inverter: Data: Expected HTTP code: ', $http_code);
        			break;        				
        		default:    $continue=false;
        			$this->lg('Growatt inverter: Data: Unexpected HTTP code: ', $http_code);
			}
		}	
		curl_close($curl);
		
		if ($continue) {
			$data = json_decode($result, JSON_PRETTY_PRINT);
			print_r($data);
			$nowpower = (float)str_ireplace('kWh', '', $data['powerValue']);
			//$todaypower = (float)str_ireplace('kWh', '', $data['todayStr']);
			$todaypower = (float)str_ireplace('kWh', '', $data['totalStr']);		// 18-04-2020
			
			$str=( $nowpower.';'. $todaypower * 1000 );	#times 1000 to convert the 0.1kWh to 100 WattHour and to convert 2.1kWh to 2100 WattHour
			//$this->lg('Growatt Inverter: '. $nowpower.' for domoticz: '.$str);
			//ud(DOMOTICZDEVICE,0,$str,'GrowattInverter: Generation updated');
		}
	}
}	


protected function lg($msg)   // Can be used to write loglines to separate file or to internal domoticz log. Check settings.php for value.
{
//	curl(domoticz.'json.htm?type=command&param=addlogmessage&message='.urlencode('--->> '.$msg));	
	IPS_LogMessage("Growatt Iverter", $msg);	
}



function curl($url){
	$headers=array('Content-Type: application/json');
	$ch=curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
 	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch,CURLOPT_TIMEOUT, 3);
	curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
	curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
	$data=curl_exec($ch);
	curl_close($ch);
	return $data;
}

}