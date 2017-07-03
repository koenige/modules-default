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
