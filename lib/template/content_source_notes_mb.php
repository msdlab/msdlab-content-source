<?php global $wpalchemy_media_access; 
?>

<ul class="meta_control">
    <li>
        <div class="input_container">
            <?php 
            $mb->the_field('notes');
            ?>
            <textarea style="width: 100%;min-height: 10em;" name="<?php print $mb->get_the_name(); ?>" id="<?php print $mb->get_the_name(); ?>"><?php print $mb->get_the_value(); ?></textarea>
       </div>
    </li>
</ul>
<script>
jQuery(function($){
    $("#content_sourcediv").after($("#_content_source_notes_mb_metabox"));
});</script>
