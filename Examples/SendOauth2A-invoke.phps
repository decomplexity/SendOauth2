<?php
    /**
     * see the README for a description
     * and a more comprehensive example
     */

    namespace decomplexity\SendOauth2;

    require 'vendor/autoload.php';

    /**
     if you are not using Composer autoload, load class files with:
     require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
     require 'vendor/phpmailer/phpmailer/src/Exception.php';
     require 'vendor/thenetworg/oauth2-azure/src/Provider/Azure.php';
     require 'vendor/league/oauth2-google/src/Provider/Google.php';
     require 'vendor/decomplexity/sendoauth2/src/SendOauth2A.php';
     require 'vendor/decomplexity/sendoauth2/src/SendOauth2B.php';
     require 'vendor/decomplexity/sendoauth2/src/SendOauth2C.php';
     require 'vendor/google/apiclient/src/Utils/Client.php';
    */


    new SendOauth2A($mailStatus, [
    'mailTo' => ['john.doe@deer.com'],
    'mailSubject' => 'Deer dear!',
    'mailText' => 'Lovely photo you sent. Tnx',
    'mailAuthSet' => '1'
    ]);

    $_SESSION = array();

    if ($mailStatus == "OK") {
        echo ("Email sent OK");
    } else {
        echo ("Sending failed. Error message: " . $mailStatus);
    }
