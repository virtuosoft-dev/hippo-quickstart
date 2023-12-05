<?php 
/**
 * Pluginable router, serves up requested content and scripts
 * from the pages directory.
 */
$authorized_pages = [
   'vue.global.js'
];

// Check for authroized page requests
if ( isset( $_GET['pages'] ) && !in_array( $_GET['pages'], $authorized_pages ) ) {
   header('HTTP/1.0 403 Forbidden');
   exit;
}

// Serve up requested JavaScript pages if extention is .js
if ( isset( $_GET['pages'] ) && substr( $_GET['pages'], -3 ) == '.js' ) {
   header('Content-Type: application/javascript');
   readfile( __DIR__ . '/pages/' . $_GET['pages'] );
   exit;
}

// Serve up requested CSS pages if extention is .css
if ( isset( $_GET['pages'] ) && substr( $_GET['pages'], -4 ) == '.css' ) {
   header('Content-Type: text/css');
   readfile( __DIR__ . '/pages/' . $_GET['pages'] );
   exit;
}
