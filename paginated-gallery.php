<?php 
/**
 * Plugin Name: Paginated Masonry Gallery
 * Plugin URI: https://github.com/SeanPeterson/AJAX-Default-WP-Gallery
 * Description: Plugin adds pagination and masonry to default WP-Gallery
 * Version: 1.0.0
 * Author: Sean Peterson
 * Author URI: http://seanpetersonwebdesign.com/
 * License: the unlicensed
 */


add_action( 'wp_enqueue_scripts', 'ajax_enqueue_scripts' );
function ajax_enqueue_scripts() {
	global $post;

	//only load scripts for specified page
	if(is_page('photo-gallery'))
    {
        wp_enqueue_script('masonry');

        wp_register_script( 'imagesloaded', plugins_url( '/js/imagesloaded.pkgd.min.js', __FILE__ ));
        wp_enqueue_script( 'imagesloaded' );

		wp_enqueue_style( 'infinte-style', plugins_url( '/css/styles.css', __FILE__ ) );

		wp_enqueue_script( 'infinite', plugins_url( '/js/base.js?ver=1.0', __FILE__ ), array('jquery'), '1.0', true ); //load script, delcare jquery as a dependancy

		//pass string ('postinfinite.ajax_url') to the script (can pass as many strings as you want).
		wp_localize_script( 'infinite', 'postinfiniteArray', array( 
			'ajax_url' => admin_url( 'admin-ajax.php' ), //postinfinite.ajax_url will output the url of the admin-ajax.php file
			'postID' => $post->ID //pass post id
		));
	}

}

add_filter('post_gallery', 'filter_gallery', 10, 2);
function filter_gallery($output, $attr) 
{
    global $post;
    static $instance = 0;
    $instance++;

    //GALLERY SETUP STARTS HERE----------------------------------------//
    if (isset($attr['orderby'])) {
        $attr['orderby'] = sanitize_sql_orderby($attr['orderby']);
        if (!$attr['orderby'])
            unset($attr['orderby']);
    }
    //print_r($attr);
    extract(shortcode_atts(array(
        'order' => 'ASC',
        'orderby' => 'menu_order ID',
        'id' => $post->ID,
        'itemtag' => 'dl',
        'icontag' => 'dt',
        'captiontag' => 'dd',
        'columns' => 3,
        'size' => 'thumbnail',
        'include' => '',
        'exclude' => ''
    ), $attr));

    $id = intval($id);
    if ('RAND' == $order) $orderby = 'none';

    if (!empty($include)) {
        $include = preg_replace('/[^0-9,]+/', '', $include);
        $_attachments = get_posts(array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby));

        $attachments = array();
        foreach ($_attachments as $key => $val) {
            $attachments[$val->ID] = $_attachments[$key];
        }
    }
    if (empty($attachments)) return '';

    //PAGINATION SETUP START HERE-------------------------------------//
    $current = (get_query_var('paged')) ? get_query_var( 'paged' ) : 1;
    $per_page = 24;
    //$offset = ($page-1) * $per_page;
    $offset = ($current-1) * $per_page;
    $big = 999999999; // need an unlikely integer


    $total = sizeof($attachments);
    $total_pages = round($total/$per_page);
    if($total_pages < ($total/$per_page))
    {   $total_pages = $total_pages+1;
    }

    //GALLERY OUTPUT START HERE---------------------------------------//
    $itemtag = tag_escape($itemtag);
    $captiontag = tag_escape($captiontag);
    $columns = intval($columns);
    $itemwidth = $columns > 0 ? floor(100/$columns) : 100;
    $float = is_rtl() ? 'right' : 'left';
    $selector = "gallery-{$instance}";

    $output = apply_filters('gallery_style', "
        <style type='text/css'>
            #{$selector} {
                margin: auto;
            }
            #{$selector} .gallery-item {
                float: {$float};
                margin-top: 10px;
                text-align: center;
                width: {$itemwidth}%;           }
            #{$selector} img {
                border: 2px solid #cfcfcf;
            }
            #{$selector} .gallery-caption {
                margin-left: 0;
            }
        </style>
        <!-- see gallery_shortcode() in wp-includes/media.php -->
        <div id='$selector' class='weblizar-flickr-div gallery galleryid-{$id}'>");
    $counter = 0;
    $pos = 0;
    foreach ($attachments as $id => $attachment) 
    {   $pos++;

        if(($counter < $per_page)&&($pos > $offset))
        {   $counter++;  
            $img = wp_get_attachment_image_src($id, "full");        
            $output .= '<dl class="gallery-item">
            				<a href="' . $img[0] . '" data-gallery="" data-lightbox="image-1"><img class="flickr-img-responsive" src="' . $img[0] . '" /></a>';
            			
            if ( $captiontag && trim($attachment->post_excerpt) ) {
            $output .= "
                            <{$captiontag} class='gallery-caption'>
                            " . wptexturize($attachment->post_excerpt) . "
                            </{$captiontag}>" ;
            }
            
            $output .=  '</dl>';
        }

    }  
    $output .= "<div class=\"clear\"></div>\n";
    $output .= "</div>";

    //PAGINATION OUTPUT START HERE-------------------------------------//
    $output .= '<div class="required-pagination">' . 
	    				paginate_links( array(
				        'base' => str_replace($big,'%#%',esc_url(get_pagenum_link($big))),
				        'format' => '?paged=%#%',
				        'current' => $current,
				        'total' => $total_pages,
				        'prev_text'    => __('&laquo;'),
				        'next_text'    => __('&raquo;'),
				        'type' => 'list'
	    				)) .  			
    			'</div>';

    return $output;
}