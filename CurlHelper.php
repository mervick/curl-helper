<?php
/**
 * CurlHelper.php
 * @author Andrey Izman <izmanw@gmail.com>
 * @link https://github.com/mervick/curl-helper
 * @license MIT
 */

/**
 * Class CurlHelper
 */
class CurlHelper
{
    /**
     * @var string
     */
    public $user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.76 Safari/537.36';

    /**
     * @var int
     */
    public $timeout = 30;

    /**
     * @var resource
     */
    protected $ch;

    /**
     * @var null|string
     */
    protected $url;

    /**
     * @var array
     */
    protected $get_data = [];

    /**
     * @var array
     */
    protected $post_data = [];

    /**
     * @var null|string
     */
    protected $post_raw;

    /**
     * @var array
     */
    protected $cookies = [];

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var array
     */
    protected $files = [];


    const MIME_X_WWW_FORM   = 'application/x-www-form-urlencoded';
    const MIME_FORM_DATA    = 'multipart/form-data';
    const MIME_JSON         = 'application/json';


    /**
     * @param string $url [optional]
     */
    public function __construct($url=null)
    {
        $this->ch = curl_init();
        $this->url = $url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param bool $follow [optional]
     * @return $this
     */
    public function follow($follow=true)
    {
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $follow);
        return $this;
    }

    /**
     * @param string $ua
     * @return $this
     */
    public function setUserAgent($ua)
    {
        $this->user_agent = $ua;
        return $this;
    }

    /**
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @param bool $debug [optional]
     * @return $this
     */
    public function debug($debug=true)
    {
        curl_setopt($this->ch, CURLOPT_VERBOSE, $debug && true);
        if (is_string($debug)) {
            curl_setopt($this->ch, CURLOPT_STDERR, $debug);
        }
        return $this;
    }

    /**
     * @param string $raw
     * @return $this
     */
    public function setPostRaw($raw)
    {
        $this->post_raw = $raw;
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setPostData($data)
    {
        $this->post_data = array_merge($this->post_data, $data);
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setGetData($data)
    {
        $this->get_data = array_merge($this->get_data, $data);
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setHeaders($data)
    {
        $this->headers = array_merge($this->headers, $data);
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setCookies($data)
    {
        $this->cookies = array_merge($this->cookies, $data);
        return $this;
    }

    /**
     * @param string $fieldname
     * @param string $filename
     * @param string|null $basename
     * @param string|null $mime_type
     * @return $this
     */
    public function putFile($fieldname, $filename, $basename=null, $mime_type=null)
    {
        $this->files[] = [
            'type' => 'file',
            'fieldname' => $fieldname,
            'file' => $filename,
            'basename' => $basename,
            'mime_type' => $mime_type,
        ];
        return $this;
    }

    /**
     * @param string $fieldname
     * @param string $file_contents
     * @param string $basename
     * @param string $mime_type
     * @return $this
     */
    public function putFileRaw($fieldname, $file_contents, $basename, $mime_type)
    {
        $this->files[] = [
            'type' => 'raw',
            'fieldname' => $fieldname,
            'file' => $file_contents,
            'basename' => $basename,
            'mime_type' => $mime_type,
        ];
        return $this;
    }

    /**
     * @return string
     */
    protected function generateUrl()
    {
        $url_data = parse_url($this->url);
        parse_str($url_data['query'], $get_data);
        $url_data['query'] = http_build_query(array_merge($get_data, $this->get_data));
        return http_build_url($url_data);
    }

    /**
     * Execute
     * @return array
     */
    public function exec()
    {
        if (isset($this->post_raw)) {
            curl_setopt($this->ch, CURLOPT_POST, 1);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->post_raw);
            $this->headers['Content-Length'] = strlen($this->post_raw);
            if (empty($this->headers['Content-Type'])) {
                $this->headers['Content-Type'] = 'text/plain';
            }
        }
        elseif (!empty($this->post_data) || !empty($this->files)) {
            curl_setopt($this->ch, CURLOPT_POST, 1);

            if (!empty($this->files)) {
                $this->headers['Content-Type'] = self::MIME_FORM_DATA;
            }
            elseif (empty($this->headers['Content-Type'])) {
                $this->headers['Content-Type'] = self::MIME_X_WWW_FORM;
            }

            if ($this->headers['Content-Type'] === self::MIME_JSON) {
                $data = json_encode($this->post_data);
            }
            elseif ($this->headers['Content-Type'] === self::MIME_FORM_DATA) {
                $data = $this->generateBoundary();
            }
            else {
                $data = http_build_query($this->post_data);
            }

            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
            $this->headers['Content-Length'] = strlen($data);
        }

        if (!empty($this->headers)) {
            $data = [];
            foreach ($this->headers as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $val) {
                        $data[] = "$k: $val";
                    }
                } else {
                    $data[] = "$k: $v";
                }
            }
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $data);
        }

        if (!empty($this->cookies)) {
            $data = [];
            foreach ($this->cookies as $k => $v) {
                $data[] = "$k=$v";
            }
            curl_setopt($this->ch, CURLOPT_COOKIE, implode('; ', $data));
        }

        curl_setopt($this->ch, CURLOPT_URL, $this->generateUrl());
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_HEADER, 1);

