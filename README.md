# **SendOauth2** #
A wrapper for PHPMailer SMTP

SendOauth2 V4.0.0 supports both OAuth2 and Basic authentication for both Microsoft 365 Exchange email and Google Gmail. 
Yahoo (and hence AOL) supports Oauth2 access tokens obtained via authorization_code grant flow (they do not support client_credentials grants) for using their SMTP gateway. The wrapper does not support Yahoo / AOL and has no plan to do so.
Amazon SES SMTP has its own credentials management system and the wrapper does not support it.


Microsoft support is for Microsoft 365 accounts using Graph V1 with the V2 authentication and authorization endpoints.
Google support is for any non-legacy version of Gmail. 

When using the 'full' wrapper (option c. below), SendOauth2 provides automatic renewal of refresh tokens for Microsoft 365 email; this is normally unnecessary for Gmail as Google refresh tokens do not life-expire by default. 
  
Both client secrets and X.509 certificates are supported for Microsoft.
TheLeague's Gmail provider only supports client secrets (and only for authorization_code grant). 
Google's Gmail API supports client secrets for authorization_code grant and X.509 certificates for client_credentials grant (aka Google 'service accounts'), and both are supported by the wrapper. Google's use of .json credentials files is out of kilter with PHPMailer's established client authentication mechanism, so the wrapper creates these files automatically (although Google's own files can optionally be used instead) 

Client_credentials grant is a more appropriate solution for daemon applications such PHPMailer than authorization_code (i.e. user) grant.
For Microsoft, both authorization_code grant and client_credentials (i.e. application) grant flows for SMTP are supported.  
For the Google API, both authorization_code grant and client_credentials (service accounts) grant for SMTP are supported (see github repository/library googleapis/google-api-php-client)
TheLeague's Google Gmail provider only supports authorization_code grant, and this is supported by the wrapper.

Where the provider or client supports them, both $_SESSION 'state' exchange and PKCE code exchange are implemented automatically by the wrapper to forestall CSRF attacks during authorisation_code flow.   


There are three very different ways to use the wrapper:

**a.** in an otherwise 'standard' PHPMailer email application, replace the instantiations of the provider, e.g. oauth2-azure, and of PHPMailer's OAuth2 by an instantiation of SendOauth2B using PHPMailer's optional OAuthTokenProvider. The call to SendOauth2B will need all the usual OAuth2 arguments such as clientID. Parameters peculiar to the email service supplier such as appropriate scope arguments are provided automatically (by SendOauth2C). The PHPMailer's examples folder has a full sample application. 

**b.** like a., except that there is no need to supply the numerous (up to 16) OAuth2 arguments to SendOauth2B, but merely pass the PHPMailer object (instantiated in your code) and the name or number of the chosen 'authentication set'. The latter is described below, but is the name of one of potentially several groups of authentication parameters that are created to generate offline an initial refresh token. The new refresh token and the other parameters are then stored in a one-record file for later operational use by SendOauth2B. 

**c.** complete replacement of your PHPMailer application by a front-end - SendOauth2A  - that, among other things, refreshes Microsoft refresh tokens and writes them back  to the one-record file for use on the next call to PHPMailer.

The remainder of the present document is essentially for b. and c.; a. is covered in the sample application in PHPMailer repository. 
  
*Why wrap?* Non-trivial websites typically use email at many points (Contact pages, purchase confirmations, PayPal IPNs and so on), and incorporating PHPMailer invocation code and mail settings in each such page makes maintenance unwieldy, especially if OAuth2 is set up to use a different Client Secret for each point  - which is the more secure approach. 
Furthermore, Microsoft refresh tokens have a maximum life of 90 days before the issuer must re-authorize to get a new one unless in the meantime he or she had authorised to extend the life of an existing one (the '90 days' is the *maximum inactive time*). The alternative is to ask for a new refresh token each time an access token is issued.     

Using the complete SendOauth2 wrapper as in c. above, a page can contain as little as:  

```php
new SendOauth2A ($mailStatus,[
'mailTo' => ['john.doe@deer.com'],
'mailSubject' => 'Deer dear!',
'mailText'=>'Lovely photo you sent. Tnx',
'mailAuthSet' => '1'
]);
```

plus a few extra 'admin' lines of PHP. 

SendOauth2's aim is to simplify the implementation of Oauth2 authentication and authorisation that, particularly for Microsoft, is considerably more complex than Basic Authentication, although SendOauth2 also supports Basic Authentication in order to make transition to OAuth2 easier.      



 

## 1. INSTALLATION ##
Use Composer to get the latest stable versions of SendOauth2, PHPMailer, thenetworg's Microsoft provider, TheLeague's oauth2-google provider and so forth. Composer will do all this for you.

