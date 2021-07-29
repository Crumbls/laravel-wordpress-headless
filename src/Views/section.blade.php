<?php
    \Event::dispatch('component.start', $component);
?>
<div id="{{ $module_id }}" class="{{ is_array($module_class) ? implode(' ', $module_class) : $module_class }}">
    <?php
    if(isset($slot))  {
        echo $slot;
    }
    ?>
</section>
<?php
\Event::dispatch('component.end', $component);