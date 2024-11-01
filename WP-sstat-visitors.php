<?php
/* 
Plugin Name: WP-sstat-visitors
Plugin URI: http://mytecblog.wordpress.com/wp-sstat-visitors/
Description: Track your blog visitors path. Based on WP-ShortStat. Find what was the visitors path and timings in your site. This Plugin present some information on each visitor at your site, including where did he came from, what was he doing and for how long.

Version: 0.5
Author: Raz Peleg
Author URI: http://mytecblog.wordpress.com/
*/

class visitor_path{

    var $languages;
    var $table_stats;
    var $table_search;
    var $tz_offset;
    var $current_time;
    var $time_format;

        // Constructor -- Set things up.
    function visitor_path() {
        global $table_prefix;

        // tables
        $this->table_stats  = $table_prefix . "ss_stats";
        $this->table_search = $table_prefix . "ss_search";

        $this->tz_offset = get_option('gmt_offset') * 3600;
        $this->current_time = strtotime(gmdate('Y-m-d g:i:s a'))+$this->tz_offset;

        // Longest Array Line Ever...
        $this->languages = array( "af" => "Afrikaans", "sq" => "Albanian", "eu" => "Basque", "bg" => "Bulgarian", "be" => "Byelorussian", "ca" => "Catalan", "zh" => "Chinese", "zh-cn" => "Chinese/China", "zh-tw" => "Chinese/Taiwan", "zh-hk" => "Chinese/Hong Kong", "zh-sg" => "Chinese/singapore", "hr" => "Croatian", "cs" => "Czech", "da" => "Danish", "nl" => "Dutch", "nl-nl" => "Dutch/Netherlands", "nl-be" => "Dutch/Belgium", "en" => "English", "en-gb" => "English/United Kingdom", "en-us" => "English/United States", "en-GB" => "English/United Kingdom", "en-US" => "English/United States","en-au" => "English/Australian", "en-ca" => "English/Canada", "en-nz" => "English/New Zealand", "en-ie" => "English/Ireland", "en-za" => "English/South Africa", "en-jm" => "English/Jamaica", "en-bz" => "English/Belize", "en-tt" => "English/Trinidad", "et" => "Estonian", "fo" => "Faeroese", "fa" => "Farsi", "fi" => "Finnish", "fr" => "French", "fr-be" => "French/Belgium", "fr-fr" => "French/France", "fr-ch" => "French/Switzerland", "fr-ca" => "French/Canada", "fr-lu" => "French/Luxembourg", "gd" => "Gaelic", "gl" => "Galician", "de" => "German", "de-at" => "German/Austria", "de-de" => "German/Germany", "de-ch" => "German/Switzerland", "de-lu" => "German/Luxembourg", "de-li" => "German/Liechtenstein", "el" => "Greek", "he" => "Hebrew", "he-il" => "Hebrew/Israel", "hi" => "Hindi", "hu" => "Hungarian", "ie-ee" => "Internet Explorer/Easter Egg", "is" => "Icelandic", "id" => "Indonesian", "in" => "Indonesian", "ga" => "Irish", "it" => "Italian", "it-ch" => "Italian/ Switzerland", "ja" => "Japanese", "ko" => "Korean", "lv" => "Latvian", "lt" => "Lithuanian", "mk" => "Macedonian", "ms" => "Malaysian", "mt" => "Maltese", "no" => "Norwegian", "pl" => "Polish", "pt" => "Portuguese", "pt-br" => "Portuguese/Brazil", "rm" => "Rhaeto-Romanic", "ro" => "Romanian", "ro-mo" => "Romanian/Moldavia", "ru" => "Russian", "ru-mo" => "Russian /Moldavia", "gd" => "Scots Gaelic", "sr" => "Serbian", "sk" => "Slovack", "sl" => "Slovenian", "sb" => "Sorbian", "es" => "Spanish", "es-do" => "Spanish", "es-ar" => "Spanish/Argentina", "es-co" => "Spanish/Colombia", "es-mx" => "Spanish/Mexico", "es-es" => "Spanish/Spain", "es-gt" => "Spanish/Guatemala", "es-cr" => "Spanish/Costa Rica", "es-pa" => "Spanish/Panama", "es-ve" => "Spanish/Venezuela", "es-pe" => "Spanish/Peru", "es-ec" => "Spanish/Ecuador", "es-cl" => "Spanish/Chile", "es-uy" => "Spanish/Uruguay", "es-py" => "Spanish/Paraguay", "es-bo" => "Spanish/Bolivia", "es-sv" => "Spanish/El salvador", "es-hn" => "Spanish/Honduras", "es-ni" => "Spanish/Nicaragua", "es-pr" => "Spanish/Puerto Rico", "sx" => "Sutu", "sv" => "Swedish", "sv-se" => "Swedish/Sweden", "sv-fi" => "Swedish/Finland", "ts" => "Thai", "tn" => "Tswana", "tr" => "Turkish", "uk" => "Ukrainian", "ur" => "Urdu", "vi" => "Vietnamese", "xh" => "Xshosa", "ji" => "Yiddish", "zu" => "Zulu", 'empty' => 'No language recorded');

    }
    
