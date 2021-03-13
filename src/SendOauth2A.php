<?php
     /**
       * SendOauth2A Wrapper for Microsoft OIDC/OAUTH2 For PHPMailer
       * PHP Version 5.5 and greater
       * @version  1.0.0
       * @category   Class
       * @see        https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
       * @author     Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
       * @copyright  2021 Max Stewart and PHPMailer authors
       * @license    MIT
       * @deprecated MSFT does not (yet) deprecate SMTP Basic AUTH but rank it 'insecure'
       */


       namespace decomplexity\SendOauth2;
//     require 'vendor/autoload.php';
  /**  if autoload fails to load the five class-files needed, load them with:    
       require_once 'phpmailer/phpmailer/src/PHPMailer.php';
       require_once 'phpmailer/phpmailer/src/SMTP.php';
       require_once 'phpmailer/phpmailer/src/Exception.php';
       require_once 'decomplexity/sendoauth2/src/SendOauth2B.php';
       require_once 'decomplexity/sendoauth2/src/SendOauth2C.php';
   */	   


//      require_once 'phpmailer/phpmailer/src/OAuth.php';
//      require_once 'thenetworg/oauth2-azure/src/Provider/Azure.php';
//      require_once 'league/oauth2-google/src/Provider/Google.php';


//      use phpmailer\phpmailer\OAuth;
//      use phpmailer\phpmailer\SMTP;
//      use phpmailer\phpmailer\Exception;
//      use TheNetworg\OAuth2\Client\Provider\Azure;
//      use League\OAuth2\Client\Provider\Google;

        use phpmailer\phpmailer\PHPMailer;

    /**
     * SendOauth2A Wrapper for Microsoft and Google OIDC/OAUTH2 For PHPMailer
     *
     * Outer wrapper for Microsoft and Google OIDC/OAUTH2 for PHPMailer.
     *
     * @author Max Stewart (decomplexity) <SendOauth2@decomplexity.com>
     */

class SendOauth2A
{

   /**
    * obvious!
    * @constant string
    */
    protected const NO_REPLY = "DO_NOT_REPLY";

   /**
    * used to separate 'address, name' combinations
    * @constant string
    */
    protected const ADDRESS_DELIM = ",";

   /**
    *   string returned to global after a successful send
    *   @constant string
    */
    protected const SEND_OK = "OK";


    /**
     * Internal - a parameter array used for linking objects via the recipient's __construct
     *
     * @var string
     */
    protected array $options;

    /**
     * Internal - used with $options
     *
     * @var string
     */
    protected array $oauth2_settings_array;

   /**
     * Instantiation of SendOauth2B_ob
     *
     * @var string
     */
    protected $SendOauth2B_obj;


   /**
     * Instantiation of PMPMailer class
     *
     * @var object
     */
    protected $mail = "";

   /**
     * Instantiation of Azure provider class
     * Created in the SendOauth2B factory class
     * @var object
     */
    protected $provider = "";

    /**
     * Instantiation of Azure provider class
     *
     * @var object
     */
    protected $providerC = "";


    /**
     * Arrays that contains the To, CC and BCC recipient names and addresses
     * and the 'From' name and address.
     * At least one of the To, CC or BCC fields must have an address, as must the
     * From field. 'Name' is optional.
     * The ReplyTo field is optional and will default to the Fron name and address
     * The format of each entry is: address,name; address,name; address,name etc
     * 'From' must obviously contain only one name and address
     * @var string
     */
    /**
    * To, CC and BCC recipient names and addresses
    *
    * @var array
    */

    protected array $mailTo = [];
    protected array $mailCC = [];
    protected array $mailBCC = [];


    /**
    * Sender 'From' name and address.
    * $fromName and $fromAddress are extracted from = array $mailFrom
    *
    * @var array
    */
    protected array $mailFrom = [];

    protected $fromName = '';
    protected $fromAddress = '';

    /**
    * ReplyTo name and address. Defaults to the From name and address
    * Normally unnecessry to set them
    * @var array
    */

    /**
    * Obvious - set in SendOauth2D
    *
    * @var string
    */
    protected $fromNameDefault = '';


    protected array $mailReplyTo = [];

    /**
     * HTML or plain Text of email to be sent
     *
     * @var string
     */
    protected $mailText = "";

   /**
     * Optional plain text of email to be sent
     *
     * @var string
     */
    protected $mailTextPlain = "";


    /**
    * Ttext to go into the email's Subject line
    *
    * @var string
    */

    protected $mailSubject = "";

    /**
     * URI and other details of an attachment
     *
     * @var string
     */
    protected array $mailAttach = [];

    /**
     * URI and other details of an attachment to be sent embedded (inline)
     *
     * @var string
     */
    protected array $mailAttachInline = [];

    /**
     * URI and other details of a string attachment (e.g. bit string) attachment
     * See PHPMailer documentation for the format
     * @var string
     */

    protected array $mailAttachString = [];
    /**
     * URI and other details of a string attachment (e.g. bit string) embedded attachment
     * See PHPMailer documentation for the format
     * @var string
     */

