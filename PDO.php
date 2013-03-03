<?php
//All Errors
error_reporting(-1);
//Content-Type
header("Content-Type: text/html; charset=ISO-8859-1");
//Database Connection Details
$dbhost = 'localhost'; //Set Host
$dbuname = ''; //Database User
$dbpass = ''; //Database Password
$dbname = ''; //Database Name
$prefix = 'nuke'; //Database Prefix
$nukeurl = '';//No trailing slash here (ie: http://www.clanthemes.com)
$module_name = 'News';  //Module Name

//Let's connect
$dbh = new PDO('mysql:host='.$dbhost.';dbname='.$dbname, $dbuname, $dbpass);

$sqlstory ='SELECT sid, title, time FROM '.$prefix.'_stories ORDER BY sid';
$querystory = $dbh->prepare($sqlstory);
$querystory->execute();
$newsloop = $querystory->fetchAll();

//Friendly Urls
function urltitle($title){
	$urltitle = str_replace('_', '-' , $title);
	$urltitle = strtolower(str_replace(' ' , '-' , $urltitle));
	$urltitle = strtolower(str_replace("'" , "" , $urltitle));
	$urltitle = preg_replace('/[^a-z0-9-\']/i', '', $urltitle);
	$urltitle = str_replace('--', '-' , $urltitle);
	$urltitle = str_replace('---', '-' , $urltitle);
	return $urltitle;
}

//Some Replacements
function disqushtml($context){
	//Could add some more filters if you want here.
	$context = strip_tags($context, '<a><blockquote>');
	return $context;
}

//XML Head
$dom = new DOMDocument('1.0', 'ISO-8859-1');
$rss = $dom->createElement('rss');
$rss->setAttribute('version', '2.0');
$rss->setAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
$rss->setAttribute('xmlns:dsq', 'http://www.disqus.com/');
$rss->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
$rss->setAttribute('xmlns:wp', 'http://wordpress.org/export/1.0/');
$channel = $dom->createElement('channel');


foreach($newsloop as $news){
	$sid = intval($news['sid']);
	$title = $news['title'];
	$time = $news['time'];
	$pageurl = $nukeurl.'/modules.php?name='.$module_name.'&file=article&sid='.$sid;
	//$pageurl = $nukeurl.'/article'.$sid.'-'.urltitle($title).'.html'; (just an example how you can modify this)
	$item = $dom->createElement('item');
	$ttitle = $dom->createElement('title');
	$ttitledata = $dom->createCDATASection(htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8'));
	$ttitledata = $ttitle->appendChild($ttitledata);
	$link = $dom->createElement('link', htmlentities($pageurl));
	$dsqthread = $dom->createElement('dsq:thread_identifier', $module_name.' '.$sid);
	$postdate = $dom->createElement('wp:post_date_gmt', $time);
	$status = $dom->createElement('wp:comment_status', 'open');

	$sqlcomm = 'SELECT tid, pid, date, name, email, url, host_name, comment FROM '.$prefix.'_comments WHERE sid = '.$sid.' ORDER BY tid';
	$querycomm = $dbh->prepare($sqlcomm);
	$querycomm->execute();
	//if no comments for this article, don't add to xml.
	if($querycomm->rowcount() > 0){
		$channel->appendChild($item);
		$item->appendChild($ttitle);
		$item->appendChild($link);
		$item->appendChild($dsqthread);
		$item->appendChild($postdate);
		$item->appendChild($status);
		foreach($querycomm as $comms){
			$cid = intval($comms['tid']);
			$parentid = intval($comms['pid']);
			$ctime = $comms['date'];
			$cname = $comms['name'];
			$email = $comms['email'];
			$url = $comms['url'];
			//I'm attempting to add some missing data, but usernaming matching is iffy.
			if(empty($email) OR empty($url)){
				$sqluser = 'SELECT user_email, user_website FROM '.$prefix.'_users WHERE username = :cname LIMIT 1';
				$queryuser = $dbh->prepare($sqluser);
				$queryuser->execute(array('cname' => $cname));
				$resultuser = $queryuser->fetch();
				$email = $resultuser['user_email'];
				$url = $resultuser['user_website'];
			}
			$ipaddy = $comms['host_name'];
			$comment = disqushtml($comms['comment']);
			$wpcom = $dom->createElement('wp:comment');
			$commid = $dom->createElement('wp:comment_id', $cid);
			$cauthor = $dom->createElement('wp:comment_author', $cname);
			$cemail = $dom->createElement('wp:comment_author_email', $email);
			$cauthurl = $dom->createElement('wp:comment_author_url', $url);
			$cauthip = $dom->createElement('wp:comment_author_IP', $ipaddy);
			$cdate = $dom->createElement('wp:comment_date_gmt', $ctime);
			$ccontent = $dom->createElement('wp:comment_content');
			$ccontentdata = $dom->createCDATASection(htmlentities($comment, ENT_NOQUOTES, 'UTF-8'));
			$ccontentdata = $ccontent->appendChild($ccontentdata);
			$capproved = $dom->createElement('wp:comment_approved', '1');
			$cparent = $dom->createElement('wp:comment_parent', $parentid);
			$item->appendChild($wpcom);
			$wpcom->appendChild($commid);
			$wpcom->appendChild($cauthor);
			$wpcom->appendChild($cemail);
			$wpcom->appendChild($cauthurl);
			$wpcom->appendChild($cauthip);
			$wpcom->appendChild($cdate);
			$wpcom->appendChild($ccontent);
			$wpcom->appendChild($capproved);
			$wpcom->appendChild($cparent);
		}
	}
}

$rss->appendChild($channel);
$dom->appendChild($rss);
$dom->formatOutput = true;
echo utf8_decode($dom->save('exported.xml'));  //We're saving this to same location as this file
//echo utf8_decode($dom->saveXML()); This will print it to screen

//Bye Bye Connection
$dbh = null;