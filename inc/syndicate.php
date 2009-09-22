<?php
function lifestream_rss_feed()
{
	/**
	 * Outputs an RSS feed of your lifestream activity.
	 * @todo
	 */
	global $wpdb, $lifestream;
	
	$options = array(
		'limit'				=> $lifestream->get_option('feed_items'),
		'break_groups'		=> true,
	);
	
	$events = $lifestream->get_events($options);
	
	header('Content-Type: application/xml+rss');
	
	$date = date(DATE_RFC822);
	$lines = array('<?xml version="1.0" encoding="UTF-8"?>');
	$lines[] = '<rss xmlns:lifestream="http://www.davidcramer.net/a-url-which-ill-make-later" xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">';
	$lines[] = '	<channel>';
	$lines[] = '		<title>Lifestream for '.get_bloginfo('blogname').'</title>';
	$lines[] = '		<generator>wp-lifestream '.LIFESTREAM_VERSION.'</generator>'
	$lines[] = '		<description>'.get_bloginfo('description').'</description>';
	$lines[] = '		<link>'.get_bloginfo('url').'</link>';
	$lines[] = '		<atom:link href="'.$lifestream->get_rss_feed_url().'" rel="self" type="application/rss+xml"/>';
	$lines[] = '		<pubDate>'.$date.'</pubDate>';
	
	foreach ($events as $event)
	{
		$label_inst = $event->feed->get_label($event, $options);
		if ($lifestream->get_option('show_owners'))
		{
			$label = $label_inst->get_label_single_user();
		}
		else
		{
			$label = $label_inst->get_label_single();
		}
		
		$lines[] = '		<item>';
		$lines[] = '			<guid isPermaLink="false">'.get_bloginfo('url').'#lifestream#'.$event->id.'</guid>';
		$lines[] = '			<title>'.strip_tags($label).'</title>';
		$lines[] = '			<description>'.htmlspecialchars($event->feed->render_item($event, $event->data[0])).'</description>';
		$lines[] = '			<lifestream:feed>'.$event->feed->get_constant('ID').'</lifestream:feed>';
		$lines[] = '			<lifestream:label>'.htmlspecialchars($label).'</lifestream:label>';
		$lines[] = '			<lifestream:event>'.htmlspecialchars($event->feed->render_item($event, $event->data[0])).'</lifestream:event>';
		$lines[] = '		</item>';
	}
	$lines[] = '	</channel>';
	$lines[] = '</rss>';
	echo implode("\n", $lines);
}

function lifestream_opml_feed()
{
	/**
	 * Outputs an OPML feed which can be used to backup your lifestream settings.
	 * @todo
	 */
	global $wpdb, $lifestream;
	
	$date = date(DATE_RFC822);
	$lines = array('<?xml version="1.0" encoding="UTF-8"?>');
	$lines[] = '<!-- OPML generated by Lifestream '.LIFESTREAM_VERSION.' on '.$date.' -->';
	$lines[] = '<opml version="1.1">';
	$lines[] = '	<head>';
	$lines[] = '		<title>lifestream.feeds</title>';
	$lines[] = '		<dateCreated>'.$date.'</dateCreated>';
	$lines[] = '		<dateModified>'.$date.'</dateModified>';
	$lines[] = '	</head>';
	$lines[] = '	<body>';
	
	$list_of_feeds = array();
	foreach ($lifestream->feeds as $key=>$class)
	{
		if (method_exists($class, 'get_url'))
		{
			$list_of_feeds[] = $key;
		}
	}
	if (count($list_of_feeds))
	{
	
		$results =& $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."lifestream_feeds` WHERE `feed` IN ('".implode("', '", $list_of_feeds)."')");
		foreach ($results as $result)
		{
			$instance = Lifestream_Feed::construct_from_query_result($lifestream, $result);
			if (method_exists($instance, 'get_url'))
			{
				$urls = $instance->get_url();
				if (!is_array($urls)) $urls = array($urls);
				$items = array();
				foreach ($urls as $url_data)
				{
					if (is_array($url_data))
					{
						// url, key
						list($url, $key) = $url_data;
					}
					else
					{
						$url = $url_data;
						$key = '';
					}
					$lines[] = '		<outline text="'.htmlspecialchars($instance->get_feed_display()).'" xmlUrl="'.$url.'" type="'.$instance->get_constant('ID').'"/>';
				}
			}
		}
	}
	$lines[] = '	</body>';
	$lines[] = '</opml>';
	echo implode("\n", $lines);
}
?>