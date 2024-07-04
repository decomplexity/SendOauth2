<?php
/**
 * SendOauth2D Wrapper For Microsoft and Google OIDC/OAUTH2 For PHPMailer
 * PHP Version 5.5 and greater
 *
 * @category Class
 * @see      https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 * @author   Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
 * @copyright 2021 Max Stewart
 * @license  MIT
 */

namespace decomplexity\SendOauth2;

  
    /**
     * SendOauth2D Class Doc Comment
     *
     * @category Class
     * @package  SendOauth2D
     * @author   Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
     * but the core code for MSFT is Jan Hajek's own Azure provider authorisation flow
     * which in turn is based on Ben Ramsey's (Theleague's) authorisation flow.
     * The Google provider is Woody Gilk's (via TheLeague).
     * The GoogleAPI provider and authorisation package are Google's own.
     * @license  MIT
     * @note SendOauth2D provides a wrapper for creating a new refresh token
     */


class SendOauth2D
{
    use SendOauth2ETrait;

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
  
    /**
     * file (optional) to contain the PKCE code verifier
     * @constant string
     */
    protected const CODE_VERIFIER_FILE = 'CodeVerifier.txt';
     
    /**
     * PKCE verifier length
     * @integer
     */
    protected const CODE_VERIFIER_LENGTH = 128;
  
    /**
     * PKCE hash method for PHP (used here for hash)
     * @string
     */
    protected const PKCE_HASH_METHOD_INTERNAL = 'SHA256';
  
    /**
     * PKCE hash method for call to authorisation server
     * @string
     */
    protected const PKCE_HASH_METHOD_EXTERNAL = 'S256';
  
    /**
     * instance of sendOauth2C
     */
  
    protected $SendOauth2C_obj;

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
    protected $grantType = "";

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
     * usual OAuth2 app operands.
     */
    protected $tenant = "";
    protected $clientId = "";
    protected $clientSecret = "";
    protected $redirectURI = "";
   
    /**
     * operands for $provider. Technically are extensions of
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
     * for GoogleAPI service accounts only. Not needed for authorization_code grant
     */
    protected $serviceAccountName = "";
   
    /**
     * for GoogleAPI service accounts only. Not needed for authorization_code grant.
     * Project ID is the lower-case version of the value in the 'footprint'
     * box at the head of an IAM page
     */
    protected $projectID = "";
  
    /**
     * for GoogleAPI service accounts with delegated domain-wode authority.
     * The email address of the user for the service account to impersonate
     */
    protected $impersonate = "";

    /**
     * for GoogleAPI service accounts: the file name of the .json to be used
     * for authentication. This will default to a string value set by SendOauth2B
     */
    protected $gmailXoauth2Credentials;

    /**
     * for GoogleAPI service accounts: a true/false flag to indicate
     * which .json file is used: the default one generated by SendOauth2ETrait
     * or one supplied by the user, either a copy of Google’s (from console)
     * or a user-modified one. Default is set in SendOauth2B (normally ‘true’).
     */
    protected $writeGmailCredentialsFile;

    /**
     * the state generated by the provider and sent to the AUTH server
     * It is saved over the exit and re-entry in file SESSION_STATE_FILE
     * since some browsers wrongly get confused if we store in a SESSION variable
     * held by the the browser if more than one tab is in use.
     * @constant string
     */
    protected $localState = "";

    /**
     * the state generated for GoogleAPI
     *  @string
     */
    protected $remoteState = "";


    /**
     * PKCE challenge
     * @string
     */
    protected $codeChallenge = "";

    /**
     * PKCE verifier
     * @string
     */
    protected $codeVerifier = "";
   
    /**
     * PKCE challenge cryptographic method
     * @string
     */
    protected $codeChallengeMethod = "";
   


