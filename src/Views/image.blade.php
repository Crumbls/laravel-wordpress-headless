<?php
\Event::dispatch('component.start', $component);
extract($component->attributes->getAttributes());

if (!isset($url)) {
    $url = null;
}
?>
<div id="{{ $module_id }}" class="{{ is_array($module_class) ? implode(' ', $module_class) : $module_class }}">
    @if($url)
        <a href="{!! $url !!}" title="{{ $title_text  }}">
    @endif
    <img src="{{ $src }}" alt="{{ $title_text }}" />
    @if($url)
        </a>
    @endif
    <?php
    if(isset($slot))  {
        echo $slot;
    }
    ?>
</div>
<?php
\Event::dispatch('component.end', $component);