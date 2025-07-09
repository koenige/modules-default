<!--
# default module
# markdown help
#
# Part of »Zugzwang Project«
# https://www.zugzwang.org/modules/default
#
# @author Gustaf Mossakowski <gustaf@koenige.org>
# @copyright Copyright © 2024-2025 Gustaf Mossakowski
# @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
#
-->


# Markdown

Markdown simplifies the input of formatted content in web forms by using
easy-to-learn syntax for headings, lists, links, or emphasis — without
requiring any HTML knowledge.

## Paragraphs and Line Breaks

* A paragraph consists of multiple lines and is separated from the next
paragraph by a **blank line**.
* A **line break** within a paragraph is
created by **two spaces at the end of the line**.

## Headings

There are several ways to write headings:

    # 1st level heading (H1)
    ## 2nd level heading (H2)
    ### 3rd level heading (H3)
    #### 4th level heading (H4)
    ##### 5th level heading (H5)
    ###### 6th level heading (H6)

Or alternatively with `=` and `-`:

    Heading 1 (H1)
    ========================================= 
    
    Heading 2 (H2)
    -----------------------------------------

## Lists

**Unsorted lists:**

    * Topic,
    - topic or
    + topic

**Sorted lists:**

    1. First topic
    2. Second topic, etc.

*Hierarchical lists:*

    * Main topic
      * Sub topic (indented with two spaces)
      * then the list is automatically displayed hierarchically
    * further main topic (again outdented)

Tip: If a number should be displayed with a period at the beginning of a
line it must be marked with a `\` (backslash) to prevent it from
automatically becoming a sorted list:

    1986\. What a wonderful year.

## Text formatting

*Emphasized text:*

    *italic* or _italic_

*Strongly emphasized (or highlighted) text:*

    **bold** or __bold__
    
And here too, if you don’t want that, escape it with a backslash:

    \*escaped asterisk*

## Links

Directly in the text (**inline**):

    This is [an example](http://example.com/ "title") inline link.
    
    [This link](http://example.net/) has no title attribute.
    
    There is more information on the [About me](/about/) page.

Or as a **reference** in the text, with the URL specified at the end of
a text. Case is not important.

    This is [an example][id] of a reference link.
    
    [id]: http://example.com/ "Optional title"

**Link references** can also be abbreviated:

    [Google][]
    
    [Google]: http://google.com/

**Simple links**, if web or email addresses should remain visible:

    <http://www.example.com/>
    <address@example.com>
    
## Images and graphics

Directly in the text

    ![Alternative text](/path/to/img.jpg)
    
    ![Alternative text](/path/to/img.jpg "Title")
    
or as a reference in the text, with the URL specified at the end of a text

    ![Alternative text][id]
    
    [id]: /path/to/graphic "Title"
    
## Quotations

    > This is a quotation.
    >> Nested quotation.

## Code

**Inline code:** Code blocks are enclosed in <code>\`</code>. If the
code itself contains such a character, the code must be introduced and
ended with two characters.

    E.g. HTML code: `<title>`
 
    ``Code with ` in the text.``
  
    `Code`

**Code blocks** (program excerpts, HTML snippets) can be indented with
four spaces or one tab and are then displayed as entered.

    <html>
        <head></head>
    </html>

## Horizontal lines

	* * *

	***

	- - -

	---

## Masking of special characters

`&` as well as `<` and `>` are automatically masked by Markdown.

Since some characters have a special meaning in certain places in
Markdown, they must be masked with a '\' if they still want to be
displayed:

    \ ' * _ {} [] () # + - . !

## HTML in Markdown

HTML can be used directly in Markdown. Important:

* Separate block elements with blank lines
* Do not indent tags
* Markdown is not interpreted within HTML blocks

Example:

    Normal paragraph.

	<table>
		<tr>
			<td>Blah</td>
		</tr>
	</table>
    
    New paragraph, an <abbr title="Abbreviation">abbreviation</abbr> can
    also be inserted here.
    
## Extensions: Markdown-Extra

Markdown-Extra is an extension to Markdown. This offers a few more options:

### Abbreviations

look similar to links and are placed at the end of the text. The
abbreviation is then automatically included with the long form
throughout the text.

    *[BBR]: Federal Office for Building and Regional Planning

### Tables

are a bit more complicated: individual table cells are separated by `|`,
each table requires a table header (also important for accessibility).
Table example:

    Year    | Event
    ------- | ------------------
    1969    | Moon landing
    1989    | Fall of the Berlin Wall
    
### Markdown within HTML blocks

works with `<div markdown="1"> … </div>` –this way you can, for
example, simply apply classes to Markdown areas and format the area
above.

There are also additional formatting options for footnotes,
definition lists (in HTML: DL, DT, DD), and link anchors in headings.

***

## References

* [Complete Markdown Reference](https://daringfireball.net/projects/markdown/syntax)
* [Complete Markdown Extra Reference](https://michelf.ca/projects/php-markdown/extra/)
