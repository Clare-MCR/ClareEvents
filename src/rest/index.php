<?php namespace Rest;
/**
 * Created by PhpStorm.
 * User: rjgun
 * Date: 10/04/2016
 * Time: 19:20
 */

// fix for dirname not matching url due to silent redirect
//$_SERVER['DOCUMENT_ROOT']=dirname(dirname(__FILE__));



require_once __DIR__.'/vendor/autoload.php';
require_once 'config.php';

if (!defined('JSON_PRETTY_PRINT'))
    define('JSON_PRETTY_PRINT', 128);

use Rest\Controllers\BookingsController;
use Rest\Controllers\DefaultController;
use Rest\Controllers\EventsController;
use Rest\Controllers\UsersController;

spl_autoload_extensions('.class.php');
spl_autoload_register();

$mode = 'debug'; // 'debug' or 'production'
$server = new \Jacwright\RestServer\RestServer($mode);
//$server->refreshCache(); // uncomment momentarily to clear the cache if classes change in production mode

$server->addClass(new DefaultController);
$server->addClass(new UsersController, '/users'); // adds this as a base to all the URLs in this class
$server->addClass(new EventsController, '/events'); // adds this as a base to all the URLs in this class
$server->addClass(new BookingsController, '/bookings'); // adds this as a base to all the URLs in this class

$server->handle();
