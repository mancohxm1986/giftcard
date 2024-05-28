<?php
	#########################################################################################
	# Copyright BreynInc 2 (Pty) Ltd, 2013-2015.
	# All rights assigned to DHI / Paythem.
	# This source code is protected by copyright.
	# Copyright assigned to: Distribution House International (Qatar) 2015
	#########################################################################################
	include "class.Aes.php";
	include "class.AesCtr.php";

	class PTN_API_v2 {
		## Instantiate internal variables
		private $cURLing;					# cURL variable
		private $resultCode					= '';
		private $serverTransactionID		= 0;
		private $innerVars					= array();	# Array to hold __set variables

		## Magic method to GET local variable
		public function __get($prop){
			if(isset($this->innerVars[$prop])){
				return $this->innerVars[$prop];
			}else{
				return NULL;
			}
		}

		## Magic method to SET local variable
		public function __set($prop, $val) {
			$this->innerVars[$prop] = $val;
		}

		## Class constructor. Set variables.
		public function __construct($environment="", $appID){
			$this->innerVars["API_VERSION"]					= "2.0.1";
			$this->innerVars["DEBUG_OUTPUT"]				= false;
			$this->innerVars["SERVER_URI"]					= "https://vvs".$environment.".paythem.net/API/$appID/";
			$this->innerVars["PARAMETERS"]					= array();
			$this->innerVars["FAULTY_PROXY"]				= false; // FLAG is ignored currently
			$this->innerVars["SERVER_DEBUG"]				= false;
			$this->cURLing = curl_init();
		}

		## Do the post to Genie, receive response and decrypt if necessary.
		public function callAPI($debug=false){
			## Set global debug flag.
			$this->innerVars["DEBUG_OUTPUT"]				= $debug;

			## Fill in incomplete variables
			$this->setVariableDefaults("ENCRYPT_RESPONSE"	, false);
			$this->setVariableDefaults("SOURCE_IP"			, $this->getServerIP());

			## Check that all required variables are filled.
			$this->checkVariable("API_VERSION"				, "API_C_00001", "Client API version cannot be empty.");
			$this->checkVariable("SERVER_URI"				, "API_C_00003", "Server URI cannot be empty.");
			$this->checkVariable("PUBLIC_KEY"				, "API_C_00004", "Public key cannot be empty.");
			$this->checkVariable("PRIVATE_KEY"				, "API_C_00005", "Private key cannot be empty.");
			$this->checkVariable("USERNAME"					, "API_C_00006", "Username cannot be empty.");
			$this->checkVariable("PASSWORD"					, "API_C_00007", "Password cannot be emtpy.");

			## Build the content (POST) variable
			$content										= json_encode(
				array(
					"API_VERSION"							=> $this->innerVars["API_VERSION"],
					"SERVER_URI"							=> $this->innerVars["SERVER_URI"],
					"SERVER_DEBUG"							=> $this->innerVars["SERVER_DEBUG"],
					"FAULTY_PROXY"							=> $this->innerVars["FAULTY_PROXY"],
					"USERNAME"								=> $this->innerVars["USERNAME"],
					"PASSWORD"								=> $this->innerVars["PASSWORD"],
					"PUBLIC_KEY"							=> $this->innerVars["PUBLIC_KEY"],
					"SOURCE_IP"								=> $this->innerVars["SOURCE_IP"],
					"SERVER_TIMESTAMP"						=> date("Y-m-d H:i:s"),
					"SERVER_TIMEZONE"						=> date_default_timezone_get(),
					"ENCRYPT_RESPONSE"						=> $this->innerVars["ENCRYPT_RESPONSE"],
					"FUNCTION"								=> $this->innerVars["FUNCTION"],
					"PARAMETERS"							=> $this->innerVars["PARAMETERS"]
				)
			);
			$this->debugPrint("\nGenerated JSON string to post to server : ", $content);

			## Generate the HMAC hash
			$hash = hash_hmac('sha256', $content, $this->innerVars["PRIVATE_KEY"]);
			$this->debugPrint("\nGenerated HMAC Hash of JSON string: ", $hash, false);

			## Create the headers for the POST
			$headers = array(
				'X-Public-Key: '	.$this->innerVars["PUBLIC_KEY"],
				'X-Hash: '			.$hash,
				'X-Sourceip: '		.$this->innerVars["SOURCE_IP"]
			);
			if($this->innerVars["FAULTY_PROXY"]){
				$headers["X-Forwarded-For-Override: "] = $this->getServerIP();
			}
			$this->debugPrint("\nHTTP Headers", $headers);

			## Encrypt the POST content and set the PUBLIC KEY
			$encryptedContent = array(
				"PUBLIC_KEY"		=> $this->innerVars["PUBLIC_KEY"]									,
				"CONTENT"			=> $this->doEncrypt($content, $this->innerVars["PRIVATE_KEY"])
			);
			$this->debugPrint("Encrypted POST content", $encryptedContent);

			## Prepare cURL
			curl_setopt($this->cURLing, CURLOPT_URL				, $this->innerVars["SERVER_URI"]);
			curl_setopt($this->cURLing, CURLOPT_POST			, 1);
			curl_setopt($this->cURLing, CURLOPT_POSTFIELDS		, $encryptedContent);
			curl_setopt($this->cURLing, CURLOPT_RETURNTRANSFER	, true);
			curl_setopt($this->cURLing, CURLOPT_SSL_VERIFYPEER	, true);
			curl_setopt($this->cURLing, CURLOPT_SSL_VERIFYPEER	, false);
			curl_setopt($this->cURLing, CURLOPT_HTTPHEADER		, $headers);

			## Execute and close cURL
			$this->httpResponse = $this->removeWhiteSpaces(curl_exec($this->cURLing));

			$this->response = json_decode($this->httpResponse, true);

			$this->debugPrint("Original API Call returned response :", $this->httpResponse);
			$this->debugPrint("Array API Call returned response :", $this->response);
			curl_close($this->cURLing);

			## Decrypt results
			if($this->innerVars['ENCRYPT_RESPONSE']){
				$this->result		= json_decode($this->doDecrypt($this->response["CONTENT"], $this->innerVars["PRIVATE_KEY"]), true);
			}else{//if((int)$this->response["RESULT"] == 0){
				$this->result		= json_decode($this->response['CONTENT'], true);
			}
			$this->debugPrint("Final resultant returned to calling function :", $this->result);
			$this->resultCode				= $this->response['RESULT'];
			$this->serverTransactionID		= $this->response['SERVER_TRANSACTION_ID'];
			return $this->result;
		}

		private function debugPrint($title, $var, $newLine=true){
			if(!$this->innerVars["DEBUG_OUTPUT"])
				return;
			if($newLine){
				echo "\n$title \n".str_repeat("=", strlen($title)-1)."\n";
			}else{
				echo "\n$title : ";
			}
			if(is_array($var)){
				var_dump($var);
			}else{
				echo("$var");
			}
		}

		## Encrypt variable (string)
		function doEncrypt($encrypt, $key){
			return base64_encode(AesCtr::encrypt($encrypt, $key, 256));
		}

		## Decrypt variable (string)
		function doDecrypt($decrypt, $key){
			return AesCtr::decrypt(base64_decode($decrypt), $key, 256);
		}

		## Get the IP address of the server
		function getServerIP($overrideIP=""){
			if($overrideIP!==""){
				return $overrideIP;
			}else{
				return gethostbyname(gethostname());
			}
		}

		## Check if an variable has been registered in innerVars, stop processing if not.
		private function checkVariable($var, $errCode="UNSPECIFIED", $errMsg="UNSPECIFIED"){
			$this->debugPrint("Checking", $var, false);
			if(!isset($this->innerVars[$var])){
				die("ERROR $errCode : $errMsg \n\n");
			}
		}

		## Set default values for variables.
		private function setVariableDefaults($var, $default){
			$this->debugPrint("Setting", $var, false);
			if(!isset($this->innerVars[$var])){
				$this->innerVars[$var] = $default;
			}
		}

		public static function removeWhiteSpaces($var){
			return $var;
			$var = preg_replace('!\s+!', ' ', $var);
			$var = str_replace("\t", " ", $var);
			$var = str_replace("\n", " ", $var);
			$var = str_replace("\r", " ", $var);
			return $var;
		}
	}