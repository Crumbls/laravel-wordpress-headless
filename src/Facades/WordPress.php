<?php

namespace Crumbls\LaravelWordpress\Facades;

use Illuminate\Support\Facades\Facade;

class WordPress extends Facade {

    /**
    * Get the registered name of the component.
    *
    * @return string
    */
    protected static function getFacadeAccessor() {
        return 'wordpress';
    }

}