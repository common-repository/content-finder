<?php
/*
Plugin Name: Content Dragon
Plugin URI: http://contentdragon.com/word-press-plugin
Description: Quickly Add - Free Keyword Targeted Articles to your Word Press Blog.
Version: 1.0.2
Author: Aaron
Author URI: http://www.contentdragon.com
*/
set_time_limit(60);
//== For Article Harvest ====================================
function fc_nolines($content) { return(preg_replace("/[\n\r\t]/i", "", $content)); }
function fc_get_contents($url, $method, $post)
{
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_TIMEOUT, 25);
  curl_setopt($ch, CURLOPT_POST, $method);
  curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
  curl_setopt($ch, CURLOPT_VERBOSE, 0);
  curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
  if ($method == 1){ curl_setopt($ch, CURLOPT_POSTFIELDS, $post); }
  $result = curl_exec($ch);
  curl_close($ch);
  return ($result);
}

function fc_deformatURL($name)
{ return(trim(ucwords(preg_replace("/[\W\_]/", " ", urldecode($name))))); }

function fc_formatURL($name)
{ return(trim(strtolower(preg_replace("/[\W\_]/", "_", urldecode($name))))); }

function fc_getarticle($keyword)
{
	$htmllink = "http://www.contentdragon.com/searcharticles.php?keyword=".urlencode($keyword);
	$contents = fc_nolines(fc_get_contents($htmllink, 0, NULL));
	$reg = "[<id=]{4}[title]{5}[>](.*?)[<\/id=]{5}[title]{5}[>][<id=]{4}[sby]{3}[>](.*?)[<\/id=]{5}[sby]{3}[>][<id=]{4}[content]{7}[>](.*?)[<\/id=]{5}[content]{7}[>][<id=]{4}[url]{3}[>](.*?)[<\/id=]{5}[url]{3}[>]";
	preg_match_all("/$reg/i", $contents, $articles);
	return($articles);
}
//-----------------------------------------------------------

//-- Hook the fishy
add_action('admin_menu', 'fc_add_findcontent_page');

//-- Stick the fish in the basket
function fc_add_findcontent_page()
{ add_submenu_page('post-new.php', 'Find Content', 'Find Content', 8, 'find_content_page', 'fc_findcontent_main'); }

//-- Its a keeper
function fc_insert_contentpost($post_title, $post_content, $post_category)
{
	global $userdata;
  get_currentuserinfo();
	$post_author = $userdata->user_ID;
	$post_status = 'draft';
	$post_category = split("," , $post_category);
	foreach($post_category as $key=>$val) $post_category[$key] = get_cat_ID($val);

	$post_date = current_time('mysql');
	$post_date_gmt = current_time('mysql', 1);
	$post_data = compact('post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_category', 'post_status');
	$post_ID = wp_insert_post($post_data);
	
	if (!$post_ID) return(false); else return($post_ID);
}