    public function __construct($mailAuthSet)
    {
    /**
     * check to avoid a PHP 'Notice' message, specially as module is re-entrant
     */
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
     * update the $optionsD operands: 'clientCertificatePrivateKey' and
     * 'clientCertificateThumbprint', and unset the client secret:
     * 'clientSecret' => NULL

     * 'thumbprint' is also called 'fingerprint' or (to Google) 'private_key_id'
     * You can create a key pair using e.g.
     * openssl genrsa -out private.key 2048
     * openssl req -new -x509 -key private.key -out publickey.cer -days 365
     * and add the publickey.cer to your app in AAD
     */
       
       
        require 'SendOauth2D-settings.php';


    /**
     * check settings for any that that are not set at all
     * and need to be nullified
     */

            $this->checkParm('tenant', $optionsD);
            $this->checkParm('clientId', $optionsD);
            $this->checkParm('clientSecret', $optionsD);
            $this->checkParm('clientCertificatePrivateKey', $optionsD);
            $this->checkParm('clientCertificateThumbprint', $optionsD);
            $this->checkParm('redirectURI', $optionsD);
            $this->checkParm('serviceProvider', $optionsD);
            $this->checkParm('authTypeSetting', $optionsD);
            $this->checkParm('SMTPAddressDefault', $optionsD);
            $this->checkParm('fromNameDefault', $optionsD);
            $this->checkParm('grantType', $optionsD);
            $this->checkParm('hostedDomain', $optionsD);
            $this->checkParm('serviceAccountName', $optionsD);
            $this->checkParm('projectID', $optionsD);
            $this->checkParm('impersonate', $optionsD);
            $this->checkParm('gmailXoauth2Credentials', $optionsD);
            $this->checkParm('writeGmailCredentialsFile', $optionsD);
            $this->checkParm('SMTPPassword', $optionsD);
            $this->checkParm('refresh', $optionsD);
            $optionsD['mailSMTPAddress'] = "";  // SendOauth2C will check the key exists

    /**
     * delete the state diagnostics file if it exists
     */
        if (file_exists(self::DUMP_SESSION)) {
            unlink(self::DUMP_SESSION);
        }
     

    /**
     * only get the provider and then the authorization code  when we are using authorization_code flow
     * not needed for client_credentials flow (although won't hurt if we do)
     */

        if ($optionsD['authTypeSetting'] ==  'XOAUTH2') {
            if ($optionsD['grantType'] == "authorization_code") {
                $this -> clientId = $optionsD['clientId'];
                $this -> clientSecret = $optionsD['clientSecret'];
                $this -> redirectURI = $optionsD['redirectURI'];
                $this -> serviceProvider =  $optionsD['serviceProvider'];
                $this -> clientCertificatePrivateKey = $optionsD['clientCertificatePrivateKey'];
                $this -> SMTPAddressDefault = $optionsD['SMTPAddressDefault'];
                $this -> grantType = $optionsD['grantType'];
                $this -> impersonate = $optionsD['impersonate'];
                $this -> gmailXoauth2Credentials = $optionsD['gmailXoauth2Credentials'];
                $this -> writeGmailCredentialsFile = $optionsD['writeGmailCredentialsFile'];

    /**
     * credentials file is written here (even though SendOauth2B writes one) because for
     * authorization_code grant we need to get an access token now in order to get a
     * refresh token to add to the interchange file to pass to SendOauth2B
     */

                $this -> GoogleAPIOauth2File(); // write credentials file, but only if GoogleAPI

    /**
     * instantiate SendOauth2C to get provider
     */
                $this->SendOauth2C_obj = new SendOauth2C($optionsD);
                $this->provider = $this->SendOauth2C_obj->setProvider();
     
                if (!isset($_GET['code'])) {

    /**
     * If we don't have an authorization code then get one
     * but first get the non-default AUTHN scope from SendOauth2C
     */
                    $options = [
                    'scope' => [
                    $this->SendOauth2C_obj->getScope()
                    ]
                    ];
                 
    /**
     * Set state and PKCE code. The state is checked  (for CSRF) in the response from
     * the authorization_code server. The PKCE challenge code is sent to the
     * authorization_code server and the verification code is sent to the token server
     * to check that the authorizer is the same client as the token requester.
     * (The access token will be discarded) but the refresh token retained)
     */
                 
                    if ($this->SendOauth2C_obj->getIsGoogleAPI()) {
    /**
     * set the state (Google API only). TheLeague providers do this transparently
     */
                        $this->remoteState = $this->getStateForGoogleApi();
                        $this->provider->setState($this->remoteState);
                    }
                
    /**
     * set the PKCE challenge and verification codes (Google API only).
     * (TheLeague providers do this transparently)
     */
               
                    if ($this->SendOauth2C_obj->getIsGoogleAPI()) {
                
    /**
     * derive PKCE challenge and verification codes
     */
                        $this->pkce();
                
    /**
     * pkce has now created codeChallenge (used now) and codeVerifier (used after re-entrance)
     */
                        $authUrl = $this->provider->createAuthUrl(null, [
                        'code_challenge' => $this->codeChallenge,
                        'code_challenge_method' => self::PKCE_HASH_METHOD_EXTERNAL]);
                    } else {
                
    /**
     * providers that do not support PKCE
     */
                        $authUrl = $this->provider->getAuthorizationUrl($options);
                    }

                    $this->putSavedState();
                    header('Location: ' . $authUrl);
                    exit;
                } else {
      
      
    /**
     * retrieve the locally-stored state and (optional) PKCE verificaion code
     */
                    $this->getSavedState();

    /**
     * if the state returned in the URL is not the same as stored by us (whether in $_SESSION or file)
     * getSavedState will have already abended  with an error mssage and diagnostics
     *
     */
                    $verifier_parm = $this->SendOauth2C_obj->getIsPKCE() ?  $this->codeVerifier : "";
                    if ($this->SendOauth2C_obj->getIsGoogleAPI()) {
                        $this->accessToken = $this->provider->fetchAccessTokenWithAuthCode(
                            $_GET['code'],
                            $verifier_parm
                        );
                    } else {
                        $this->accessToken = $this->provider->getAccessToken(
                            $this->grantType,
                            [
                            'code' => $_GET['code'],
                            'scope' => $this->SendOauth2C_obj->getScope(),
                            ]
                        );
                    }


        /**
         * added to the interchange file; useful for later debugging of auth failures
         */
                    $optionsD['accessToken'] = $this->accessToken;
        
    /**  DEBUG GOOGLE ACCESS TOKENS
     * to debug athentication failures, MSFT access-token fields can be displayed via https://jwt.ms.
     * Google's are not jwt format, but can be checked as follows:

        $curl_url = "https://oauth2.googleapis.com/tokeninfo?access_token=" . $this->accessToken;
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $curl_url);
        curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,true);
        $ret = curl_exec($curl_handle);
        echo $ret;
        curl_close($curl_handle);
     */



    /**
     * add the refresh token to the oauth2 parms
     */
                    if ($this->SendOauth2C_obj->getIsGoogleAPI()) {  // a nested ternary operator here looks confusing!
                        if (array_key_exists('refresh_token', $this->accessToken)) {
                            $optionsD['refreshToken'] = $this->accessToken['refresh_token'];
                        } else {
                            $optionsD['refreshToken'] = "";
                        }
                    } else {
                         $optionsD['refreshToken'] = $this->accessToken->getRefreshToken();
                    }

                    if (empty($optionsD['refreshToken'])) {
                        echo("ERROR - refresh token not created.");
                        echo("You may need to revoke app access via the service provider's OAuth2 'console'. ");
                        echo("Then try again");
                    } else {
                        echo("Refresh token successfully created");
                 
                   // to display the refresh token when debugging, uncomment:
                   // echo ("REFRESH TOKEN = " . $optionsD['refreshToken']);
                    }
                }  // ends yes - we have an authorization code in the redirect URL
            } else { // } ends XOAUTH2 with authorization_code flow

    /**
     * still XOAUTH2 but not authorization_code
     */

                $this->grantType =  $optionsD['grantType'];
                switch ($this->grantType) {
                    case "client_credentials":
                        $optionsD['refreshToken'] = "DUMMY REFRESH TOKEN FOR CLIENT-CREDENTIALS GRANT";
                       // the above is a useful note when debugging from the Oauth2parms file
                        echo ("OAuth2 settings now stored.<br />Refresh token not needed for client_credentials grant");
                        break;

                    default:
                        echo ("Invalid grantType = " . $this->grantType);
                        exit;
                } //ends switch
            }

    /**
     * end of Oauth2-specific AUTH code, including the state set and restore
     */
        } else {
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
        $optionsD['tenant'],
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
        $optionsD['serviceAccountName'],
        $optionsD['projectID'],
        $optionsD['impersonate'],
        $optionsD['gmailXoauth2Credentials'],
        $optionsD['writeGmailCredentialsFile'],
        $optionsD['refresh'],
        $optionsD['refreshToken'],
        $optionsD['grantType']
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
    /**
     * Providers like TheNetworg's use the TheLeague's Abstract provider
     * as extension. This inserts the value of 'state' in URL parameter options
     * transparently to the caller. $this->provider->getState can read it
     * for CSRF checking, but only if the provider is instantiated - which
     * when using the Google API it isn't. For the latter, we manage 'state' oursselves
     */
                if ($this->SendOauth2C_obj->getIsGoogleAPI()) {
                    $_SESSION['oauth2state'] = $this->remoteState;
                } else {
                    $_SESSION['oauth2state'] = $this->provider->getState();
                }
           
    /**
     * save the PKCE verification code (Google API only).
     */
                if ($this->SendOauth2C_obj->getIsPKCE()) {
                    $_SESSION['oauth2verifier'] = $this->codeVerifier;
                }
                break;
           
           
            case "file":
                if ($this->SendOauth2C_obj->getIsGoogleAPI()) {
                    file_put_contents(self::SESSION_STATE_FILE, $this->remoteState);
                } else {
                    file_put_contents(self::SESSION_STATE_FILE, $this->provider->getState());
                }
           
    /**
     * save the PKCE verification code (curently Google API only).
     */
                if ($this->SendOauth2C_obj->isPKCE()) {
                    file_put_contents(self::CODE_VERIFIER_FILE, $this->codeVerifier);
                }
                break;
          
            default:
                echo ("Property $sessionStateSave value invalid");
                exit;
        }  // ends switch
    }
    /**
     * ends putSavedState method
     */