Composer will install SendOauth2, PHPMailer and the providers in your site's vendor/decomplexity/sendoauth2/src folder; merely specify in your json: 

```
{
    "require": {
        "decomplexity/SendOauth2": ">=4.0"
}
}
```

TWO CODE CHANGES ARE CURRENTLY (March 2024) NEEDED:

**NB(1): one code change is currently needed to the Microsoft provider thenetworg oauth2-azure Azure.php
This cannot be done as an override:
- for release 19 (v2.2.2), at lines 40 and 280, replace  *graph.windows.net*  by  *graph.microsoft.com*  ** 
Any later versions of Azure may well have this already amended, as Azure AD Graph is deprecated by Microsoft and replaced by Microsoft Graph. 



**NB(2): a one-line code addition is needed to TheLeague oauth2-client/src/Token/AccessToken.php if you wish to have refresh tokens updated automatically. The wrapper checks for this change; if it is not present, the wrapper will default to letting refresh tokens expire (if they do; Google's normally don't) in the normal way.
The existing code from around line 107 (exact line is version dependent) reads:

```
        if (!empty($options['refresh_token'])) {
            $this->refreshToken = $options['refresh_token'];
```

Then before the }, add the line:

```
  	        $_SESSION[__NAMESPACE__ . "\\updatedRefreshToken"] =  $this->refreshToken; 
```


## 2. PROVIDERS: ##
The Microsoft  provider is thenetworg * *oauth2-azure* * written by Jan Hajek and others.
The Google API is the 'official' Client API written by Google. 
The Gmail provider is the PHP League * *oauth2-google* *  written by Woody Gilk and others.


## 3. CLASSES and FILES ##
SendOauth2 consists of four PHP classes and one Trait held in PHP files of those names, stored by default in the vendor/decomplexity/sendoauth2/src folder.

There are three further files that are distributed in the Examples folder and should be moved to /vendor's parent folder for modification by the developer. One file (SendOauth2D-settings) is a template for authenticating up to five email services: Microsoft 365 OAuth2, Microsoft 365 Basic Authentication (userid and password), TheLeague's Google Gmail OAuth2 and Basic Authentication, and Google API OAuth2 (with 'domain-wide delegation' when using service accounts). This file is in the form of a PHP 'switch' block with five 'cases' and is required by class SendOauth2D. The other two files (SendOauth2A-invoke and SendOauth2D-invoke) are templates for instantiating SendOauth2A (which 'sends mail') and SendOauthD (which, for authorization_code grant, acquires OAuth2 refresh tokens). The sample code in SendOauth2A-invoke is intended to be edited and incorporated into the developer's website pages when using the complete wrapper; if instead the developer elects to use the wrapper via an otherwise 'standard' PHPMailer email application, this is also supporteed and an example is given in PHPMailer 'Examples'.  
      

<p align=center>
<img src=https://user-images.githubusercontent.com/65123375/111808913-5bd00f00-88cc-11eb-8d37-bc9c41b75c46.gif#diagram width=60%></img>
</p>

**FLOW SUMMARY (when using the complete wrapper)**

Microsoft and Google OAauth2 settings => paste => SendOauth2D-settings

Invoke SendOauth2D  <=> SendOauth2C (provider factory)

SendOauth2D => writes interchange file containing inter alia a refresh token

Invoke SendOauth2A => SendOauth2B to read interchange file

SendOauth2B authenticates, then => SendOauth2A for PHPMailer sending

**THE CLASSES**

- SendOauth2A -  instantiated from  global PHP (see examples later)
- SendOauth2B -  instantiated from SendOauthA, primarily to perform Oauth2 authentication
- SendOauth2C -  the Provider factory class. It is instantiated from SendOauth2B AND from SendOauth2D
- SendOauth2D -  is instantiated standalone from a few lines of global PHP such as in section 7 below.
- SendOauth2ETrait -  is used by  SendOauth2B and SendOauth2D to create and write the .json credentials file used by Google API. 

SendOauth2D 'requires' a file SendOauth2D-settings.php that contains security settings such as clientId, clientSecret, redirectURI, service provider (Microsoft, Google), authentication type (e.g. XOAUTH2) and so on. 
There is a group of these security settings for each PHPMailer invocation (typically one website page) that needs different security settings or a different provider: one website can use any combination of Microsoft and Google (and any others added further to SendOauth2C) and any number of different security groups. 
Each setting has unique identifier (numbered 1,2,3,4 in the template SendOauth2D-settings), but developers are free to use anything more meaningful. 
When SendOauth2D is instantiated by SendOauth2D-invoke (see Section 7 below), the latter specifies the group number, and SendOauth2D then produces an 'interchange' file with (for OAuth2) a refresh token plus other security settings such as client ID and client Secret. If a Basic authentication group is selected, the file output is similar but includes an SMTP password and excludes Oauth2 settings. There is one interchange file for each group of security settings.
			   
