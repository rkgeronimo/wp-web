<?php
/**
 * Plugin Name: RK Geronimo
 * Plugin URI: http://rkgeronimo.hr
 * Description: RK Geronimo plugin
 * Version: 1.0.0
 * Author: Adrijan Adanić <adanic.ado@gmail.com>
 * Author URI: adrijan-adanic.from.hr
 * Text Domain: rkgeronimo
 */

spl_autoload_register(function ($className) {
    $namespace = 'RKGeronimo\\';
    $len       = strlen($namespace);
    if (strncmp($namespace, $className, $len) !== 0) {
        return;
    }
    $relativeClass = substr($className, $len);
    $relativeClass = str_replace("\\", DIRECTORY_SEPARATOR, $relativeClass);
    $file          = plugin_dir_path(__FILE__).'lib/'.$relativeClass.'.php';
    if (file_exists($file)) {
        require $file;
    }
});

if (!defined('WPINC')) {
    die;
}

require_once plugin_dir_path(__FILE__).'lib/Activator.php';
register_activation_hook(__FILE__, array('RKGeronimo\Activator', 'activate'));
register_deactivation_hook(__FILE__, array('RKGeronimo\Activator', 'deactivate'));

require_once plugin_dir_path(__FILE__).'includes/RKGeronimoDep.php';
require_once plugin_dir_path(__FILE__).'includes/RKGeronimoExcursion.php';

new RkGeronimoDep();
$plugin = new RKGeronimo\RKGeronimo();
$plugin->run();
