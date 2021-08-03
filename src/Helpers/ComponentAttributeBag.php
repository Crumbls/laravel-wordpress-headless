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

    /**
     * Create a new component attribute bag instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes($attributes);
    }
    public function set($key, $value) : void {
//        print_r(get_class_methods($this));
        $value = urldecode($value);
        parent::offsetSet($key, $value);
        return;
        echo $key;
        exit;
        $this->attributes[$key] = $value;
    }
    public function remove($key) : bool {
        if (!array_key_exists($key, $this->attributes)) {
            return false;
        }
        unset($this->attributes[$key]);
        return true;
    }
    /**
     * Merge additional attributes / values into the attribute bag.
     *
     * @param  array  $attributeDefaults
     * @param  bool  $escape
     * @return \Illuminate\View\ComponentAttributeBag
     */
    public function merge(array $attributeDefaults = [], $escape = true)
    {
        echo __CLASS__;exit;
        $attributeDefaults = array_map(function ($value) use ($escape) {
            return $this->shouldEscapeAttributeValue($escape, $value)
                ? e($value)
                : $value;
        }, $attributeDefaults);

        [$appendableAttributes, $nonAppendableAttributes] = collect($this->attributes)
            ->partition(function ($value, $key) use ($attributeDefaults) {
                return $key === 'class' ||
                    (isset($attributeDefaults[$key]) &&
                        $attributeDefaults[$key] instanceof AppendableAttributeValue);
            });

        $attributes = $appendableAttributes->mapWithKeys(function ($value, $key) use ($attributeDefaults, $escape) {
            $defaultsValue = isset($attributeDefaults[$key]) && $attributeDefaults[$key] instanceof AppendableAttributeValue
                ? $this->resolveAppendableAttributeDefault($attributeDefaults, $key, $escape)
                : ($attributeDefaults[$key] ?? '');

            return [$key => implode(' ', array_unique(array_filter([$defaultsValue, $value])))];
        })->merge($nonAppendableAttributes)->all();

        return new static(array_merge($attributeDefaults, $attributes));
    }


    /**
     * Create a new appendable attribute value.
     *
     * @param  mixed  $value
     * @return \Illuminate\View\AppendableAttributeValue
     */
    public function prepends($value)
    {
        echo __METHOD__;
        exit;
        return new AppendableAttributeValue($value);
    }

    /**
     * Set the underlying attributes.
     *
     * @param  array  $attributes
     * @return void
     */
    public function setAttributes(array $attributes)
    {
        $attributes = array_map(function($e) {
            return urldecode($e);
        }, $attributes);
        return parent::setAttributes($attributes);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $value = urldecode($value);
        return parent::offsetSet($offset, $value);
    }
}

