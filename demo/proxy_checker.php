<?php

# Setting time and memory limits
ini_set('max_execution_time',0);
ini_set('memory_limit', '128M');

define('AC_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..');

# Including classes
require_once( AC_DIR  . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'RollingCurl.class.php');
require_once( AC_DIR  . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'AngryCurl.class.php');

# Extending with proxy export method
class ProxyChecker extends AngryCurl {
    public function export_proxy_list()
    {
        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="proxy_list.txt"');
        echo implode("\r\n", $this->array_proxy);
    }
}

$AC = new ProxyChecker();
$AC->__set('window_size', 200);
$AC->load_proxy_list(
    AC_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'proxy_list.txt',
    200,
    'http',
    'http://google.com',
    'title>G[o]{2}gle'
);
$AC->export_proxy_list();