<?php
\Event::dispatch('component.start', $component);
extract($component->attributes->getAttributes());
?>
<pre>
        {!! var_export($component->attributes->getAttributes()) !!}
    </pre>

<div id="{{ $module_id }}" class="row flex">
    <?php
    if(isset($slot))  {
        echo $slot;
    }
    ?>
</div>
<?php
\Event::dispatch('component.end', $component);