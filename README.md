Headless WordPress Content for Laravel
========================================

## This project is in an early state of development. Do not use in a production environment. ##

The goal of this project is to parse WordPress content and convert shortcodes into Laravel Components.  You still have to
write and register the components.  I'll be adding in default WP shortcodes sometime soon.

Due to some of our needs, we need a good content management system, but couldn't find a cheap / free one that worked well enough.
We have some great content editors who love WordPress, but due to technical requirements, we have to forego using them
on the front end of a site.   We also didn't want to have to add an extra plugin to every WP install.  With this, you can just attach to
the wp-json feed, or use something like corcel/corcel to import it, then this package to parse it.  We use it in house to 
allow us to display webpages written in Divi inside of Laravel, but without all of the overhead of WP.  ie. We create a component and register it to cover [et-pb-blurb], which then can be rendered.

## I'd love to build this out more.  I'm putting this very early pre-release out there to see if there is any interest before investing time. ##

---

This package does not include a caching component.  I'll add an example of how to cache below.

Here's a horrible usage example.  At this point, you could echo $page->content in the view and get the parsed content.
        
        $pageId = 1;
        $page = WordPress::json()->get('https://yourwordpresssite.com/wp-json/wp/v2/pages/'.$pageId);

        abort_if(!$page, 404);

        $page->content = WordPress::parse($page->content);

        return response()->view('view.test', [
            'page' => $page
        ]);
