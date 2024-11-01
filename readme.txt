=== Plugin Name ===
Contributors: saquery.com
Donate link: http://saquery.com/wordpress/
Tags: Log incoming queries, searchengines, widget, seo
Requires at least: 3.0
Tested up to: 3.1.3
Stable tag: 2.2.7

Another form of keyword tagging...

== Description ==

This Plugin logs incoming queries from searchengines like Bing, Google and Yahoo. The last requests can be displayed via a sidebar widget. All keywords must be moderated before they appear in the widget. The Plugin will serve dynamicly a Tag-Search-Page for each Keyword Phrase wich is linked with the appropriate entry in the widget. This page will list all related posts regarding to the submited keywords. 
So far a complete translation is available in English, German, French, Russian and Arab. 

A sample page search can be found at <a href="http://saquery.com/tags/javascript-execution-context" target="_blank">http://saquery.com/tags/javascript-execution-context</a>.

== Changelog ==
<ul>
	<span>2.2.7</span>
	<li>Fixed Html Error in Sidebar Widget!</li>
</ul>
<ul>
	<span>2.2.6</span>
	<li>Global notification respectively errormessage in Worpdress Admin Backend Settings area if the wp-seo-tags-template.php file is missing. It is important that this file can be located in your current theme folder.</li>
	<li>Improved moderation overview table in layout and a better human readable date time information for the last visits.</li>
</ul>
<ul>
	<span>2.2.5.5</span>
	<li>David figured out a problem with french language. This problem is fixed since version 2.2.5.5.</li>
</ul>
<ul>
	<span>2.2.5.4</span>
	<li>Fixed missing readme problem.</li>
</ul>

<ul>
	<span>2.2.5.3</span>
	<li>Fixed language support problem.</li>
</ul>

<ul>
	<span>2.2.5.2</span>
	<li>WP SEO TAGS is proud to announce that this plugin is now available in one more language. 
	Thanks goes to Muhammad from Egypt. He added a language translation for Arabic (ar).</li>
</ul>

<ul>
	<span>2.2.5.1</span>
	<li>Added an errormessage to Admin' s Backend if the template file could not be found.</li>
</ul>

<ul>
	<span>2.2.5</span>
	<li>Tested up to: 3.1.2 (fixed info in Plugin' s readme.txt).</li>
</ul>
<ul>
	<span>2.2.4.2</span>
	<li>Added a Donate Button to make it possible to support the development.</li>
	<li>Added a Help Link to get support from development.</li>
</ul>

<ul>
	<span>2.2.4</span>
	<li>Fixed: Undefined index: HTTP_REFERER.</li>
</ul>

<ul>
	<span>2.2.3</span>
	<li>Update: Sidebarwidget compatible to Wordpres >= 2.8.</li>
</ul>

<ul>
	<span>2.2.22</span>
	<li>Fixed Sidebar Widget display problem.</li>
</ul>

<ul>
	<span>2.2.21</span>
	<li>Fixed usage of an obsolete Wordpress Function Parm.</li>
</ul>

<ul>
	<span>2.2.2</span>
	<li>Fixed a display problem with dark Wordpress themes.</li>
</ul>

<ul>
	<span>2.2.1</span>
	<li>Fixed html structure for Sidebar Widget</li>
</ul>

<ul>
	<span>2.2</span>
	<li>The rules relating to the keyword length have been relaxed. From version 2.2, key words with a character length of 3 or mroe will be considered.</li>
	<li>Sample: <a href="http://saquery.com/tags/sql" target="_blank">Query SQL on saquery.com</a></li>
</ul>

<ul>
	<span>2.1</span>
	<li>Rules regarding the max keyword length were relaxed. The minimum keyword length is now 3.</li>
	<li>Fixed a bug regarding the permalinked keywords in searchresult.</li>
</ul>

<ul>
	<span>2.1</span>
	<li>Search engines will be logged now even when visitors land on a tags search page.</li>
	<li>Fixed a bug regarding the permalinked keywords in searchresult.</li>
</ul>

<ul>
	<span><strong>2.0</strong></span>
	<li>Searchresult is now sorted if relevance level is available.</li>
	<li>Improvements in speed.</li>
	<li>setup_postdata does work. I' ve fixed my bug to deal with it.</li>
</ul>

<ul>
	<span>1.9.9.5</span>
	<li>Seems that there a little problem with setup_postdata. I' ve deactived the output to avoid dublicate content.</li>
</ul>

<ul>
	<span>1.9.9.4</span>
	<li>Fixed the ugly empty page title bug.</li>
</ul>

<ul>
	<span>1.9.9.3</strong></span>
	<li>Searchresults for keywords has been improved significantly.</li>
</ul>
 
<ul>
	<span>1.9.9.2</span>
	<li>Marcis G. from <a href=""http://pc.de/"">http://pc.de</a> added support for Belarusian.</li> 
	<li>Tested up to: Wordpress 3.0.</li>
</ul>

<ul>
	<span>1.9.9.1</span>
	<li>Orktos recently fixed a bug with cyrillic letters in urls. He also added support for Russian language. Thanks a lot!</li>
</ul>


== Installation ==

This section describes how to install the plugin and get it working.

   1. Upload wp-seo-tags.php to the /wp-content/plugins/wp-seo-tags/ directory.
   2. <strong style="color:red;">Upload wp-seo-tags-template.php to the active theme directory /wp-content/themes/…/ of your Wordpress blog.</strong>
   3. Activate the plugin through the ‘Plugins’ menu in WordPress
   4. Be sure to make use of the WP SEO TAGS Sidebar Widget which is available after activation of plugin.
   5. Open Yahoo or Google to simulate a visitor. Search and visit your website.
   6. Moderate the incoming searchquery.
   7. Thats it! You should see a new item in the “Latest Queries” widget. Try the link to be shure your template file works.
   
   Take a look at the <a href="http://wordpress.org/extend/plugins/wp-seo-tags/screenshots/">Screenshots</a> to see the results.

== Frequently Asked Questions ==

= I got an error while visiting http://myBLOG.com/tags/xyz =

If you got error messages like 
Warning: require_once(/.../wp-content/themes/.../wp-seo-tags-template.php) [function.require-once]: failed to open stream: No such file or directory in /.../wp-includes/theme.php on line 822
Fatal error: require_once() [function.require]: Failed opening required '.../wp-content/themes/.../wp-seo-tags-template.php' (include_path='.:/usr/share/php:..') in /.../wp-includes/theme.php on line 822

You have missed to upload the wp-seo-tags-template.php to your theme folder. Edit it to be shure it fits to your layout!

= Why is the layout of the new Tag-Search-Page so strange? =

You need to edit the wp-seo-tags-template.php to be shure it fits to your layout. 

== Screenshots ==

1. Administration
2. Sidebarwidget
3. WP SEO Tags - Tag-Search-Page
4. Other relevant search queries