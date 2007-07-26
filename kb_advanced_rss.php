<?php
/*
Plugin Name: KB Advanced RSS Widget
Description: Gives user complete control over how feeds are displayed.
Author: Adam R. Brown
Version: 1.7
Plugin URI: http://adambrown.info/b/widgets/category/kb-advanced-rss/
Author URI: http://adambrown.info/
*/

// Credit where it's due: This widget is a (heavily) modified version of the default RSS widget distributed with the Sidebar Widgets plugin. Kudos to those guys for figuring out the essentials.


// SETTINGS
define('KBRSS_HOWMANY', 20);	// max number of KB RSS widgets that you can have. Set to whatever you want. But don't put it higher than you need, or you may gum up your server.
define('KBRSS_MAXITEMS', 10);	// max number of items you can display from a feed. Obviously, you can't get more than are in the actual feed.
define('KBRSS_FORCECACHE', false); // if your widgets don't update after more than 1 hour, set this to true.
define('KBRSS_WPMU', false); // set to TRUE if you're on WP-MU to add a few extra filters to what folks can put into their widgets.





/* CHANGE LOG
	1.0 	Original
	1.0.1 	Simply some code
	1.1	Fix bug that kept it from working on pre-PHP5 systems
	1.1a	Minor text changes
	1.2	Title can be blank
	1.2.1	bug
	1.3	Workaround for a WP v2.2 bug
	1.3.1	Better troubleshooter
	1.4	More robust compatibility with 2.2+
	1.5	New options 
		- can now choose whether to link title to RSS feed. 
		- use ?kbrss_cache=flush to force purge of cache.
		- Easier to have more than 9 widgets (use the setting below)
		- Now defaults to 18 widgets max (for folks making a news aggregation page, I guess)
	1.5.1	Links to correct URL if link option is selected.
		- now defaults to 20 max
	1.5.2	Embeds only the necessary CSS info.
	1.5.3	works with wp2.2.1. When will the developers quit screwing with the widgets api on every WP update? grrrrr
	1.5.4	for real this time
	1.6	option to convert feed from ISO-8859-1 to UTF-8. Thanks to Christoph Juergens (www.cjuergens.de)
	1.6.1	new setting: easily change the max number of items a feed can have.
	1.7	several:
		- checking cache freshness is now an optional setting
		- unless KBRSS_WPMU is true, fewer filters on what can be in widget options
*/






// okay, settings are done. Stop editing.



/* NOTE TO PEOPLE MODIFYING THIS PLUGIN:
	My apologies for the messiness of this code. I originally wrote it for my own uses, adding new options over time.
	If I had started with the intention of writing something that would do everything that this does now,
	I'm sure I could have written a much cleaner plugin.
	As it stands, this is some horrendous coding to figure out. Good luck.
	If you make modifications that others might appreciate, post a comment on the plugin's page.
	thanks
*/




