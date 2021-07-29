<?php

namespace Crumbls\LaravelWordpress;

use Crumbls\LaravelDivi\Components\Blurb;
use Crumbls\LaravelDivi\Components\Button;
use Crumbls\LaravelDivi\Components\Column;
use Crumbls\LaravelDivi\Components\ColumnInner;
use Crumbls\LaravelDivi\Components\Cta;
use Crumbls\LaravelDivi\Components\Divider;
use Crumbls\LaravelDivi\Components\EtPbColumn;
use Crumbls\LaravelDivi\Components\EtPbColumnInner;
use Crumbls\LaravelDivi\Components\EtPbImage;
use Crumbls\LaravelDivi\Components\EtPbPostTitle;
use Crumbls\LaravelDivi\Components\EtPbRow;
use Crumbls\LaravelDivi\Components\EtPbRowInner;
use Crumbls\LaravelDivi\Components\EtPbSection;
use Crumbls\LaravelDivi\Components\EtPbTestimonial;
use Crumbls\LaravelDivi\Components\EtPbText;
use Crumbls\LaravelDivi\Components\Image;
use Crumbls\LaravelDivi\Components\PostTitle;
use Crumbls\LaravelDivi\Components\Row;
use Crumbls\LaravelDivi\Components\RowInner;
use Crumbls\LaravelDivi\Components\Section;
use Crumbls\LaravelDivi\Components\Testimonial;
use Crumbls\LaravelDivi\Components\Text;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class HeadlessWordpressProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        \App::bind('wordpress', function()
        {
            return new WordPress;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Views for shortcodes
         */
        $this->loadViewsFrom(__DIR__.'/Views', 'wordpress');
        $this->publishes([
            __DIR__.'/Views' => resource_path('views/vendor/wordpress'),
        ]);

        /**
         * Boot shortcodes.
         */
        $this->bootShortcodes();


        \Event::listen('component.start', function ($component) {
                $component->prerender();
        });
        \Event::listen('component.end', function ($component) {
            $component->postrender();
        });
    }

    /**
     * Boot our components.
     */
    protected function bootShortcodes() : void {
        return;
        Blade::component('et-pb-row_inner', RowInner::class);
        Blade::component('et-pb-column_inner', ColumnInner::class);
        $this->loadViewComponentsAs('et-pb', [
            Blurb::class,
            Button::class,
            Column::class,
            ColumnInner::class,
            Cta::class,
            Divider::class,
            Image::class,
            PostTitle::class,
            Row::class,
            RowInner::class,
            Section::class,
            Testimonial::class,
            Text::class
        ]);
    }
}
