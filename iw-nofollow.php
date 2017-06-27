<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$wgExtensionCredits['other'][] = array(
        'name' => 'Nofollow for interwiki links',
        'author' => '[http://rationalwiki.com/wiki/User:Nx Nx]',
        'url' => 'http://rationalwiki.com/',
        'description' => 'Adds the rel="nofollow" attribute to interwiki links',
        'descriptionmsg' => 'iwnofollow-desc'
);

$wgHooks['LinkEnd'][] = 'addnofollow';

$wgIwNofollowIP = dirname( __FILE__ );
$wgExtensionMessagesFiles['iw-nofollow'] = "$wgIwNofollowIP/iw-nofollow.i18n.php";

global $wgIwNofollowWhitelist;
$wgIwNofollowWhitelist = false;

$wgIwNofollowWhitelist = '/http:\/\/en\.wikipedia\.org/';

function buildRegexes( $lines ) {
  # Code duplicated from the SpamBlacklist extension (r19197)

  # Strip comments and whitespace, then remove blanks
  $lines = array_filter( array_map( 'trim', preg_replace( '/#.*$/', '', $lines ) ) );

	# No lines, don't make a regex which will match everything
	if ( count( $lines ) == 0 ) {
		wfDebug( "No lines\n" );
		return false;
	} else {
		# Make regex
		# It's faster using the S modifier even though it will usually only be run once
		//$regex = 'http://+[a-z0-9_\-.]*(' . implode( '|', $lines ) . ')';
		//return '/' . str_replace( '/', '\/', preg_replace('|\\\*/|', '/', $regex) ) . '/Si';
		$regexes = '';
		$regexStart = '/http:\/\/+[a-z0-9_\-.]*(';
		$regexEnd = ')/Si';
		$regexMax = 4096;
		$build = false;
		foreach( $lines as $line ) {
			// FIXME: not very robust size check, but should work. :)
			if( $build === false ) {
				$build = $line;
			} elseif( strlen( $build ) + strlen( $line ) > $regexMax ) {
				$regexes .= $regexStart .
					str_replace( '/', '\/', preg_replace('|\\\*/|', '/', $build) ) .
					$regexEnd;
				$build = $line;
			} else {
				$build .= '|' . $line;
			}
		}
		if( $build !== false ) {
			$regexes .= $regexStart .
				str_replace( '/', '\/', preg_replace('|\\\*/|', '/', $build) ) .
				$regexEnd;
		}
		return $regexes;
	}
}

function filterLink( $url ) {
  global $wgIwNofollowWhitelist;
  $msg = wfMessage( 'iwnofollow-whitelist' )->inContentLanguage();

  $whitelist = $msg->isBlank()
               ? false 
               : buildRegexes( explode( "\n", $msg->text() ) );

  $cwl = $wgIwNofollowWhitelist !== false ? preg_match( $wgIwNofollowWhitelist, $url ) : false;
  $wl  = $whitelist !== false ? preg_match( $whitelist, $url ) : false;

  return !( $cwl || $wl );
}

function addnofollow($skin, $target, $options, &$text, &$attribs, &$ret) {
  if (array_key_exists('class',$attribs) && strpos($attribs['class'],'extiw') !== false ) {
    if (filterLink($attribs['href'])) {
      $attribs['rel'] = 'nofollow';
    }
  }
  return true;
}
