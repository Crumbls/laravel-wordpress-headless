<?php

namespace Crumbls\LaravelWordpress;

use Crumbls\LaravelWordpress\Exceptions\CorcelNotInstalled;
use Crumbls\LaravelWordpress\Helpers\Api;
use Crumbls\LaravelWordpress\Helpers\Parser;
use Crumbls\LaravelWordpress\Helpers\WpJson;

class WordPress {
    /**
     * A wrapper for our wp-json parser.
     */
    public static function json() {
        return WpJson::getInstance();
    }
    /**
     * A wrapper for corcel/corcel.
     * Just part of standardization.
     */
    public static function corcel() {
        if (!class_exists(\Corcel\Corcel::class)) {
            throw new CorcelNotInstalled();
        }
        throw new \Exception('Sorry, this is not working yet.');
    }

    public static function parse(string $input) : string {
        return Parser::getInstance()->process($input);
    }


}