<!--
# default module
# Help: Configuring detail tables based on categories
#
# Part of »Zugzwang Project«
# https://www.zugzwang.org/modules/default
#
# @author Gustaf Mossakowski <gustaf@koenige.org>
# @copyright Copyright © 2025-2026 Gustaf Mossakowski
# @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
#
# Variables
# audience = programmer
-->

# Configuring detail tables based on categories

For greater flexibility, it is possible to configure detail tables based
on entries in the `categories` table.

The form declares a **context** per subtable type. Category rows declare
where they apply via **`context`**, optional per-context overrides via
**`if`**, and reverse-relation settings via **`reversed`**.

## Form context

In the form script, set the context for each subtable type:

	$values['context']['roles'] = 'events_contacts';
	mf_default_categories_restrict($values, 'roles');

	$no = 60;
	foreach ($values['roles'] as $role)
		mf_contacts_contacts_subtable($zz, 'events', $role, $no++);

Several subtable types on one form each have their own context, e. g.:

	$values['context']['contactdetails'] = 'contacts_persons';
	$values['context']['relations'] = 'contacts_persons';
	$values['context']['categories'] = 'contacts_persons';

There is no form-wide default: `contacts-general` restricts only
relations, not contact details.

### Context identifiers

A context is either a **registered slug** or a **category path**.

**Registered slugs** are listed in the module’s `settings.cfg` with
`scope[] = context` on the section name, e. g. `contacts_persons`,
`events_contacts`, `news_articles`, `shop`. The section name is the
context string used in PHP and in `context[…]` / `if[…]` keys.

**Category paths** contain a slash, e. g. `contact/school`, `contact/club`.
They are valid when the path exists in the categories tree (resolved via
`wrap_category_id()`). They are not registered in `settings.cfg`.

### Multiple contexts (OR)

Normally one context string is used per subtable type. An **array of
category paths** is supported when a form applies to several contact
types; categories match if **any** path matches:

	$values['context']['relations'] = ['contact/school', 'contact/club'];

This OR rule applies only to several category paths, not to mixing slug
contexts.

### Unknown context

If a context string is neither a registered slug nor a resolvable
category path, the engine logs **`E_USER_NOTICE`** and skips that
context.

## Category parameters

### `context`

On a category row, tag inclusion for a context:

	context[contacts_persons]=1
	context[contacts_organisations]=1
	context[contact/school]=1
	context[events_contacts]=1
	context[shop]=1

`mf_default_categories_restrict()` loads categories from the subtree and
keeps those with `context[$context]=1` for the form’s context.

### `if`

Per-context overrides on a category row. The `if[$context]` block has the
same shape as category `parameters` and is merged when the context
applies (field overrides under `zzform`):

	if[contacts_places][zzform][title]=Telefon
	if[contacts_places][zzform][explanation]=Festnetz vor Ort. Bitte im Format %2B49 4321 56789
	if[shop][zzform][max_records]=1
	if[contacts_organisations][reverse_relation]=1
	if[contacts_organisations][contacts_details_separate]=1

When several contexts match (path OR), matching `if` blocks are merged;
later blocks override earlier ones.

### `reversed`

When a relation is shown from the main-contact side, use
`if[$context][reverse_relation]=1` and optional overrides:

	reversed[path]=local-group
	reversed[add_details]=/db/local-groups/
	reversed[category]=Local Groups

## Restricting categories (engine)

`mf_default_categories_restrict()` in the module’s zzform/definition file
reads `$values['context'][$type]`, filters categories by `context`, applies
`if` and `reversed`, and fills `$values[$type]`.

## Using detail tables with identical keys

Some detail tables allow linking e. g. contacts to other contacts. Both
foreign keys have the same type (`contact_id`, `main_contact_id` in
`contacts_contacts`), so the definition can be used from the child form
and from the main-contact form.

1. To use a different title, use `split_title=1` and a category name in
the order **normal / reverse** (i. e. `some_id / main_some_id`), e. g.
`Universities / Local Groups`. On the normal side (`contact_id`), the
first part (`Universities`) is used; on the reverse side
(`main_contact_id`), the second part (`Local Groups`).

2. For reverse settings, set `if[$context][reverse_relation]=1` and use
`reversed[…]` for overrides (see above).

3. To associate records, use `association=1`. Two detail records are shown
in combination: one with the main key and one with the normal key as
foreign key, via zzform’s `integrate_in_next`. A new record is always
added with main key as foreign key.
