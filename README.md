What is this?
-------------

This is a set of PHP classes for converting Markdown to HTML, with a focus on speed and simplicity. The parser can be extended to recognize new elements by adding new traits to a Markdown flavor, or by defining an entirely new flavor as an extension of the base parser class.

Currently the following Markdown flavors are supported:

- [CommonMark](https://spec.commonmark.org/)
- [GitHub-Flavored Markdown](https://github.github.com/gfm/)
- [GitLab-Flavored Markdown](https://docs.gitlab.com/ee/user/markdown.html)
- [Chyrp-Flavoured Markdown](https://chyrplite.net/wiki/Chyrp-Flavoured-Markdown.html)

Requirements
-----------

- PHP 8.0+ is required.
- UTF-8 is the only supported text encoding.

Limitations
-----------

Because it is focused on speed, the parser is limited in some ways that result in it not being completely conformant with the CommonMark and GFM specifications. Currently it is able to pass 77% of CommonMark test cases and 76% of GFM test cases.

The most notable limitations of the parser are:
1. It does not support indentation with intermingled tabs and spaces;
2. It does not recognize setext headings that span multiple lines;
3. It does not allow "lazy" continuation lines in blockquotes or lists.

Usage
-----

The first step is to choose the Markdown flavor and instantiate the parser:
- CommonMark:  
  `$parser = new \xenocrat\markdown\Markdown();`
- GitHub-Flavored Markdown:  
  `$parser = new \xenocrat\markdown\GithubMarkdown();`
- GitLab-Flavored Markdown:  
  `$parser = new \xenocrat\markdown\GitlabMarkdown();`
- Chyrp-Flavoured Markdown:  
  `$parser = new \xenocrat\markdown\ChyrpMarkdown();`

The next step is to call the parser method:
- Use `parse()` for parsing the text using the full Markdown language;
- Use `parseParagraph()` to parse only inline elements in the text.

Here are some examples:

```php
// CommonMark; parse full text
$parser = new \xenocrat\markdown\Markdown();
echo $parser->parse($markdown);

// GFM
$parser = new \xenocrat\markdown\GithubMarkdown();
echo $parser->parse($markdown);

// GLM
$parser = new \xenocrat\markdown\GitlabMarkdown();
echo $parser->parse($markdown);

// CFM
$parser = new \xenocrat\markdown\ChyrpMarkdown();
echo $parser->parse($markdown);

// CommonMark; parse only inline elements (useful for one-line descriptions)
$parser = new \xenocrat\markdown\Markdown();
echo $parser->parseParagraph($markdown);
```

You may set one of the following options on the parser object before parsing:

- `$parser->html5 = true` to enable HTML5 output instead of HTML4.
- `$parser->convertTabsToSpaces = true` to convert all tabs into 4 spaces before parsing.
- `$parser->setContextId(string)` to set an identifier string for the rendering context.
- `$parser->maximumNestingLevel = int` to set the maximum level of nested elements to parse.
- `$parser->maximumNestingLevelThrow = true` to throw if the maximum nesting level is exceeded.
- `$parser->keepListStartNumber = false` to ignore the starting numbers of ordered lists.
- `$parser->keepReversedList = true` to enable ordered lists with descending numbers.
- `$parser->headlineAnchors = true` to add GitHub-style anchors when rendering headings.
- `$parser->renderLazyImages = true` to render images with a deferred loading attribute.
- `$parser->enableImageDimensions = false` to disable extended syntax for image dimensions.

For GitHub-Flavored Markdown:

- `$parser->enableNewlines = true` to convert all newlines in the text to `<br/>` tags.
- `$parser->renderCheckboxInputs = true` to render task items as inputs instead of emoji.
- `$parser->disallowedRawHTML = false` to disable section 6.11 of the GFM specification.

For GitLab-Flavored Markdown:

- `$parser->enableNewlines = true` to convert all newlines in the text to `<br/>` tags.
- `$parser->renderCheckboxInputs = true` to render task items as inputs instead of emoji.
- `$parser->renderFrontMatter = false` to disable rendering of front matter blocks as code.
- `$parser->renderOrderedToc = true` to render the table of contents as an ordered list.
- `$parser->renderLazyMedia = true` to render video and audio with a deferred loading attribute.

Security Considerations
-----------------------

By design Markdown [allows HTML to be included within the Markdown text](https://spec.commonmark.org/0.31.2/#html-blocks), meaning that the input may contain Javascript and CSS styles. This allows Markdown to be very flexible for creating output that is not limited by the Markdown syntax, but it comes with a security risk if you are parsing untrusted input (see [XSS](https://en.wikipedia.org/wiki/Cross-site_scripting)).

The GitHub-Flavored Markdown specification includes an extension to CommonMark, [Disallowed Raw HTML (section 6.11)](https://github.github.com/gfm/#disallowed-raw-html-extension-), which defines a subset of raw HTML to be filtered and rendered as text in the output. This parser implements section 6.11 of the GFM specification.

If you are parsing user input or any other type of untrusted input, you are strongly advised to process the resulting HTML with tools like [HTML Purifier](http://htmlpurifier.org/) that filter out all elements which you have chosen to disallow.

Extended image syntax
---------------------

By default, LinkTrait enables an extension to the Markdown syntax for specifying the intrinsic dimensions of an image. The image width and height can be specified as `![title](url){width}` or `![title](url){width:height}`, with `width` and `height` values being numbers between 1 and 999999999. See above if you wish to disable this extended syntax.

Extending the language
----------------------

Markdown consists of two types of language elements - let's call them block and inline elements, similar to what you have in HTML with `<div>` and `<span>`. Block elements are normally spread over several lines and are separated by blank lines. The most basic block element is a paragraph (`<p>`). Inline elements are elements that are added inside of block elements i.e. inside of text.

This Markdown parser allows you to extend the Markdown language by changing the behavior of existing elements and also adding new block and inline elements. You do this by extending from the parser class and adding/overriding class methods and properties. For the different element types there are different ways to extend them, as you will see in the following sections.

### Adding block elements

The Markdown is parsed line by line to identify each non-empty line as one of the block element types. To identify a line as the beginning of a block element it calls all protected class methods having a name beginning with `identify`. An identify method returns true if it has identified the block element it is responsible for or false if the line does not match its requirements.

Parsing a block element is done in three steps:

1. **Identifying** the method responsible for parsing a block, by calling all detected `identify{blockName}()` methods until one returns true.

2. **Consuming** all the lines belonging to a block, by iterating over the lines starting from the identified line until an end condition occurs. This step is implemented by a method named `consume{blockName}()` where `{blockName}` is the same name as used for the identify method above. The consume method also takes the lines array and the number of the current line. It will return two arguments: an array representing the block element in the abstract syntax tree of the Markdown document and the line number to parse next. In the abstract syntax array the first element refers to the name of the element, all other array elements can be freely defined by yourself.

3. **Rendering** the element. After all blocks have been consumed, each block is rendered using the method `render{elementName}()` where `elementName` refers to the name of the element in the abstract syntax tree.

### Adding inline elements

Adding inline elements is done differently from block elements because they are parsed using string markers in the text. An inline element is identified by a marker of one or more characters that marks the possible beginning of an inline element (e.g. `[` marks the possible beginning of a link or `` ` `` marks possible inline code).

Parsing an inline element is done in two steps:

1. **Parsing** methods for inline elements are protected and have names beginning with `parse`. Additionally a matching method suffixed with `Markers` is needed to register a parse method for one or more markers. E.g. `parseEscape()` and `parseEscapeMarkers()`. The parse method will be called when any of its registered markers is found in the text. As an argument the parse method takes the text starting at the position of the marker. The parser method will return an array containing an element to be added to the abstract sytnax tree and the offset of the text it has parsed from the input. All text up to this offset will be removed from the Markdown before the search continues for the next marker.

2. **Rendering** the element. Each element is rendered using the method `render{elementName}()` where `elementName` refers to the name of the element in the abstract syntax tree.

### Composing your own Markdown flavor

This Markdown parser is composed of traits so it is very easy to create your own Markdown flavor by adding and/or removing the single feature traits.

Designing your Markdown flavor consists of four steps:

1. Select a base class to extend;
2. Select language feature traits;
3. Define escapeable characters;
4. Optionally add custom rendering behavior.

#### Select a base class

If you want to extend a flavor and add features you can use one of the existing classes as your base class. If you want to define a subset of the Markdown language, i.e. remove some of the features, you have to extend your class from `Parser`.

#### Select language feature traits

In general, just adding traits with `use` is enough. During parsing, block identifiers added by traits are sorted and called in alphabetical order. This could be a problem if you create a trait to parse a block type that must be identified early. You can bust the alphabetical sort/call strategy by defining the property `blockPriorities` in your Markdown flavor and supplying a predefined call order for block identifier methods. Any methods detected at runtime that are not listed in the predefined call order will be called in alphabetical order after all predefined methods.

If you use HeadlineTrait, LinkTrait, or FootnoteTrait it may be useful to implement `prepare()` to reset variables before parsing to ensure you get a reusable parser object.

#### Define escapeable characters

Depending on the language features you have chosen to implement, a different set of characters must be defined as escapable using a backslash (`\`). The parser defines only backslash as escapable (`\\`) initially.

#### Add custom rendering behavior

Optionally you can adjust rendering behavior by overriding some methods. Refer to the `consumeParagraph()` method of the flavors for inspiration on different rules defining which elements are allowed to interrupt a paragraph.

Acknowledgements
----------------

Carsten Brandt would like to thank [@erusev][] for creating [Parsedown][] which heavily influenced this work and provided the idea of the line based parsing approach.

[@erusev]: https://github.com/erusev "Emanuil Rusev"
[Parsedown]: http://parsedown.org/ "The Parsedown PHP Markdown parser"

Authors
-------

This software was created by the following people:

* cebe/markdown: Carsten Brandt
* xenocrat/chyrp-markdown: Daniel Pimley

License
-------

This software is open source and licensed under the MIT License. See [LICENSE][] for details.

[LICENSE]: https://github.com/xenocrat/chyrp-markdown/blob/master/LICENSE