        return $this->generateResponse();
    }

    /**
     * @return string
     */
    protected function generateBoundary()
    {
        $eol = PHP_EOL;
        $boundary = '----CurlHelperBoundary' . md5(microtime());

        $this->headers['Content-Type'] = self::MIME_FORM_DATA . "; boundary=$boundary";
        $this->headers['X-Requested-With'] = 'XMLHttpRequest';

        $data = [];
        $each = function ($field, $value) use (&$data, &$each, $boundary, $eol) {
            if (is_array($value)) {
                if (empty($value)) {
                    $each("{$field}[]", '');
                } else {
                    foreach ($value as $key => $item) {
                        if (is_int($key)) $key = '';
                        $each("{$field}[{$key}]", $item);
                    }
                }
            } else {
                $data[] = "--$boundary$eol";
                $data[] = "Content-Disposition: form-data; name=\"$field\"$eol$eol";
                $data[] = "$value$eol";
            }
        };
        foreach ($this->post_data as $field => $value) {
            $each($field, $value);
        }

        foreach ($this->files as $file) {
            if ($file['type'] === 'file') {
                if (empty($file['basename'])) {
                    $file['basename'] = basename($file['file']);
                }
                if (empty($file['mime_type'])) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $file['mime_type'] = finfo_file($finfo, $file['file']);
                    finfo_close($finfo);
                }
            }
            if (empty($file['mime_type'])) {
                $file['mime_type'] = 'application/octet-stream';
            }
            $data[] = "--$boundary$eol";
            $data[] = "Content-Disposition: form-data; name=\"{$file['fieldname']}\"; filename=\"{$file['basename']}\"$eol";
            $data[] = "Content-Type: {$file['mime_type']}$eol$eol";
            $data[] = ($file['type'] === 'file' ? file_get_contents($file['file']) : $file['file']) . $eol;
        }

        $data[] = "--$boundary--$eol$eol";

        return implode('', $data);
    }

    /**
     * @return array
     */
    protected function generateResponse()
    {
        $response = curl_exec($this->ch);
        $header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        $header = substr($response, 0, $header_size);
        $content = substr($response, $header_size);

        curl_close($this->ch);

        $headers = [];
        foreach (explode("\n", $header) as $line) {
            $line = explode(':', $line, 2);
            if (isset($line[1])) {
                list($key, $value) = $line;
                $value = ($value = trim($value)) && !empty($value) ? $value : null;
                if (isset($headers[$key]) && $headers[$key] != $value) {
                    if (!is_array($headers[$key])) {
                        $headers[$key] = [$headers[$key]];
                    }
                    $headers[$key][] = $value;
                } else {
                    $headers[$key] = $value;
                }
            }
        }

        $cookies = [];
        if (isset($headers['Set-Cookie'])) {
            foreach (is_array($headers['Set-Cookie']) ? $headers['Set-Cookie'] : [$headers['Set-Cookie']] as $cookie)
            {
                $cookie = explode('=', explode(';', $cookie, 2)[0], 2);
                if (isset($cookie[1])) {
                    $cookies[$cookie[0]] = $cookie[1];
                }
            }
        }

        $type = isset($headers['Content-Type']) ? is_array($headers['Content-Type']) ?
            $headers['Content-Type'][0] : $headers['Content-Type'] : 'text/plain';

        $json_data = !empty($content) && in_array($content{0}, ['{', '[']) ? json_decode($content, true) : false;

        return [
            'status' => $status,
            'type' => $type,
            'headers' => $headers,
            'cookies' => $cookies,
            'headers_raw' => $header,
            'content' => $content,
            'data' => $json_data,
        ];
    }

}