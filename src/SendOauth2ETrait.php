<?php

/**
 * SendOauth2B Wrapper For Microsoft and Google OIDC/OAUTH2 For PHPMailer
 * PHP Version 5.5 and greater
 * SendOauth2ETrait Trait
 * @category Trait
 * @package  SendOauth2
 * @author   Max Stewart  (decomplexity) <SendOauth2@decomplexity.com>
 * @license  MIT
 * @note SendOauth2ETrait provides common settings and methods for SendOauth2B
 * and SendOauth2D. It also provides uppercase 'constant' properties
 * because PHP Traits earlier than PHP 8.2.0 do not support CONSTS
 */


      namespace decomplexity\SendOauth2;

/**
 * Provides generic array navigation tools.
 */
trait SendOauth2ETrait
{

/**
 * NB: THESE 'CONSTANTS' ARE NOT PHP CONSTs PER SE.
 * THEY ARE NEVERTHLESS IN UPPERCASE SO THAT IN
 * A FUTURE RELEASE OF THE WRAPPER PHP THEY CAN BE THE SINGLE
 * SOURCE OF CONSTs
*/

   /**
     * grant flow types
     *
     * @var string
     */
    private $AUTHCODE = 'authorization_code';
    private $CLIENTCRED = 'client_credentials';
    private $SERVICE_ACCOUNT = 'service_account';
    private $UNIVERSE_DOMAIN = 'googleapis.com';

   /**
    * number of arguments passed to constructor that indicate that
    * caller is SendOauth2A and not global code.
    * crude - but saves passing an additional argument
    */
    //private $NUMPARMS = 3;


   /**
    * parameters to fill in the Google API json
    */

    private $AUTH_URI = 'https://accounts.google.com/o/oauth2/auth';
    private $AUTH_PROVIDER_X509_CERT_URL = 'https://www.googleapis.com/oauth2/v1/certs';
    private $TOKEN_URI = 'https://oauth2.googleapis.com/token';


   /**
    * the Google API  credentials file
    protected $gmailXoauth2Credentials;  inherited from SendOauth2B or D
    */

   /**
    * a yes/no choice of whether the Google credentials file is to be written
    * (or you wish to use an existing external 'credentials json' from
    * https://console.cloud.google.com/
    protected $writeGmailCredentialsFile; inherited from SendOauth2B or D
    */

   /**
    * Google's API methods are  bit different from MSFT and TheLeagure Google ones
    * Google access tokens are also returned in an array with other unnecessary stuff
    */
    private $GOOGLE_API = 'GoogleAPI';


   /**
    filename prefix for the parameter file that is passed from running SendOauth2D
    */
    private $OAUTH2_PARAMETERS_FILE = 'Oauth2parms';

   /**
    implode/explode array variables separator
    */
    private $IMPLODE_GLUE = 'IMPLODE_GLUE';



    protected function GoogleAPIOauth2File()
    {

   /**
    *check if we actually need to write this file
    */
        if ($this -> serviceProvider == $this->GOOGLE_API &&
            $this-> writeGmailCredentialsFile == 'yes') {

   /**
    * create json. Note that redirectURI is not used to obtain an access token,
    * only when obtaining an authorization code.
    * CONSTs are assigned to variables because they are not evaluated within double quotes
    */

            $auth_uri = $this->AUTH_URI;
            $token_uri = $this->TOKEN_URI;
            $auth_provider_x509_cert_url = $this->AUTH_PROVIDER_X509_CERT_URL;

            $googleAPIGrantType = ($this->grantType == $this->CLIENTCRED) ?
            $this->SERVICE_ACCOUNT :
            $this->AUTHCODE;

    /**
     * the private key contains uneascaped many \n
     * When used below, these need to be escaped and json enclode_does this
     */

            switch ($googleAPIGrantType) {
   /**
    * note: json_encode with UTF-8 chars escapes each / in https://
    * in practice, json interpreters accept \/ as /
    * bit you never can tell!
    */

                case $this->AUTHCODE:
                    $xoauth2_credentials = json_encode(

                        ['web' =>
                        ['client_id' => $this->clientId,
                        'project_id' => $this->projectID,
                        'auth_uri' => $this->AUTH_URI,
                        'token_uri' => $this->TOKEN_URI,
                        'auth_provider_x509_cert_url' => $this->AUTH_PROVIDER_X509_CERT_URL,
                        'client_secret' => $this->clientSecret,
                        'redirect_uris' => [$this->redirectURI],
                        'javascript_origins' => ['blah'],
                        ]
                        ],
                        JSON_UNESCAPED_SLASHES // or json_encode escapes each / in https://
                    );

                    break;



                case $this->SERVICE_ACCOUNT:
                    $xoauth2_credentials = json_encode(
                        [
                        'type' => 'service_account',
                        'project_id' => $this->projectID,
                        'private_key_id' => $this->clientCertificateThumbprint,
                        'private_key' => $this->clientCertificatePrivateKey,
                        'client_email' => $this->serviceAccountName . '@' . $this->projectID .
                                          '.iam.gserviceaccount.com',
                        'client_id' => $this->clientId,
                        'auth_uri' => $this->AUTH_URI,
                        'token_uri' => $this->TOKEN_URI,
                        'auth_provider_x509_cert_url' => $this->AUTH_PROVIDER_X509_CERT_URL,
                        'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/' .
                                                  $this->serviceAccountName . '%40' .
                                                  $this->projectID . '.iam.gserviceaccount.com',
                        'universe_domain' => $this->UNIVERSE_DOMAIN
                        ],
                        JSON_UNESCAPED_SLASHES // or json_encode escapes each / in https://.
                    );

                    break;
            } // end switch

    /**
      * json_encode may have escaped \n (as \\n). Remove them.
      * Note that the \\ in str_replace must itself be escaped!
      */
            $xoauth2_credentials = str_replace('\\\n', '\\n', $xoauth2_credentials);

    /**
     * write it for Google Oauth2 to use to create an access token
     */

            if ($this->writeGmailCredentialsFile == 'yes') {
                file_put_contents(
                    basename($this->gmailXoauth2Credentials),
                    $xoauth2_credentials
                );
            }
        }
    } // ends GoogleAPIOauth2File method

/**
  * ends Trait SendOauth2ETrait
  */
}
