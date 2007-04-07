=== KB Advanced RSS Widget ===
Contributors: adamrbrown
Donate link: http://adambrown.info/b/widgets/
Tags: widget, rss, feeds
Requires at least: 2.0
Tested up to: 2.0
Stable tag: trunk

Similar to the default RSS widget, but gives you complete control over how RSS feeds are parsed for your sidebar.

== Description ==

The Sidebar Widgets plugin comes with an RSS widget, but you get no control over how the feed shows up in your sidebar. Want more control? The KB Advanced RSS widget gives it to you. With it, you can

* Decide which RSS fields to display (as opposed to the default RSS widget, which limits you to link and title), and
* Decide how to format the fields (it doesn't have to be a list if you don't want it to be).

Be aware that it's called "advanced" for a reason. You need to know some HTML to use this fully. Also, please note that this is a widget. That means you need to be using the [Wordpress sidebar widgets plugin](http://wordpress.org/extend/plugins/widgets/).

Instructions and examples are available at the [KB Advanced RSS plugin page](http://adambrown.info/b/widgets/kb-advanced-rss/).

= Support =

If you post your support questions as comments below, I probably won't see them. If the FAQs don't answer your question, you can post support questions at the [KB Advanced RSS plugin page](http://adambrown.info/b/widgets/kb-advanced-rss/) on my site.

== Installation ==

Because this plugin is a widget, you must have the Sidebar Widgets plugin installed and running for this plugin to work.

1. Upload `kb_advanced_rss.php` to the `/wp-content/plugins/widgets/` directory.
1. Activate the widget through the 'Plugins' menu in WordPress.
1. Add the new KB Advanced RSS widget to your sidebar through the 'Presentation => Sidebar Widgets' menu in WordPress. You'll find that the widget has several options, but only the first couple are required.

If you want more (up to 9) KB Advanced RSS widgets, scroll down and increase the allotment, just like you would with text or regular RSS widgets.

== Screenshots ==

You can see examples at the [KB Advanced RSS plugin page](http://adambrown.info/b/widgets/kb-advanced-rss/).

== Frequently Asked Questions ==

= What code do I need to place in my sidebar? =

None. This is a widget, so you need to have the [widgets plugin](http://wordpress.org/extend/plugins/widgets/) running and you need to be using a widgets-enabled theme. You control all options for KB Countdown from the widgets administration menu.

= Which fields are available in the feed? =

Look at the source code for the feed. But note that Wordpress parses feeds in ways that you might not expect. After you've installed my widget, you can add `?kbrss=http://path.to.feed/` to your blog's URL to see exactly which fields are available. (You'll need to be logged in to do this).

If you see that there is a field called `title` (there probably is), you would include this in your widget's output by writing `^title$`. You would probably want to wrap this in some HTML, like this: `<li>^title$</li>`.

= How do I trim the length of an RSS field? =

Easy. Say that some of the titles in your feed are really long. If you want to trim them all to 50 characters, you would write this: `^title%%50$`, using `%%` to separate the field name from the desired character length.

= Some of the available fields are arrays! =

No problem. If you want to access a specific element in the array--for example, the "url" element in the "media" array--you would write `^media=>url$`. If you want to loop through all the elements in the array--for example, the "categories" element from a Wordpress feed--you would write something like this: `^categories||<li>||</li>$`, which would enclose each category in `<li>` tags.

= I have a question that isn't addressed here. =

You may ask questions by posting a comment to the [KB Advanced RSS plugin page](http://adambrown.info/b/widgets/kb-advanced-rss/).