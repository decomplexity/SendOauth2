# SendOauth2
A wrapper for PHPMailer SMTP

SendOauth2 supports both OAuth2 and Basic authentication for both Microsoft and Google.
Microsoft support is primarily for Microsoft 365 accounts using Graph V1 with the V2 authentication and authorization endpoints.
Google support is for any Gmail.
  
*Why wrap?* Non-trivial websites typically use email at many points (Contact pages, purchase confirmations, PayPal IPNs and so on), and incorporating PHPMailer invocation code and mail settings in each such page is a pain, especially if OAuth2 is set up to use a different Client Secret (and possibly even a different Client ID) for each point  - which is the recommended secure approach. 

**1. CLASSES**
SendOauth2 consists of four PHP classes held in PHP files of those names:
- SendOauth2A -  instantiated from  global PHP (see examples later)
- SendOauth2B -  instantiated from SendOauthA, primarily to perform Oauth2 authentication
- SendOauth2C -  the Provider factory class. It is instantiated from SendOauth2B AND from SendOauth2D
- SendOauth2D -  is instantiated standalone from a few lines of global PHP such as in section 7 below.
It contains security settings such as clientId, clientSecret, redirectURI, service provider (Microsoft, Google) and authentication type (e.g. XOAUTH2).
There is a group of these security settings for each PHPMailer invocation (typically one website page) that needs different security settings or a different provider: one website can use any combination of Microsoft and Google (and any others added further to SendOauth2C) and any number of different security groups. 
Each setting has unique identifier (numbered 1,2,3,4 in the skeleton SendOauth2D, but developers are free to use anything more meaningful. 
When SendOauth2D is instantiated by SendOauth2D-invoke (see Section 7 below), the latter specifies the group number, and SendOauth2D then produces an 'interchange' file with (for OAuth2) a refresh token plus other security settings such as client ID and client Secret. If a Basic authentication group is selected, the file output is similar but includes an SMTP password and excludes Oauth2 settings. There is one interchange file for each group of security settings.
			   
When SendOauth2A is instantiated from a web page for example, the relevant group number is passed to it. This in turn is passed to SendOauth2B which reads the appropriate interchange file of security settings. 

ClientId, clientSecret, redirectURI and refreshToken thus only need to be copied from Microsoft  AAD or Google console.cloud into SendOauth2D. There is no need to replicate this into the code that invokes PHPMailer because is available 'on file'. This also means that if necessary, SendOauth2D can be moved afterwards to somewhere more secure, and there are dummy encrypt and decrypt point indicators in SendOauth2D and SendOauth2B respectively if developers wish to add further security to the interchange files.



**2. SERVICE SETTINGS:**
For Microsoft AAD client setup , it appears unnecessary to add 'offline_access' and 'SMTP.Send' Graph permissions as long as SendOauth2D 
authenticates with a logon using the user principal name (email address). This may be a result of Microsoft implementing Exchange (outlook.office.com) as the resource API for OAuth2 authenticated SMTP Send but not Graph (although Exchange does not itself now have a SMTP.Send permission to use!)
If SendOauth2D authenticates with a logon from another email account in the same tenant, it IS necessary to add these as Graph permissions and 'grant Admin consent' for the tenant.

Google is simpler, but it is worth ensuring that when adding permissions via the OAuth consent screen that the Gmail API has been enabled (or you won’t be able to find 'mail.google.com' in order to select it).



**3. PROVIDERS:**
The Gmail provider is the PHP League * *oauth2-google* *  written by Woody Gilk and others.
The Microsoft  provider is thenetworg * *oauth2-azure* * written primarily by Jan Hajek.

 

**4. INSTALLATION**
Use Composer to get the latest versions of PHPMailer, thenetworg's Microsoft provider and PHP League oauth2-google provider

NB: one code change is currently needed to the Microsoft provider thenetworg oauth2-azure Azure.php
This cannot be done as an override:
- for release 2.0.1, at line 214, replace * *graph.windows.net* * by * *graph.microsoft.com* *  

Composer will install PHPMailer and the providers in your php’s /vendor  folder.

The four SendOauth2 class files and the SendOauth2S-invoke file of global instantiation code should be placed in the parent folder to /vendor.
They are available here on Git for direct installation , and will shortly also be installable from Packagist via Composer 
   


**5. SendOauth2D SETTINGS:**
To define security settings to SendOauth2D:
- Simply scroll down to the switch section that contains some sample security groups.
- Select an appropriate one (Microsoft XOAUTH2 is the first and the default) and insert your own settings copied from Microsoft AAD or Google console.cloud.
There are two additional settings:
 - 'SMTPAddressDefault' - which you set to the user principal name (e.g. the AAD admin email address)
 - 'fromNameDefault' - the default you want to use for the email * *From* * name
Both of these can be overridden when SendOauth2A is invoked.


**6. SendOauth2A INSTANTIATION:**
```
SendOauth2A has two arguments:
$mailStatus
$options - an array of the options described below

So instantiation looks something like:

new SendOauth2A ($mailStatus,$options)

It is preceded by:
require_once('SendOauth2A.php'):

and followed by your test for success or failure ($mailStatus returns "OK" for a successful send):

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
```
require_once('SendOauth2A.php');

new SendOauth2A ($mailStatus,[
'mailTo' => ['john.doe@deer.com'],
'mailSubject' => 'Deer dear!',
'mailText'=>'Lovely photo you sent. Tnx',
'mailAuthSet' => ‘1’
]);

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
```
require_once('SendOauth2A.php');

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
'mailAuthSet' => ‘1’
]);

if ($mailStatus == "OK") {
echo ("Email sent OK");
}
else
{
echo ("Sending failed. Error message: ". $mailStatus); 
}
```


**7. SendOauth2D INSTANTIATION:**
The global code you use to instantiate SendOauth2D MUST have the same URI as the redirect URI you specify to Microsoft AAD or Google console.cloud. SendOauth2D-invoke.php is one such file.


So the redirect URI looks something like:
https://mydomain.com/php/SendOauth2D-invoke.php

To select security group 1, it merely needs to contain:
```
session_start();  
require_once 'SendOauth2D.php';
new SendOauth2D ('1');
$_SESSION = array();  
```
