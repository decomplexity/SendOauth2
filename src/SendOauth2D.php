<?php
/**
 * SendOauth2D Wrapper For Microsoft and Google OIDC/OAUTH2 For PHPMailer
 * PHP Version 5.5 and greater
 *
 * @category Class
 * @see      https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 * @author   Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
 * @copyright  2021 Max Stewart
 * @license  MIT
 */

namespace decomplexity\SendOauth2;

     /**
     * SendOauth2D Class Doc Comment
     *
     * @category Class
     * @package  SendOauth2D
     * @author   Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
     * but the core code is Jan Hajek's own Azure provider authorisation flow
     * which in turn is based on Theleague's authorisation flow
     * @license  MIT
     * @note SendOauth2D provides a wrapper for creating a new refresh token
     */


class SendOauth2D
{
    /**
    * Browsers occasionally have trouble remembering the session state
    * that is stored before reqesting an authorization code and is then
    * retrieved and checked when a code is provided in order to 
    * forestall CSRF attacks. This module is re-entrant and may
    * confuse things. But the usual cause of such problems is
    * the callback URL, redirect URL and module invocation URL nor matching,
    * especially some with www and some not.
    * The property below can take two values: "session" and "file"
    *  - "session" (default) stores the state using a $_SESSION variable
    *  - "file" stores in a temporary local file   
    */
    protected $sessionStateSave = "session"; 
		
   /**
     * key to select the AUTHN 'case' below
     */
    protected $mailAuthSet = "";

   /**
    * authorization flow type (for OAuth2 only)
    * for future use when e.g. MSFT supports SMTP.Send with 'ciient_credentials'
    */
    protected $grantTypeValue = "";


    /**
     * the service provider (Microsoft, Google...)
     * @var string
     */
    protected $serviceProvider = "";


    /**
     * Instantiation of Oauth2 provider
     * @var string
     */
    protected $provider;

   /**
     * Instantiation of PHPMailer
     * @var string
     */
    protected $mail = "";

    /**
     * usual OAuth2 app operands
     */
    protected $clientId = "";
    protected $clientSecret = "";
    protected $redirectURI = "";
	
     /**
      * operands for $provider. Techncally are extensions of
      * TheLeague's generic $provider
      */
	
     protected $clientCertificatePrivateKey = "";
     protected $clientCertificateThumbprint = "";
	

	/**
    * Access token generated in order to then generate refresh token
    * @var string
    */
		
     protected $accessToken = ""; 	
			
		
       /**
	* for GSuite accounts only - used to restrict access to a specific domain
        * @var string -  NB for use with ALL GSuite accounts this must be '* and not blank
	* Documentation is conflicting about whether using '*' for not-business (i.e. 'domestic'
	* GMail accounts will block access or not 
        */
     protected $hostedDomain = "";
	
	
      /**
        * the state generated by the provider and sent to the AUTH server
	* It is saved over the exit and re-entry in file SESSION_STATE_FILE
	* since some browsers wrongly get confused if we store in a SESSION variable
	* held by the the browser if more than one tab is in use.  
        *  @constant string
        */
     protected $localState = "";

     /**
     * filename prefix for the parameter file that is passed from running SendOauth2D
     * @constant string
     */
     protected const OAUTH2_PARAMETERS_FILE = 'Oauth2parms';

    /**
    * implode/explode array variables separator
    * @constant string
    */
     protected const IMPLODE_GLUE = 'IMPLODE_GLUE';
	
    /**
     * see comments above above re property $sessionStateSave
     * @constant string
     */
     protected const SESSION_STATE_FILE = 'Sessionstate.txt';
   
    /**
      * a diagnostic file for use when state reeturned from AUTH server and state stored locally
      * do not agree
      */
     protected const DUMP_SESSION = 'Dumpsession.txt';
	 
