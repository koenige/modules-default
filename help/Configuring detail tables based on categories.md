<!--
# default module
# Help: Configuring detail tables based on categories
#
# Part of »Zugzwang Project«
# https://www.zugzwang.org/modules/default
#
# @author Gustaf Mossakowski <gustaf@koenige.org>
# @copyright Copyright © 2025 Gustaf Mossakowski
# @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
#
-->

# Configuring detail tables based on categories

For greater flexibility, it is possible to configure detail tables based
on entries in the `categories` table.

## Restricting categories

For this purpose, there’s a function `mf_default_categories_restrict()`
in the module’s zzform/definition file.

An example from the `events` module in conjunction with the `contacts`
module:

	$values['roles_restrict_to'] = 'events';
	mf_default_categories_restrict($values, 'roles');

	$no = 60;
	foreach ($values['roles'] as $role)
		mf_contacts_contacts_subtable($zz, 'events', $role, $no++);

This will restrict the linking of contacts to these categories:

- `roles`: categories that belong to the subtree roles
- `events`: among those categories, restrict the selection to categories
with the parameter `events=1`

## Using detail tables with identical keys

Some detail tables allow the linking of e. g. contacts to other contacts.
Thus the keys have the identical type. Typically, one key is defined as
child key and the other as main key, e. g., `contact_id` and
`main_contact_id` in `contacts_contacts`.

This means you can access the definition from two sides: from the form
containing the child records and from the form containing the main
records.

1. To use a different title, use `split_title=1` and use a category name
in the order of `contact_id / main_contact_id`, e. g. `Local Groups /
Universities`. If viewed from `contact_id`, `Universities` will be used
as title, if viewed from `main_contact_id`, `Local Groups` will be used.

2. To add reverse settings, add `_reverse` to each setting. Examples:

    add_details=/db/universities/ 
    add_details_reverse=/db/local-groups/
    path_reverse=local-group

3. To associate records, use `association=1`. Here, two detail records
are shown in combination: One with the main key and one with the normal
key as foreign key. This is done via zzform’s `integrate_in_next`. A new
record is always added with main key as foreign key.
