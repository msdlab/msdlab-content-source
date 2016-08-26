<?php
//* ---------------------------------------------------------------------- */
/* Check the current post for the existence of a short code
/* ---------------------------------------------------------------------- */

if ( !function_exists('msdlab_has_shortcode') ) {

    function msdlab_has_shortcode($shortcode = '') {
    
        global $post;
        $post_obj = get_post( $post->ID );
        $found = false;
        
        if ( !$shortcode )
            return $found;
        if ( stripos( $post_obj->post_content, '[' . $shortcode ) !== false )
            $found = true;
        
        // return our results
        return $found;
    
    }
}

/*
* A useful troubleshooting function. Displays arrays in an easy to follow format in a textarea.
*/
if ( ! function_exists( 'ts_data' ) ) :
function ts_data($data){
    $ret = '<textarea class="troubleshoot" cols="100" rows="20">';
    $ret .= print_r($data,true);
    $ret .= '</textarea>';
    print $ret;
}
endif;
/*
* A useful troubleshooting function. Dumps variable info in an easy to follow format in a textarea.
*/
if ( ! function_exists( 'ts_var' ) && function_exists( 'ts_data' ) ) :
function ts_var($var){
    ts_data(var_export( $var , true ));
}
endif;