     public function __construct($mailAuthSet)
    {
        // check to avoid a PHP 'Notice' message, specially as module is re-entrant
	   if (session_status() === PHP_SESSION_NONE) {
           session_start();
           }
        
	  $this->mailAuthSet = $mailAuthSet;
     
      /**
        * $authTypeSetting is either CRAM-MD5, LOGIN, PLAIN or XOAUTH2
        * this wrapper is essentially for XOAUTH2
        * PLAIN is not recommended!
		*
		* For OAuth2, to use an openssl or other client certificate instead of a client secret, 
		* update the $optionsD operands below: 
		* 'clientCertificatePrivateKey' => 'your azure-client-certificate-private-key',
                * 'clientCertificateThumbprint' => 'your azure-client-certificate-thumbprint',
		*  and unset the client secret:
		* 'clientSecret' => NULL,
		
		* You can create a key pair using e.g.
		* openssl genrsa -out private.key 2048
        * openssl req -new -x509 -key private.key -out publickey.cer -days 365
		* and add the publickey.cer to your app in AAD  
		*/

       /** 
	 * Now pull in the parameter settings for each service provider and service type.
         * Not all setttings will be used for any one service provider and service type: 	  
	      clientId
	      clientSecret
	      clientCertificatePrivateKey
	      clientCertificateThumbprint
	      redirectURI
	      serviceProvider
	      authTypeSetting
	      SMTPAddressDefault
	      fromNameDefault
	      grantTypeValue
	      SMTPPassword
		  hostedDomain
	      refresh 
	  */
         		 

        require 'SendOauth2D-settings.php';


       /**
	 * delete the state diagnostics file if it exists
	 */
		if (file_exists(self::DUMP_SESSION)) {
		    unlink(self::DUMP_SESSION); 
	        }
	  
	  	  
      /**
       * only obtain authorization and refresh codes if OAuth2
       */
        if ($optionsD['authTypeSetting'] ==  'XOAUTH2') {

       /**
       * instantiate SendOauth2C to get provider
       */
         $SendOauth2C_obj = new SendOauth2C($optionsD);
         $this->provider = $SendOauth2C_obj->setProvider();
       /**
        * ideally we would serialize the provider here and file it 
        * so that we can unserialize later. But serialization of provider
        * fails...
        */

        if (!isset($_GET['code'])) {
                  /**
                  * If we don't have an authorization code then get one
                  * but first get the non-default AUTHN scope from SendOauth2C
                  */

                $options = [
                   'scope' => [
                   $SendOauth2C_obj->getScope()
                   ]
                 ];


                $authUrl = $this->provider->getAuthorizationUrl($options);

				
		/**
		* save the state
		*/
  		$this->putSavedState();
               
		header('Location: ' . $authUrl);
                exit;
        } 
			
					
			 else
			
        /**
		 * retrieve the locally-stored state
		 */
      	{
	 
		$this->getSavedState();
		    	  
		/**
		* if the state returned in the URL is not the same as stored by us (whether in $_SESSION or file)
		* getSavedState will have already abended  with an error mssage and diagnostics
		* 
		*/
		
		$this->grantTypeValue =  $optionsD['grantTypeValue'];
		$this->accessToken = $this->provider->getAccessToken($this->grantTypeValue, [ 
                    'code' => $_GET['code'],
                    'scope' => $SendOauth2C_obj->getScope(),
            ]);

                 
        $optionsD['accessToken'] = $this->accessToken;
 		
       /**
        * add the refresh token to the oauth2 parms
        */
       
	   switch($this->grantTypeValue) {
	   
	   case "authorization_code":
	   $optionsD['refreshToken'] = $this->accessToken->getRefreshToken();  
           if (empty($optionsD['refreshToken'])) {
                    echo("ERROR - refresh token not created.");
                    echo("You may need to revoke app access via the service provider's Oauth2 'console'.");
                    echo("Then try again!");
           } else {
                    echo("Refresh token successfully created");
        }
      break;
	  
      case "client_credentials": 
	  $optionsD['refreshToken'] = "DUMMY REFRESH TOKEN FOR CLIENT-CREDENTIALS GRANT";
	  // useful for diagnostics from the Oauth2parms file 
	  echo ("OAuth2 settings now stored. Refresh token not needed for client_credentials grant");
	  break;

      default:
	  echo ("Invalid grantTypeValue");
	  exit; 
  	}   
	  
	  
      // to display the refresh token when debugging, uncomment:
      // echo ("REFRESH TOKEN = " . $optionsD['refreshToken']);

     /**
      * ends the code starting 'GET' ...
      */
      }

       /**
       * end of Oauth2-specific AUTH code, including the state set and restore
       */
       
	    }
	 else
	    {
            $optionsD['refreshToken'] = "";
            echo("Run for non-Oauth2 authentication apparently successful");

       /**
        * this was just to forestsll any problems on exploding in SendOauthB
        * when using Basic Auth
        */
        }


       $obj = $this->saveParameters($optionsD);

       /**
        * end __construct method
        */
       }