//-- Start the fillet-o fish
function fc_findcontent_main()
{	
	if ($_POST['fc_save_draft'] == "Add to Drafts")
	{
		extract($_POST);
		$postID = fc_insert_contentpost($article_title, $article_body, "");
		if (!$postID)
			echo '<h2>Failure!</h2><div style="padding-left: 10px;">failed while saving "'.$article_title.'" in to your drafts!</div>';
		else
		{
			// Do NOT Remove This! Terms and Conditions PROHIBIT doing so
			$emailofsite = get_option('admin_email'); 
			$websiteurl = get_option('siteurl');
			$urllocationofarticle = $websiteurl."/?p=".$postID;
			$published = 0;
			$post = "keyword=$searcherkeyword&articlename=$article_title&websiteurl=$websiteurl&emailofsite=$emailofsite"
			. "&urllocationofarticle=$urllocationofarticle&published=$published";
			$success = fc_get_contents("http://www.contentdragon.com/updatelog.php", 1, $post);
			// --
			
			echo '<br><br><h2>Success!</h2><div style="padding-left: 10px;">saved "<b>'.$article_title.'</b>" in to your drafts!'
			. '<br><br>This draft was saved with no category set.'
			. '<br><br><h2>By publishing this  article you agree to the following terms - </h2>
			<p><br />
			</p>
			<ul>
				<li>
					<p>Respect the copyrights of the authors by publishing the entire article as it is with no changes.</p>
				</li>
				<li>
					<p>Agree not to change the title or content of the article in any way.</p>
				</li>
				<li>
					<p>Agree to make all links so that they are Active/Linkable with no syntax changes.</p>
				</li>
				<li>
					<p>Agree to include the article source credit below each article reprinted with the link active: Article Source: <U><a href="http://contentdragon.com/"  target="_blank">Content Dragon.com</a></U></p>
				</li>
			</ul>
			<p><br />
			</p>
			<p>To view the complete "<strong>Publisher Terms of Use</strong>" visit <U><a href="http://contentdragon.com/terms/</a></U></p>'
			. '</div>';
		}
	}
	else if ($_POST['fc_searched'] == "Search!")
	{
		echo '<br>
		<br>
		<h2>Search For Content</h2>
		<div style="padding-left: 10px;">
		<p><strong>Quickly  Add - Free Keyword Targeted Content to your Word Press Blog</strong>.</p>
		<p>Content Dragon accepts only fresh informative articles that are reviewed for  quality by our QUALITY ASSURANCE Editorial Team before you ever see  them. Search our Article database by entering a keyword below.</p>
		<form id="searcherkeywordform" name="searcherkeywordform" method="post" action="">
			<label for="searcherkeyword">Keyword:</label>
			<input type="text" name="searcherkeyword" id="searcherkeyword" value="'.$_POST['searcherkeyword'].'" />
			<input type="submit" name="fc_searched" id="fc_searched" value="Search!" /><br>
			<font size="1">( search may take a few seconds )</font>
			</form><br>';
		
		echo '<script language="javascript">
		function fc_open_article(articleid)
		{
			var formObj = document.forms[articleid];
			var printTitle = formObj.article_title.value;
			var printContent = "<h2>"+printTitle+"</h2>"+formObj.article_body.value;
			
			//header
			var printHead="";
			printHead+="<html>\n";
			printHead+="<head>\n";
			printHead+="<title>"+printTitle+"</title>\n";
			printHead+="<style>\n";
			printHead+="a:active, a:link, a:hover, a:visited{\n";
			printHead+="color: #0000FF;\n";
			printHead+="text-decoration: none;\n";
			printHead+="}\n";
			printHead+="td, th, body{\n";
			printHead+="font-size: 14px;\n";
			printHead+="}\n";
			printHead+="</style>\n";
			printHead+="</head>\n";
			printHead+="<body topmargin=\'5\' leftmargin=\'5\'>\n";
			
			printContent="<div id=\"printContent\">"+printContent+"</div>";
			
			//footer
			var printFoot="";
			printFoot+="</body>\n";
			printFoot+="</html>\n";
			
			printWindow = window.open("","printWindow");
			printWindow.document.write(printHead+printContent+printFoot);
			printWindow.document.close();
		}
		</script>';
		
		$fc_articles = fc_getarticle($_POST['searcherkeyword']);
		$amount = count($fc_articles[0]);
		
		echo '<h2>Search Results</h2>
		<div style="padding-left: 10px;">';
		
		if ($amount == 0)
		{
			echo '<p>Opps!!! It seems that your keyword did not turn up any articles. Try another keyword or use broad keyword. If you would like to search our database of articles visit <U><a href="http://contentdragon.com/" target="_blank">http://contentdragon.com</a></U> </p>';
		}
		else
		{
			for ($i=0; $i<$amount; $i++)
			{
				$article_title = $fc_articles[1][$i];
				$article_submitted = $fc_articles[2][$i];
				$article_body = $fc_articles[3][$i];
				$article_url = $fc_articles[4][$i];
				
				$articleid = md5(fc_formatURL($article_title));
				
				$fc_body_preview = substr(strip_tags($article_body), 0, 200)." [...]";
				$article_full = fc_nolines("<br>$article_body");
				
				echo '<form name="'.$articleid.'" id="'.$articleid.'" method="post" action="">';
				echo '<input type="hidden" name="article_title" id="article_title" value="'.$article_title.'" />';
				echo '<input type="hidden" name="article_body" id="article_body" value="'.str_replace('"', "&quot;", $article_full).'" />';
				echo '<input type="hidden" name="article_url" id="article_url" value="'.$article_url.'" />';
				echo '<input type="hidden" name="searcherkeyword" id="searcherkeyword" value="'.$_POST['searcherkeyword'].'" />';
				echo '<em><b>'.$article_title.'</b></em><br>
				<div style="width: 400px;">'.$fc_body_preview.'</div><br>
				<input type="button" onClick="fc_open_article(\''.$articleid.'\');" style="height: 25px; " value="Read Article" />&nbsp;
				<input type="submit" name="fc_save_draft" id="fc_save_draft" style="height: 25px; " value="Add to Drafts" /><br>
				<br>';
				echo '</form>';
			}
		}

		echo '<p><em>To view Content Dragons complete category listings </em><U><a href="http://contentdragon.com/content/" target="_blank">click here</a></U><em> or visit </em><U><a href="http://contentdragon.com/" target="_blank">http://contentdragon.com</a></U></p>';
		echo '</div>';
	}
	else
	{
		echo '<br>
		<br>
		<h2>Search For Content</h2>
		<div style="padding-left: 10px;">
		<p><strong>Quickly  Add - Free Keyword Targeted Content to your Word Press Blog</strong>.</p>
		<p>Content Dragon accepts only fresh informative articles that are reviewed for  quality by our QUALITY ASSURANCE Editorial Team before you ever see  them. Search our Article database by entering a keyword below.</p>
		<form id="searcherkeywordform" name="searcherkeywordform" method="post" action="">
			<label for="searcherkeyword">Keyword:</label>
			<input type="text" name="searcherkeyword" id="searcherkeyword" value="'.$_POST['searcherkeyword'].'" />
			<input type="submit" name="fc_searched" id="fc_searched" value="Search!" /><br>
			<font size="1">( search may take a few seconds )</font>
		</form>
		<p><strong>Ready  to share YOUR KNOWLEDGE with the world and promote yourself and your  blog?</strong> </p>
		<p><br />
		</p>
		<p>Syndicate Your Articles FREE with Content Dragon.  Simply sign up and submit  your articles. We will then promote your articles to all of our  Publishers and Webmasters. <strong>You SUBMIT; we PROMOTE!</strong></p>
		<p><br />
		</p>
		<p><U><a href="http://contentdragon.com/signup/" target="_blank">Click  here to Register</a></U> or visit <U><a href="http://www.contentdragon.com/" target="_blank">http://contentdragon.com</a></U> for more information on our services</p>
		</div>
		<br>
	<br>';
	}
}
?>