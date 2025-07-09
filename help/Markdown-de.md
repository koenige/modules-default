<!--
# default module
# markdown help, German
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

Markdown erleichtert in Webformularen die Eingabe formatierter Inhalte,
indem es einfache Zeichenfolgen für Überschriften, Listen, Links oder
Hervorhebungen verwendet – ganz ohne HTML-Kenntnisse.

## Absätze und Zeilenumbrüche

* Ein Absatz besteht aus mehreren Zeilen und wird durch eine **Leerzeile**
vom nächsten Absatz getrennt.
* Ein **Zeilenumbruch** innerhalb eines Absatzes wird durch **zwei
Leerzeichen am Zeilenende** erzeugt.

## Überschriften

Es gibt mehrere Möglichkeiten, Überschriften zu schreiben:

	# Überschrift 1. Ordnung (H1)
	## Überschrift 2. Ordnung (H2)
	### Überschrift 3. Ordnung (H3)
	#### Überschrift 4. Ordnung (H4)
	##### Überschrift 5. Ordnung (H5)
	###### Überschrift 6. Ordnung (H6)

Oder alternativ mit `=` und `-`:

	Überschrift 1 (H1)
	=========================================

	Überschrift 2 (H2)
	-----------------------------------------

## Listen

**Unsortierte Listen:**

	* Punkt,
	- Punkt oder
	+ Punkt

**Sortierte Listen:**

	1. Erster Punkt
	2. Zweiter Punkt usw.
	
*Hierarchische Listen:*
	
	* Hauptpunkt
	  * Unterpunkt (mit zwei Leerzeichen Einrückung)
	  * dann wird die Liste automatisch hierarchisch dargestellt
	* weiterer Hauptpunkte (wieder ausgerückt)

Tipp: Soll eine Zahl am Zeilenanfang mit einem Punkt dargestellt werden,
muss sie mit einem `\` (Backslash) markiert werden, um nicht automatisch
zur sortierten Liste zu werden:

	1986\. Was für ein wundervolles Jahr.

## Textformatierung

*Betonter Text:*

    *kursiv* oder _kursiv_

*Stark betonter (oder hervorgehobener) Text:*

	**fett** oder __fett__

Und auch hier, wenn man das nicht will, die Maskierung mit dem Backslash:

	\*maskiertes Sternchen*	

## Links

Direkt im Text (**inline**):

	Dies ist [ein Beispiel](http://example.com/ "Titel") Inline-Link.
	
	[Dieser Link](http://example.net/) hat kein Titel-Attribut.

	Auf der Seite [Über mich](/about/) gibt es weitere Informationen.

Oder als **Referenz** im Text, mit Angabe der URL am Ende eines Textes.
Groß- und Kleinschreibung ist dabei egal.

	Dies ist [ein Beispiel][id] für einen Referenz-Link.

	[id]: http://example.com/  "Optionaler Titel"

**Linkreferenzen** können auch abgekürzt werden:

	[Google][]
	
	[Google]: http://google.com/

**Einfache Links**, wenn Web- oder E-Mail-Adressen sichtbar bleiben sollen:

	<http://www.example.com/>

	<address@example.com>

## Bilder und Grafiken

Direkt im Text

	![Alternativer Text](/pfad/zu/img.jpg)

	![Alternativer Text](/pfad/zu/img.jpg "Titel")

oder als Referenz im Text, mit Angabe der URL am Ende eines Textes

	![Alternativer Text][id]

	[id]: /pfad/zur/grafik  "Titel"

## Zitate

	> Dies ist ein Zitat.
	>> Verschachteltes Zitat.

## Code

**Inline-Code:** Code-Blöcke werden in <code>\`</code> eingebunden.
Enthält der Code selber so ein Zeiche, muß der Code mit zwei Zeichen
eingeführt und beendet werden.

	Z. B. HTML-Code: `<title>`
	
	``Code mit ` im Text.``

    `Code`

**Code-Blöcke** (Programmauszüge, HTML-Schnipsel) können mit vier
Leerzeichen oder einem Tabulator eingerückt und werden dann so
dargestellt wie eingegeben.

    <html>
        <head></head>
    </html>

## Horizontale Linien

	* * *

	***

	- - -

	---

## Maskierung von Sonderzeichen

`&` sowie `<` und `>` werden von Markdown automatisch maskiert.

Da einige Zeichen an bestimmten Stellen in Markdown eine besondere
Bedeutung haben, muss man sie mit einem '\' maskieren, wenn man sie
trotzdem darstellen möchte:

	\ ' * _ {} [] () # + - . !

## HTML in Markdown

HTML kann direkt im Markdown verwendet werden. Wichtig:

* Blockelemente durch Leerzeilen abtrennen
* Keine Einrückung der Tags
* Innerhalb von HTML-Blöcken wird kein Markdown interpretiert

Beispiel:

	Normaler Absatz.

	<table>
		<tr>
			<td>Bla</td>
		</tr>
	</table>
	
	Neuer Absatz, hier kann auch eine 
	<abbr title="Abkürzung">Abk.</abbr> eingebaut werden.

## Erweiterungen: Markdown-Extra

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

funktioniert mit `<div markdown="1"> … </div>` - so kann man z. B.
einfach Klassen auf Markdown-Bereiche anwenden und den Bereich darüber
formatieren.

Dazu kommen weitere Formatierungsmöglichkeiten für Fußnoten,
Definitionslisten (in HTML: DL, DT, DD) und Linkanker bei Überschriften.

***

## Referenzen

* [Komplette Referenz Markdown, Original auf Englisch](https://daringfireball.net/projects/markdown/syntax)
* [Komplette Referenz Markdown, Deutsche Übersetzung](https://markdown.de/)
* [Komplette Referenz Markdown-Extra, Original auf Englisch](https://michelf.ca/projects/php-markdown/extra/)
