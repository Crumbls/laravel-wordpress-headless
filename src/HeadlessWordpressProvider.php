<?php

namespace Crumbls\LaravelWordpress;


use Crumbls\LaravelWordpress\Components\Audio;
use Crumbls\LaravelWordpress\Components\Caption;
use Crumbls\LaravelWordpress\Components\Embed;
use Crumbls\LaravelWordpress\Components\Gallery;
use Crumbls\LaravelWordpress\Components\Playlist;
use Crumbls\LaravelWordpress\Components\Video;
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
        Blade::component('audio', Audio::class);
        Blade::component('caption', Caption::class);
        Blade::component('embed', Embed::class);
        Blade::component('gallery', Gallery::class);
        Blade::component('playlist', Playlist::class);
        Blade::component('video', Video::class);
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
