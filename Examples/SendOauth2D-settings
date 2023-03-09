<?php
        
        switch ($this->mailAuthSet) {

        /**
        * $authTypeSetting is either CRAM-MD5, LOGIN, PLAIN or XOAUTH2
        * this wrapper is essentially for XOAUTH2
        * PLAIN is not recommended!
	*
	* For OAuth2, to use an openssl or other client certificate instead of a client secret, 
	* use $optionsD operands below: 
	* 'clientCertificatePrivateKey' => 'your azure-client-certificate-private-key',
        * 'clientCertificateThumbprint' => 'your azure-client-certificate-thumbprint',
	*  and unset the client secret:
	* 'clientSecret' => "",
		
	* You can create a key pair using e.g.
	* openssl genrsa -out private.key 2048
        * openssl req -new -x509 -key private.key -out publickey.cer -days 365
	* and add the publickey.cer to your app in AAD  
	*/

            case "1": // Microsoft Oauth2
            default:
                $optionsD = [
                'clientId'                    => 'long string',
                'clientSecret'                => 'long string',
		'clientCertificatePrivateKey' => "",
		'clientCertificateThumbprint' => "",
		'redirectURI'                 => 'https://www.mydomain.com/php/SendOauth2D-invoke.php',
                'serviceProvider'             => 'Microsoft',
                'authTypeSetting'             => 'XOAUTH2',
                'SMTPAddressDefault'          => 'me@mydomain.com',
                'fromNameDefault'             => 'My website',
		'grantTypeValue'              => 'authorization_code',
		 ];
               /**
                * MSFT has (at March 2023) client_credentials grant for SMTP in work.  
		* Refresh indicator and the refreshToken are added below
                */

               /**
                * invoke the provider and set overrides
                * but first tell SendOauthC that when the provider is instantiated
                * it must request a refresh token
                */
                $optionsD['refresh'] = true;
				
		// not needed for MSFT 
		$optionsD['hostedDomain'] = "";
		
		// not needed for XOAUTH2
		$optionsD['SMTPPassword'] = "";
		 
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
			
			 
	       // not needed for MSFT Basic Auth
		$optionsD['clientId'] = "";  
 	        $optionsD['clientSecret'] = "";
		$optionsD['clientCertificatePrivateKey'] = "";
		$optionsD['clientCertificateThumbprint'] = "";
		$optionsD['redirectURI'] = "";
		$optionsD['grantTypeValue'] = "";
		$optionsD['hostedDomain'] = ""; 
			   				
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
		'grantTypeValue'            =>  'authorization_code',
		'hostedDomain'              =>  "",
                ];

                $optionsD['refresh'] = true;
				
		// not needed for Google XOAUTH2
		$optionsD['SMTPPassword'] = "";
  		$optionsD['clientCertificatePrivateKey'] = "";
		$optionsD['clientCertificateThumbprint'] = "";				
                break;


            case "4": // Google Basic Auth
                $optionsD = [
                'serviceProvider'           =>  'Google',
                'authTypeSetting'           =>  'LOGIN',
                'SMTPAddressDefault'        =>  'me@gmail.com',
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
		 $optionsD['hostedDomain'] = ""; 
								
           break;


       /**
        *  ends switch
        */
        }
        ?>