    protected array $mailAttachStringInline = [];


    /**
     * Selects a set of oauth2 or other auth settings. These may be the same for e.g. different Contact pages
     * but different for e.g. a Shop page
     * Note that this is just a character ('1' is the default) and not the settings themselves
     * and may be set freely in the SendOauth2D 'switch' list
     * It is returned from method getOauth2Settings in SendOauth2B.
     *
     * @var string
     */
    protected $mailAuthSet = "";

     /**
     * This should only be set in global if caller wants to override the
     * SMTPAddressDefault set for that switch case in SendOauth2D
     * snd is carried over in the refresh token. When SendOauth2D is run,
     * for MSFT at least the currently logged-on member of the tenant is authorized
     * or a member is requested to log to authorize. This must normally have email address
     * that is the same as thst set in SMTPAddressDefault, otherwise SendOAuth2B authentication fails.
     * If used for Basic Auth, authorization will fail unless the SMTP password is the same as
     * that in the SendOauthD switch case. To improve security, global cannot override the latter
     * i.e. there is no $mailSMTPPassword operand for global to use
     *
     * @var string
     */
    protected $mailSMTPAddress = '';


    /**
     * Domain name of sender (mail account holder)
     *
     * @var string
     */
    protected $senderDomain = '';


    /**
     * Internal variable (used for exploding email addresses)
     *
     * @var string
     */
    protected $email = '';


