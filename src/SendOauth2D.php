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
namespace decomplexity\SendOauth2;

/** if autoload fails to load the two class-files needed, load them with:  
require_once 'phpmailer/phpmailer/src/OAuth.php';
require_once 'thenetworg/oauth2-azure/src/Provider/Azure.php';
*/


///require_once 'league/oauth2-google/src/Provider/Google.php';
//require_once 'decomplexity/sendoauth2/src/SendOauth2C.php';

use phpmailer\phpmailer\OAuth;
//use TheNetworg\OAuth2\Client\Provider\Azure;
//use League\OAuth2\Client\Provider\Google;


     /**
     * SendOauth2D Class Doc Comment
     *
     * @category Class
     * @package  SendOauth2D
     * @author   Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
     * but the core code is Jan Hajek's own Azure provider authorisation flow
     * which in turn is based on Theleague's authorisation flow
     * @license  MIT
     * @note     SendOauth2C provides a wrapper for creating a new refresh token

     */


class SendOauth2D
{
     /**
     * key to select the AUTHN 'case' below
     */
    protected $mailAuthSet = "";

     /**
     * the service provider (Microsoft, Google...)
     * @var string
     */
    protected $serviceProvider = "";


     /**
     * Instantiation of Oauth2 provider
     * @var string
     */
    public $provider;

    /**
     * Instantiation of PHPMailer
     * @var string
     */
    protected $mail = "";

    protected $clientId = "";
    protected $clientSecret = "";
    protected $redirectURI = "";
	
		
	/**
	* for GSuite accounts only - used to restrict access to a specific domain
    * @var string -  NB for use with ALL GSuite accounts this must be '* and not blank
	* Documentation is conflicting about whether using '*' for not-business (i.e. 'domestic'
	* GMail accounts will block access or not 
    */
	protected $hostedDomain = "";
		
    protected $implode_array = "";

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


    public function __construct($mailAuthSet)
    {

        $this->mailAuthSet = $mailAuthSet;

        switch ($this->mailAuthSet) {

        /** ======================================================================================= */
        /**
        * $authTypeSetting is either CRAM-MD5, LOGIN, PLAIN or XOAUTH2
        * this wrapper is essentially for XOAUTH2
        * PLAIN is not recommended!
        '*/

            case "1": // Microsoft Oauth2
            default:
                $optionsD = [
                'clientId'                  => 'long string',
                'clientSecret'              => 'long string',
                'redirectURI'               => 'https://www.mydomain.com/php/SendOauth2D-invoke.php',
                'serviceProvider'           => 'Microsoft',
                'authTypeSetting'           => 'XOAUTH2',
                'SMTPAddressDefault'        => 'me@mydomain.com',
                'fromNameDefault'           => 'My website',
                 ];
               /**
                *  refresh indicator and the refreshToken is added later
                */

              /**
               * invoke the provider and set overrides
               * but first tell Send_Oauth_C that when the provider is instantiated
               * it must request a refresh token
               */
                $optionsD['refresh'] = true;
                break;


            case "2": // Microsoft Basic Auth
                $optionsD = [
                'serviceProvider'           =>  'Microsoft',
                'authTypeSetting'           =>  'LOGIN',
                'SMTPAddressDefault'        =>  'me@mydomain.com',
                'fromNameDefault'           =>  'My website',
                'SMTPPassword'              =>  'basic authentication password'
                ];

            /**
            * just to be consistent, although it should be irrelevant...
            */
                $optionsD['refresh'] = false;
                break;


            case "3": // Google
                $optionsD = [
                'clientId'                  =>  'long string',
                'clientSecret'              =>  'long string',
                'redirectURI'               =>  'https://www.mydomain.com/php/SendOauth2D-invoke.php',
                'serviceProvider'           =>  'Google',
                'authTypeSetting'           =>  'XOAUTH2',
                'SMTPAddressDefault'        =>  'an email address',
                'fromNameDefault'           =>  'My website',
                ];



                $optionsD['refresh'] = true;
                break;


            case "4": // Google Basic Auth
                $optionsD = [
                'serviceProvider'           =>  'Google',
                'authTypeSetting'           =>  'LOGIN',
                'SMTPAddressDefault'        =>  'me@gmail.com',
                'fromNameDefault'           =>  'My website',
                'SMTPPassword'              =>  'basic authentication password'
                ];

                $optionsD['refresh'] = false;
                break;


      /** ======================================================================================= */

        /**
        *  ends switch
        */
        }

       /**
       * if not Oauth2, then bypass obtaining authorization and refresh codes
       */
        if ($optionsD['authTypeSetting'] ==  'XOAUTH2') {

       /**
       * instantiate SendOauth2C to get provider
       */
            $SendOauth2C_obj = new SendOauth2C($optionsD);
            $provider = $SendOauth2C_obj->setProvider();


        /**
        *store the paramters set above for use when this module is re-entered via its redirectUri
        */

            $_SESSION['Oauth2parms'] = $optionsD;

        /**
        * ideally we would serialize the provider here and add it to $_SESSION
        * so that we can unserialize later. But serialization of provider
        * fails.
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


                      $authUrl = $provider->getAuthorizationUrl($options);
                      $_SESSION['oauth2state'] = $provider->getState();
                      header('Location: ' . $authUrl);
                      exit;

                  /**
                  * Check given state against previously stored one to mitigate CSRF attack
                  */
            } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
                 unset($_SESSION['oauth2state']);
                 exit('Invalid state');
            } else {
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code'],
                    'scope' => $provider->scope,
                ]);


                 $optionsD = $_SESSION['Oauth2parms'];

                 /**
                 * add the refresh token to the oauth2 parms
                 */

                 $optionsD['refreshToken'] = $token->getRefreshToken();

                if (empty($optionsD['refreshToken'])) {
                    echo("ERROR - refresh token not created.");
                    echo("You may need to revoke app access via the service provider's Oauth2 'console'.");
                    echo("Then try again!");
                } else {
                    echo("Refresh token successfully created");
                }

     /**
     * to display the refresh token when debugging, uncomment:
     * echo ("REFRESH TOKEN = " . $optionsD['refreshToken']);
     */


            /**
            * ends the code starting 'GET' ...
            */
            }

       /**
       * end of Oauth2-specific AUTH code, including the $_SESSION set and restore
       */
        } else {
            $optionsD['refreshToken'] = "";
            echo("Run for non-Oauth2 authentication apparently successful");

        /**
         * this was just to forestsll any problems on exploding in Send_Oauth_B
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
        $optionsD['redirectURI'],
        $optionsD['serviceProvider'],
        $optionsD['authTypeSetting'],
        $optionsD['fromNameDefault'],
        $optionsD['SMTPAddressDefault'],
        $optionsD['SMTPPassword'],
		    $optionsD['hostedDomain'],
        $optionsD['refresh'],
        $optionsD['refreshToken']
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

   /**
   *  end class SendOauth2D
   */
}