function widget_kbrss_init() {

	// replicate a PHP 5 function for users of older versions
	if ( !function_exists('htmlspecialchars_decode') ){
	    function htmlspecialchars_decode($text){
	        return strtr($text, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
	    }
	}

	// prevent fatals
	if ( !function_exists('register_sidebar_widget') )
		return;
		

	function widget_kbrss($args, $number = 1) {
		if ( file_exists(ABSPATH . WPINC . '/rss.php') )
			require_once(ABSPATH . WPINC . '/rss.php');
		else
			require_once(ABSPATH . WPINC . '/rss-functions.php');
		extract($args);
		$options = get_option('widget_kbrss');
		$num_items = (int) $options[$number]['items'];
		$show_summary = $options[$number]['show_summary'];
		
		if ( empty($num_items) || $num_items < 1 || $num_items > KBRSS_MAXITEMS ) $num_items = KBRSS_MAXITEMS;
		$url = $options[$number]['url'];
		
		if ( empty($url) )
			return;
		
		while ( strstr($url, 'http') != $url )
			$url = substr($url, 1);


		$md5 = md5($url);

		// for some reason, the feeds don't always update like they should. So let's verify here that the cache is less than 1 hour (3600 seconds) old.
		if ( KBRSS_FORCECACHE ){
			$url_timestamp = get_option("rss_{$md5}_ts");
			if ( $url_timestamp < ( time() - 3600 ) )
				delete_option("rss_{$md5}");
		}

		// force deletion of cache (must be logged in as admin)
		if ( 'flush' == $_GET['kbrss_cache'] ){
			global $userdata;
			if ( $userdata->user_level >= 7 ){
				delete_option("rss_{$md5}");
			}
		}


	
		$rss = @fetch_rss($url);	// @ prevents errors after deleting the option
		$link = wp_specialchars(strip_tags($rss->channel['link']), 1);
		
		while ( strstr($link, 'http') != $link )
			$link = substr($link, 1);
		
		$desc = wp_specialchars(strip_tags(html_entity_decode($rss->channel['description'], ENT_QUOTES)), 1);
		$title = $options[$number]['title'];
		
		/*if ( empty($title) )
			$title = htmlentities(strip_tags($rss->channel['title']));
		
		if ( empty($title) )
			$title = $desc;
		
		if ( empty($title) )
			$title = __('Unknown Feed', 'kbwidgets'); */
		
		$output_format = $options[$number]['output_format'];
		$output_begin = $options[$number]['output_begin'];
		$output_end = $options[$number]['output_end'];
		$utf = $options[$number]['utf'];

		
		if ( empty($output_format) )
			$output_format = '<li><a class="kbrsswidget" href="^link$" title="^description$">^title$</a></li>';


		$url = wp_specialchars(strip_tags($url), 1);

		if ( ( "link" == $options[$number]['linktitle'] ) && $title )
			$title = "<a href='$link'>$title</a>";
		
		$icon = $options[$number]['icon'];
		
		if ( '' != $icon )
			$title = "<a class='kbrsswidget' href='$url' title='Syndicate this content'><img width='14' height='14' src='$icon' alt='RSS' /></a> $title";
		/* else
			$title = "$title"; */


			echo $before_widget;
			if ( '' != $title )
				print($before_title . $title . $after_title);
			echo $output_begin;
		
		/* HERE COMES SOME TRULY HIDEOUS CODE. SORRY. AT LEAST IT WORKS, RIGHT? */
		
		if ( is_array( $rss->items ) ) {
			$rss->items = array_slice($rss->items, 0, $num_items);
					
			// prepare the output. e.g. $output_format = '<li>^title$ and ^description$</li>';
			$output_format_one = explode('^', $output_format);	// e.g. = array( '<li>', 'title$ and', 'description$</li>';
			$output_format_two = array();
			foreach($output_format_one as $value_one){
				$output_format_two[] = explode('$',$value_one);	// e.g. array(  array('<li>') ,  array('title', ' and') ,  array('description', '</li>')  );
			}
			unset($output_format_two[0]);	// e.g. array(    array('title', ' and') ,    array('description', '</li>')    );
			// done preparing output format. Note that each $output_format_two[][0] contains a tag to replace from the feed (title, description). We use this later.

			// loop through each item in feed
			foreach ($rss->items as $item ) {
				while ( strstr($item['link'], 'http') != $item['link'] )
					$item['link'] = substr($item['link'], 1);
				$link = wp_specialchars(strip_tags($item['link']), 1);
				$title = wp_specialchars(strip_tags($item['title']), 1);
				if ( empty($title) )
					$title = __('Untitled');
				$desc = '';
				if ( $show_summary ) {
					$summary = '<div class="kbrssSummary">' . $item['description'] . '</div>';
				} else {
					$desc = str_replace(array("\n", "\r"), ' ', wp_specialchars(strip_tags(html_entity_decode($item['description'], ENT_QUOTES)), 1));
					$summary = '';
				}

				// prepare the customized parsing
				$item_output_format = $output_format;
				
				// okay, this is embarassingly ugly for a little bit.
				// loop through each requested element (recall that we're in the middle of another loop right now)
				foreach($output_format_two as $value_two){
					$value_two[0] = strtolower($value_two[0]);
					$replaceme = $value_two[0];

					if ( strpos( $value_two[0], '%%' ) ){	// e.g. ^description%%100$ to trim to 100 chars
						$trim_this_rss_thing = explode( '%%', $value_two[0] );
						$value_two[0] = $trim_this_rss_thing[0];
					}	// we'll do the actual trimming in 20 or 30 lines
					
					// let's figure out what exactly the user wants from the feed.
					// at end, $this_rss_thing will contain the requested feed element.
					if ($value_two[0] == 'link'){
						$this_rss_thing = $link; // already defined (above)
					}elseif ($value_two[0] == 'title'){
						$this_rss_thing = $title; // already defined (above)
					}elseif ($value_two[0] == 'description'){
						$this_rss_thing = $desc; // already defined (above)
					// if you make a custom field tag, you would put it here:
					// CUSTOM FIELD TAGS
					}else{ // okay, user wants a non-standard element from the feed. This is why this plugin exists.
						unset( $doozy ); // in case it was set on a previous iteration
						unset( $this_rss_thing ); // same deal
						// case 1: Element contains an array, and user wants each element of the array.
						if ( strpos( $value_two[0], '||' ) ){	//  e.g. Write: ^categories||<li>||</li>$. we only require the first ||, second is optional.
							$doozy = explode( '||', $value_two[0] ); // break up user's request by '||'
							foreach( $item[ $doozy[0] ] as $anotherdoozy ){ // in example, $doozy[0] is "categories" element of feed
								$this_rss_thing .= $doozy[1];	// e.g. <li> in this example
								$this_rss_thing .= $anotherdoozy;	// not stripping tags or using wp_specialchars
								$this_rss_thing .= $doozy[2];	// e.g. </li> in this example. Might be blank.
							}
						// case 2: Element contains an array, and user wants a particular element from the array.
						}elseif( strpos( $value_two[0], '=>' ) ){	// E.g. "media:content" => "url" from Yahoo. Write: ^media:content=>url$
							$doozy = explode( '=>', $value_two[0] ); // gives [0] = "media:content" and [1] = "url"
							$this_rss_thing = $item[ $doozy[0] ][ $doozy[1] ];	// not stripping tags or anything
						// case 3 (simplest): Element just contains a string.
						}else{
							# $this_rss_thing = wp_specialchars( strip_tags( $item[$value_two[0]] ), 1); // prevents images from showing up properly.
							$this_rss_thing = $item[$value_two[0]]; // grab the element.
						}
					}

					// trim, if requested
					if ( is_array( $trim_this_rss_thing ) ){
						if ( 0 < $trim_this_rss_thing[1] ){
							$this_rss_thing = substr( $this_rss_thing, 0, $trim_this_rss_thing[1] );
						}
					}
					unset( $trim_this_rss_thing );
					// end trimming

					// okay, we have feed content in $this_rss_thing. Let's substitute feed contact in place of the "^element$" tag.
					// Note that this occurs in a loop, so this replace function happens once for each tag.
					$item_output_format = str_replace('^'.$replaceme.'$', $this_rss_thing, $item_output_format);
				}

				// convert to utf 8 if requested
				if ( $utf )
					$item_output_format = utf8_encode( $item_output_format );

				// done with customized parsing. This contains the item's data. We still need to repeat (we're in a FOREACH) for additional items in feed.
				echo $item_output_format; // spit out this feed item
			}
		} else {
			echo __('<li>An error has occurred; the feed is probably down. Try again later.</li>', 'kbwidgets');
		} 
			echo $output_end;
			echo $after_widget;
	}

	function widget_kbrss_control($number) {
		$options = get_option('widget_kbrss');
		$newoptions = $options;

		if ( $_POST["kbrss-submit-$number"] ) {
			$newoptions[$number]['items'] = (int) $_POST["kbrss-items-$number"];
			$newoptions[$number]['url'] = strip_tags(stripslashes($_POST["kbrss-url-$number"]));
			$newoptions[$number]['icon'] = strip_tags(stripslashes($_POST["kbrss-icon-$number"]));
			if (KBRSS_WPMU){
				$newoptions[$number]['title'] = trim(strip_tags(stripslashes($_POST["kbrss-title-$number"])));
			}else{
				$newoptions[$number]['title'] = trim( stripslashes($_POST["kbrss-title-$number"]) );
			}
			$newoptions[$number]['linktitle'] = ( "link" == $_POST["kbrss-linktitle-$number"] ) ? "link" : null;
			$newoptions[$number]['output_format'] = htmlspecialchars_decode( stripslashes($_POST["kbrss-output_format-$number"]) );
			$newoptions[$number]['output_begin'] = htmlspecialchars_decode( stripslashes($_POST["kbrss-output_begin-$number"]) );
			$newoptions[$number]['output_end'] = htmlspecialchars_decode( stripslashes($_POST["kbrss-output_end-$number"]) );
			$newoptions[$number]['utf'] = ( "utf" == $_POST["kbrss-utf-$number"] ) ? "utf" : null;


		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_kbrss', $options);
		}
		$url = htmlspecialchars($options[$number]['url'], ENT_QUOTES);
		$icon = htmlspecialchars($options[$number]['icon'], ENT_QUOTES);
		$items = (int) $options[$number]['items'];
		$title = htmlspecialchars($options[$number]['title'], ENT_QUOTES);
		$linktitle = $options[$number]['linktitle'];
		$output_format = htmlspecialchars($options[$number]['output_format'], ENT_QUOTES);
		#$output_format = $options[$number]['output_format'];
		$output_begin = htmlspecialchars($options[$number]['output_begin'], ENT_QUOTES);
		#$output_begin = $options[$number]['output_begin'];
		$output_end = htmlspecialchars($options[$number]['output_end'], ENT_QUOTES);
		#$output_end = $options[$number]['output_end'];
		$utf = $options[$number]['utf'];


		if ( empty($items) || $items < 1 ){
			$items = 10;
		}
		if ( '' == $output_format ){
			$output_format = "<li><a class='kbrsswidget' href='^link\$' title='^description\$'>^title\$</a></li>";
		}
		if ( '' == $url ){
			$output_begin = "<ul>";	// note that we're checking whether the url is empty. that way we don't re-populate these fields if somebody
			$output_end = "</ul>";	// intentionally cleared them. we only want to populate them when beginning a new widget.
			if ( file_exists(dirname(__FILE__) . '/rss.png') ){
				$icon = str_replace(ABSPATH, get_settings('siteurl').'/', dirname(__FILE__)) . '/rss.png';
			}else{
				$icon = get_settings('siteurl').'/wp-includes/images/rss.png';
			}
			$url = "http://";
		}
		
	?>
				<p><strong>Basic Settings</strong></p>
				<table>
				<tr>
					<td><?php _e('Title (optional):', 'kbwidgets'); ?> </td>
					<td><input style="width: 400px;" id="kbrss-title-<?php echo "$number"; ?>" name="kbrss-title-<?php echo "$number"; ?>" type="text" value="<?php echo $title; ?>" /></td>
				</tr>
				<tr>
					<td><?php _e('RSS feed URL:', 'kbwidgets'); ?> </td>
					<td><input style="width: 400px;" id="kbrss-url-<?php echo "$number"; ?>" name="kbrss-url-<?php echo "$number"; ?>" type="text" value="<?php echo $url; ?>" /></td>
				</tr>
				<tr>
					<td><?php _e('RSS icon URL (optional):', 'kbwidgets'); ?> </td>
					<td><input style="width: 400px;" id="kbrss-icon-<?php echo $number; ?>" name="kbrss-icon-<?php echo $number; ?>" value="<?php echo $icon; ?>" /></td>
				</tr>
				<tr>
					<td><?php _e('Number of items to display:', 'kbwidgets'); ?> </td>
					<td><select id="kbrss-items-<?php echo $number; ?>" name="kbrss-items-<?php echo $number; ?>"><?php for ( $i = 1; $i <= KBRSS_MAXITEMS; ++$i ) echo "<option value='$i' ".($items==$i ? "selected='selected'" : '').">$i</option>"; ?></select></td>
				</tr>
				<tr>
					<td>Link title to feed URL? </td>
					<td><input type="checkbox" name="kbrss-linktitle-<?php echo $number; ?>" id="kbrss-linktitle-<?php echo $number; ?>" value="link" <?php if ( "link" == $linktitle ) { echo 'checked="checked"'; } ?> /> </td>
				</tr>
				<tr>
					<td>Convert feed to UTF-8?</td>
					<td><input type="checkbox" name="kbrss-utf-<?php echo $number; ?>" id="kbrss-utf-<?php echo $number; ?>" value="utf" <?php if ( "utf" == $utf ) { echo 'checked="checked"'; } ?> /> </td>
				</tr>
				</table>
				
				<p> &nbsp; </p>
				
				<p><strong>Advanced Options</strong><br /><small>Use the default settings to make your feed look like it would using the built-in RSS widget. To customize, use the advanced fields below.<br />Visit the <a href="http://wordpress.org/extend/plugins/kb-advanced-rss-widget/instructions/">KB Advanced RSS page</a> for tips and support.</small></p>
				<p style="text-align:center;"><?php _e('What HTML should precede the feed? (Default: &lt;ul&gt;)', 'kbwidgets'); ?></p>
				<input style="width: 680px;" id="kbrss-output_begin-<?php echo "$number"; ?>" name="kbrss-output_begin-<?php echo "$number"; ?>" type="text" value="<?php echo $output_begin; ?>" />
				<p style="text-align:center;"><?php _e('What HTML should follow the feed? (Default: &lt;/ul&gt;)', 'kbwidgets'); ?></p>
				<input style="width: 680px;" id="kbrss-output_end-<?php echo "$number"; ?>" name="kbrss-output_end-<?php echo "$number"; ?>" type="text" value="<?php echo $output_end; ?>" />
				<p style="text-align:center;"><?php _e("How would you like to format the feed's items? Use <code>^element$</code>. Default:", 'kbwidgets'); ?><br /><small><code>&lt;li&gt;&lt;a href='^link$' title='^description$'&gt;^title$&lt;/a&gt;&lt;/li&gt;</code></small></p>
				<textarea style="width:680px;height:50px;" id="kbrss-output_format-<?php echo "$number"; ?>" name="kbrss-output_format-<?php echo "$number"; ?>" rows="3" cols="40"><?php echo $output_format; ?></textarea>
				<input type="hidden" id="kbrss-submit-<?php echo "$number"; ?>" name="kbrss-submit-<?php echo "$number"; ?>" value="1" />
	<?php
	}

	function widget_kbrss_setup() {
		$options = $newoptions = get_option('widget_kbrss');
		if ( isset($_POST['kbrss-number-submit']) ) {
			$number = (int) $_POST['kbrss-number'];
			if ( $number > KBRSS_HOWMANY ) $number = KBRSS_HOWMANY;
			if ( $number < 1 ) $number = 1;
			$newoptions['number'] = $number;
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_kbrss', $options);
			widget_kbrss_register($options['number']);
		}
	}

	function widget_kbrss_page() {
		$options = $newoptions = get_option('widget_kbrss');
	?>
		<div class="wrap">
			<form method="POST">
				<h2>KB Advanced RSS Feed Widgets</h2>
				<p style="line-height: 30px;"><?php _e('How many KB Advanced RSS widgets would you like?', 'kbwidgets'); ?>
				<select id="kbrss-number" name="kbrss-number" value="<?php echo $options['number']; ?>">
	<?php for ( $i = 1; $i <= KBRSS_HOWMANY; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
				</select>
				<span class="submit"><input type="submit" name="kbrss-number-submit" id="kbrss-number-submit" value="<?php _e('Save'); ?>" /></span></p>
			</form>
		</div>
	<?php
	}

	function widget_kbrss_register() {
		global $wp_version;
		$options = get_option('widget_kbrss');
		$number = $options['number'];
		if ( $number < 1 ) $number = 1;
		if ( $number > KBRSS_HOWMANY ) $number = KBRSS_HOWMANY;
		for ($i = 1; $i <= KBRSS_HOWMANY; $i++) {
			$name = array('KB Advanced RSS %s', null, $i);
			if ( '2.2' == $wp_version ){
				register_sidebar_widget($name, $i <= $number ? 'widget_kbrss' : /* unregister */ '', '', $i);
				register_widget_control($name, $i <= $number ? 'widget_kbrss_control' : /* unregister */ '', 700, 580, $i);
			}elseif ( function_exists( 'wp_register_sidebar_widget' ) ){	// we're using v2.2.1+ here
				$id = "kb-advanced-rss-$i"; // Never never never translate an id
				$dims = array('width' => 700, 'height' => 580);
				$class = array( 'classname' => 'widget_kbrss' ); // css classname
				$name = sprintf(__('KB Advanced RSS %d'), $i);
				wp_register_sidebar_widget($id, $name, $i <= $number ? 'widget_kbrss' : /* unregister */ '', $class, $i);
				wp_register_widget_control($id, $name, $i <= $number ? 'widget_kbrss_control' : /* unregister */ '', $dims, $i);
			}else{ // pre-2.2 (widgets as a plugin)
				register_sidebar_widget($name, $i <= $number ? 'widget_kbrss' : /* unregister */ '', $i);
				register_widget_control($name, $i <= $number ? 'widget_kbrss_control' : /* unregister */ '', 700, 580, $i);
			}
		}
	
		add_action('sidebar_admin_setup', 'widget_kbrss_setup');
		add_action('sidebar_admin_page', 'widget_kbrss_page');

		if ( is_active_widget('widget_kbrss') )
			add_action('wp_head', 'widget_kbrss_head');
	}

	function widget_kbrss_head() {
	?>
		<style type="text/css">a.kbrsswidget img{background:orange;color:white;}</style>
	<?php
	}

	widget_kbrss_register();

}

// add a filter for troubleshooting feeds
function widget_kbrss_troubleshooter(){
	global $userdata;
	if ( !($_GET['kbrss']) )
		return;

	if ( $userdata->user_level >= 7 ){	// that ought to do it
		if ( file_exists(ABSPATH . WPINC . '/rss.php') )
			require_once(ABSPATH . WPINC . '/rss.php');
		else
			require_once(ABSPATH . WPINC . '/rss-functions.php');
		$rss = @fetch_rss($_GET['kbrss']);
		$out = "<html><head><title>KB RSS Troubleshooter</title></head><body><div style='background:#cc0;padding:1em;'><h2>KB Advanced RSS Troubleshooter</h2><p>Below, you should see the feed as Wordpress passes it to the KB Advanced RSS widget.</p></div><pre>";
		$out .= htmlspecialchars( print_r($rss->items, true) );
		$out .= "</pre></body></html>";
		print $out;
		die;
	}else{
		print "<p>You must be logged in as an administrator to troubleshoot feeds.</p>";
		die;
	}
	return;
}

add_action('widgets_init', 'widget_kbrss_init');
add_action('template_redirect', 'widget_kbrss_troubleshooter');

?>