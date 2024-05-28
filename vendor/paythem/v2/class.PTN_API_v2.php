<?php
/**
	* This class is used for direct integration with the PayThem.Net Electronic Voucher Distribution API subsystem.
	* 
	* This is the base class. Any calls to this must follow the API protocal inherently, without deviation.
	* 
	* @author Richard S. de Breyn
	* @version 2.0.4
	* @copyright PayThem.Net WLL, 2014-2020
	*
	* @requires Requires openSSL if available. If not available, it fails back to the AESCTR library.
	*
	* @update 2020-05-07 Converted to 2.0.4 API protocol.
	* @update 2020-05-07 Changed constructor $environment variable to be required.
	* @update 2020-05-07 Refractored code & micro-optimized strings " to ' and removed unnecessary {} combinations.
	* @update 2020-05-07 Changed copyright.
	* @update 2020-05-07 Added HASH_STUB randomized variable to call. Resolve HMAC duplication for concurrent calls.
	* @update 2020-05-07 Removed removeWhiteSpaces
	* @update 2020-05-07 Changed certain functions access rights to private
	* @update 2020-05-07 Added phpDoc comments to constructor
	*
	* @todo Develop a wrapper class to perform standard functions. Convert current class into a protocol only class.
*/
include 'class.Aes.php';
include 'class.AesCtr.php';

class PTN_API_v2 {
	private $cURLing;								# cURL variable
	private $resultCode								= '';
	private $serverTransactionID					= 0;
	private $innerVars								= [];
	public  $IV										= '';

	/**
	  * Constructor.
	  *
	  * @param String $environment The server environment that this transaction will be executed against.
	  * @param int $appID The API Application to communicate with.
	  * @param int $iv The IV to be used for openSSL encryption. If no IV is passed, AESCTR encrytion 
	  * will be used. Needs to be exactly 16 alphanumerical characters.
	*/
	public function __construct($environment, $appID, $iv=''){
		$this->innerVars['API_VERSION']				= '2.0.4';
		$this->innerVars['DEBUG_OUTPUT']			= false;
		$this->innerVars['SERVER_URI']				= "https://vvs{$environment}.paythem.net/API/{$appID}/";
		$this->innerVars['PARAMETERS']				= [];
		$this->innerVars['FAULTY_PROXY']			= false; // FLAG is ignored currently
		$this->innerVars['SERVER_DEBUG']			= false;
		if(strlen($iv) == 16){
			if(!extension_loaded('openssl'))
				$this->dPrt(PHP_EOL.'OPENSSL extension NOT LOADED in PHP, IV ignored.'.PHP_EOL, '');
			else
				$this->IV							= $iv;
		}
		$this->cURLing								= curl_init();
	}

	## Magic method to GET local variable
	public function __get($prop){
		if(isset($this->innerVars[$prop]))
			return $this->innerVars[$prop];
		else
			return NULL;
	}

	## Magic method to SET local variable
	public function __set($prop, $val) {
		$this->innerVars[$prop]						= $val;
	}

