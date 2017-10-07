<?php
require 'api/vendor/autoload.php';


session_start();
$app = new \Slim\App;
$app->add(new \Slim\Csrf\Guard());

$slimGuard = new \Slim\Csrf\Guard;
$slimGuard->validateStorage();

// Generate new tokens
$csrfNameKey = $slimGuard->getTokenNameKey();
$csrfValueKey = $slimGuard->getTokenValueKey();
$keyPair = $slimGuard->generateToken();
print_r($keyPair);
