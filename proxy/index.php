<?php
require 'vendor/autoload.php';
require_once( __DIR__ . '/plugins/DevstiaPlugin.php' );
use Proxy\Config;
use Proxy\Http\Request;
use Proxy\Proxy;


// Create a new proxy instance
$proxy = new Proxy();

// Add basic plugins to the proxy
$proxy->addSubscriber( new \Proxy\Plugin\HeaderRewritePlugin() );
$proxy->addSubscriber( new \Proxy\Plugin\CookiePlugin() );
$proxy->addSubscriber( new \Proxy\Plugin\DevstiaPlugin() );

// Get the URL to be proxied
$url = $_GET['q'];

// Create a new request
$request = Request::createFromGlobals();

// Forward the request to the target URL
$response = $proxy->forward( $request, $url );

// Send the response back to the client
$response->send();
