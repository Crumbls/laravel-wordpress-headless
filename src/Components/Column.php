<?php

namespace Crumbls\LaravelDivi\Components;

class Column extends AbstractElement
{
    public $attributesExtended = [
        'type' => '4_4',
    ];

    public function prerender() {
        parent::prerender();

        $class = array_unique(array_filter(explode(' ',(string)$this->attributes->get('module_class', ''))));

        if (!$class) {
            $class[] = 'w-full';
        }

        $type = $this->attributes->get('type');
        if ($type == '4_4') {
        } else if ($type == '1_4') {
            $class[] = 'md:w-1/4';
        } else if ($type == '1_2') {
            $class[] = 'md:w-1/2';
        } else if ($type == '1_3') {
            $class[] = 'md:w-1/3';
        } else if ($type == '2_3') {
            $class[] = 'md:w-2/3';
        } else if ($type == '3_4') {
            $class[] = 'md:w-3/4';
        } else {
            echo __METHOD__;
            echo $type;
            exit;
        }

        $class[] = 'column';

        $this->attributes->offsetSet('module_class', implode(' ',$class));
    }

}
