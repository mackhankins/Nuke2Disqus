<?php
error_reporting(-1);

ini_set('display_errors', 'On');

header('Content-Type: text/html; charset=UTF-8');

$outputfilename = 'exported.xml';//Writes to the same directory as .php file located

$dbhost = 'localhost';//Host

$dbuname = '';//Database Username

$dbpass = '';//Database Password

$dbname = '';//Database Name

$prefix = 'nuke';//Table Prefix

$nukeurl = '';//No trailing slash here (ie: http://www.clanthemes.com)

$module_name = 'News';//Module Name



//Some Modifications

function disqushtml($context)
{
	//Could add some more filters here if you need theme
	
	//Can comment out if you don't want to strip out any html.
	
	$context = strip_tags($context, '<a><blockquote>');
	
	return $context;
}



//Let's connect
$dbh = new PDO('mysql:host='.$dbhost.';dbname='.$dbname, $dbuname, $dbpass);



//XML Head
$dom = new DOMDocument('1.0', 'UTF-8');

$rss = $dom->createElement('rss');

$rss->setAttribute('version', '2.0');

$rss->setAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');

$rss->setAttribute('xmlns:dsq', 'http://www.disqus.com/');

$rss->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');

$rss->setAttribute('xmlns:wp', 'http://wordpress.org/export/1.0/');

$channel = $dom->createElement('channel');

//Query for Stories
$sqlstory ='SELECT sid, title, time FROM '.$prefix.'_stories ORDER BY sid';

$querystory = $dbh->prepare($sqlstory);

$querystory->execute();

$newsloop = $querystory->fetchAll();

//Loop Stories to build Items
foreach($newsloop as $news)
{
	$sid = intval($news['sid']);

	$title = $news['title'];

	$time = $news['time'];

	//Modify this to fit you're specific needs
	$pageurl = $nukeurl.'/modules.php?name='.$module_name.'&file=article&sid='.$sid;
	
	//Query Comments table based on sid(story id)
	$sqlcomm = 'SELECT tid, pid, date, name, email, url, host_name, comment FROM '.$prefix.'_comments WHERE sid = '.$sid.' ORDER BY tid';
	
	$querycomm = $dbh->prepare($sqlcomm);
	
	$querycomm->execute();
	
	$countcomm = $querycomm->rowCount();
	
	//Let's not build items if the article has no comments
	if($countcomm > 0)
	{
		$item = $dom->createElement('item');

		$ttitle = $dom->createElement('title');

		$ttitledata = $dom->createCDATASection(htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8'));

		$ttitledata = $ttitle->appendChild($ttitledata);

		$link = $dom->createElement('link', htmlentities($pageurl));

		$dsqthread = $dom->createElement('dsq:thread_identifier', $module_name.'-'.$sid);

		$postdate = $dom->createElement('wp:post_date_gmt', $time);

		$status = $dom->createElement('wp:comment_status', 'open');

		$channel->appendChild($item);

		$item->appendChild($ttitle);

		$item->appendChild($link);

		$item->appendChild($dsqthread);

		$item->appendChild($postdate);

		$item->appendChild($status);		
		
		//Loop Comment Results
		foreach($querycomm as $comms)
		{
			$cid = intval($comms['tid']);
			
			$parentid = intval($comms['pid']);
			
			$ctime = $comms['date'];
			
			$cname = $comms['name'];
			
			$email = $comms['email'];
			
			$url = $comms['url'];
			
			$ipaddy = $comms['host_name'];
			
			//Filter function
			$comment = disqushtml($comms['comment']);			
			
			//I decided to query the users table since some of comments were missing urls and emails.
			//That being said, matching by username is hit and miss.
			if(empty($email) OR empty($url))
			{
				$sqluser = 'SELECT user_email, user_website FROM '.$prefix.'_users WHERE username = :cname LIMIT 1';
				
				$queryuser = $dbh->prepare($sqluser);
				
				$queryuser->execute(array('cname' => $cname));
				
				$resultuser = $queryuser->fetch();
				
				if(empty($email))
				{
					$email = $resultuser['user_email'];
				}
				
				if(empty($url))
				{
					$url = $resultuser['user_website'];
				}
			}
			
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
		} //Close Comments Loop
	} //End If 
} //Close Article Loop


$rss->appendChild($channel);

$dom->appendChild($rss);

$dom->formatOutput = true;

//Writes file to directory and prints bytes written.
echo 'Successfully Wrote ' .$dom->save($outputfilename). ' bytes';

//Close
$dbh = null;