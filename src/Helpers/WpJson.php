<?php

namespace Crumbls\LaravelWordpress\Helpers;

use Crumbls\LaravelWordpress\Exceptions\InvalidResponse;
use Crumbls\LaravelWordpress\Exceptions\InvalidUrl;
use Crumbls\LaravelWordpress\WordPress;
use \Illuminate\View\ComponentAttributeBag as BaseAttributeBag;

class WpJson {
    protected static $instance;

    /**
     * Instance handler.
     * @return mixed
     */
    public static function getInstance() {
        if (!static::$instance) {
            $class = get_called_class();
            static::$instance = new $class;
        }
        return static::$instance;
    }

    /**
     * Get an item from wp-json.
     * @param string $wpJsonUri
     * @return \stdClass
     * @throws InvalidResponse
     * @throws InvalidUrl
     */
    public function get(string $wpJsonUri) : \stdClass
    {
        if (filter_var($wpJsonUri, FILTER_VALIDATE_URL) === FALSE) {
            throw new InvalidUrl();
        }
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $wpJsonUri);
        $res = (string)$res->getBody();
        $res = json_decode($res);

        if (!$res || !is_object($res) || !property_exists($res, 'content')) {
            throw new InvalidResponse();
        }
        $res->content = $res->content->rendered;

        return $res;
    }
}

