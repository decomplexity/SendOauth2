<?php
    /**
     * see the README for a description
     */
    namespace decomplexity\SendOauth2;

    session_start();

    require 'vendor/autoload.php';

    /**
     if you are not using Composer autoload, load class files with:
     require 'vendor/thenetworg/oauth2-azure/src/Provider/Azure.php';
     require 'vendor/league/oauth2-google/src/Provider/Google.php';
     require 'vendor/decomplexity/sendoauth2/src/SendOauth2C.php';
     require 'vendor/google/apiclient/src/Utils/Client.php';
     */


    new SendOauth2D('1');
