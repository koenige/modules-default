<?php 

/**
 * default module
 * page elements: blocks
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/** 
 * page blocks
 * 
 * @param array $params remaining brick vars after `page` / handler name
 * @param array $page brick parameter bag (by ref so `media` can be passed through like `page_topimage`)
 * @param array $local_settings parsed `key=value` from the brick (e.g. `size=medium`)
 * @return string $text
 */
function page_blocks($params, &$page, $local_settings = []) {
	if (!wrap_page_field('page_id')) return '';
	
	$sql = 'SELECT /*_PREFIX_*/blocks.block_id, /*_PREFIX_*/blocks.title, /*_PREFIX_*/blocks.block
			, block_categories.category AS block_category
			, layout_categories.category AS layout_category
			, SUBSTRING_INDEX(layout_categories.path, "/", -1) AS layout_class
			, layout_categories.parameters AS layout_parameters
		FROM /*_PREFIX_*/blocks
		LEFT JOIN /*_PREFIX_*/webpages_blocks USING (block_id)
		LEFT JOIN /*_PREFIX_*/categories block_categories
			ON block_categories.category_id = /*_PREFIX_*/blocks.block_category_id
		LEFT JOIN /*_PREFIX_*/categories layout_categories
			ON layout_categories.category_id = /*_PREFIX_*/webpages_blocks.layout_category_id
		WHERE /*_PREFIX_*/webpages_blocks.page_id = %d
		ORDER BY /*_PREFIX_*/webpages_blocks.sequence, /*_PREFIX_*/blocks.identifier';
	$sql = sprintf($sql, wrap_page_field('page_id'));
	$data = wrap_db_fetch($sql, 'block_id');
	if (!$data) return '';
	$data = wrap_translate($data, 'blocks');

	$media = wrap_media(array_keys($data), 'blocks');
	if ($media) wrap_include('request', 'zzbrick');

	$size = $local_settings['size']
		?? wrap_setting('default_media_size')
		?: 'medium';

	foreach ($data as $block_id => &$line) {
		if ($line['layout_parameters']) {
			parse_str($line['layout_parameters'], $line['layout_parameters']);
			if (!empty($line['layout_parameters']['default_blocks_class']))
				$data[$block_id]['layout_class'] = $line['layout_parameters']['default_blocks_class'];
			elseif (!empty($line['layout_parameters']['alias']))
				$data[$block_id]['layout_class'] = substr($line['layout_parameters']['alias'], strrpos($line['layout_parameters']['alias'], '/') + 1);
		}
		$block_media = $media[$block_id] ?? [];
		if (!$block_media) continue;
		$data[$block_id]['image'] = brick_request_link($block_media, ['image', 1, $size], 'sequence');
	}
	unset($line);

	$my_page['text'] = wrap_template('blocks', $data);
	$my_page = wrap_page_replace($my_page);
	return $my_page['text'];
}
