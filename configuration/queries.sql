/**
 * default module
 * SQL queries
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


-- default_text__table --
/* text */

-- default_translationfields__table --
/* _translationfields */

-- default_translation_breadcrumbs__table --
/* webpages */

-- default_translation_pages__table --
/* webpages */

-- default_translations --
SELECT translation_id, translationfield_id, translation, field_id,
"/*_SETTING default_source_language _*/" AS source_language
FROM /*_PREFIX_*/_translations_%s translations
LEFT JOIN /*_PREFIX_*/languages languages USING (language_id)
WHERE translationfield_id IN (%s) 
AND field_id IN (%s)
AND languages.iso_639_1 = "%s"
