CurlHelper
==========

Small useful helper for PHP Curl.  
Require PHP >= 5.4


## Installation
Install via `composer`:
```bash
composer require mervick/curl-helper
```
or download `CurlHelper.php` and include it in yours php files.


## Usage
```php
$url = 'http://example.com/path/script?get1=val1';

// Simple GET request
$response = (new \CurlHelper($url))->exec();
var_dump($response);
// Will be output array with few keys:
//
// $response['status']      - http code, example "200' on success
// $response['type']        - `Content-Type` from headers, example "application/json; charset=utf-8"
// $response['headers']     - assoc array with all parsed headers
// $response['cookies']     - assoc array with cookies from `Set-Cookie` headers
// $response['headers_raw'] - headers raw
// $response['content']     - content
// $response['data']        - assoc array of json decoded content or false

// Add and/or modify some GET params 
$response = (new \CurlHelper($url))
    ->setGetData(['get2' => 'val2'])
    ->exec();

// Follow location
$response = (new \CurlHelper($url))
    ->follow(true)
    ->exec();

// Writes verbose information to STDERR
$response = (new \CurlHelper($url))
    ->debug(true)
    ->exec();

// Writes verbose information to the file
$response = (new \CurlHelper($url))
    ->debug('/path/to/file')
    ->exec();

// POST request with headers
$response = (new \CurlHelper($url))
    // Content-Type: application/x-www-form-urlencoded
    // this one is default for POST, so you can skip it
    ->setHeaders(['Content-Type' => CurlHelper::MIME_X_WWW_FORM])
    ->setHeaders([
        'Some-Header1' => 'SomeValue1',
        'Some-Header2' => 'SomeValue2',
    ])
    ->setPostData([
        'somePostField' => 'somePostVal',
        'somePostArray' => [ // POST array
            'item1',
            'item2',
        ],
    ])
    ->exec();

// JSON POST request
$response = (new \CurlHelper($url))
    // Content-Type: application/json
    ->setHeaders(['Content-Type' => CurlHelper::MIME_JSON])
    ->setPostData(['somePostField' => 'somePostVal'])
    ->exec();

// Set cookies
$response = (new \CurlHelper($url))
    ->setCookies(['someField' => 'someVal'])
    ->exec();

// Send file
$response = (new \CurlHelper($url))
    // Content-Type: multipart/form-data
    // this is default for sending files, so you can skip it
    ->setHeaders(['Content-Type' => CurlHelper::MIME_FORM_DATA])
    ->putFile('fieldName', '/path/to/file')
    ->exec();

// Send multiple files 
$response = (new \CurlHelper($url))
    ->putFile('fieldName', '/path/to/file1')
    ->putFile('fieldNameArr[]', '/path/to/file2')
    ->putFile('fieldNameArr[]', '/path/to/file3')
    ->exec();

// Send raw file
$file_contents = file_get_contents('/file/to/path');
$response = (new\CurlHelper($url))
    ->putFileRaw('fieldName', $file_contents, 'some.name', 'mime-type')
    ->exec();
    
// Send POST contents raw 
$response = (new\CurlHelper($url))
    ->setPostRaw($postRawContents)
    ->exec();
```

## License
MIT