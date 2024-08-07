<!--
# default module
# markdown help, German
#
# Part of »Zugzwang Project«
# https://www.zugzwang.org/modules/default
#
# @author Gustaf Mossakowski <gustaf@koenige.org>
# @copyright Copyright © 2024 Gustaf Mossakowski
# @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
#
-->

# Markdown

## HTML

HTML kann direkt in den Text geschrieben werden und wird direkt
ausgegeben. Blockelemente sollten durch Leerzeilen vom Text abgetrennt
und die Start- und Endmarkierungen nicht eingerückt werden. Innerhalb
von HTML-Blockelementen (Tabellen, Überschriften, Absätzen etc.) wird
kein Markdown interpretiert.

Beispiel:

	Normaler Absatz.

	<table>
		<tr>
			<td>Bla</td>
		</tr>
	</table>
	
	Neuer Absatz, hier kann auch eine 
	<abbr title="Abkürzung">Abk.</abbr> eingebaut werden.

## Sonderzeichen

& sowie < und > werden von Markdown automatisch maskiert.

## Block-Elemente

### Absätze und Zeilenumbrüche

Ein Absatz besteht aus mehreren Zeilen, die von anderen Absätzen durch
eine Leerzeile abgetrennt werden.

Zeilenumbrüche können durch das Einfügen von zwei Leerzeichen am Ende
einer Zeile erzwungen werden.

### Überschriften

	Dies ist eine Überschrift 1. Ordnung (H1)
	=========================================

	Dies ist eine Überschrift 2. Ordnung (H2)
	-----------------------------------------

oder

	# Dies ist eine H1

	## Dies ist eine H2

	###### Dies ist eine H6

oder

	# Dies ist eine H1 #

	## Dies ist eine H2 ##

	### Dies ist eine H3 ######

### Zitate

	> Zitatblöcke funktionieren wie in E-Mails.
	> Zitate können auch verschachtelt werden:
	>> Dies ist ein Zitat von meinem Vorredner.

### Listen

	* unsortierte Listen werden mit * oder
	* mit - oder
	* mit + als erstem Zeichen einer Zeile markiert.

	1. Sortierte Listen
	2. beginnen mit Zahlen, Punkt und Leerzeichen
	
	* Hierarchische Listen
	  * mit zwei Leerzeichen Einrückung
	  * dann wird die Liste automatisch hierarchisch dargestellt
	* weitere Punkte wieder ausgerückt

Da jede Nummer mit Punkt und Leerzeichen dazu führt, dass Text als
Listeneintrag interpretiert wird, kann man dies durch Einfügen eines \
verhindern:

	1986\. Was für ein wundervolles Jahr.
	
### Code-Blöcke

Code-Blöcke (Programmauszüge, HTML-Schnipsel) werden mit vier
Leerzeichen oder einem Tabulator eingerückt und werden dann so
dargestellt wie eingegeben.

### Horizontale Linien

	* * *

	***

	*****

	- - -

	---------------------------------------

	_ _ _

## Text-Elemente

### Links

Direkt im Text:

	Dies ist [ein Beispiel](http://example.com/ "Titel") Inline-Link.
	
	[Dieser Link](http://example.net/) hat kein Titel-Attribut.

	Auf der Seite [Über mich](/about/) gibt es weitere Informationen.

Oder als Referenz im Text, mit Angabe der URL am Ende eines Textes.
Groß- und Kleinschreibung wird dabei nicht beachtet.

	Dies ist [ein Beispiel][id] für einen Referenz-Link.

	[id]: http://example.com/  "Optionalen Titel hier eintragen"

Linkreferenzen können auch abgekürzt werden:

	[Google][]
	
	[Google]: http://google.com/

### Betonung

	*Betonter Text, wird meist kursiv dargestellt*

	_Betonter Text, wird meist kursiv dargestellt_
	
	**Stark betonter Text, wird meist fett dargestellt**
	
	__Stark betonter Text, wird meist fett dargestellt__
	
	\*Text, von Sternchen umschlossen \*

### Code

Code-Blöcke werden in Zollzeichen ('' ' '') eingebunden. Enthält der
Code selber ein Zollzeichen, muß der Code mit zwei Zollzeichen
eingeführt und beendet werden.

	Z. B. HTML-Code: '<title>' 
	
	''Code mit ' im Text.''
	

### Grafiken

Direkt im Text

	![Alternativer Text](/pfad/zu/img.jpg)

	![Alternativer Text](/pfad/zu/img.jpg "Optionaler Titel")

oder als Referenz im Text, mit Angabe der URL am Ende eines Textes

	![Alternativer Text][id]

	[id]: url/zur/grafik  "Optionales title-Attribut"

### einfache Links

	<http://www.example.com/>

	<address@example.com>

### Maskierung

Da einige Zeichen an bestimmten Stellen in Markdown eine besondere
Bedeutung haben, muß man sie mit einem '\' maskieren, wenn man sie
trotzdem darstellen möchte:

	\ ' * _ {} [] () # + - . !

***
	
* [Komplette Referenz Markdown, Original auf Englisch](http://daringfireball.net/projects/markdown/syntax)
* [Komplette Referenz Markdown, Deutsche Übersetzung](http://markdown.de/syntax/)

***

Markdown-Extra            {#extra}
==============

Markdown-Extra ist eine Erweiterung zu Markdown. Dadurch gibt es ein
paar weitere Möglichkeiten:

### Abkürzungen

sehen ähnlich wie Links aus und werden an das Ende des Textes gestellt.
Die Abkürzung wird dann überall im Text automatisch mit der Langform
hinterlegt. 

	*[BBR]: Bundesamt für Bauwesen und Raumordnung

### Tabellen

sind etwas komplizierter: einzelne Tabellenzellen werden durch |
getrennt, jede Tabelle benötigt einen Tabellenkopf (wichtig auch für die
Barrierefreiheit)

  Tabellenbeispiel:

	Jahr    | Ereignis 
	------- | ------------------
	1969    | Mondlandung
	1989    | Mauerfall 

### Markdown innerhalb von HTML-Blöcken

funktioniert mit &lt;div markdown="1"> ... &lt;/div> - so kann man z. B.
einfach Klassen auf Markdown-Bereiche anwenden und den Bereich darüber
formatieren.

Dazu kommen weitere Formatierungsmöglichkeiten für Fußnoten,
Definitionslisten (in HTML: DL, DT, DD) und Linkanker bei Überschriften.

***

* [Komplette Referenz Markdown-Extra, Original auf Englisch](http://michelf.com/projects/php-markdown/extra/)
