<?php

/**
 * default module
 * Single sign on via trusted login (shared secret)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009-2010, 2017, 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Single sign on via trusted login
 *
 * @param array $params -
 * @return array $page
 */
function mod_default_sso($params) {
	$single_sign_on_secret = wrap_setting('single_sign_on_secret');

	// no parameter allowed!
	if (!empty($params)) return false;
	
	// required URL query strings
	$required_query_strings = ['username'];
	$optional_query_strings = ['context', 'url'];
	if (array_key_exists('token', $_GET)) {
		// login via password token
		$required_query_strings[] = 'token';
		$hash = false;
	} else {
		// login via sso hash
		$required_query_strings[] = 'start';
		$required_query_strings[] = 'end';
		$required_query_strings[] = 'hash';
		$token = false;
	}
	
	// get required keys
	foreach ($required_query_strings as $key) {
		if (empty($_GET[$key])) {
			wrap_quit(403);
		} else {
			$$key = $_GET[$key];
			unset($_GET[$key]);
		}
	}
	// get optional keys
	foreach ($optional_query_strings as $key) {
		if (!empty($_GET[$key])) {
			$$key = $_GET[$key];
			unset($_GET[$key]);
		} else {
			$$key = '';
		}
	}
	$full_username = $username.($context ? '.' : '').$context;
	// if there are more URL parameters, that's simply not allowed
	if (!empty($_GET)) {
		// Too many URL parameters
		wrap_error(wrap_text('Single sign on: too many URL parameters.').' ('.$full_username.') ', E_USER_NOTICE);
		wrap_quit(403);
	}

	// test if hash is correct
	if ($token AND wrap_password_token($username.$context, 'sso_key') === $token) {
		$hash_correct = true;
		$via = 'sso_token';
	} elseif ($hash AND sha1($username.$context.$start.$end.$single_sign_on_secret) === $hash) {
		$hash_correct = true;
		$token = $single_sign_on_secret;
		$via = 'sso_hash';
	} else {
		// Incorrect login credentials
		wrap_error(wrap_text('Single sign on: incorrect login credentials.').' ('.$full_username.') ', E_USER_NOTICE);
		wrap_quit(403);
	}
	
	// test if time frame is valid
	if ($hash AND !($start < time() AND $end > time())) {
		// Login-URL is invalid. Please get a new link, this link is too old./
		// All links using the trusted login mechanism are only valid for x minutes
		// echo 'Login-URL is invalid, time\'s up';
		wrap_error(wrap_text('Single sign on: possible login period expired.')
			.' ('.$full_username.') ', E_USER_NOTICE);
		wrap_quit(403, '<strong>'.wrap_text('Sorry, the possible login period has expired. Please get a new login link.').'</strong>');
	}

	// everything okay, so check if this user is not already logged in!
	if (!empty($_SESSION['logged_in']) AND !empty($_SESSION['username']) 
		AND (strtolower($full_username) == strtolower($_SESSION['username']))) {
		// User is already logged in
		if (!$url) $url = '/';
		return cms_login_redirect($url);
	}

	// now we'll cross check username against user database
	// if it's not in the user database, must be on one of the remote login servers
	// user will be logged in (fill $_SESSION);
	return cms_login(['Single Sign On', $via, $token, $username, $context]);
}
