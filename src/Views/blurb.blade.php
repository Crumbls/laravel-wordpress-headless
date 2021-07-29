<?php
\Event::dispatch('component.start', $component);
extract($component->attributes->getAttributes());
?>
<div id="{{ $module_id }}" class="{{ is_array($module_class) ? implode(' ', $module_class) : $module_class }}">
    <?php
    if(isset($slot))  {
        echo $slot;
    }
    ?>
</div>
<?php
\Event::dispatch('component.end', $component);