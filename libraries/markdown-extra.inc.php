<?php

/**
 * default module
 * Use 'markdown' in procedural style
 * based on Michel Fortin's example 'Readme.php'
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


require_once $zz_setting['lib'].'/markdown-extra/Michelf/MarkdownExtra.inc.php';

# Get Markdown class
use \Michelf\MarkdownExtra;


function markdown($text) {
	# Pass content through the Markdown parser
	$html = MarkdownExtra::defaultTransform($text);
	return $html;	
}

function markdown_inline($text) {
	$html = MarkdownExtra::defaultTransform($text);
	$html = trim($html);
	if (substr($html, 0, 3) !== '<p>') return $html;
	if (substr($html, -4) !== '</p>') return $html;
	$html = substr($html, 3, -4);
	return $html;	
}

function markdown_attribute($text) {
	$text = markdown_inline($text);
	$text = strip_tags($text);
	return $text;
}