<?php

/**
 * SendOauth2C Wrapper For Microsoft and Google OIDC/OAUTH2 For PHPMailer
 * PHP Version 5.5 and greater
 *
 * @version  1.0.3
 * @category Class
 * @see      https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 * @author   Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
 * @copyright  2021 Max Stewart
 * @license  MIT
 */

namespace decomplexity/SendOauth2;

/**  if autoload fails to load the two class-files needed, load them with:    
require_once 'thenetworg/oauth2-azure/src/Provider/Azure.php';
require_once 'league/oauth2-google/src/Provider/Google.php';
*/

use TheNetworg\OAuth2\Client\Provider\Azure;
use League\OAuth2\Client\Provider\Google;
/**

 * SendOauth2C Class Doc Comment
 *
 * @category Class
 * @package  SendOauth2C
 * @author   Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
 * @license  MIT
 * @note     SendOauth2C is a fsctory to instantiate an oauth2 'provider'
 * @note     It supports more than one provider - currently Microsoft and Google -
 * @note     but allows others to be added transparently to SendOauth2A
 * @note     Oauth2 scopes and any overrrides to provider methods are here

  */

class SendOauth2C
{

    /**
     * the service provider (Microsoft, Google...)
     * @var string
     */
    protected $serviceProvider = "";


     /**
     * authentication type: either CRAM-MD5, LOGIN, PLAIN or XOAUTH2
     */
    protected $authTypeSetting = "";

    /**
    * two parameters which indicate whether or not to generate a refresh token
    * boolean 'refresh' is sent from Send_Oauth_D and  SendOauth2B
    * It is decoded into accessPrompt (see below) and accessType (set from Send_Oauth_D
    * as offline and from SendOauth2B as online)
    * SendOauth2D's output is a refresh token, but each time Send_Oauth_B
    * is invoked, we don't want to generate a new refresh token as well as the
    * access token since Google in particular limits the number of extant refresh tokens
    * and deletes the old ones
    * when we need s refresh token generated, accessPrompt is set to 'consent select_account'
    * which forces a user consent screen  (this is not always needed)
    */
    protected $accessType = "";
    protected $accessPrompt = "";


     /**
     * Instantiation of Oauth2 provider
     * @var string
     */
    public $provider;

     /**
     * scopeAuth is passed to Send_OauthD via method getScope
     * for use with getAuthorizationUrl
     * Google will not register the scope request at console.cloud.google.com
     * if it is set as a 'provider' scope (below) either via $this->provider->scope
     * or via a parameter in the instantiation of the provider
     * Note that three default scopes - openid, email and profile - are set automatically
     * and are in the Google project registration
     */

    protected $scopeAuth = "";


    /**
     * Instantiation of PHPMailer
     * @var string
     */
    protected $mail = "";

     /**
     * SMTP server domain name
     */
    protected $SMTPserver = "";

    protected $clientId;
    protected $clientSecret;
    protected $redirectURI;


    /**
	* for GSuite accounts only - used to restrict access to a specific domain
    * @var string
    */
	protected $hostedDomain;


    /**
     * __construct Method Doc Comment
     *
     * @category Method
     */

    public function __construct($optionsC)
    {

        $this->clientId = $optionsC['clientId'];
        $this->clientSecret = $optionsC['clientSecret'];
        $this->redirectURI = $optionsC['redirectURI'];
        $this->serviceProvider = $optionsC['serviceProvider'];
        $this->authTypeSetting = $optionsC['authTypeSetting'];
		$this->hostedDomain = $optionsC['hostedDomain'];
		$this->refresh = $optionsC['refresh'];

        switch ($this->refresh) {
            case true:
                $this->accessType = 'offline';
                $this->accessPrompt = 'consent select_account';
                break;

            case false:
                $this->accessType = 'online';
                $this->accessPrompt = 'none';
                break;


     /**
      * ends scope parasmeter switch
     */
        }

        switch ($this->serviceProvider) {
            case "Microsoft":
            default:
                $this->SMTPserver   = 'smtp.office365.com';
             /**
             * don't instantiate the Oauth2 provider unless the authType is XOAUTH2
             */
                if ($this->authTypeSetting != 'XOAUTH2') {
                    break;
                }

             /**
              * Instantiate Jan Hajek's TheNetworg provider for MSFT
              */
                $this->provider = new Azure(
                    [
                    'clientId'          => $this->clientId,
                    'clientSecret'      => $this->clientSecret,
                    'redirectUri'       => $this->redirectURI,
                    'accessType'        =>  $this->accessType,
                    'prompt'          =>   $this->accessPrompt
                    ]
                );


             /**
              * Azure provider overrides for v2 endpoint and Graph
              */

                $this->provider->urlAPI = "https://graph.microsoft.com/";
                $this->provider->API_VERSION = '1.0';
                $this->provider->defaultEndPointVersion = TheNetworg\OAuth2\Client\Provider\Azure::ENDPOINT_VERSION_2_0;

              /**
                 * NB  NB  NB  NB  NB  NB !
                 * One change is needed to provider's oauth2-azure-2.0.0 Azure.php
                 * that cannot be done as an override:
                 * At circa line 210, replace graph.windows.net by graph.microsoft.com
                 */


              /**
                * XXXXX  This scope MUST NOT currently  contain any Graph-specific scopes  XXXXXX
                * else it will use Graph as resource endpoint and not outlook.office.com
                * and MSFT in their wisdom have not implemented SMTP AUTH for Oauth2 in Graph!
                */
                $this->scopeAuth = 'offline_access https://outlook.office.com/SMTP.Send';
                /** or to override directly, use
                $this->provider->scope = 'offline_access https://outlook.office.com/SMTP.Send';
                */
                break;


            case "Google":
                $this->SMTPserver   = 'smtp.gmail.com'; // Google SMTP server
           /**
           * don't instantiate the Oauth2 provider unless the authType is XOAUTH2
           */
                if ($this->authTypeSetting != 'XOAUTH2') {
                    break;
                }


                $this->provider     = new Google([
                'clientId'          => $this->clientId,
                'clientSecret'      => $this->clientSecret,
                'redirectUri'       => $this->redirectURI,
				'hostedDomain'      => $this->hostedDomain,
                /**
                * note that adding:
                *'scope'  =>  'https://mail.google.com/'
                * here doesn't work - it needs to be in SendOauth2D's $options in
                * $authUrl = $provider->getAuthorizationUrl($options);
                * which is set from $this->scopeAuth below
				*/				               
                'accessType'      =>  $this->accessType,
                'prompt'          =>   $this->accessPrompt
                ]);

            /**
            * Google scope
            */
                $this->scopeAuth = 'https://mail.google.com/';
        /**
         * note that Google will bounce 'offline_access' as a scope
         */
                break;
        /**
        * ends second switch
        */
        }


      /**
       * ends __construct method
       */
    }

    public function getScope()
    {
        return $this->scopeAuth;
    }


    public function setProvider()
    {
        return $this->provider;
    }


    public function setSMTPServer()
    {
        return $this->SMTPserver;
    }


    /**
    * ends class SendOauth2C
    */
}
