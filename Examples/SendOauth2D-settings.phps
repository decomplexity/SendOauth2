<?php
        
switch ($this->mailAuthSet) {

   /**
    * Each 'case' below consists of parameters that may (usually) be set
    * followed by parameters that are set to null.
    * $authTypeSetting is either CRAM-MD5, LOGIN, PLAIN or XOAUTH2
    * this wrapper is essentially for XOAUTH2
    * PLAIN is not recommended!
    *
    * Npte that Google service-account grantType is 'client_credentials'
    *
    * For OAuth2, to use an openssl or other client certificate instead of a client secret,
    * use these operands below:
    * 'clientCertificatePrivateKey' => 'your azure-client-certificate-private-key',
    * 'clientCertificateThumbprint' => 'your azure-client-certificate-thumbprint',
    * and unset the client secret: 'clientSecret' => ""
    * You can use the key generation facility provided by MSFT or Google, or
    * you can create a key pair using e.g.
    * openssl genrsa -out private.key 2048
    * openssl req -new -x509 -key private.key -out publickey.cer -days 365
    * and add the publickey.cer to your app in AAD or Google console
    */

    case "1": // Microsoft Oauth2
    default:
                $optionsD = [
                'clientId'                    => 'long string',
                'clientSecret'                => 'long string', // or null if using a certificate
                'clientCertificatePrivateKey' => "",
                'clientCertificateThumbprint' => "",
                'serviceProvider'             => 'Microsoft', // literal
                'authTypeSetting'             => 'XOAUTH2',   // literal
                'redirectURI'                 => 'https://www.mydomain.com/php/SendOauth2D-invoke.php',
                'SMTPAddressDefault'          => 'me@mydomain.com',
                'fromNameDefault'             => 'My website',
                'grantType'                   => 'authorization_code', // or 'client_credentials'
                'tenant'                      => 'long string',
                ];
 
               /**
                * tell SendOauthC that when the provider is instantiated
                * it must request a refresh token
                */
                $optionsD['refresh'] = true;
                
               // not needed for MSFT
                $optionsD['hostedDomain'] = "";
                $optionsD['serviceAccountName'] = "";
                $optionsD['projectID'] = "";
                $optionsD['impersonate'] = "";
                $optionsD['gmailXoauth2Credentials'] = "";
                $optionsD['writeGmailCredentialsFile'] = "";


              // not needed for XOAUTH2
                $optionsD['SMTPPassword'] = "";
                break;


    case "2": // Microsoft Basic Auth
                $optionsD = [
                'serviceProvider'           =>  'Microsoft', // literal
                'authTypeSetting'           =>  'LOGIN',     // literal
                'SMTPAddressDefault'        =>  'me@mydomain.com',
                'fromNameDefault'           =>  'My website',
                'SMTPPassword'              =>  'basic authentication password',
                ];

              /**
               * just to be consistent, although it should be irrelevant...
               */
                $optionsD['refresh'] = false;


              // not needed for MSFT Basic Auth
                $optionsD['clientId'] = "";
                $optionsD['clientSecret'] = "";
                $optionsD['clientCertificatePrivateKey'] = "";
                $optionsD['clientCertificateThumbprint'] = "";
                $optionsD['redirectURI'] = "";
                $optionsD['grantType'] = "";
                $optionsD['tenant'] = "";
                $optionsD['hostedDomain'] = "";
                $optionsD['serviceAccountName'] = "";
                $optionsD['projectID'] = "";
                $optionsD['impersonate'] = "";
                $optionsD['gmailXoauth2Credentials'] = "";
                $optionsD['writeGmailCredentialsFile'] = "";
                break;


    case "3": // Google (NOT GoogleAPI)
                $optionsD = [
                'clientId'                   =>  'long string',
                'clientSecret'               =>  'long string',
                'redirectURI'                =>  'https://www.mydomain.com/php/SendOauth2D-invoke.php',
                'serviceProvider'            =>  'Google',        // literal
                'authTypeSetting'            =>  'XOAUTH2',       // literal
                'SMTPAddressDefault'         =>  'me@mydomain.com',
                'fromNameDefault'            =>  'My website',
                'grantType'                  =>  'authorization_code',
                'hostedDomain'               =>  'mydomain.com', // optional
                ];

                $optionsD['refresh'] = true;

              // not needed for Google XOAUTH2
                $optionsD['SMTPPassword'] = "";
                $optionsD['tenant'] = "";
                $optionsD['clientCertificatePrivateKey'] = "";
                $optionsD['clientCertificateThumbprint'] = "";
                $optionsD['serviceAccountName'] = "";
                $optionsD['projectID'] = "";
                $optionsD['impersonate'] = "";
                $optionsD['gmailXoauth2Credentials'] = "";
                $optionsD['writeGmailCredentialsFile'] = "";
                break;

    case "4": // Google Basic Auth
                $optionsD = [
                'serviceProvider'           =>  'Google',  // literal
                'authTypeSetting'           =>  'LOGIN',   // literal
                'SMTPAddressDefault'        =>  'me@mydomain.com',
                'fromNameDefault'           =>  'My website',
                'SMTPPassword'              =>  'basic authentication password',
                ];

                $optionsD['refresh'] = false;
                
              /**
               * not needed for Google Basic Auth
               */
                $optionsD['clientId'] = "";
                $optionsD['clientSecret'] = "";
                $optionsD['clientCertificatePrivateKey'] = "";
                $optionsD['clientCertificateThumbprint'] = "";
                $optionsD['redirectURI'] = "";
                $optionsD['grantTypeValue'] = "";
                $optionsD['tenant'] = "";
                $optionsD['hostedDomain'] = "";
                $optionsD['gmailXoauth2Credentials'] = "";
                $optionsD['writeGmailCredentialsFile'] = "";
                break;


    case "5": // GoogleAPI
                $optionsD = [
                'clientId'                    =>  'long string',
                'clientSecret'                =>  'long string',
                'clientCertificatePrivateKey' =>  'extremely long string',
                'clientCertificateThumbprint' =>  'long string',
                'redirectURI'                 =>  'https://www.mydomain.com/php/SendOauth2D-invoke.php',//auth_code only
                'serviceProvider'             =>  'GoogleAPI',          // literal
                'authTypeSetting'             =>  'XOAUTH2',            // literal
                'SMTPAddressDefault'          =>  'me@mydomain.com',
                'fromNameDefault'             =>  'My website',
                'grantType'                   =>  'authorization_code', // or 'client_credentials'
                'hostedDomain'                =>  'mydomain.com',       // optional
                'serviceAccountName'          =>  'string',             // grantType client_credentials only
                'projectID'                   =>  'string',             // grantType client_credentials only
                'impersonate'                 =>  'you@mydomain.com',   // client_creds with Google WSpace accts only
                'gmailXoauth2Credentials'     =>  'gmail-xoauth2-credentials.json', // client_credentials with Google
                                                                        // Wspace accts only. Choose a different
                                                                        // .json filename here if you wish.
                'writeGmailCredentialsFile'   =>  'yes' or 'no',        // Client_creds with Google WSpace accts only.
                                                                        // A 'yes' or 'no' string, defaults to 'yes'
                                                                        // Yes implies that the .json
                                                                        // generated by SendOauth2ETrait is used.
                                                                        // No that an external file is to be used
                ];

                $optionsD['refresh'] = true;

               // not needed for GoogleAPI XOAUTH2
                $optionsD['SMTPPassword'] = "";
                $optionsD['tenant'] = "";
                break;


       /**
        *  ends switch
        */
}