    function getLastIPs( $n, $from )
    {
        global $wpdb;
        $plgin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)); 

        $query = "SELECT * FROM ( SELECT browser, version, remote_ip, platform, language, dt 
                  FROM $this->table_stats
                  WHERE browser != 'Crawler/Search Engine' 
                  AND platform != ''  
                  ORDER BY dt DESC 
                ) as a
                GROUP BY remote_ip
                ORDER BY dt DESC 
                LIMIT $from,$n";
                
        if ($results = $wpdb->get_results($query)) 
        {
            $ul  = "";            
            foreach( $results as $r ) 
            {
                $ip = $r->remote_ip;
                $ul .= "\t<div class=\"module\">\n\t\t<h3>
                   <a href=\"http://www.google.com/search?hl=en&q=%22$ip%22\" 
                   title=\"Google visitor's IP($ip)\" target=\"_blank\" style=\"color: #FFF;\">
                   <img src=\"$plgin./famfamfam/page_find.gif\" width=\"10\" height=\"10\"/> $r->browser $r->version, $r->platform</a>
                   <span title=\"". $this->languages[$r->language]."\">$r->language
                   <a href=\"http://urbangiraffe.com/map/?from=WP-sstat-visitorsn&ip=$ip\"  target=\"_blank\">
                   <img src=\"$plgin./famfamfam/icon_get_world.gif\" title=\"Map location\" width=\"10\" height=\"10\"/></a></span></h3>\n";
                $ul .= "\t\t<div>". $this->getUserActivity($r->remote_ip) ."</div>\n";
                $ul .= "\t</div>";
            }
        }
        return $ul;
    
    }
    
    function getUserActivity( $ip )
    {
        global $wpdb;
        $plgin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)); 

        $query = "SELECT remote_ip, referer, resource, dt
                  FROM $this->table_stats
                  WHERE remote_ip = '$ip'
                  ORDER BY dt";
    
        if ($results = $wpdb->get_results($query)) {
            $ul  = "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n";
            foreach( $results as $r ) 
            {
                $resource = $this->truncate($r->resource,(($r->referer != '' ) ? 27 : 30) );
                $when = ($r->dt >= strtotime(date("j F Y", $this->current_time)))?strftime("%H:%M:%S",$r->dt):strftime("%e %b",$r->dt);
                $when_long = strftime("%H:%M:%S, %d/%m/%y",$r->dt);
                $titl =  "Resource: ".$r->resource."\n".(( $r->referer != '' ) ? 'Referer: '.$r->referer : 'No Referer');
                $ul .= "\t<tr>\n\t\t<td>\n\t\t<span title=\"$titl\">".$resource."</span>";
                if( $r->referer != '' ){
                    $ul .= "\n\t\t<a href=\"$r->referer\" title=\"Visit referrer\" target=\"_blank\"><img src=\"$plgin./famfamfam/arrow_right.gif\" width=\"16\" height=\"10\"/></a>";
                }
                $ul .=  "\n\t\t</td>\n\t\t<td class=\"last\" title=\"$when_long\">$when</td>\n\t</tr>\n";
            }
            $ul .= "\t</table>";
        }
        return $ul;    
    }


    function truncate($var, $len = 120) {
        if (empty ($var)) return "";
        if (strlen ($var) < $len) return $var;
        $match = '';
        if (preg_match ('/(.{1,$len})\s./ms', $var, $match)) {
            return $match [1] . "...";
        } else {
            return substr ($var, 0, $len) . "...";
        }
    }

    function trimReferer($r) {
        $r = eregi_replace("http://","",$r);
        $r = eregi_replace("^www.","",$r);
        $r = $this->truncate($r,36);
        return $r;
    }

    function get_number_of_visitors()
    {
        global $wpdb;

        $query = "SELECT COUNT(DISTINCT remote_ip) 
                FROM ( SELECT browser, remote_ip, platform
                    FROM $this->table_stats
                    WHERE browser != 'Crawler/Search Engine' 
                    AND platform != ''  
                    ) as a";
        return $wpdb->get_var($query);
        
     }
}


