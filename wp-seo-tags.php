<?php
/*
Plugin Name: WP SEO Tags
Plugin URI: http://www.saquery.com/wordpress/wp-seo-tags
Description: WP SEO Tags
Version: 2.2.7
Author: Stephan Ahlf
Author URI: http://saquery.com
*/
	global $wpseotags_preview;
	$wpseotags_preview = false;
	$wpdb->wpseotags_dbTable = $wpdb->prefix."wpseotags_referer";
	$saqClickCheck_db_version = "0.1";

	function saq_wpseotags_DBSetup() {
		global $wpdb;
		$sql="CREATE TABLE IF NOT EXISTS ".$wpdb->wpseotags_dbTable."(
			q 		varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
			blogtarget	varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
			target 		varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
			hits 		INT(11) NOT NULL default 1,
			dt 		TIMESTAMP NOT NULL default CURRENT_TIMESTAMP, 
			moderated 	INT(1) NOT NULL default 0, 
			deleted 	INT(1) NOT NULL default 0, 
			engine 		varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
			PRIMARY KEY (q)
		) ENGINE = MYISAM CHARACTER SET ascii COLLATE ascii_general_ci";
      		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      		dbDelta($sql);

		$wpdb->hide_errors();
		$sql="CREATE FULLTEXT INDEX post_related ON ".$wpdb->posts."(post_title,post_content);"; 
		$wpdb->query($sql);
		$wpdb->show_errors();
	}
	if(isset($_GET['activate']) && $_GET['activate'] == 'true')
	add_action('init', 'saq_wpseotags_DBSetup');

	if (function_exists('load_plugin_textdomain')) {
		if ( !defined('WP_PLUGIN_DIR') ) {
			load_plugin_textdomain('wp-seo-tags', str_replace( ABSPATH, '', dirname(__FILE__) ));
		} else {
			load_plugin_textdomain('wp-seo-tags', false, dirname(plugin_basename(__FILE__)));
		}
	}
	function saq_wpseotags_is_keyword() {
		return (strtolower(substr($_SERVER["REQUEST_URI"], 1, 4))=="tags") ;
	}

	function saq_wpseotags_keywords_parseQuery() {
		if (saq_wpseotags_is_keyword()) {
			global $wp_query, $wpdb;
			$wp_query->is_single = false;
			$wp_query->is_page = false;
			$wp_query->is_archive = false;
			$wp_query->is_search = false;
			$wp_query->is_home = false;
			$arr = explode("/",strtolower($_SERVER["REQUEST_URI"]));
			$keys = $arr[2];

			$sql = "select count(*) from ".$wpdb->wpseotags_dbTable." where target = '$keys' and moderated = 1 and deleted = 0";
			$cnt=$wpdb->get_var($sql);	

			if ($cnt>0 || current_user_can('edit_users')){
				if($cnt==0)$wp_query->saq_wpseotags_preview=true;
				$wp_query->is_search = true;
				add_action('template_redirect', 'saq_wpseotags_keywords_includeTemplate');
			}
		}
	}

	function saq_wpseotags_keywords_includeTemplate() {
		if (saq_wpseotags_is_keyword()) {
			//add_filter('wp_title', 'saq_wpseotags_title');
			add_filter('the_title', 'saq_wpseotags_title');
			//add_filter('wp_footer', 'saq_wpseotags_add_footer');
			$template = TEMPLATEPATH . "/wp-seo-tags-template.php";
			load_template($template);
			exit;
		}
		return;
	}

	function saq_wpseotags_title(){
		global $searchphrase, $blogname;
		$result = str_replace("%blogname%",get_bloginfo('name'),saq_('Search for %searchphrase% on %blogname%'));
		$result = str_replace("%searchphrase%",saq_wpseotags_getTitleKeyWords(),$result);
		$result = urldecode($result);
		remove_filter('the_title', 'saq_wpseotags_title');
		return $result;
	}

	function saq_wpseotags_logSearchEngineQuery(){
		global $wpdb;

		if (isset ($_SERVER['HTTP_REFERER'])) {
			$engines['google.'] = 'q=';
			$engines['altavista.com'] = 'q=';
			$engines['search.msn.'] = 'q=';
			$engines['yahoo.'] = 'p=';
			$engines['bing.'] = 'q=';
			$engines['yandex.'] = 'text=';

			$referer = $_SERVER['HTTP_REFERER'];
			$blogtarget = $_SERVER["REQUEST_URI"];
			$ref_arr = parse_url("$referer");
			$ref_host = $ref_arr['host'];

			foreach($engines as $host => $skey){
				if (strpos($ref_host, $host) !== false){
					
					$res_query = urldecode($ref_arr['query']);
					if (preg_match("/{$engines[$host]}(.*?)&/si",$res_query."&",$matches)){
						$query = trim($matches[1]);
						$target = str_replace("'","''",str_replace(";","",sanitize_title_with_dashes($query)));
						$sql= "insert into ".$wpdb->wpseotags_dbTable.
							"(q, blogtarget, target,engine) VALUES ('$query', '$blogtarget','$target', '$ref_host')  ON duplicate KEY UPDATE hits=hits+1, DT=CURRENT_TIMESTAMP";
						$msg="SearchEngineRedirect successful logged.";
						try { 
							$result = $wpdb->query($sql); 
							$sql = "select count(*) from ".$wpdb->wpseotags_dbTable." where moderated = 1 and target = '$target'";
							$cnt=$wpdb->get_var($sql);	
							if ($cnt>0){
								$sql = "update ".$wpdb->wpseotags_dbTable." set moderated = 1 where moderated = 0 and target = '$target'";
								$result = $wpdb->query($sql); 
							}
							$sql = "select count(*) from ".$wpdb->wpseotags_dbTable." where deleted = 1 and target = '$target'";
							$cnt=$wpdb->get_var($sql);	
							if ($cnt>0){
								$sql = "update ".$wpdb->wpseotags_dbTable." set deleted = 1 where deleted = 0 and target = '$target'";
								$result = $wpdb->query($sql); 
							}
						} catch (Exception $e) { 
							$msg=$e->getMessage(); 
						}
						echo "\n<!-- WP SEO Tags : $parm ; http://saquery.com/wp-seo-tags/ -->\n" ;
					}
					break;
				}
			}  
		}
	}

	function saq_wpseotags_add_meta(){
		if (saq_wpseotags_is_keyword()) {
			echo 	"\n<!-- WP SEO Tags - http://www.saquery.com/ -->" .
				"\n<meta name=\"keywords\" content=\"" . urldecode(saq_wpseotags_getMetaKeyWords()) . "\" />".
				"\n<link rel=\"canonical\" href=\"".get_bloginfo('url')."/tags/".saq_wpseotags_getMetaKeyWords("-")."/\" />".
			 	"\n<!-- // WP SEO Tags -->\n";
		}
		saq_wpseotags_logSearchEngineQuery();
	}

	function saq_wpseotags_add_footer(){

	}

	add_action('wp_head', 'saq_wpseotags_add_meta', 0);


	//print_radd_cacheaction("add_cacheaction", "saq_wpseotags_add_meta");

	function saq_keywords_createRewriteRules($rewrite) {
		global $wp_rewrite;
	
		$KEYWORDS_QUERYVAR = "tags";
		// add rewrite tokens
		$keytag_token = '%' . $KEYWORDS_QUERYVAR . '%';
		$wp_rewrite->add_rewrite_tag($keytag_token, '(.+)', $KEYWORDS_QUERYVAR . '=');
	    
		$keywords_structure = $wp_rewrite->root . $KEYWORDS_QUERYVAR . "/$keytag_token";
		$keywords_rewrite = $wp_rewrite->generate_rewrite_rules($keywords_structure);
		try { 
			//print_r($rewrite );
			if ( gettype($rewrite) == "array" && gettype($keywords_rewrite) == "array" ) {
				return ( $rewrite + $keywords_rewrite );
			} else {
				return $rewrite;
			}
//			return array_merge($rewrite,$keywords_rewrite);
		} catch (Exception $e) { 
		}
	}
	add_action('parse_query', 'saq_wpseotags_keywords_parseQuery');
	add_filter('generate_rewrite_rules', 'saq_keywords_createRewriteRules');

	function saq_($txt){
		return __($txt,'wp-seo-tags');
	}
	
	function saq_wpseotags_get_template_folder(){
		$url = get_bloginfo("template_url");
		$temp = explode("wp-content/themes/",$url);
		$active_theme_name = $temp[1];	// The second value will be the theme name
		return str_replace("\\", "/", get_theme_root()."/".$active_theme_name) ;
	}
	
	
	function saq_wpseotags_admin_notices() {
		settings_errors( 'global' );
		$templatePath = saq_wpseotags_get_template_folder()."/";
		$templateFile = "wp-seo-tags-template.php";
		$fn = $templatePath.$templateFile;
		
		if (!is_file($fn)){
		$msg = "WP SEO TAGS - ERROR! <br /><br />The file ".$fn." could not be found. Please upload a copy of this file from the zip archive located at <a href=\"http://wordpress.org/extend/plugins/wp-seo-tags/\">http://wordpress.org/extend/plugins/wp-seo-tags/</a> to your server.";
			add_settings_error('wp_seo_tags_tamplate_error',esc_attr('wpseotagstemplatetest'),$msg);
		}
	}
	
	
	
	function saq_wpseotags_adminoptions1(){
		global $wpdb;


		if (isset($_POST['delete'])){
			$sql= "update ".$wpdb->wpseotags_dbTable." set deleted = 1 where target in "."('".implode("','",array_keys($_POST))."')";
			$res = $wpdb->get_results($sql);
		}
		if (isset($_POST['unmoderate'])){
			$sql= "update ".$wpdb->wpseotags_dbTable." set moderated = 0 where target in "."('".implode("','",array_keys($_POST))."')";
			$res = $wpdb->get_results($sql);
		}
		if (isset($_POST['undelete'])){
			$sql= "update ".$wpdb->wpseotags_dbTable." set deleted = 0 where target in "."('".implode("','",array_keys($_POST))."')";
			$res = $wpdb->get_results($sql);
		}
		if (isset($_POST['submit'])){
			$sql= "update ".$wpdb->wpseotags_dbTable." set moderated = 1 where target in "."('".implode("','",array_keys($_POST))."')";
			$res = $wpdb->get_results($sql);
		}

		echo 
		'<script>
		function CkAllNone(obj,id){
			var _a=document.getElementById(id).getElementsByTagName("INPUT");
			for (var i=0;i<_a.length;i++) _a[i].checked=obj.checked;
		}
(function($){
	function prettyDate(time){
		var date = new Date((time || "").replace(/-/g,"/").replace(/[TZ]/g," ")),
			diff = (((new Date()).getTime() - date.getTime()) / 1000),
			day_diff = Math.floor(diff / 86400);
				
		if ( isNaN(day_diff) || day_diff < 0 || day_diff >= 31 )
			return;
				
		return day_diff == 0 && (
				diff < 60 && "just now" ||
				diff < 120 && "1 minute ago" ||
				diff < 3600 && Math.floor( diff / 60 ) + " minutes ago" ||
				diff < 7200 && "1 hour ago" ||
				diff < 86400 && Math.floor( diff / 3600 ) + " hours ago") ||
			day_diff == 1 && "Yesterday" ||
			day_diff < 7 && day_diff + " days ago" ||
			day_diff < 31 && Math.ceil( day_diff / 7 ) + " weeks ago";
	}

	if ( typeof jQuery != "undefined" )
	jQuery.fn.prettyDate = function(){
		return this.each(function(){
			var date = prettyDate($(this).text());
			if ( date )
				jQuery(this).html( date + " - <i>"+$(this).text()+"</i>" );
		});
	};
	$(function(){
		$(".wp-seo-tags-date-time").prettyDate();
		$("#wp-seo-tags-donation-box").fadeIn(10000);
	});
})(jQuery);		
		</script>';
		$where = "where deleted = 0 and moderated = 0";

		if (isset($_POST['showmoderated'])){
			$where = "where deleted = 0 and moderated=1";
		}
		if (isset($_POST['showdeleted'])){
			$where = "where deleted = 1";
		}
		if (isset($_POST["saq_txt_the_filter"])){
			$where .= " and target like '%".$_POST["saq_txt_the_filter"]."%'";
		}

		$query = "SELECT target, moderated, deleted, max(dt) as dt from ".$wpdb->wpseotags_dbTable ." $where group by target, moderated, deleted order by moderated asc, dt desc";
		$res = $wpdb->get_results($query);
		echo '<div name=\"top\" id="icon-tools" class="icon32"><br></div>
		<h2>'.saq_('Moderate incoming searchengine keywords').'</h2>
		<div class="form-wrap">
		<p><strong><br />'.saq_('Tip').':</strong><br />'.saq_('You can check if new incoming search queries deliver results in WP SEO Tag Search. Only Keyswords with a length of more than 3 letters will be considered').'.</p>
		<hr />
		';

		echo '<div id="wp-seo-tags-donation-box" style="display:none;" class="update-nag"><br />
		<p><strong>Thank you for using wp-seo-tags! </strong><span style="color:#990000;">If you think this software is useful please support the development with a donation or <a target="_blank" href="http://wordpress.org/extend/plugins/wp-seo-tags/">rate this software</a>.</span><br />Feel free to contact me for support at <a href="http://saquery.com/wordpress/wp-seo-tags/" target="_blank" >http://saquery.com/wordpress/wp-seo-tags/</a><br />
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
		
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="hosted_button_id" value="SQRUU7JKE7KFS">
		<input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110429-1/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
		<img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110429-1/de_DE/i/scr/pixel.gif" width="1" height="1">
		</form>
		</div>
<style>		
.wp-seo-tags
{
	
	margin-top:11px;
	width: 100%;
	border-collapse: collapse;
	text-align: left;
}
.wp-seo-tags th
{
	font-size: 14px;
	font-weight: bold;
	padding: 10px 8px;
	border-bottom: 2px solid #6678b1;
}
.wp-seo-tags td
{
	border-bottom: 1px solid #ccc;
	padding: 6px 8px;
	vertical-align:top;
	background-color:#fff;
}
.wp-seo-tags tbody tr:hover td
{
	color: #009;
}

</style>
		';
		echo "<form id=\"frm_wpseo\" action=\"\" method=\"post\">";
		echo "<table class=\"wp-seo-tags\">";
		echo "<tr><th>".saq_('Selection')."</th>";
		echo "<th>URL</th>";
		echo "<th>".saq_('Last Searchengine Visitor')."</th>";
		echo "<th>".saq_('Moderated')."</th>";
		echo "<th>".saq_('Deleted')."</th>";
		echo "</tr>";
		foreach($res as $row) {
			echo "<tr>";
			echo "<td><input type=\"checkbox\" name=\"$row->target\" value=\"1\" /></td>";
			$lnk = "<a title=\"Test new WP SEO Tag Search URL...\" href=\"".get_bloginfo('url')."/tags/".$row->target."\" target=\"_blank\">$row->target</a>";
			$lnk = urldecode($lnk);
			echo "<td>$lnk</td>";
			echo "<td class=\"wp-seo-tags-date-time\">".$row->dt."</td>";
			if ($row->moderated==1){
				$chk = "<input type=\"checkbox\" disabled=\"disabled\" checked=\"checked\" />";
			} else {
				$chk = "<input type=\"checkbox\" disabled=\"disabled\" />";
			}
			echo "<td>$chk</td>";
			if ($row->deleted==1){
				$chk = "<input type=\"checkbox\" disabled=\"disabled\" checked=\"checked\" />";
			} else {
				$chk = "<input type=\"checkbox\" disabled=\"disabled\" />";
			}
			echo "<td>$chk</td>";
			echo "</tr>";
		}
		$_btn = "<input type=\"submit\" style=\"width:170px;\"";
		$_txt = "<input type=\"text\" style=\"width:130px;\"";
		$delim="&#32;&#187;&#32;";
		echo "</table>";
		echo "<input type=\"checkbox\" onclick=\"CkAllNone(this,'frm_wpseo');\" />".saq_('Select all items in list').".<hr />";
		echo "$_btn name=\"submit\" value=\"".saq_('Moderate')."\" onclick=\"return confirm('".saq_('Mark selected URLs as moderated now')."?');\" />$delim";
		echo "$_btn name=\"delete\" value=\"".saq_('Delete')."\" onclick=\"return confirm('".saq_('Mark selected URLs as deleted now')."?');\" />$delim";
		echo "$_btn name=\"unmoderate\" value=\"".saq_('Remove moderation')."\" onclick=\"return confirm('".saq_('Mark selected URLs as unmoderated now')."?');\" />$delim";
		echo "$_btn name=\"undelete\" value=\"".saq_('Undelete')."\" onclick=\"return confirm('".saq_('Undelete selected URLs now')."?');\" />";
		echo "<hr />";
		echo "$_btn name=\"refresh\" value=\"".saq_('Show unmoderated')."\" />$delim";
		echo "$_btn name=\"showmoderated\" value=\"".saq_('Show moderated')."\" onclick=\"return confirm('".saq_('Show all moderated URLs now')."?');\" />$delim";
		echo "$_btn name=\"showdeleted\" value=\"".saq_('Show deleted')."\" onclick=\"return confirm('".saq_('Show all deleted URLs now')."?');\" />$delim";
		echo "Url-Text-Filter:$delim";
		echo "$_txt name=\"saq_txt_the_filter\" name=\"saq_txt_the_filter\"";
		if (isset($_POST["saq_txt_the_filter"])) echo " value=\"".$_POST["saq_txt_the_filter"]."\"";
		echo " />";
		echo "</form>";


		echo '</div></div>';
	}

	function saq_wpseotags_admin_menu(){
		add_submenu_page('options-general.php', 'WP SEO Tags options', 'WP SEO Tags', 'administrator', __FILE__, 'saq_wpseotags_adminoptions1');
		saq_wpseotags_admin_notices();
	}

	add_action('admin_menu', 'saq_wpseotags_admin_menu');

	function saq_array_insert(&$array, $insert, $position = -1) {
	     $position = ($position == -1) ? (count($array)) : $position ;
	     if($position != (count($array))) {
		  $ta = $array;
		  for($i = $position; $i < (count($array)); $i++) {
		       if(!isset($array[$i]))die();
		       $tmp[$i+1] = $array[$i];
		       unset($ta[$i]);
		  }
		  $ta[$position] = $insert;
		  $array = $ta + $tmp;
	     } else {
		  $array[$position] = $insert;         
	     }
		 
	     ksort($array);
	     return true;
	}

	function saq_wpseotags_strArrayFilter($val){ 
  		return (strlen($val)>2);
	}


	function saq_wpseotags_getMetaKeyWords($delimiter=",", $maxKeywordLength=-1){
		$arr = explode("/",strtolower($_SERVER["REQUEST_URI"]));
		$result=null;
		if ($maxKeywordLength==-1){
			$result=str_replace("-",$delimiter,$arr[2]);
		} else {
			$keys = explode("-",$arr[2]);
			$keys = array_filter($keys, "saq_wpseotags_strArrayFilter");
			$result = implode($delimiter, $keys);
		}
		return $result;
	}
	function saq_wpseotags_getTitleKeyWords(){
		$result="";
		$_orWord = saq_('or');
		$keys = explode(",",saq_wpseotags_getMetaKeyWords(",",3));//explode("-", $arr[2]);
		if (count($keys)>1) saq_array_insert($keys, $_orWord, count($keys)-1);
		foreach($keys as $item){
			if ($item!=$_orWord) $item = ucfirst($item);
			$result .= $item." ";
		}
		return trim($result);
	}

	 function saq_wpseotags_sortByLength($a,$b){
		 if($a == $b) return 0;

		 return (count(explode(" ",$a)) > count(explode(" ",$b)) ? -1 : 1);
	 }

	function saq_wpseotags_permutations($end, $start = 0){
		if($start == $end)return array(array($end));
		if($start > $end)list($start, $end) = array($end, $start);
		$rtn = array(array($start));
		for($i = $start + 1; $i <= $end; $i++){
			$temp = array();
			foreach($rtn as $k => $v) for($j = 0; $j <= count($v); $j++)$temp[] = saq_wpseotags_array__insert($v, $i, $j);
			$rtn = $temp;
		}
		return array_reverse($rtn);
	}

	function saq_wpseotags_array__insert($array, $num, $pos){
		foreach($array as $k => $v){
			if($k == $pos)$rtn[] = $num;
			$rtn[] = $v;
		}
		if($k < $pos) $rtn[] = $num;
		return $rtn;
	}

	function saq_wpseotags_word_strings($in){
		$words = preg_split('/\s+/', $in);
		$perms = saq_wpseotags_permutations(($c = count($words)) - 1);
		$code = 'foreach($perms as $v)$rtn[] = implode(" ", array(';
		$comma = '';
		for($i = 0; $i < $c; $i++){
			$code .= $comma.'$words[$v['.$i.']]';
			$comma =',';
		}
		$code .= '));';
		eval($code);
		return $rtn;
	}

	function saq_wpseotags_keyWordCombo($array){
		$results = array(array());
		foreach ($array as $element)
			foreach ($results as $combination)
				array_push($results,array_merge(array($element),$combination));
		$p = array(); 
		foreach($results as $combo) if (implode(" ", $combo)!="") array_push($p, implode(" ", $combo))  ;

		$r = array(); 		
		foreach ($p as $i1) {
//			array_push($r, $i1);
			$k = saq_wpseotags_word_strings($i1);
			foreach($k as $i2) array_push($r, $i2);
		}


		usort($r,"saq_wpseotags_sortByLength");
		return $r;

	} 

	//ob_start();
	
	function saq_wpseotags_the_excerpt($txt, $keys, $permaLink){
		$result = $txt;
		//FB::info($txt);
		//FB::info($keys);
		//FB::info($permaLink);
		//FB::info("------------------");
		for($i = 0; $i < count($keys); $i++){
			$hits=0;
			$key=trim($keys[$i]);
			$replace = "<a href=\"".$permaLink."\">".$key."</a>";
			preg_match_all('#[^>]+(?=<[^/])|[^>]+$#', $result, $matches, PREG_SET_ORDER);
			foreach ($matches as $val) {
				$result = str_ireplace($val[0],str_ireplace($key,$replace,$val[0]),$result);
				$hits = $hits+1;
			}
		}
		//if (strlen($result)>300) $result = substr(0,200,$result);
		return $result;
	}

	function getFadedColor($pCol, $pPercentage) {
		$pPercentage = 100 - $pPercentage;
		$rgbValues = array_map( 'hexDec', str_split( ltrim($pCol, '#'), 2 ) );

		for ($i = 0, $len = count($rgbValues); $i < $len; $i++) {
			$rgbValues[$i] = decHex( floor($rgbValues[$i] + (255 - $rgbValues[$i]) * ($pPercentage / 100) ) );
		}

		return '#'.implode('', $rgbValues);
	}

	function saq_wpseotags_getSqlQuery($keys, $boolKeys, $allwaysIncludeTarget){
		global $wpdb;
     	$s=" SELECT *,
		MATCH (
		post_title, post_content
		)
		AGAINST (
		'".$keys."'
		) AS relevance
		FROM ".$wpdb->posts." 
		WHERE 
		(post_status = 'publish'
		AND MATCH (
		post_title, post_content
		)
		AGAINST (
		'".$keys."'
		IN BOOLEAN
		MODE
		))";
		if ($allwaysIncludeTarget) $s=$s." or post_name='".saq_wpseotags_getMetaKeyWords('-')."'";
		$s=$s." HAVING relevance > 0.1
		ORDER BY relevance DESC
		LIMIT 0 , 25";

		return $s;
	}

	function saq_wpseotags_getpost($r,$id){						
		reset($r);
		while (list($key, $post) = each($r)) if ($post->ID==$id) return $post;
	}
	
	function saq_wpseotags_printRealtedPosts(){
		//error_reporting(0);
		global $wpdb,$wp_query;
		$keys = str_replace(";","",str_replace("'","''",saq_wpseotags_getMetaKeyWords(" ",3)));
		$keys1 = "+".str_replace(",",", +",$keys);

	 	$sql2 = saq_wpseotags_getSqlQuery($keys, "\"".$keys."\"", false);
		$res = $wpdb->get_results($sql2);
		
		if (!$res){
		 	$sql2 = saq_wpseotags_getSqlQuery($keys, $keys1, false);
			$res = $wpdb->get_results($sql2);
			if (!$res){
				$keys1 = trim(str_replace("+","",$keys1));
			 	$sql2 = saq_wpseotags_getSqlQuery($keys, $keys1, true);
				$res = $wpdb->get_results($sql2);
			}
		}
		

		
		$res2=array();
		$_res=array();
		$maxScore=null;
		foreach ($res as $row){
			if ($maxScore==null) $maxScore=$row->relevance;
			$res2[$row->ID]=($row->relevance/$maxScore)*100;
			$_res[$row->ID] = $row->ID;
		}
		
		$posts=implode(",",array_keys($res2));	

		$_ids=implode(",",$_res);
		//print_r($_ids);
		/*
		*/
		$resultByTextSearch=null;
		if ($posts) {
			$args = array(
				'numberposts' => 25,
				'include' => $_ids
			); 
			$resultByTextSearch = get_posts($args);
		}  else {
			$posts = query_posts( array( 'post__in' => $res_2 ) );
		}

		if (!$resultByTextSearch){
			$args = array(
				'numberposts' => 25,
				'orderby' => 'date',
				'tag'=> saq_wpseotags_getMetaKeyWords()
			); 
			$attachments = get_posts($args);
		}

		$printed = array(); 
		$arr = explode("/",strtolower($_SERVER["REQUEST_URI"]));
		if ($arr[1]=="tags"){
			$keys=explode("-", $arr[2]);
			$_keys = array_filter($keys, "saq_wpseotags_strArrayFilter");
			usort($_keys,"saq_wpseotags_sortByLength");
			$_keys = array_unique($_keys);
			$_keys = saq_wpseotags_keyWordCombo(array_slice($_keys, 0, 5));

			$t=saq_wpseotags_getTitleKeyWords();
			$txt = str_replace("%URI%",get_bloginfo('url')."/tags/$arr[2]/", saq_("This is only a Preview! %URI% is not moderated! You can view this page because you have an administrative userrole"));
			if ($wp_query->saq_wpseotags_preview) echo "<i><p style=\"padding:5px;color:red\"><strong>$txt</strong></p></i>";

			$t=str_replace("%searchphrase%",$t, saq_('Looking for %searchphrase%'));
/*My*/
			$t=urldecode($t);
			echo "<h2>".$t."?</h2>";
			echo "<h3>".saq_('We have the following articles on these keywords for you')."...</h3>";
			
			global $post;
			//print_r($_keys);
			if ($resultByTextSearch) foreach ($_res as $POST_ID) {

				$post = saq_wpseotags_getpost($resultByTextSearch, $POST_ID);
				setup_postdata($post);

				$defaultColor = getFadedColor("#FFCC0", 20);

				if (!$printed[$post->ID] && $res2[$post->ID]>0){
					$permaLink = get_permalink();//get_permalink($post->ID);
					echo "<h3>";
					echo "<a ";
					if ($res2[$post->ID]==100) echo "style=\"color:red;\" ";
					echo "href=\"";the_permalink();echo "\">";the_title();echo "</a>";
					echo "</h3>";
					//echo $post->ID;
					if ($res2[$post->ID]==100) {
						echo "<p>";
					} else {
						echo "<p>";
					};

					echo saq_wpseotags_the_excerpt(get_the_excerpt(), $_keys, $permaLink) ;
					echo "</p>";
					echo '<p style="';
					if ($res2[$post->ID]==100) {
						echo 'font:bold;color:red;';
					} else {
						//echo 'background:'.getFadedColor("#FFCC0", $res2[$post->ID]).';';
					};
					echo 'font-size:xx-small;font-weight:bold;">'.saq_('Relevance').': '. number_format($res2[$post->ID],2).'&#37;</p>';
					echo "<hr />";
					$printed[$post->ID]=true;
				}
			}
			
			
			if ($attachments) foreach ($attachments as $post) {
				if (!$printed[$post->ID]){
					setup_postdata($post);
					echo "<h3>";
					echo "<a href=\"".get_permalink()."\">";the_title();echo "</a>";
					echo "</h3>";
					echo "<p>";
					echo saq_wpseotags_the_excerpt(get_the_excerpt(), $_keys, get_permalink());
					echo "</p><hr />";
					$printed[$post->ID]=true;
				}
			}
			
			

			$w = "target like '%".str_replace(" " , "%' or target like '%", str_replace(";","",str_replace("'","''",saq_wpseotags_getMetaKeyWords(" ",3))))."%'";
			$sql = "SELECT * FROM ".$wpdb->wpseotags_dbTable." WHERE target <> '".saq_wpseotags_getMetaKeyWords("-")."' and ($w) and moderated = 1 and deleted = 0
				order by hits desc, dt desc
				LIMIT 0 , 6";			

			echo "<h3>".saq_('Other relevant searches').":</h3><ul>";
			$res = $wpdb->get_results($sql);
			foreach ($res as $row){
				echo "<li>";
				echo "<a href=\"".get_bloginfo('url')."/tags/".$row->target."/\" target=\"_self\" title=\"".$row->engine." request\">".htmlspecialchars($row->q)."</a>&#32;(".$row->hits." hits)";
				echo "</li>";
			}
			echo "</ul>";
			echo '<p style="font-style:italic;font-size:xx-small;text-align:right;">';
			echo 'Powered by <a href="http://saquery.com/wordpress/wp-seo-tags/" target="_blank" title="saquery.com Wordpress Plugins">WP SEO Tags</a>';
			echo '</p>';
		}
	}

//widget	
	function saq_wpseotags_widget($args) {
		global $wpdb;
		extract($args);
		echo $before_widget;
		echo $before_title.saq_('Latest queries').$after_title;
		echo "<ul>";
		$sql=" SELECT * FROM ".$wpdb->wpseotags_dbTable." where moderated = 1 and deleted = 0 order by dt desc LIMIT 0 , 15";
		$rows=$wpdb->get_results($sql);	
		foreach($rows as $row){
			$t="Von ".str_replace("www.", "", htmlspecialchars($row->engine));
			$a = "<a href=\"".get_bloginfo('url')."/tags/".$row->target."\">".htmlspecialchars($row->q)."</a>";
			echo "<li>$a</li>";
		}
		echo "</ul>";

		echo $after_widget;

	}

	function register_saq_wpseotags_widget() {
		wp_register_sidebar_widget('saq_wpseotags_widget', 'WP Seo Tags Widget', 'saq_wpseotags_widget');
	}
	add_action('init', 'register_saq_wpseotags_widget');

?>