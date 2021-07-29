<?php

namespace Crumbls\LaravelWordpress\Helpers;

use \Illuminate\View\ComponentAttributeBag as BaseAttributeBag;

class ComponentAttributeBag extends BaseAttributeBag {

    /**
     * The raw array of attributes.
     *
     * @var array
     */
    protected $attributes = [];

    public function set($key, $value) : void {
        $this->attributes[$key] = $value;
    }
    public function remove($key) : bool {
        if (!array_key_exists($key, $this->attributes)) {
            return false;
        }
        unset($this->attributes[$key]);
        return true;
    }
}