    protected function getSavedState()
    {
        switch ($this->sessionStateSave) {
            case "session":
                $this->localState = $_SESSION['oauth2state'];
                       
                if ($this->SendOauth2C_obj->getIsPKCE()) {
                    $this->codeVerifier = $_SESSION['oauth2verifier'];
                }
                                    
                break;

            case "file":
                $this->localState = file_get_contents(self::SESSION_STATE_FILE);
                unlink(self::SESSION_STATE_FILE);
           
                if ($this->SendOauth2C_obj->getIsPKCE()) {
                    $this->codeVerifier = file_get_contents(self::CODE_VERIFIER_FILE);
                    unlink(self::CODE_VERIFIER_FILE);
                }
                break;

            default:
                echo ("Property $sessionStateSave value invalid");
                exit;
        } // ends switch

    /**
     * now check if stored state is the same as the one returned along with the auth code
     * GogleAPI auth server does not support 'state'
     */
       
        if (empty($_GET['state']) || ($_GET['state'] !== $this->localState)) {
       // stored code and URL-returned code don't match
            file_put_contents(self::DUMP_SESSION, date("H:i:s") . '  $_GET[state] = ' . $_GET['state'] .
            'and' . 'Locally-stored state = ' . $this->localState);
             echo ('ERROR - INVALID STATE <br />');
             echo ('&state in URL = ' . $_GET['state'] . '<br />');
             echo ('$_SESSION or Locally-stored state = ' . $this->localState . '<br />');
             echo ('Check redirect URL and the URL used to call SendOauthD-invoke; ' .
             'especially that both have WWW prefix or both not.' . '<br />');
             echo ('This is because a $_SESSION is associated with a browser URI. <br />');
             echo ('See also the DUMP SESSION diagnostics file. <br />');
             echo ('If all else fails, set $sessionStateSave property to "file"');
             exit();
        }
    }

