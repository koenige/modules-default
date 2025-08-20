<!--
# default module
# Help: Search with modules
#
# Part of »Zugzwang Project«
# https://www.zugzwang.org/modules/default
#
# @author Gustaf Mossakowski <gustaf@koenige.org>
# @copyright Copyright © 2025 Gustaf Mossakowski
# @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
#
-->

# Search with modules

The default module offers a possibility to show search results from
several modules on one page.

Search can be included with the brick `%%% request search %%%`. This
function looks inside each module for a function `mf_<module>_search()`
in the file `<module>/search.inc.php`. If you just want search results
from one or more modules, you can restrict the search to these modules:
`%%% request search module=ratings %%%`

This function has one parameter, `array $q`, the search term split up
into words. The default search function expects an array. This array
needs an index with the module key and the results. Normally with
numerical indexes inside, depending how you output will be, but this can
be a simple, pre-formatted string as well.

Each module needs to have a template, too:
`templates/search-<module>.template.txt`. Here, the results can be
printed out using `%%% loop ratings %%%`

## Settings

- `default_search_heading_for_results`
- `default_searchform_bottom`
- `default_searchform_top`
