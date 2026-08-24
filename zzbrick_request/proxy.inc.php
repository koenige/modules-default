<?php

/**
 * default module
 * Proxy
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * get content via proxy, rewrite URLs in scripts
 *
 * @param array $params
 * @return void
 */
function mod_default_proxy($params) {
	// get remote URL
	// @todo add .html etc. as well
	$url = sprintf('https://%s', implode('/', $params));
	if (str_ends_with(wrap_setting('request_uri'), '/')) $url .= '/';

	// check if host name is on whitelist	
	$hostname = parse_url($url, PHP_URL_HOST);
	$found = false;
	foreach (wrap_setting('default_proxy_hosts') as $host) {
		if (!str_ends_with($hostname, $host)) continue;
		$found = true;
	}
	if (!$found) wrap_quit(404);

	// get remote content
	wrap_include('syndication', 'zzwrap');
	wrap_setting('cache', true);
	$url_parts = explode('/', $url);
	$ext = end($url_parts) ? wrap_file_extension(end($url_parts)) : 'html';
	if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'])) {
		mod_default_proxy_forward($url, $ext);
		return;
	}

	$resource = wrap_syndication($url, ['type' => $ext]);
	if (!$resource) wrap_quit(404);
	$filename = wrap_cache_filename('url', $url);
	if (!$filename) wrap_quit(404);
	if (!file_exists($filename)) wrap_quit(404);

	// Syndication cache is the origin copy; do not write a local page cache
	// that wrap_send_cache() would serve without revalidating the origin.
	wrap_setting('cache', false);

	if (in_array($ext, ['html', 'css', 'js'])) {
		$data = file_get_contents($filename);
		$data = preg_replace_callback('~(https://|http://|//)([a-z-0-9.]+)~', 'mod_default_proxy_callback', $data);
		wrap_send_text($data, $ext);
	}

	mod_default_proxy_send_cached($url, $filename, $ext);
}

/**
 * forward non-GET/HEAD methods to the remote URL (no cache)
 *
 * @param string $url
 * @param string $ext
 * @return void
 */
function mod_default_proxy_forward($url, $ext) {
	wrap_setting('cache', false);

	$settings = [
		'method' => $_SERVER['REQUEST_METHOD'],
		'data' => $_POST ?: file_get_contents('php://input'),
		'headers' => []
	];
	foreach (apache_request_headers() as $key => $header) {
		if (in_array($key, ['Cookie', 'Host'])) continue;
		$settings['headers'][] = sprintf('%s: %s', ucfirst($key), $header);
	}
	if (isset($_SERVER['CONTENT_LENGTH']))
		$settings['headers'][] = sprintf('Content-Length: %s', $_SERVER['CONTENT_LENGTH']);
	if (isset($_SERVER['CONTENT_TYPE']))
		$settings['headers'][] = sprintf('Content-Type: %s', $_SERVER['CONTENT_TYPE']);

	list($status, $headers, $data) = wrap_syndication_http_request($url, $settings);
	if (in_array('content-encoding: gzip', $headers))
		$data = gzdecode($data);
	if (in_array('content-type: application/json', $headers)) {
		$ext = 'json';
		wrap_cache_header('content-type: application/json');
	}
	if ($status !== 200) {
		echo wrap_print($settings);
		echo wrap_print($status);
		echo wrap_print($headers);
		echo wrap_print($data);
		exit;
	}

	if (in_array($ext, ['html', 'css', 'js'])) {
		$data = preg_replace_callback('~(https://|http://|//)([a-z-0-9.]+)~', 'mod_default_proxy_callback', $data);
		wrap_send_text($data, $ext);
	}
	wrap_send_resource('memory', $data);
}

/**
 * send a cached remote file, passing origin validators through when present
 *
 * @param string $url
 * @param string $filename
 * @param string $ext
 * @return void
 */
function mod_default_proxy_send_cached($url, $filename, $ext) {
	$file = [
		'name' => $filename,
		'ext' => $ext,
		'send_as' => basename(parse_url($url, PHP_URL_PATH))
	];
	$headers_file = wrap_cache_filename('headers', $url);
	if ($headers_file AND file_exists($headers_file)) {
		$etag = wrap_cache_get_header($headers_file, 'ETag');
		if ($etag) $file['etag'] = $etag;
		$last_modified = wrap_cache_get_header($headers_file, 'Last-Modified');
		if ($last_modified) $file['last_modified'] = $last_modified;
		$content_type = wrap_cache_get_header($headers_file, 'Content-Type');
		if ($content_type) $file['content_type'] = $content_type;
	}
	wrap_send_file($file);
}

/**
 * replace URLs with proxy URLs
 * e. g.  https://example.com/test => https://myhost.example/proxy/example.com/test
 *
 * @param array $match
 * @return string
 */
function mod_default_proxy_callback($match) {
	return sprintf('%s%s%s', $match[1], wrap_setting('hostname'), wrap_path('default_proxy', $match[2]));
}
