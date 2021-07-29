<?php

namespace Crumbls\LaravelWordpress\Components;

use Illuminate\View\Component;

class Caption extends AbstractElement
{
    public $attributesExtended = [
    ];

    /**
     * Class builder
     */
    protected function generateClass() {
        // Class builder.
        // Add php class to class list.
        $class[] = strtolower(class_basename(get_class($this)));

        // Converet to array.
        $class = implode(' ', array_unique($class));
        $this->attributes->set('module_class', $class);
    }
}
