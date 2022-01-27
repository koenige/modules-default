<?php

/**
 * default module
 * Use 'markdown' in procedural style
 * based on Michel Fortin’s example 'Readme.php'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


require_once $zz_setting['lib'].'/markdown-extra/Michelf/MarkdownExtra.inc.php';

# Get Markdown class
use \Michelf\MarkdownExtra;


function markdown($text) {
	if (!$text) return '';
	# Pass content through the Markdown parser
	$html = MarkdownExtra::defaultTransform($text);
	return $html;	
}

function markdown_inline($text) {
	if (!$text) return '';
	$html = MarkdownExtra::defaultTransform($text);
	$html = trim($html);
	$html = str_replace('<p>', ' ', $html);
	$html = str_replace('</p>', ' ', $html);
	$html = trim($html);
	return $html;	
}

function markdown_attribute($text) {
	if (!$text) return '';
	$text = markdown_inline($text);
	$text = strip_tags($text);
	return $text;
}
