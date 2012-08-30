# AngryCurl
- used for parsing information from remote resourse using user-predefined amount of simultaneous connections over proxies-list.

## Basic information

### Depencies:

* RollingCurl
* cURL
 
### Use cases:

* multi-threaded parsing over proxy
* overcoming simple parsing protection by using User-Agent header and proxy-lists
* proxy list checking
* validating proxies' response
 
### Main features

* loading proxy-list from file or array
* removing duplicates
* filtering alive proxies
* checking if proxy given response content is correct
* loading useragent-list from file or array
* changing proxy/useragent "on the fly"
* multi-thread connections
* callback functions
* web-console mode
* logging

## Documentation

### Basic usage

```php
require("RollingCurl.class.php");
require("AngryCurl.class.php");

function my_callback($response, $info, $request)
{
    // callback function here
}

// sending callback function name as param
$AC = new AngryCurl('my_callback');
// initializing console-style output
$AC->init_console();
// setting amount of threads
$AC->__set('window_size', 200);

// loading/testing/filtering proxy list (filename, type, test link, test content regexp)
$AC->load_proxy_list('./lib/proxy_list.txt','http','http://google.com','title>G[o]{2}gle');
// loading useragent list
$AC->load_useragent_list('./lib/useragent_list.txt');

// telling that we are going to use proxy and useragent lists to AC
$AC->__set('use_proxy_list',true);
$AC->__set('use_useragent_list',true);

while(/* */)
{
    $url = /**/;
    // adding URL to queue
    $AC->get($url);
}

// starting connections
$AC->execute();

//AngryCurl::print_debug(); // if console_mode is off

unset($AC);
```

### cURL options

You may also pass options for each url before adding to queue like here:
```php
$request = new RollingCurlRequest($url);
$request->options = array(CURLOPT_HEADER => true, CURLOPT_NOBODY => true);
$AC->add($request);
```

Because this class is kind of extension of RollingCurl class you may use any constructions RollingCurl has.
For other information read here:
http://code.google.com/p/rolling-curl/source/browse/trunk/

### Credits
You may join this class discussion here:
http://stupid.su/php-curl_multi/
Any questions, change requests and other things you may send to my email written in class comments.

Thank you for reading.
- naive