function WS_shortstat_NotFound()
{
?>
 <div class="wrap">
      <h2>Visitors</h2>
      <p class="error">WP-SStat-visitors plugin is based on 
           <a href="http://blog.happyarts.de/">Markus Kaemmerer/Happy Arts</a>'s 
           <a href="http://wordpress.org/extend/plugins/wp-shortstat2/">WP-ShortStat plugin.</a><br />
           Please <a href="http://wordpress.org/extend/plugins/wp-shortstat2/">install</a> it in order to activate this plugin.</p>
  </div>
<?php
}

// Always want that instance
$userss = new visitor_path();

// Installation/Initialization Routine
add_action('activate_'.plugin_basename(__FILE__), array(&$userss, 'setup'));


// For the admin page
if (!function_exists('visitor_path_display_stats')) {
    function visitor_path_display_stats()
    {
        global $userss;
        setlocale (LC_TIME, WPLANG);
        load_plugin_textdomain('wp-shortstat');
    $items = 12;
    $page = 1;
    $visitors = $userss->get_number_of_visitors();
    if ($visitors == NULL )
    {
        WS_shortstat_NotFound();
        return;
    }
        
    if ( isset($_GET['cpage']) ) 
    {
        $page = $_GET['cpage'];
    }
    $from = ($page-1)*$items;
    $till = ($from + $items < $visitors ? $from + $items : $visitors) -1;
    $pages = ($visitors / $items ) + 1;
        ?>
     <div class="wrap">
      <h2>Visitors</h2>
       <div class="tablenav">
        <div class="tablenav-pages">
          <span class="displaying-num">Displaying 
           <?php echo $from; ?>&#8211;<?php echo $till; ?> of <span class="total-type-count">
           <?php echo $visitors; ?>
          </span></span>
      <?php echo paginate_links(array (
              'base' => get_bloginfo('wpurl').'/wp-admin/index.php?page='
                              . plugin_basename(__FILE__) . '&cpage=%#%', 
              'format' => '%#%', 
              'current' => $page, 
              'total' => $pages, 
              'end_size' => 3, 
              'mid_size' => 2, 
              'prev_next' => true)); ?>
      </div>
     </div>
     <div id="wp_shortstat">
      <?php echo $userss->getLastIPs( $items,$from ); ?>
       </div>
       <div class="tablenav">
        <div class="tablenav-pages">
          <span class="displaying-num">Displaying 
           <?php echo $from; ?>&#8211;<?php echo $till; ?> of <span class="total-type-count">
           <?php echo $visitors; ?>
          </span></span>
      <?php echo paginate_links(array (
              'base' => get_bloginfo('wpurl').'/wp-admin/index.php?page='
                              . plugin_basename(__FILE__) . '&cpage=%#%', 
              'format' => '%#%', 
              'current' => $page, 
              'total' => $pages, 
              'end_size' => 3, 
              'mid_size' => 2, 
              'prev_next' => true)); ?>
      </div>
     </div>
         <div id="donotremove">&copy; 2009 WP-sstat-visitors. By <a href="http://mytecblog.wordpress.com/wp-sstat-visitors/">Raz Peleg</a>, Based on <a href="http://www.shauninman.com/">Shaun Inman</a>, <a href="http://blog.happyarts.de/">Markus Kaemmerer/Happy Arts</a></div>
    </div>
    <?php
    }
} else {
    var_dump(debug_backtrace());
}

function visitor_path_add_pages($s) {
    add_submenu_page('index.php', 'Visitors', 'Visitors', 2, __FILE__, 'visitor_path_display_stats');
    return $s;
}
add_action('admin_menu', 'visitor_path_add_pages');