    /**
     * ends getSavedState method
     */

    protected function getStateForGoogleApi()
    {
        return $this->getRandomState();
    } // ends state getter
    

    protected function getRandomState($length = 32)
    {
    // Converting bytes to hex will double length. Hence, we can reduce
    // the amount of bytes by half to produce the correct length.
        return bin2hex(random_bytes($length / 2));
    } // ends state generator


    protected function pkce()
    {
    /**
     * build code verifier of length CODE_VERIFIER_LENGTH from a random string
     * whose the base charset is the base64url of RFC 4648 that is suitable for
     * encoding URL parameters while avoiding URI-specific encoding oddities such as '.'
     * For PKCE specification used, see rfc7636
      */
        $base64Charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-._~';
        $base64CharsetLength = strlen($base64Charset);
    
        $this->codeVerifier = '';
        $i = 0;
        while ($i++ < self::CODE_VERIFIER_LENGTH) {
    // remember that [..] to select one string character works like substr()
            $this->codeVerifier .= $base64Charset[random_int(0, $base64CharsetLength - 1)];
        }
   
    /**
     * hash code verifier to create challenge.
     * switch + and / to - and _  (per Modified Base64 for URL), and
     * trim any = from the end after hashing
     * Step by step to clarify logic and prevent PSR12-format non-compliance of multi-line function
     */

        $hashed = hash(self::PKCE_HASH_METHOD_INTERNAL, $this->codeVerifier, true);
        $base64hashed = base64_encode($hashed);
        $base64hashedURLsafe = strtr($base64hashed, '+/', '-_');
        $this->codeChallenge = rtrim($base64hashedURLsafe, '=');

    /**
     * The codeVerifier and codeChallenge are returned to caller.
     * putSavedState and get SavedState methods are responsible for saving and restoring
     * them over the SendOauthD re-entrant call
     */
    }  // ends method pkce
     

    protected function checkParm($inparm, &$options)
    {
    /**
     * check / nullify SendOauth2D-settings
     */
        return (array_key_exists($inparm, $options) && isset($options[$inparm]))
                 ?
                 : $options[$inparm] = '';
    } // ends checkParm method
  
    
    /**
     *  end class SendOauth2D
     */
}