When SendOauth2A is instantiated from a web page for example, the relevant group number is passed to it. This in turn is passed to SendOauth2B which reads the appropriate interchange file of security settings. 

ClientId, clientSecret, redirectURI and refreshToken thus only need to be copied from Microsoft  AAD or Google console.cloud into SendOauth2D-settings. There is no need to replicate this into the code that invokes PHPMailer because is available 'on file'. This also means that if necessary, SendOauth2D-settings can be moved afterwards to somewhere more secure, and there are dummy encrypt and decrypt point indicators in SendOauth2D and SendOauth2B respectively if developers wish to add further security to the interchange files.



## 4. SERVICE SETTINGS: ##
For Microsoft AAD client setup , it appears unnecessary to add 'offline_access' and 'SMTP.Send' Graph permissions as long as SendOauth2D authenticates with a logon using the user principal name (email address) because Graph will add them automatically. This is the result of Microsoft implementing Exchange (outlook.office.com) as the resource API for OAuth2 authenticated SMTP Send but not Graph (although Exchange does not itself now have a SMTP.Send permission to use!). If SendOauth2D authenticates with a logon from another email account in the same tenant, it may be necessary to add these as Graph permissions and 'grant Admin consent' for the tenant. MSFT scope permissions are quirky; they are explained in great detail in PHPMailer's WiKi document 'Microsoft OAuth2 SMTP issues'. To use Microsoft client_credentials grant, the application must be registered (using Exchange Online PowerShell) as a service principal since the application itself and not a user - whether user principal or a delegated user -  will be invoking the Exchange resource; see https://learn.microsoft.com/en-gb/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth#authenticate-connection-requests.      

Google is less quirky, but it is worth ensuring that when adding permissions via the OAuth consent screen that the Gmail API has been enabled (or you won’t be able to find 'mail.google.com' in order to select it!). When using client credentials (service accounts) with the GoogleAPI, the permission to access Gmail is set somewhat obscurely in: https://console.cloud.google.com/apis/credentials and https://console.cloud.google.com/iam-admin/serviceaccounts (advanced settings / Domain-wide delegation) => https://admin.google.com/ => Security => Access and data control => API controls => Manage Domain-wide delegation => Add new [API client]. 
Registering impersonation of a service account is similar to registering Amazon Web Services's Security Token Service API 'Roles'. 


## 5. SendOauth2D SETTINGS: ##
To define security settings to SendOauth2D-settings:
- Select an appropriate sample security groups (Microsoft XOAUTH2 is the first and the default) and insert your own settings copied from Microsoft AAD or Google console.cloud.
There are two additional settings:
 - 'SMTPAddressDefault' - which you set to the user principal name (e.g. the AAD admin email address)
 - 'fromNameDefault' - the default you want to use for the email * *From* * name
Both of these can be overridden when SendOauth2A is invoked.



## 6. SendOauth2D INSTANTIATION: ##
The code you use to instantiate SendOauth2D **MUST** have **EXACTLY** the same URI as the redirect URI you specify to Microsoft AAD or Google console.cloud. SendOauth2D-invoke.php is one such file, but remember that SendOauth2D-invoke must be sited in the parent of the /vendor folder.   


So the redirect URI looks something like:
https://mydomain.com/php/SendOauth2D-invoke.php

To select security group 1, it merely needs to contain:
```php
namespace decomplexity\SendOauth2; 
require 'vendor/autoload.php';

new SendOauth2D ('1');

```


## 7. SendOauth2A INSTANTIATION: ##
```php
SendOauth2A has two arguments:
$mailStatus
$options - an array of the options described below

So instantiation looks something like:

new SendOauth2A ($mailStatus,$options)

It is preceded by:

namespace decomplexity\SendOauth2;
require 'vendor/autoload.php';


and followed by clearing the session variables and then your test for success or failure ($mailStatus returns "OK" for a successful send):
$_SESSION = array(); 
if ($mailStatus == "OK") {
echo ("Email sent OK");
}
else
{
echo ("Sending failed. Error message: " . $mailStatus); 
}

```

**SendOauth2A options:**

- 'mailTo'
- 'mailCC'
- 'mailBCC'
- 'mailFrom'
- 'mailReplyTo'

These are all arrays with obvious meanings. 
Each array argument can contain one or two values. The first is an email address. The optional second is the name prefix to the email address.
If there are two values, they are comma separated. Each array can contain an arbitrarily large number of arguments (email recipients). If there are more than one, they are comma separated. In the unlikely event of an email address containing a comma, make sure it is escaped with quotes.