       protected function saveParameters($optionsD)
      {
      /**
       * concatenate the parms - with a separator
       * NB  implode discards keys and uses only the values
       * so we make very obvious the order of the parameters
       * for future changes. Thia also allows
       * unnecessary empty parameters to be omitted above in e.g. Basic Auth
       */

       $optionsD1 = [
       $optionsD['clientId'],
       $optionsD['clientSecret'],
       $optionsD['clientCertificatePrivateKey'],
       $optionsD['clientCertificateThumbprint'],
       $optionsD['redirectURI'],
       $optionsD['serviceProvider'],
       $optionsD['authTypeSetting'],
       $optionsD['fromNameDefault'],
       $optionsD['SMTPAddressDefault'],
       $optionsD['SMTPPassword'],
       $optionsD['hostedDomain'],
       $optionsD['refresh'],
       $optionsD['refreshToken'],
       $optionsD['grantTypeValue']
       ];


       $optionsD2 = implode(self::IMPLODE_GLUE, $optionsD1);

      /**
      * If the contents of the file (below) need encrypting, do it here.
      * Just encrypt $optionsD2
      */


      /**
       * write the completed set of parameters to file
       */
        file_put_contents(
            self::OAUTH2_PARAMETERS_FILE . "_" . $this->mailAuthSet . ".txt",
            $optionsD2
        );

     /**
      * ends saveParameters method
      */
    }

       protected function putSavedState() 
      {
      switch ($this->sessionStateSave) {
      case "session":
        {
        $_SESSION['oauth2state'] = $this->provider->getState();
        }
      break;
      case "file":
        {
        file_put_contents(self::SESSION_STATE_FILE,$this->provider->getState());
        }
      break;
      default:
      echo ("Property $sessionStateSave value invalid");
      exit;
   } // ends switch
  }
  /**
   * ends putSavedState method
   */
   
      protected function getSavedState() { 
	  switch ($this->sessionStateSave) {
      case "session":
        {
        $this->localState = $_SESSION['oauth2state'];
        }
      break;
      case "file":
        {
        $this->localState = file_get_contents(self::SESSION_STATE_FILE);
        unlink(self::SESSION_STATE_FILE);
        }  
      break;
      default:
	   {
	   echo ("Property $sessionStateSave value invalid");
       exit;
       }
    } // ends switch

  /**
  * now check if stored state is the same as the one returned along with the auth code
  */
       if (empty($_GET['state']) || ($_GET['state'] !== $this->localState)) {
	   
       // stored code and URL-returned code don't match
       file_put_contents(self::DUMP_SESSION, date("H:i:s") . '  $_GET[state] = ' . $_GET['state'] . 'and' . 'Locally-stored state = ' . $this->localState);
       echo ('ERROR - INVALID STATE <br />'); 
       echo ('&state in URL = ' . $_GET['state'] .'<br />');
       echo ('$_SESSION or Locally-stored state = ' . $this->localState . '<br />');
       echo ('Check redirect URL and the URL used to call SendOauthD-invoke. Especially both must have WWW prefix or both not.' . '<br />');
       echo ('See also the DUMP SESSION diagnostics file. <br />');
       echo ('If all else fails, set $sessionStateSave property to "file"');
       exit();
       }   
     }
  
  /**
   * ends getSavedState method
   */
   
   /**
   *  end class SendOauth2D
   */
}
