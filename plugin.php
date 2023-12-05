<?php
/**
 * Plugin Name: Quickstart
 * Plugin URI: https://github.com/virtuosoft-dev/hcpp-quickstart
 * Description: Quickstart plugin adds the Quickstart tab for an easy-to-use guide and quick website setup.
 * Version: 1.0.0
 * Author: Virtuosoft (Stephen J. Carnam)
 * 
 */

// Register the install and uninstall scripts
global $hcpp;
require_once( dirname(__FILE__) . '/quickstart.php' );

$hcpp->register_install_script( dirname(__FILE__) . '/install' );
$hcpp->register_uninstall_script( dirname(__FILE__) . '/uninstall' );