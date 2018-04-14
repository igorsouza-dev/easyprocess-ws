<?php

//function my_autoloader($class) {
//    $docroot = $_SERVER["DOCUMENT_ROOT"];
//    include $docroot . '/files/php/class/'. $class . '.class.php';
//    include $docroot . '/files/php/tables/'. $class . '.class.php';
//    include $docroot . '/api-webservice/src/helpers/'. $class . '.class.php';
//}
//
//spl_autoload_register('my_autoloader');

//spl_autoload_register(function ($class_name) {
//    include $class_name . '.php';
//});


spl_autoload_register(function($class_name) {

    $docroot = $_SERVER["DOCUMENT_ROOT"];

    $dirs = array(
        $docroot.'/files/php/tables/',
        $docroot.'/files/php/class/',
        $docroot.'/api-webservice/src/helpers/'
    );
//    echo file_exists($docroot.'/files/php/tables/Ousuarios.php');exit;
    foreach( $dirs as $dir ) {

        if (file_exists($dir.'class.'.strtolower($class_name).'.php')) {
            require_once($dir.'class.'.strtolower($class_name).'.php');
            return;
        }
        if (file_exists($dir.$class_name.'.php')) {
            require_once($dir.$class_name.'.php');
            return;
        }
        if (file_exists($dir.strtolower($class_name.'.php'))) {
            require_once($dir.strtolower($class_name.'.php'));
            return;
        }
    }
});
if (false === stripos($_SERVER['SERVER_NAME'], '.com.br')) {
    define('APPLICATION_ENV', 'development');
} else {
    define('APPLICATION_ENV', 'production');
}


$config = Config::getConfig();

defined('CONFIG_FILE')
|| define('CONFIG_FILE', $config->config_file);
