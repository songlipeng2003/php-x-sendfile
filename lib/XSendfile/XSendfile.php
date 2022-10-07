<?php

namespace XSendfile;

class XSendfile {

	const SERVER_TYPE_APACHE = "Apache";
	const SERVER_TYPE_NGINX = "Nginx";
	const SERVER_TYPE_LIGHTTPD = "Lighttpd";
	const SERVER_TYPE_LITESPEED = "LiteSpeed";

	public static function detectServer() {
		$server_software = ! empty( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '';

		$web_servers = [
			'apache'    => self::SERVER_TYPE_APACHE,
			'nginx'     => self::SERVER_TYPE_NGINX,
			'lighttpd'  => self::SERVER_TYPE_LIGHTTPD,
			'litespeed' => self::SERVER_TYPE_LITESPEED,
		];

		foreach ( $web_servers as $web_server_pattern => $web_server_label ) {
			if ( stripos( $server_software, $web_server_pattern ) !== false ) {
				return $web_server_label;
				break;
			}
		}

		return null;
	}

	public static function xSendfile( $file, $downFilename = null, $serverType = null, $cache = true, $autoContentType = true ) {
		if ( $cache ) {
			if ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
				$modifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
				$modifiedSince = strtotime( $modifiedSince );
				if ( filemtime( $file ) == $modifiedSince ) {
					header( "HTTP/1.1 304: Not Modified" );

					return;
				}
			}

			if ( isset( $_SERVER['IF-NONE-MATCH'] ) && $_SERVER['IF-NONE-MATCH'] == md5( filemtime( $file ) ) ) {
				header( "HTTP/1.1 304: Not Modified" );

				return;
			}
		}
		if( $autoContentType ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$mime  = finfo_file( $finfo, $file );
			if ( $mime ) {
				header( "Content-type: {$mime}" );
			} else {
				header( "Content-type: application/octet-stream" );
			}
		}

		if ( $downFilename ) {
			$filename = $downFilename;
		} else {
			$filename = basename( $file );
		}

		$encodedFilename = rawurlencode( $filename );
		$userAgent       = $_SERVER["HTTP_USER_AGENT"];

		// support ie
		if ( false !== strpos( $userAgent, "MSIE" ) || preg_match( "/Trident\/7.0/", $userAgent ) ) {
			header( 'Content-Disposition: attachment; filename="' . $encodedFilename . '"' );
			// support firefox
		} else if ( false !== strpos( $userAgent, "Firefox" ) ) {
			header( 'Content-Disposition: attachment; filename*="utf8\'\'' . $encodedFilename . '"' );
			// support safari and chrome
		} else {
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		}

		header( "Content-Length: " . filesize( $file ) );

		if ( $cache ) {
			header( "Last-Modified: " . gmdate( 'D, d M Y H:i:s', filemtime( $file ) ) . ' GMT' );
			header( "Expires: " . gmdate( 'D, d M Y H:i:s', time() + 2592000 ) . ' GMT' );
			header( "Cache-Control: max-age=2592000" );
			header( 'Etag: " ' . md5( filemtime( $file ) ) . '"' );
		}

		if ( ! $serverType ) {
			$serverType = self::detectServer();
		}

		if ( $serverType ) {
			switch ( $serverType ) {
				case self::SERVER_TYPE_APACHE:
					header( "X-Sendfile: $file" );
					break;
				case self::SERVER_TYPE_NGINX:
					header( "X-Accel-Redirect: $file" );
					break;
				case self::SERVER_TYPE_LIGHTTPD:
					header( "X-LIGHTTPD-send-file: $file" );
					break;
				case self::SERVER_TYPE_LITESPEED:

					/**
					 * Unlike the X-Sendfile or X-Accel-Redirect implementations in other web servers, LiteSpeed uses a URI instead of a file path for security reasons.
					 *
					 * @see https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:config:internal-redirect#redirecting_via_url_vs_file_path
					 */

					$uri = self::pathToUri( $file );
					header( "X-LiteSpeed-Location: $uri" );
					break;
			}
		} else {
			ob_clean();
			flush();
			// unknown server , use php stream
			readfile( $file );
		}
	}

	public static function pathToUri( $path ) {
		return '/' . ltrim( str_replace( [ $_SERVER['DOCUMENT_ROOT'], '\\' ], [ '', '/' ], $path ), '/' );
	}
	
}
