<?php

namespace Crumbls\LaravelWordpress\Components;

use Crumbls\LaravelDivi\Components\Image;
use Crumbls\LaravelWordpress\Css\Generator;
use Crumbls\LaravelWordpress\Helpers\ComponentAttributeBag;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Str;
use Illuminate\View\Component;

abstract class AbstractElement extends Component {
    private $generator = null;
    private static $ids = [];
    public static $path = '';

    public function getPath() {
        return static::$path;
    }

    public function __construct() {
        $extended = property_exists($this, 'attributesExtended') ? $this->attributesExtended : [];
        $this->withAttributes($extended);
    }

    /**
     * @param array $attributes
     * @return ComponentAttributeBag
     */
    protected function newAttributeBag(array $attributes = [])
    {
        return new ComponentAttributeBag($attributes);
    }

    /**
     * Get our unique identifier.
     * @return string
     */
    protected static function getUniqueId() : string {
        $id = preg_replace('#[^a-z]#', '', \Str::uuid());
        while (in_array($id, static::$ids)) {
            $id = preg_replace('#[^a-z]#', '', \Str::uuid());
        }
        static::$ids[] = $id;
        return $id;

    }

    /**
     * Generate a unique ID, if not defined.
     * @return mixed
     */
    protected function generateUniqueId() {
        $id = null;
        if (!$this->attributes->has('module_id') || !$this->attributes->get('module_id')) {
            $id = static::getUniqueId();
            $this->attributes->set('module_id', $id);
        } else {
            return $this->attributes->get('module_id');
        }
    }

    /**
     * Generate our path.
     */
    protected function generatePath() {
        $id = $this->generateUniqueId();
        if (!static::$path) {
            static::$path = '#'.$id;
        } else {
            static::$path .= ' #'.$id;
        }
        $this->attributes->set('_module_path', static::$path);

    }

    /**
     * Class builder
     */
    protected function generateClass() {
        // Class builder.
        $class = $this->attributes->has('module_class') ? $this->attributes->get('module_class') : [];
        if (is_string($class)) {
            $class = explode(' ', $class);
        }

        $methods = array_values(preg_grep('#^generateClass#', get_class_methods($this)));
        $x = array_search(__FUNCTION__, $methods);
        if ($x !== false) {
            unset($methods[$x]);
        }
        foreach($methods as $method) {
            $temp = $this->$method();
            if (is_string($temp)) {
                $class[] = $temp;
            } else if (is_array($temp)) {
                $class = array_merge($class, $temp);
            }
        }

        // Add php class to class list.
        $class[] = strtolower(class_basename(get_class($this)));

        // Converet to array.
        $class = implode(' ', array_unique($class));
        $this->attributes->set('module_class', $class);
 //       $this->attributes = $this->attributes->merge(['module_class' => $class]);
    }

    /**
     * Style generator
     * @return Generator
     */
    protected function getStyleGenerator() {
        if (!$this->generator) {
            $this->generator = new Generator();
        }
        return $this->generator;
    }

    /**
     * Generate styles for this property.
     */
    protected function generateStyles() {
        $css = $this->getStyleGenerator();
        $css->defineIdRule($this->generateUniqueId()) // #app -> defines rule with ID as selector and "app" as selector value
//        ->set('width', '900px') // #app -> sets property width of #app
  //      ->set('margin', '0 auto') // #app -> sets property margin of #app
            ;

        $methods = array_values(preg_grep('#^generateStyle#', get_class_methods($this)));
        $x = array_search(__FUNCTION__, $methods);
        if ($x !== false) {
            unset($methods[$x]);
        }
        foreach($methods as $method) {
            $this->$method();
        }
        return $css->generate();
    }


    public function prerender() {
        $this->generateUniqueId();
        $this->generatePath();
        $this->generateClass();
        $this->generateStyles();
    }

    public function postrender() {
        $style = $this->getStyleGenerator()->generate();
        if ($style) {
            // Ugly way to dump stylesheets.
            echo '<style>'.$style.'</style>';
        }

        // Remove module_id
        if ($temp = $this->attributes->get('module_id')) {
            static::$path = preg_replace('/\s?#'.$temp.'.*$/', '', static::$path);
        }
    }

    /**
     * Resolve the Blade view or view file that should be used when rendering the component.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\Support\Htmlable|\Closure|string
     */
    public function resolveView()
    {
        if (!$this->attributes->has('module_id') || !$this->attributes->get('module_id') || $this->attributes->get('module_id') == null) {
            $this->attributes->offsetSet(
                'module_id', static::getUniqueId()
            );
        }

        $view = $this->render();

        if ($view instanceof ViewContract) {
            return $view;
        }

        if ($view instanceof Htmlable) {
            return $view;
        }

        $resolver = function ($view) {
            $factory = Container::getInstance()->make('view');

            return $factory->exists($view)
                ? $view
                : $this->createBladeViewFromString($factory, $view);
        };

        return $view instanceof Closure ? function (array $data = []) use ($view, $resolver) {
            return $resolver($view($data));
        }
            : $resolver($view);
    }

    /**
     * Get the view / contents that represents the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $view = 'wordpress::'.strtolower(Str::kebab(class_basename(get_class($this))));

        return view($view, array_merge($this->attributes->getAttributes(), ['component' => $this]));
    }

    /**
     * @param $args
     * @param array $defaults
     * @return array
     */
    protected function parseArgments($args, $defaults = array()) : array {
        echo __METHOD__;exit;
        if ( is_object( $args ) ) {
            $parsed_args = get_object_vars( $args );
        } elseif ( is_array( $args ) ) {
            $parsed_args =& $args;
        } else {
            parse_str( $args, $parsed_args );
        }

        if ( is_array( $defaults ) && $defaults ) {
            return array_merge( $defaults, $parsed_args );
        }

        return $parsed_args;
    }
}