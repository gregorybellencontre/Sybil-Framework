<?php
/*
 * Application boot file
 *
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */
 
namespace Sybil;

use Twig_Autoloader;

require_once('../vendor/autoload.php');
require_once('../app/Config.php'); // À retirer une fois la classe Config développée
require_once('../app/constants.php'); // Là dedans aussi

// Session initialization

session_name(App::slugify(Config::$app_namespace));
session_start();

// Location and time settings

define('LOCALE',!empty($_SESSION['locale']) ? filter_var($_SESSION['locale'],FILTER_SANITIZE_STRING) : Config::$locale);

date_default_timezone_set(Config::$timezone);
putenv('LC_ALL='.LOCALE);
setlocale(LC_ALL, LOCALE);

// Environment definition

define('ENVIRONMENT',Config::$environment);

Yaml::init();
Twig_Autoloader::register();

// Routes cache settings

if (ENVIRONMENT == 'development') {
	Routing::cache();
}

// Request process

$request = new Request($_SERVER['QUERY_STRING']);
$request->process();