- 'mailAttach'
- 'mailAttachInline'
- 'mailAttachString'
- 'mailAttachStringInline'
are also arrays. They allow attachments that are available to PHP as files or bit strings. Attaching a file by specifying a URI is NOT supported - 
it must be uploaded or copied first.

' mailAttach' is straightforward email attach and it takes single-value arguments that are file names.

' mailAttachInline' is similar but embeds the file (usually an image) in the 'mailText' email body (see below). 
Each 'mailAttachInline' array argument has two comma-separated values: the file name followed by an arbitrary text string (the 'cid'). The email body must then also contain an HTML indicator of where to embed the file: this is of the form: 
<img src="cid:arbitrary-text">

' mailAttachString' and 'mailAttachStringInline' are similar to 'mailAttach' and 'mailAttachInline' respectively, but instead of attaching or embedding a file, they do so with a bit-string that is the only value for a mailAttachString argument and the first of two comma-separated values for a mailAttachStringInline argument.   

- 'mailText' string is the HTML text of the of the email. 
- 'mailTextPlain' is an optional plain text version.
If 'mailTextPlain' is not specified, a plain text version is created by stripping HTML characters from 'mailText'. 
  
- 'mailSMTPAddress' string overrides the default SMTP 'username' (an address) set in SendOauth2D. This should only be set in global if you wish to override 'SMTPAddressDefault'. When SendOauth2D is run, for Microsoft at least the currently logged-on member of the tenant is authorized or a member is requested to log-on to authorize. This must normally have an email address that is the same as that set in SMTPAddressDefault, otherwise SendOAuth2B
authentication fails. If used for Basic authentication, this will fail unless the SMTP password is the same as that in the SendOauthD switch case. To improve security, global cannot override the latter, i.e. there is no $mailSMTPPassword operand for global to use. The address used is carried over in the refresh token via the intermediate file.

- 'mailAuthSet' string is the SendOauth2D group setting to use (1,2,3 or 4 in the SendOauth2D skeleton).

Operands may be specified in any order.


**MANDATORY OPERANDS:**
- At least one of 'mailTo', 'mailCC' or 'mailBCC' must have at least one argument (i.e. there must be at least one addressee for the email!)
- string 'mailAuthSet' (you * *could* * let it default to the switch case default set in SendOauth2D but this is not recommended) 
- argument string $mailStatus
 

**EXAMPLES:**
For clarity and brevity, the examples to follow display the argument values inline, whereas in practice an array such as $options will be used, and its variables in turn will also be PHP variables. 
In other words:
```
$options = [$destination,$CCto];
new SendOauth2A ($mailStatus,$options);
```

Simple example:
```php
namespace decomplexity\SendOauth2;
session_start();
require 'vendor/autoload.php';

new SendOauth2A ($mailStatus,[

'mailTo' => ['john.doe@deer.com'],
'mailSubject' => 'Deer dear!',
'mailText'=>'Lovely photo you sent. Tnx',
'mailAuthSet' => '1'
]);
$_SESSION = array();

if ($mailStatus == "OK") {
echo ("Email sent OK");
}
else
{
echo ("Sending failed. Error message: " . $mailStatus); 
}
```
Note that when specifying a PHP variable as an array argument, it will only be resolved if it is enclosed in double quotes. For example, when an argument is a string:
'mailFrom' => [“$fromsomeaddress, $fromsomename”],



More comprehensive example:
```php
namespace decomplexity\SendOauth2;
session_start();
require 'vendor/autoload.php';

new SendOauth2A ($mailStatus,[
'mailTo' => ['john.doe@deer.com, John Doe', 'jaime.matador@gmail.com,Jaime Cordobes'],
'mailCC' => ['jane.roe@gmail.com, Jane Doe', 'june.buck@outlook.com'],
'mailBCC' => ['jack.foe@battleme.co.uk, Jack the Lad'], 
'mailFrom' => ['hugo.cholmondeley@veryposh.com, Hugo Plantagenet'],
'mailReplyTo' =>['lucinda@veryposh.com, Lucinda Leveson-Gower'],
'mailSubject' => 'Windsor Castle',
'mailText'=>'Re my knighthood - see <img src="cid:cholmondeley-pic"> for our coat of arms. A letter also attached',
'mailAttach' =>['letter.jpg'],
'mailAttachInline' =>['coatofarms.jpg, cholmondeley-pic'],
'mailAuthSet' => '1'
]);

$_SESSION = array();

if ($mailStatus == "OK") {
echo ("Email sent OK");
}
else
{
echo ("Sending failed. Error message: ". $mailStatus); 
}
```
