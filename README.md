What is this? <a name="what"></a>
-------------

This is a set of PHP classes, each representing a Markdown flavor, for converting Markdown to HTML.

The implementation focus is to be **fast** and **extensible**. Parsing Markdown to HTML is as simple as calling a single method (see [Usage](#usage)), providing a solid implementation that gives most expected results even in non-trivial edge cases.

Extending the Markdown language with new elements is as simple as adding a new method to the class that converts the Markdown text to the expected output in HTML. This is possible without dealing with complex and error prone regular expressions. It is also possible to hook into the Markdown structure and add elements or read meta information using the internal representation of the Markdown text as an abstract syntax tree (see [Extending the language](#extend)).

Currently the following Markdown flavors are supported:

- [CommonMark](https://spec.commonmark.org/)
- [GitHub-Flavored Markdown](https://github.github.com/gfm/)
- [Chyrp-Flavoured Markdown](https://chyrplite.net/wiki/Chyrp-Flavoured-Markdown.html)

Requirements <a name="requirements"></a>
-----------

- [PHP 8.0+](http://www.php.net/downloads.php) is required.
- UTF-8 is the only supported text encoding.

Limitations <a name="limitations"></a>
-----------

To be as fast and efficient as possible, the parser is limited in a few ways:
1. It does not allow leading spaces for most block markers.
2. It does not allow you to combine tabs and spaces when indenting text.
3. It does not support "lazy" blockquotes.
4. It does not support link and image definitions that span multiple lines.

This means that it does not conform 100% to the CommonMark and GFM specifications.

Usage <a name="usage"></a>
-----

The first step is to choose the Markdown flavor and instantiate the parser:

- CommonMark: `$parser = new \cebe\markdown\Markdown();`
- GitHub-Flavored Markdown: `$parser = new \cebe\markdown\GithubMarkdown();`
- Chyrp-Flavoured Markdown: `$parser = new \cebe\markdown\ChyrpMarkdown();`

The next step is to call the method `parse()` for parsing the text using the full Markdown language,
or call the method `parseParagraph()` to parse only inline elements.

Here are some examples:

```php
// CommonMark; parse full text
$parser = new \cebe\markdown\Markdown();
echo $parser->parse($markdown);

// GFM
$parser = new \cebe\markdown\GithubMarkdown();
echo $parser->parse($markdown);

// CFM
$parser = new \cebe\markdown\ChyrpMarkdown();
echo $parser->parse($markdown);

// GFM; parse only inline elements (useful for one-line descriptions)
$parser = new \cebe\markdown\GithubMarkdown();
echo $parser->parseParagraph($markdown);
```

You may optionally set one of the following options on the parser object before parsing:

- `$parser->html5 = true` to enable HTML5 output instead of HTML4.
- `$parser->convertTabsToSpaces` to to convert all tabs into 4 spaces before parsing.
- `$parser->contextID` to set an optional context identifier string for this instance.
- `$parser->keepListStartNumber = true` to enable keeping the numbers of ordered lists as specified in the Markdown. The default behavior is to always start from 1 and increment by one regardless of the number in Markdown.

For GithubMarkdown:

- `$parser->enableNewlines = true` to convert all newlines to `<br/>` tags. By default only newlines with two preceding spaces or ending in `\` are converted to `<br/>` tags.

Security Considerations <a name="security"></a>
-----------------------

By design Markdown [allows HTML to be included within the Markdown text](https://spec.commonmark.org/0.31.2/#html-blocks). This also means that it may contain Javascript and CSS styles. This allows to be very flexible for creating output that is not limited by the Markdown syntax, but it comes with a security risk if you are parsing user input as Markdown (see [XSS](https://en.wikipedia.org/wiki/Cross-site_scripting)).

In that case you should process the result of the Markdown conversion with tools like [HTML Purifier](http://htmlpurifier.org/) that filter out all elements which are not allowed for users to be added.

Extending the language <a name="extend"></a>
----------------------

Markdown consists of two types of language elements, I'll call them block and inline elements simlar to what you have in HTML with `<div>` and `<span>`. Block elements are normally spreads over several lines and are separated by blank lines. The most basic block element is a paragraph (`<p>`). Inline elements are elements that are added inside of block elements i.e. inside of text.

This Markdown parser allows you to extend the Markdown language by changing existing elements behavior and also adding new block and inline elements. You do this by extending from the parser class and adding/overriding class methods and properties. For the different element types there are different ways to extend them as you will see in the following sections.

### Adding block elements

The Markdown is parsed line by line to identify each non-empty line as one of the block element types. To identify a line as the beginning of a block element it calls all protected class methods who's name begins with `identify`. An identify function returns true if it has identified the block element it is responsible for or false if not.

Parsing of a block element is done in two steps:

1. **Consuming** all the lines belonging to it. In most cases this is iterating over the lines starting from the identified line until an end condition occurs. This step is implemented by a method named `consume{blockName}()` where `{blockName}` is the same name as used for the identify function above. The consume method also takes the lines array and the number of the current line. It will return two arguments: an array representing the block element in the abstract syntax tree of the Markdown document and the line number to parse next. In the abstract syntax array the first element refers to the name of the element, all other array elements can be freely defined by yourself.

2. **Rendering** the element. After all blocks have been consumed, they are being rendered using the method `render{elementName}()` where `elementName` refers to the name of the element in the abstract syntax tree. You may also add code highlighting here.

### Adding inline elements

Adding inline elements is different from block elements as they are parsed using markers in the text.
An inline element is identified by a marker that marks the beginning of an inline element (e.g. `[` will mark a possible beginning of a link or `` ` `` will mark inline code).

Parsing methods for inline elements are also protected and identified by the prefix `parse`. Additionally a matching method name suffixed with `Markers` is needed to register the parse function for one or multiple markers. E.g. `parseEscape()` and `parseEscapeMarkers()`. The method will then be called when a marker is found in the text. As an argument it takes the text starting at the position of the marker. The parser method will return an array containing the element of the abstract sytnax tree and an offset of text it has parsed from the input Markdown. All text up to this offset will be removed from the Markdown before the next marker will be searched.

### Composing your own Markdown flavor

This Markdown library is composed of traits so it is very easy to create your own Markdown flavor by adding and/or removing the single feature traits.

Designing your Markdown flavor consists of four steps:

1. Select a base class to extend;
2. Select language feature traits;
3. Define escapeable characters;
4. Optionally add custom rendering behavior.

#### Select a base class

If you want to extend from a flavor and only add features you can use one of the existing classes
(`Markdown`, `GithubMarkdown` or `ChyrpMarkdown`) as your base class.

If you want to define a subset of the Markdown language, i.e. remove some of the features, you have to extend your class from `Parser`.

#### Select language feature traits

In general, just adding traits with `use` is enough, however there is a conflict for parsing of the `<` character. This could either be a link/email enclosed in `<` and `>` or an inline HTML tag. In order to resolve this conflict when adding the `LinkTrait`, you need to hide the `parseInlineHtml` method of the `HtmlTrait`.

If you use any trait that references the `$html5` property to adjust its output you also need to define this property in your flavor.

If you use the link trait or footnote trait it may be useful to implement `prepare()` to reset references before parsing to ensure you get a reusable object.

#### Define escapeable characters

Depending on the language features you have chosen there is a different set of characters that can be escaped using `\`. The parser defines only backslash initially.

#### Add custom rendering behavior

Optionally you may also want to adjust rendering behavior by overriding some methods. You may refer to the `consumeParagraph()` method of the `Markdown` and `GithubMarkdown` classes for inspiration on different rules defining which elements are allowed to interrupt a paragraph.

Acknowledgements <a name="ack"></a>
----------------

Carsten Brandt would like to thank [@erusev][] for creating [Parsedown][] which heavily influenced this work and provided the idea of the line based parsing approach.

[@erusev]: https://github.com/erusev "Emanuil Rusev"
[Parsedown]: http://parsedown.org/ "The Parsedown PHP Markdown parser"

### Authors

This software was created by the following people:

* cebe/markdown: Carsten Brandt
* xenocrat/chyrp-markdown: Daniel Pimley

### License

This library is open source and licensed under the [MIT License][]. Check the [license][] for details.

[MIT License]: http://opensource.org/licenses/MIT
[license]: https://github.com/xenocrat/chyrp-markdown/blob/master/LICENSE