	## Do the post to VVS, receive response and decrypt if necessary.
	public function callAPI($debug=false){
		## Set global debug flag.
		$this->innerVars['DEBUG_OUTPUT']			= $debug;

		## Fill in incomplete variables
		$this->setVariableDefaults('ENCRYPT_RESPONSE', false);
		$this->setVariableDefaults('SOURCE_IP'		, $this->getServerIP());

		## Check that all required variables are filled.
		$this->checkVariable('API_VERSION'			, 'API_C_00001', 'Client API version cannot be empty.');
		$this->checkVariable('SERVER_URI'			, 'API_C_00003', 'Server URI cannot be empty.');
		$this->checkVariable('PUBLIC_KEY'			, 'API_C_00004', 'Public key cannot be empty.');
		$this->checkVariable('PRIVATE_KEY'			, 'API_C_00005', 'Private key cannot be empty.');
		$this->checkVariable('USERNAME'				, 'API_C_00006', 'Username cannot be empty.');
		$this->checkVariable('PASSWORD'				, 'API_C_00007', 'Password cannot be emtpy.');

		## Build the content (POST) variable
		$content									= json_encode(
			[
				'API_VERSION'						=> $this->innerVars['API_VERSION'],
				'SERVER_URI'						=> $this->innerVars['SERVER_URI'],
				'SERVER_DEBUG'						=> $this->innerVars['SERVER_DEBUG'],
				'FAULTY_PROXY'						=> $this->innerVars['FAULTY_PROXY'],
				'USERNAME'							=> $this->innerVars['USERNAME'],
				'PASSWORD'							=> $this->innerVars['PASSWORD'],
				'PUBLIC_KEY'						=> $this->innerVars['PUBLIC_KEY'],
				'SOURCE_IP'							=> $this->innerVars['SOURCE_IP'],
				'SERVER_TIMESTAMP'					=> date('Y-m-d H:i:s'),
				'SERVER_TIMEZONE'					=> date_default_timezone_get(),
				'HASH_STUB'							=> rand(1111111111, 9999999999),
				'ENCRYPT_RESPONSE'					=> $this->innerVars['ENCRYPT_RESPONSE'],
				'FUNCTION'							=> $this->innerVars['FUNCTION'],
				'PARAMETERS'						=> $this->innerVars['PARAMETERS'],
			]
		);
		$this->dPrt(PHP_EOL.'Generated JSON string to post to server : ', $content);

		## Generate the HMAC hash
		$hash										= hash_hmac('sha256', $content, $this->innerVars['PRIVATE_KEY']);
		$this->dPrt(PHP_EOL.'Generated HMAC Hash of JSON string: ', $hash, false);

		## Create the headers for the POST
		$headers									= array(
			'X-Public-Key: '						.$this->innerVars['PUBLIC_KEY'],
			'X-Hash: '								.$hash,
			'X-Sourceip: '							.$this->innerVars['SOURCE_IP']
		);
		if($this->innerVars['FAULTY_PROXY'])
			$headers['X-Forwarded-For-Override: ']	= $this->getServerIP();
		$this->dPrt(PHP_EOL.'HTTP Headers'			, $headers);

		## Check to confirm openSSL is available. Without, IV is useless and will be reset.
		if(!extension_loaded('openssl')){
			$this->IV								= '';
			$this->dPrt(PHP_EOL.'OPENSSL extension NOT LOADED in PHP', '');
		}

		## Encrypt the POST content and set the PUBLIC KEY
		$encryptedContent							= [
			'PUBLIC_KEY'							=> $this->innerVars['PUBLIC_KEY']									,
			'CONTENT'								=> $this->doEncrypt($content, $this->innerVars['PRIVATE_KEY'], $this->IV)
		];
		if($this->IV != '')
			$encryptedContent['ZAPI']				= $this->IV;
		$this->dPrt(PHP_EOL.'Encrypted POST content: ', $encryptedContent);

		## Prepare cURL
		curl_setopt($this->cURLing, CURLOPT_URL				, $this->innerVars['SERVER_URI']);
		curl_setopt($this->cURLing, CURLOPT_POST			, 1);
		curl_setopt($this->cURLing, CURLOPT_POSTFIELDS		, $encryptedContent);
		curl_setopt($this->cURLing, CURLOPT_RETURNTRANSFER	, true);
		curl_setopt($this->cURLing, CURLOPT_SSL_VERIFYPEER	, true);
		curl_setopt($this->cURLing, CURLOPT_SSL_VERIFYPEER	, false);
		curl_setopt($this->cURLing, CURLOPT_HTTPHEADER		, $headers);

		## Execute and close cURL
		$this->httpResponse							= curl_exec($this->cURLing);

		$this->response								= json_decode($this->httpResponse, true);

		$this->dPrt('Original API Call returned response :', $this->httpResponse);
		$this->dPrt('Array API Call returned response :', $this->response);
		curl_close($this->cURLing);

		## Decrypt results
		if($this->innerVars['ENCRYPT_RESPONSE'])
			$this->result							= json_decode(
				$this->doDecrypt(
					$this->response['CONTENT'],
					$this->innerVars['PRIVATE_KEY']
				),
				true
			);
		else
			$this->result							= json_decode($this->response['CONTENT'], true);
		$this->dPrt('Final resultant returned to calling function :', $this->result);
		$this->resultCode							= $this->response['RESULT'];
		$this->serverTransactionID					= $this->response['SERVER_TRANSACTION_ID'];
		return $this->result;
	}

	private function dPrt($title, $var, $newLine=true){
		if(!$this->innerVars['DEBUG_OUTPUT'])
			return;
		if($newLine)
			echo PHP_EOL.$title.PHP_EOL.str_repeat('=', strlen($title)-1).PHP_EOL;
		else
			echo PHP_EOL.$title.' : ';
		if(is_array($var))
			var_dump($var);
		else
			echo($var);
	}

	## Encrypt variable (string)
	private function doEncrypt($encrypt, $key, $iv=''){
		if($iv == '')
			return base64_encode(AesCtr::encrypt($encrypt, $key, 256));
		else
			return base64_encode(openssl_encrypt($encrypt, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv));
	}

	## Decrypt variable (string)
	private function doDecrypt($decrypt, $key, $iv=''){
		if($iv == '')
			return AesCtr::decrypt(base64_decode($decrypt), $key, 256);
		else
			return openssl_decrypt(base64_decode($decrypt), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
	}

	## Get the IP address of the server
	function getServerIP($overrideIP=''){
		if($overrideIP !== '')
			return $overrideIP;
		else
			return gethostbyname(gethostname());
	}

	## Check if an variable has been registered in innerVars, stop processing if not.
	private function checkVariable($var, $errCode='UNSPECIFIED', $errMsg='UNSPECIFIED'){
		$this->dPrt('Checking', $var, false);
		if(!isset($this->innerVars[$var]))
			die("ERROR $errCode : $errMsg \n\n");
	}

	## Set default values for variables.
	private function setVariableDefaults($var, $default){
		$this->dPrt('Setting', $var, false);
		if(!isset($this->innerVars[$var]))
			$this->innerVars[$var]					= $default;
	}
}