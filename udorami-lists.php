<?php
/**
 * @package Udorami_Lists
 * @version 1.4.1
 */
/*
Plugin Name: Udorami Lists
Plugin URI: http://wordpress.org/extend/plugins/udorami-lists/
Description: Include a list from the Udorami social lists website.
Version: 1.4.1
Author: Jamii Corley
Author URI: http://www.udorami.com/us/
License:     GPL2
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

$site = "https://www.udorami.com/lists";

if (is_admin()) {
    add_action( 'admin_menu', 'udorami_lists_menu' );
    add_action( 'admin_init', 'register_udorami_settings' );
}

function udorami_pict_layout($layout, $pict_url, $show_pict, $pict_width, $url) {
    $box = "";
    switch ($layout) {
        case 0:
           $add = " width=$pict_width ";
           break;
        case 1:
           $add = " width=$pict_width ";
           break;
        case 2:
           $add = " width=90% class='umi_horiz_center' ";
           break;
        case 3:
           $add = " width=90% class='umi_horiz_center' ";
           break;
        case 4:
	   return "";
	   break;
    }
    if ($pict_url && $show_pict) {
        $box = "<img $add src='$pict_url'>\n";
    } else {
        if ($show_pict) {
            $box = "&nbsp;";
        } else {
            $box = "";
        }
    }
    if ($layout == 3 && $show_pict) {
        $box = "<a target='_prod' href='$url'>" . $box . "</a>";
    }
    return $box;
}

function udorami_format_item( $pict_width, $show_link, $show_pict, 
                              $show_descr, $layout, $item, $prod, $row) {
    $item_content = "";
    $description = $item['description'];
    $url = $prod['url'];
    $pict_url = $item['pict_url'];
    $name = $prod['name'];
        
    $box_pict = udorami_pict_layout($layout, $pict_url, $show_pict, $pict_width, $url);

    if (! $show_link && $prod['local_url'] == 1) {
        $box_text = "<b>$name</b>\n"; 
    } else {
        $box_text = "<a target='_prod' href='$url'>$name</a>\n"; 
    }
    if ($show_descr) {
        $box_text .= "<br>$description"; 
    }
    $item_content = "";
    switch ($layout) {
        case 0:
           $item_content .= "<tr class='umi_tr'>";
	   $item_content .= "<td class='umi_td'>\n"; 
           $item_content .= "<div class='umi_horiz_center umi_pict' style='float:left;'>$box_pict</div>";
           $item_content .= "<div class='umi_name umi_horiz_center'>$box_text</div>";
           $item_content .= "</td></tr>";
           break;
        case 1:
           $item_content .= "<tr class='umi_tr'>";
	   $item_content .= "<td class='umi_td'>\n";
           if (($row % 2) == 1) {
               $item_content .= "<div class='umi_horiz_center umi_pict' style='float:right;'>$box_pict</div>";
               $item_content .= "<div class='umi_name umi_horiz_center'>$box_text</div>";
           } else {
               $item_content .= "<div class='umi_horiz_center umi_pict' style='float:left;'>$box_pict</div>";
               $item_content .= "<div class='umi_name umi_horiz_center'>$box_text</div>";
           }
           $item_content .= "</td></tr>";
           break;
        case 2:
           $item_content .= "<div class='item'>";
           $item_content .= " <div class='umi_pict umi_center'>$box_pict</div>";
           $item_content .= " <div class='umi_center umi_name'>$box_text</div>";
           $item_content .= "</div>";
           break;
        case 3:
           $item_content .= "<div class='item'>";
           $item_content .= " <div class='umi_pict umi_center'>$box_pict</div>";
           $item_content .= "</div>";
           break;
        case 4:
	   $item_content .= "<li>$box_text</li>";
    }
    return $item_content;
}

function udorami_list_header($layout) {
    $item_top = "";
    switch ($layout) {
        case 0:
           $item_top .= "<table class='umi_table'>"; 
           break;
        case 1:
           $item_top .= "<table class='umi_table'>"; 
           break;
        case 2:
           $item_top .= "<div id='umi_bento' data-umicolumns>"; 
           break;
        case 3:
           $item_top .= "<div id='umi_bento' data-umicolumns>"; 
           break;
	case 4:
	   $item_top .= "<ul>";
	   break;
    }
    return $item_top;
}

function udorami_list_footer($layout) {
    $item_bottom = "";
    switch ($item_bottom) {
        case 0:
           $item_bottom .= "</table>"; 
           break;
        case 1:
           $item_bottom .= "</table>"; 
           break;
        case 2:
           $item_bottom .= "</div>"; 
           break;
        case 3:
           $item_bottom .= "</div>"; 
           break;
	case 4: 
	   $item_bottom .= "</ul>";
    }
    return $item_bottom;
}

function udorami_list_view ( $atts ) {
    global $site;

//  Pick up API key for validation with server
    $opt_name = 'udorami_api_key';
    $api_key = get_option( $opt_name );
//
//  Make sure there's a list to process
    if ($atts['list']) { $list_id = $atts['list']; }
    else { return; }
//
//  Set picture width from option or default
    if ($atts['picsize'] && is_numeric($atts['picsize'])) { 
       $width = $atts['picsize']; 
       if ($width < 1 || $width > 1000) { $width = 150; }
    } else { 
       $width = 150; 
    }
//
// Process list options: show author, show link, show title
// show picture, and layout.
//
    $author = 1;
    $title = 1;
    $link = 1;
    $descr = 1;
    if (ISSET($atts['noauthor'])) { $author = 0; }
    if (ISSET($atts['nolink'])) { $link = 0; }
    if (ISSET($atts['notitle'])) { $title = 0; }
    if (ISSET($atts['nodescr'])) { $descr = 0; }
    $layout = 0;
    if (ISSET($atts['layout'])) { $layout = $atts['layout']; }
    $picture = 1;
    if (ISSET($atts['nopic'])) { $picture = 0; }
//
// Get list data
    $url = $site . "/wish_lists/rest_view/" .
           $list_id . "/" . $api_key . ".json";
    $response = wp_remote_get( $url );
    if ( is_wp_error( $response ) ) {
       $error_string = $response->get_error_message();
       return sprintf( '%s<br>The URL %1s could not be retrieved.', $error_string, $url );
    }
    $data = wp_remote_retrieve_body( $response );
    if ( ! is_wp_error( $data )  ) {
       $list = json_decode( $data, true );
       $wish_list = $list['wish_list'];
       if (! $wish_list['WishList']) { 
           print "No list available<br>"; 
           return;
       }
       $list_name = $wish_list['WishList']['name'];
       $owner = $wish_list['User']['first_name'] . " " .  
                $wish_list['User']['last_name'];
 
       $list_content = "";
       if ($title) {
           $list_content .= "<h2 class='umi_header'>$list_name</h2>\n";
       }
       if($author) {
          $list_content .= "by $owner<br>\n";
       }
       $num_items = $wish_list['WishList']['item_count'];
       $list_content .= udorami_list_header($layout);
              
       $item = $wish_list['Item'];
       for ($i = 0; $i < $num_items; $i++) {
           $prod = $item[$i]['Product'];
           $list_content .= udorami_format_item($width, $link, $picture, $descr,
                                                $layout, $item[$i], $prod, 
                                                $i);
       }
       $list_content .= udorami_list_footer($layout);
    }
    return $list_content;
}

function udorami_lists_menu() {
	add_options_page( 'Udorami API Key', 'Udorami Lists', 'manage_options', 'udorami', 'udorami_options' );
}

function register_udorami_settings() {
  register_setting( 'udorami-group', 'udorami_api_key' );
}

function udorami_options() {
        global $site;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
        $opt_name = 'udorami_api_key';
        $opt_val = get_option( $opt_name );

	echo '<div class="wrap">';
        echo '<h2>Udorami Lists Setup</h2>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'udorami-group' );
        do_settings_sections( 'udorami-group' );
        echo 'API Key <input type="text" name="' . $opt_name .
             '" value="' . $opt_val . '" size=50>';
        submit_button();
        echo '</form>';
        echo "<h2>Usage</h2>";
        echo "A Udorami list is displayed by the shortcode <em>udorami_list</em>\n";
        echo "You need to know the list-id of the list you want to display.<br>\n";
        echo "To find your list-id, login to udorami.com, go to your list.<br>\n";
        echo "The URL for you list will look like this:<br>\n";
        echo "<p>$site/wish_lists/view/41</p>\n";
        echo "The list-id in this example is 41. Your short-code will look like this:<br>\n";
        echo "<p>[udorami_list list=41]</p>\n";
        echo "Options you can are are ";
        echo "<ol><li><b>nopic=1</b> - Don't show the picture.</li>\n";
        echo "<li><b>picsize=200</b> - Set the max picture size in pixels.</li>\n";
        echo "<li><b>layout=0</b> - Default. List, pictures to the left.</li>\n";
        echo "<li><b>layout=1</b> - List, alternating pictures left, then right.</li>\n";
        echo "<li><b>layout=2</b> - Masonry grid.</li>\n";
        echo "<li><b>layout=3</b> - Masonry grid of pictures only</li>\n";
        echo "<li><b>layout=4</b> - List of titles only (for widgets)</li>\n";
        echo "<li><b>noauthor=1</b> - Don't show the list author.</li>\n";
        echo "<li><b>nodescr=1</b> - Don't show the description.</li>\n";
        echo "<li><b>notitle=1</b> - Don't show the list title.</li>\n";
        echo "<li><b>nolink=1</b> - Don't link to www.udorami.com items.</li>\n";
        echo "</ul>\n";
	echo '</div>';
}

function umi_enqueue() {
    global $post;
    if( is_a( $post, 'WP_Post' ) && 
              has_shortcode( $post->post_content, 'udorami_list') ) {
         wp_register_style( 'umi_salvattore_style', plugins_url('css/umi_salvattore.css', __FILE__) );
         wp_register_style( 'umi_style', plugins_url('css/umi.css', __FILE__) );
         wp_register_script('umi_salvattore', 
                            plugin_dir_url(__FILE__) . 'js/umi_salvattore.min.js', 
                            array('jquery'), '1.0', true);
         wp_enqueue_script('umi_salvattore');
         wp_enqueue_style( 'umi_salvattore_style' );
         wp_enqueue_style( 'umi_style' );
    }
}
add_action( 'wp_enqueue_scripts', 'umi_enqueue' );
add_shortcode( 'udorami_list', 'udorami_list_view' ); 

?>
