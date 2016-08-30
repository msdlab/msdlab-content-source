<?php
/*
Plugin Name: MSD Content Source
Description: A little plugin for marking pages with content that needs review.
Author: MSDLAB
Version: 0.0.2
Author URI: http://msdlab.com
*/

/*TODO:Create settings page for
 * 1) Allow change of default copy (FPO)
 * 2) Allow a switch for notes on or off
 * 3) Allow a switch for notes and/or content source capability adjustment
 * 4) Add a "remove all remarks" button to clear items prior to launch
 * 
 * Also maybe add an admin bar dropdown that shows the notes on thr front end?
 */

if(!class_exists('GitHubPluginUpdater')){
    require_once (plugin_dir_path(__FILE__).'/lib/resource/GitHubPluginUpdater.php');
}

if ( is_admin() ) {
    new GitHubPluginUpdater( __FILE__, 'msdlab', "msdlab-content-source" );
}

if(!class_exists('WPAlchemy_MetaBox')){
    if(!include_once (WP_CONTENT_DIR.'/wpalchemy/MetaBox.php'))
    include_once (plugin_dir_path(__FILE__).'/lib/resource/wpalchemy/MetaBox.php');
}

global $msd_content_source;

/*
 * Pull in some stuff from other files
*/
if(!function_exists('requireDir')){
    function requireDir($dir){
        $dh = @opendir($dir);

        if (!$dh) {
            throw new Exception("Cannot open directory $dir");
        } else {
            while($file = readdir($dh)){
                $files[] = $file;
            }
            closedir($dh);
            sort($files); //ensure alpha order
            foreach($files AS $file){
                if ($file != '.' && $file != '..') {
                    $requiredFile = $dir . DIRECTORY_SEPARATOR . $file;
                    if ('.php' === substr($file, strlen($file) - 4)) {
                        require_once $requiredFile;
                    } elseif (is_dir($requiredFile)) {
                        requireDir($requiredFile);
                    }
                }
            }
        }
        unset($dh, $dir, $file, $requiredFile);
    }
}
if(!class_exists('MSDContentSource')){
    class MSDContentSource{
        protected static $instance = NULL;
        function __construct(){
            global $current_screen;
            register_activation_hook(__FILE__, array(&$this,'activate'));
            //Actions
            add_action( 'init', array(&$this,'register_taxonomy_content_source'), 99 );
            add_action( 'init', array( &$this, 'add_metaboxes' ), 99 );
            add_action( 'wp_print_styles', array(&$this,'add_css') );
            
            //Filters
            add_filter( 'body_class', array(&$this,'watermark_content') );
        }
        
        public static function init() {
           NULL === self::$instance and self::$instance = new self;
           return self::$instance;
        }
        
        function register_taxonomy_content_source(){
            
            $labels = array( 
                'name' => _x( 'Content Source', 'content-source' ),
                'singular_name' => _x( 'Content Source', 'content-source' ),
                'search_items' => _x( 'Search Content Sources', 'content-source' ),
                'popular_items' => _x( 'Popular Content Sources', 'content-source' ),
                'all_items' => _x( 'All Content Sources', 'content-source' ),
                'parent_item' => _x( 'Parent Content Source', 'content-source' ),
                'parent_item_colon' => _x( 'Parent Content Source:', 'content-source' ),
                'edit_item' => _x( 'Edit Content Source', 'content-source' ),
                'update_item' => _x( 'Update Content Source', 'content-source' ),
                'add_new_item' => _x( 'Add new Content Source', 'content-source' ),
                'new_item_name' => _x( 'New Content Source name', 'content-source' ),
                'separate_items_with_commas' => _x( 'Separate Content Sources with commas', 'content-source' ),
                'add_or_remove_items' => _x( 'Add or remove Content Sources', 'content-source' ),
                'choose_from_most_used' => _x( 'Choose from the most used Content Sources', 'content-source' ),
                'menu_name' => _x( 'Content Sources', 'content-source' ),
            );
        
            $args = array( 
                'labels' => $labels,
                'public' => true,
                'show_in_nav_menus' => true,
                'show_ui' => true,
                'show_tagcloud' => false,
                'hierarchical' => true, 
                'show_in_quick_edit' => true,
                'show_admin_column' => true,
                'rewrite' => array('slug'=>'content-source','with_front'=>false),
                'query_var' => true,
                'meta_box_cb' => array(&$this,'taxonomy_meta_box'),
            );
        
            register_taxonomy( 'content_source', get_post_types( '', 'names' ), $args );
        }
        
        
        function add_metaboxes(){
            global $content_source_notes_mb;
            $content_source_notes_mb = new WPAlchemy_MetaBox(array
                (
                    'id' => '_content_source_notes_mb',
                    'title' => 'Content Source Notes',
                    'types' => get_post_types( '', 'names' ),
                    'context' => 'normal',
                    'priority' => 'high',
                    'template' => plugin_dir_path(__FILE__).'/lib/template/content_source_notes_mb.php',
                    'autosave' => TRUE,
                    'mode' => WPALCHEMY_MODE_EXTRACT, // defaults to WPALCHEMY_MODE_ARRAY
                    'prefix' => '_content_source_notes_mb_' // defaults to NULL
                ));
        }
        
        function taxonomy_meta_box( $post, $box ) {
            $tax_name = 'content_source';
            $taxonomy = get_taxonomy($tax_name);
            $tax_singular_label = $taxonomy->labels->singular_name;
            ?>
            <div id="taxonomy-<?php echo $tax_name; ?>" class="categorydiv">
        
            <?php //took out tabs for most recent here ?>
        
                <div id="<?php echo $tax_name; ?>-all">
                    <?php
                    $name = ( $tax_name == 'category' ) ? 'post_category' : 'tax_input[' . $tax_name . ']';
                    echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
                    $term_obj = wp_get_object_terms($post->ID, $tax_name ); //_log($term_obj[0]->term_id) 
                    wp_dropdown_categories( array( 'taxonomy' => $tax_name, 'hide_empty' => 0, 'name' => "{$name}[]", 'selected' => $term_obj[0]->term_id, 'orderby' => 'name', 'hierarchical' => 0, 'show_option_none' => "Select ".$tax_singular_label ) ); 
                    ?>
                </div>
            <?php if ( current_user_can( $taxonomy->cap->edit_terms ) ) : ?>
                <p><a href="<?php echo site_url(); ?>/wp-admin/edit-tags.php?taxonomy=<?php echo $tax_name ?>&post_type=YOUR_POST_TYPE">Add New</a></p>
            <?php endif; ?>
            </div>
            <?php
        }

        function activate(){
            $this->register_taxonomy_content_source();
            wp_insert_term(
              'Approved Content', // the term 
              'content_source', // the taxonomy
              array(
                'description'=> 'Content that has been fully approved.',
                'slug' => 'approved-content'
              )
            );
        }
        
        function watermark_content($classes){
            global $post;
            if(is_cpt('post')){
                return $classes;
            }
            if(!has_term('approved-content','content_source',$post)){
                $classes[] = "watermark";
            }
            return $classes;
        }
        
        function add_css(){
            global $post;
            $terms = get_the_terms($post->ID,'content_source');
            if(count($terms)>0){
                $content_source_string = $terms[0]->name;
            } else {
                $content_source_string = 'FPO'; //default setting?
            }
            $css = '
                <style>
                    body.watermark main {
                        position: relative;
                    }
                    body.watermark main:before {
                        display: block;
                        content: "'.$content_source_string.'";
                        color: red;
                        font-size: 20vw;
                        opacity: 0.1;
                        position: absolute;
                        top: 0;
                        left: 0;
                        text-align: center;
                        -moz-transform: rotate(-30deg);
                        -webkit-transform: rotate(-30deg);
                        -o-transform: rotate(-30deg);
                        -ms-transform: rotate(-30deg);
                        transform: rotate(-30deg);
                        line-height: 1;
                        width: 100%;
                        z-index: -1;
                    }
                </style>
            ';
            print $css;
        }
    }
}

//instantiate
$msd_content_source = new MSDContentSource();