    /**
     * __construct Method Doc Comment
     * Sends the email!
     *
     * @version  1.0
     * @see      https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
     * @category Method
     * @author   Max Stewart
     */
    public function __construct(&$mailStatus, $options)
    {

    /**
     * note the call by reference for &$mailStatus so that success or faulure can be pass3ed back to global scope
     */

    /**
    * Set the script time zone to UTC
    * Google's authentication servers are a bit picky about this
    */
        date_default_timezone_set('Etc/UTC');


     /**
     * Instantiate PHPMailer
     */
        $this->mail = new PHPMailer(true);


     /**
     *remember that mailTo, mailCC, mailBCC, mailReplyTo and the attachment variables are themselves arrays
     */

        $this->checkKeys($this->mailTo, $options, 'mailTo');
        $this->checkKeys($this->mailCC, $options, 'mailCC');
        $this->checkKeys($this->mailBCC, $options, 'mailBCC');

        $this->checkKeys($this->mailFrom, $options, 'mailFrom');
        $this->checkKeys($this->mailReplyTo, $options, 'mailReplyTo');

        $this->checkKeys($this->mailText, $options, 'mailText');
        $this->checkKeys($this->mailTextPlain, $options, 'mailTextPlain');

        $this->checkKeys($this->mailSubject, $options, 'mailSubject');

        $this->checkKeys($this->mailAttach, $options, 'mailAttach');
        $this->checkKeys($this->mailAttachInline, $options, 'mailAttachInline');
        $this->checkKeys($this->mailAttachString, $options, 'mailAttachString');
        $this->checkKeys($this->mailAttachStringInline, $options, 'mailAttachStringInline');

        $this->checkKeys($this->mailSMTPAddress, $options, 'mailSMTPAddress');
        $this->checkKeys($this->mailAuthSet, $options, 'mailAuthSet');

        $this->do_not_reply = self::NO_REPLY;

       /**
        * mailAuthSet goes one way to SendOauth2B
        * mailSMTPAddress goes there and back. If the mainline code doesn't set it,
        * SendOauth2B imposes a default (which was originally set in SendOauth2D)
        * Note that the mail object is needed in SendOauth2B
        */
        $optionsB = [
        'mailAuthSet' => $this->mailAuthSet,
        'mailSMTPAddress' => $this->mailSMTPAddress,
        'mail' => $this->mail
        ];

        /**
        * Get the oauth2 settings from SendOauth2B
        * If mailSMTPAddress is null, SendOauth2B will have set it
        * to the defaults that will have come via file from SendOauth2D
        */

        $this->SendOauth2B_obj = new SendOauth2B($optionsB);
        $this->oauth2_settings_array = $this->SendOauth2B_obj->getOauth2Settings();
      /**
       * the following come from SendOauth2B
       */

        $this->fromNameDefault = $this->oauth2_settings_array['fromNameDefault'];
        $this->mailSMTPAddress = $this->oauth2_settings_array['mailSMTPAddress'];


        $this->mail->isSMTP();                                      // Set mailer to use SMTP
        $this->mail->SMTPAuth = true;                               // Enable SMTP authentication
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Enable TLS encryption
        $this->mail->Port = 587;                                    // Set TLS port
        $this->mail->CharSet = PHPMailer::CHARSET_UTF8;             // unless you want iso-8859-1 or whatever

        /**
        * for diagnostics, uncomment:
        $this->mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL;
        */


        $this->assignValues([$this->mail,'addAddress'], $this->mailTo);
        $this->assignValues([$this->mail,'addCC'], $this->mailCC);
        $this->assignValues([$this->mail,'aAddBCC'], $this->mailBCC);
        $this->assignValues([$this->mail,'setFrom'], $this->mailFrom);
        $this->assignValues([$this->mail,'addReplyTo'], $this->mailReplyTo);
        $this->assignValues([$this->mail,'addAttachment'], $this->mailAttach);
        $this->assignValues([$this->mail,'addEmbeddedImage'], $this->mailAttachInline);
        $this->assignValues([$this->mail,'addStringAttachment'], $this->mailAttachString);
        $this->assignValues([$this->mail,'addStringEmbeddedImage'], $this->mailAttachStringInline);


     /**
     * Some Contact forms for example may not have a 'from; name
     * or a 'from' address or (a bit pointless...) even neither.
     * Try to do something sensible in most contexts where various combinations
     * of fromName, fromAddress and replyTo are set or not.
     * Finally, if fromAddress is blank, Reply-T0 addresss is set to do-not-reply@senderdomain
       ..... this prevents fromName prefixing a do-not-reply address in a reply
      */

     /** first, split 'mailFrom'
     * note that mailFrom is an array but parseArrayValue needs a string argument
     */
        $fromarray = $this->parseArrayValue(strval($this->mailFrom[0]));
        $this->fromAddress = $fromarray[0];
        $this->fromName  = $fromarray[1];

        /**
        * Note: mail->From is an email ADDRESS
        * Both MSFT and Google want outgoing smail to be from their own email addresses
        * to satisfy SPF and DKIM
        * Any Contact form 'From' must be sent in the text.
        *
        * Note also that PHPMailer property $Sender is not settable by callers
        * either in global or in SendOauth2D
        * It is the 'envelope' address and used as a bounce address
        */
        $this->mail->From = $this->mailSMTPAddress;


        if (!empty($this->fromAddress)) {
            if ($this->fromName != "") {
                $this->mail->FromName = $this->fromName;
                if ($this->mailReplyTo == []) {
                    $this->mail->addReplyTo($this->fromAddress, $this->fromName);
                } else {
                    $this->assignValues([$this->mail,'AddReplyTo'], $this->mailReplyTo);
                }
            } else {
                $this->mail->FromName = $this->fromNameDefault;
                if ($this->mailReplyTo == []) {
                    $this->mail->addReplyTo($this->fromAddress, "");
                } else {
                    $this->assignValues([$this->mail,'AddReplyTo'], $this->mailReplyTo);
                }
            }
        } else {  // fromAddress is empty
            if ($this->fromName != "") {
                $this->mail->FromName = $this->fromName;
            } else {
                $this->mail->FromName = $this->fromNameDefault;
            }
            // Prevent sender's name prepending DO NOT REPLY "address" in a reply
            $this->senderDomain = explode('@', $this->mailSMTPAddress)[1]; //extract domain name
            $this->mail->addReplyTo($this->do_not_reply . "@" . $this->senderDomain, "");
        }


     /**
     * Send HTML or Plain Text email (normally leave as HTML)
     */

        $this->mail->isHTML(true);
        $this->mail->Subject = $this->mailSubject;
        $this->mail->Body = $this->mailText;

       /**
       * create alternative plain text version needed by MIME for some older email clients
       */
        if (!empty($this->mailTextPlain)) {
            $this->mail->AltBody = $this->mailTextPlain;
        } else {

        /**
        * if the following fails, use strip_tags() instead!
        */
           $this->mail->AltBody = $this->mail->html2text($this->mailText);
        }

        /**
        NOW SEND!!
        */

        if (!$this->mail->send()) {
            $mailStatus = $this->mail->ErrorInfo;
        } else {
            $mailStatus = self::SEND_OK;
        }

    /**
    * Ends __construct method
    */
    }

     /**
     * @category Method
     * Ensure each each expected array key exists
     * If not, create it and assign a null value to the property
     */
    protected function checkKeys(&$property, $arr, $key)
    {
        if (array_key_exists($key, $arr)) {
            $property = $arr[$key];
        }
        return;
    }

     /**
     * @category Method
     * Some calling parameters are arrays. This method assigns an array argument
     * to an appropriate PHPMailer method. Note that methods of instantiated
     * objects are passed as an array with the object at [0] and the method name at [1]
     */
    protected function assignValues(array $method, array $from)
    {
        foreach ($from as $this->email) {
            $valout = $this->parseArrayValue($this->email);
            $method[0]->{$method[1]}($valout[0], $valout[1]);

    /**
     * PHP note: the passed method above needs to be enlosed in {} as above or the statement will be
     * interpreted as ($method[0]->$method)[1] when the function name will be treated as an array
     * and not a string!
     */
        }
    }

     /**
     * @category Method
     * This method splits the input argument and assigns each part to
     * two array elements returned
     */
    protected function parseArrayValue($valin)
    {
        $valout[0] = $valout[1] = "";
        $valout = explode(self::ADDRESS_DELIM, $valin);
        return $valout;
    }
/**
* Ends class SendOauth2A
*/
}
