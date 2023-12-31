<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DomCrawler\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerTest extends TestCase
{
    public function testConstructor(): void
    {
        $crawler = new Crawler();
        $this->assertCount(0, $crawler, '__construct() returns an empty crawler');

        $doc = new \DOMDocument();
        $node = $doc->createElement('test');

        $crawler = new Crawler($node);
        $this->assertCount(1, $crawler, '__construct() takes a node as a first argument');
    }

    public function testGetUri(): void
    {
        $uri = 'http://symfony.com';
        $crawler = new Crawler(null, $uri);
        $this->assertEquals($uri, $crawler->getUri());
    }

    public function testGetBaseHref(): void
    {
        $baseHref = 'http://symfony.com';
        $crawler = new Crawler(null, null, $baseHref);
        $this->assertEquals($baseHref, $crawler->getBaseHref());
    }

    public function testAdd(): void
    {
        $crawler = new Crawler();
        $crawler->add($this->createDomDocument());
        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->add() adds nodes from a \DOMDocument');

        $crawler = new Crawler();
        $crawler->add($this->createNodeList());
        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->add() adds nodes from a \DOMNodeList');

        $list = [];
        foreach ($this->createNodeList() as $node) {
            $list[] = $node;
        }

        $crawler = new Crawler();
        $crawler->add($list);
        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->add() adds nodes from an array of nodes');

        $crawler = new Crawler();
        $crawler->add($this->createNodeList()->item(0));
        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->add() adds nodes from a \DOMNode');

        $crawler = new Crawler();
        $crawler->add('<html><body>Foo</body></html>');
        $this->assertEquals('Foo', $crawler->filterXPath('//body')->text(), '->add() adds nodes from a string');
    }

    public function testAddInvalidType(): void
    {
        $this->expectException('InvalidArgumentException');
        $crawler = new Crawler();
        $crawler->add(1);
    }

    public function testAddMultipleDocumentNode(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Attaching DOM nodes from multiple documents in the same crawler is forbidden.');
        $crawler = $this->createTestCrawler();
        $crawler->addHtmlContent('<html><div class="foo"></html>', 'UTF-8');
    }

    public function testAddHtmlContent(): void
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent('<html><div class="foo"></html>', 'UTF-8');

        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->addHtmlContent() adds nodes from an HTML string');
    }

    public function testAddHtmlContentWithBaseTag(): void
    {
        $crawler = new Crawler();

        $crawler->addHtmlContent('<html><head><base href="http://symfony.com"></head><a href="/contact"></a></html>', 'UTF-8');

        $this->assertEquals('http://symfony.com', $crawler->filterXPath('//base')->attr('href'), '->addHtmlContent() adds nodes from an HTML string');
        $this->assertEquals('http://symfony.com/contact', $crawler->filterXPath('//a')->link()->getUri(), '->addHtmlContent() adds nodes from an HTML string');
    }

    /**
     * @requires extension mbstring
     */
    public function testAddHtmlContentCharset(): void
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent('<html><div class="foo">Tiếng Việt</html>', 'UTF-8');

        $this->assertEquals('Tiếng Việt', $crawler->filterXPath('//div')->text());
    }

    public function testAddHtmlContentInvalidBaseTag(): void
    {
        $crawler = new Crawler(null, 'http://symfony.com');

        $crawler->addHtmlContent('<html><head><base target="_top"></head><a href="/contact"></a></html>', 'UTF-8');

        $this->assertEquals('http://symfony.com/contact', current($crawler->filterXPath('//a')->links())->getUri(), '->addHtmlContent() correctly handles a non-existent base tag href attribute');
    }

    public function testAddHtmlContentUnsupportedCharset(): void
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent(file_get_contents(__DIR__.'/Fixtures/windows-1250.html'), 'Windows-1250');

        $this->assertEquals('Žťčýů', $crawler->filterXPath('//p')->text());
    }

    /**
     * @requires extension mbstring
     */
    public function testAddHtmlContentCharsetGbk(): void
    {
        $crawler = new Crawler();
        //gbk encode of <html><p>中文</p></html>
        $crawler->addHtmlContent(base64_decode('PGh0bWw+PHA+1tDOxDwvcD48L2h0bWw+'), 'gbk');

        $this->assertEquals('中文', $crawler->filterXPath('//p')->text());
    }

    public function testAddHtmlContentWithErrors(): void
    {
        $internalErrors = libxml_use_internal_errors(true);

        $crawler = new Crawler();
        $crawler->addHtmlContent(<<<'EOF'
<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <nav><a href="#"><a href="#"></nav>
    </body>
</html>
EOF
        , 'UTF-8');

        $errors = libxml_get_errors();
        $this->assertCount(1, $errors);
        $this->assertEquals("Tag nav invalid\n", $errors[0]->message);

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);
    }

    public function testAddXmlContent(): void
    {
        $crawler = new Crawler();
        $crawler->addXmlContent('<html><div class="foo"></div></html>', 'UTF-8');

        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->addXmlContent() adds nodes from an XML string');
    }

    public function testAddXmlContentCharset(): void
    {
        $crawler = new Crawler();
        $crawler->addXmlContent('<html><div class="foo">Tiếng Việt</div></html>', 'UTF-8');

        $this->assertEquals('Tiếng Việt', $crawler->filterXPath('//div')->text());
    }

    public function testAddXmlContentWithErrors(): void
    {
        $internalErrors = libxml_use_internal_errors(true);

        $crawler = new Crawler();
        $crawler->addXmlContent(<<<'EOF'
<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <nav><a href="#"><a href="#"></nav>
    </body>
</html>
EOF
        , 'UTF-8');

        $this->assertGreaterThan(1, libxml_get_errors());

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);
    }

    public function testAddContent(): void
    {
        $crawler = new Crawler();
        $crawler->addContent('<html><div class="foo"></html>', 'text/html; charset=UTF-8');
        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->addContent() adds nodes from an HTML string');

        $crawler = new Crawler();
        $crawler->addContent('<html><div class="foo"></html>', 'text/html; charset=UTF-8; dir=RTL');
        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->addContent() adds nodes from an HTML string with extended content type');

        $crawler = new Crawler();
        $crawler->addContent('<html><div class="foo"></html>');
        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->addContent() uses text/html as the default type');

        $crawler = new Crawler();
        $crawler->addContent('<html><div class="foo"></div></html>', 'text/xml; charset=UTF-8');
        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->addContent() adds nodes from an XML string');

        $crawler = new Crawler();
        $crawler->addContent('<html><div class="foo"></div></html>', 'text/xml');
        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->addContent() adds nodes from an XML string');

        $crawler = new Crawler();
        $crawler->addContent('foo bar', 'text/plain');
        $this->assertCount(0, $crawler, '->addContent() does nothing if the type is not (x|ht)ml');

        $crawler = new Crawler();
        $crawler->addContent('<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><span>中文</span></html>');
        $this->assertEquals('中文', $crawler->filterXPath('//span')->text(), '->addContent() guess wrong charset');
    }

    /**
     * @requires extension iconv
     */
    public function testAddContentNonUtf8(): void
    {
        $crawler = new Crawler();
        $crawler->addContent(iconv('UTF-8', 'SJIS', '<html><head><meta charset="Shift_JIS"></head><body>日本語</body></html>'));
        $this->assertEquals('日本語', $crawler->filterXPath('//body')->text(), '->addContent() can recognize "Shift_JIS" in html5 meta charset tag');
    }

    public function testAddDocument(): void
    {
        $crawler = new Crawler();
        $crawler->addDocument($this->createDomDocument());

        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->addDocument() adds nodes from a \DOMDocument');
    }

    public function testAddNodeList(): void
    {
        $crawler = new Crawler();
        $crawler->addNodeList($this->createNodeList());

        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->addNodeList() adds nodes from a \DOMNodeList');
    }

    public function testAddNodes(): void
    {
        $list = [];
        foreach ($this->createNodeList() as $node) {
            $list[] = $node;
        }

        $crawler = new Crawler();
        $crawler->addNodes($list);

        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->addNodes() adds nodes from an array of nodes');
    }

    public function testAddNode(): void
    {
        $crawler = new Crawler();
        $crawler->addNode($this->createNodeList()->item(0));

        $this->assertEquals('foo', $crawler->filterXPath('//div')->attr('class'), '->addNode() adds nodes from a \DOMNode');
    }

    public function testClear(): void
    {
        $doc = new \DOMDocument();
        $node = $doc->createElement('test');

        $crawler = new Crawler($node);
        $crawler->clear();
        $this->assertCount(0, $crawler, '->clear() removes all the nodes from the crawler');
    }

    public function testEq(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//li');
        $this->assertNotSame($crawler, $crawler->eq(0), '->eq() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->eq() returns a new instance of a crawler');

        $this->assertEquals('Two', $crawler->eq(1)->text(), '->eq() returns the nth node of the list');
        $this->assertCount(0, $crawler->eq(100), '->eq() returns an empty crawler if the nth node does not exist');
    }

    public function testEach(): void
    {
        $data = $this->createTestCrawler()->filterXPath('//ul[1]/li')->each(static fn($node, $i): string => $i.'-'.$node->text());

        $this->assertEquals(['0-One', '1-Two', '2-Three'], $data, '->each() executes an anonymous function on each node of the list');
    }

    public function testIteration(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//li');

        $this->assertInstanceOf('Traversable', $crawler);
        $this->assertContainsOnlyInstancesOf('DOMElement', iterator_to_array($crawler), 'Iterating a Crawler gives DOMElement instances');
    }

    public function testSlice(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//ul[1]/li');
        $this->assertNotSame($crawler->slice(), $crawler, '->slice() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler->slice(), '->slice() returns a new instance of a crawler');

        $this->assertCount(3, $crawler->slice(), '->slice() does not slice the nodes in the list if any param is entered');
        $this->assertCount(1, $crawler->slice(1, 1), '->slice() slices the nodes in the list');
    }

    public function testReduce(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//ul[1]/li');
        $nodes = $crawler->reduce(static fn($node, $i): bool => 1 !== $i);
        $this->assertNotSame($nodes, $crawler, '->reduce() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $nodes, '->reduce() returns a new instance of a crawler');

        $this->assertCount(2, $nodes, '->reduce() filters the nodes in the list');
    }

    public function testAttr(): void
    {
        $this->assertEquals('first', $this->createTestCrawler()->filterXPath('//li')->attr('class'), '->attr() returns the attribute of the first element of the node list');

        try {
            $this->createTestCrawler()->filterXPath('//ol')->attr('class');
            $this->fail('->attr() throws an \InvalidArgumentException if the node list is empty');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertTrue(true, '->attr() throws an \InvalidArgumentException if the node list is empty');
        }
    }

    public function testMissingAttrValueIsNull(): void
    {
        $crawler = new Crawler();
        $crawler->addContent('<html><div non-empty-attr="sample value" empty-attr=""></div></html>', 'text/html; charset=UTF-8');

        $div = $crawler->filterXPath('//div');

        $this->assertEquals('sample value', $div->attr('non-empty-attr'), '->attr() reads non-empty attributes correctly');
        $this->assertEquals('', $div->attr('empty-attr'), '->attr() reads empty attributes correctly');
        $this->assertNull($div->attr('missing-attr'), '->attr() reads missing attributes correctly');
    }

    public function testNodeName(): void
    {
        $this->assertEquals('li', $this->createTestCrawler()->filterXPath('//li')->nodeName(), '->nodeName() returns the node name of the first element of the node list');

        try {
            $this->createTestCrawler()->filterXPath('//ol')->nodeName();
            $this->fail('->nodeName() throws an \InvalidArgumentException if the node list is empty');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertTrue(true, '->nodeName() throws an \InvalidArgumentException if the node list is empty');
        }
    }

    public function testText(): void
    {
        $this->assertEquals('One', $this->createTestCrawler()->filterXPath('//li')->text(), '->text() returns the node value of the first element of the node list');

        try {
            $this->createTestCrawler()->filterXPath('//ol')->text();
            $this->fail('->text() throws an \InvalidArgumentException if the node list is empty');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertTrue(true, '->text() throws an \InvalidArgumentException if the node list is empty');
        }
    }

    public function testHtml(): void
    {
        $this->assertEquals('<img alt="Bar">', $this->createTestCrawler()->filterXPath('//a[5]')->html());
        $this->assertEquals('<input type="text" value="TextValue" name="TextName"><input type="submit" value="FooValue" name="FooName" id="FooId"><input type="button" value="BarValue" name="BarName" id="BarId"><button value="ButtonValue" name="ButtonName" id="ButtonId"></button>', trim(preg_replace('~>\s+<~', '><', $this->createTestCrawler()->filterXPath('//form[@id="FooFormId"]')->html())));

        try {
            $this->createTestCrawler()->filterXPath('//ol')->html();
            $this->fail('->html() throws an \InvalidArgumentException if the node list is empty');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertTrue(true, '->html() throws an \InvalidArgumentException if the node list is empty');
        }
    }

    public function testExtract(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//ul[1]/li');

        $this->assertEquals(['One', 'Two', 'Three'], $crawler->extract('_text'), '->extract() returns an array of extracted data from the node list');
        $this->assertEquals([['One', 'first'], ['Two', ''], ['Three', '']], $crawler->extract(['_text', 'class']), '->extract() returns an array of extracted data from the node list');
        $this->assertEquals([[], [], []], $crawler->extract([]), '->extract() returns empty arrays if the attribute list is empty');

        $this->assertEquals([], $this->createTestCrawler()->filterXPath('//ol')->extract('_text'), '->extract() returns an empty array if the node list is empty');
    }

    public function testFilterXpathComplexQueries(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//body');

        $this->assertCount(0, $crawler->filterXPath('/input'));
        $this->assertCount(0, $crawler->filterXPath('/body'));
        $this->assertCount(1, $crawler->filterXPath('./body'));
        $this->assertCount(1, $crawler->filterXPath('.//body'));
        $this->assertCount(5, $crawler->filterXPath('.//input'));
        $this->assertCount(4, $crawler->filterXPath('//form')->filterXPath('//button | //input'));
        $this->assertCount(1, $crawler->filterXPath('body'));
        $this->assertCount(6, $crawler->filterXPath('//button | //input'));
        $this->assertCount(1, $crawler->filterXPath('//body'));
        $this->assertCount(1, $crawler->filterXPath('descendant-or-self::body'));
        $this->assertCount(1, $crawler->filterXPath('//div[@id="parent"]')->filterXPath('./div'), 'A child selection finds only the current div');
        $this->assertCount(3, $crawler->filterXPath('//div[@id="parent"]')->filterXPath('descendant::div'), 'A descendant selector matches the current div and its child');
        $this->assertCount(3, $crawler->filterXPath('//div[@id="parent"]')->filterXPath('//div'), 'A descendant selector matches the current div and its child');
        $this->assertCount(5, $crawler->filterXPath('(//a | //div)//img'));
        $this->assertCount(7, $crawler->filterXPath('((//a | //div)//img | //ul)'));
        $this->assertCount(7, $crawler->filterXPath('( ( //a | //div )//img | //ul )'));
        $this->assertCount(1, $crawler->filterXPath("//a[./@href][((./@id = 'Klausi|Claudiu' or normalize-space(string(.)) = 'Klausi|Claudiu' or ./@title = 'Klausi|Claudiu' or ./@rel = 'Klausi|Claudiu') or .//img[./@alt = 'Klausi|Claudiu'])]"));
    }

    public function testFilterXPath(): void
    {
        $crawler = $this->createTestCrawler();
        $this->assertNotSame($crawler, $crawler->filterXPath('//li'), '->filterXPath() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->filterXPath() returns a new instance of a crawler');

        $crawler = $this->createTestCrawler()->filterXPath('//ul');
        $this->assertCount(6, $crawler->filterXPath('//li'), '->filterXPath() filters the node list with the XPath expression');

        $crawler = $this->createTestCrawler();
        $this->assertCount(3, $crawler->filterXPath('//body')->filterXPath('//button')->parents(), '->filterXpath() preserves parents when chained');
    }

    public function testFilterRemovesDuplicates(): void
    {
        $crawler = $this->createTestCrawler()->filter('html, body')->filter('li');
        $this->assertCount(6, $crawler, 'The crawler removes duplicates when filtering.');
    }

    public function testFilterXPathWithDefaultNamespace(): void
    {
        $crawler = $this->createTestXmlCrawler()->filterXPath('//default:entry/default:id');
        $this->assertCount(1, $crawler, '->filterXPath() automatically registers a namespace');
        $this->assertSame('tag:youtube.com,2008:video:kgZRZmEc9j4', $crawler->text());
    }

    public function testFilterXPathWithCustomDefaultNamespace(): void
    {
        $crawler = $this->createTestXmlCrawler();
        $crawler->setDefaultNamespacePrefix('x');
        $crawler = $crawler->filterXPath('//x:entry/x:id');

        $this->assertCount(1, $crawler, '->filterXPath() lets to override the default namespace prefix');
        $this->assertSame('tag:youtube.com,2008:video:kgZRZmEc9j4', $crawler->text());
    }

    public function testFilterXPathWithNamespace(): void
    {
        $crawler = $this->createTestXmlCrawler()->filterXPath('//yt:accessControl');
        $this->assertCount(2, $crawler, '->filterXPath() automatically registers a namespace');
    }

    public function testFilterXPathWithMultipleNamespaces(): void
    {
        $crawler = $this->createTestXmlCrawler()->filterXPath('//media:group/yt:aspectRatio');
        $this->assertCount(1, $crawler, '->filterXPath() automatically registers multiple namespaces');
        $this->assertSame('widescreen', $crawler->text());
    }

    public function testFilterXPathWithManuallyRegisteredNamespace(): void
    {
        $crawler = $this->createTestXmlCrawler();
        $crawler->registerNamespace('m', 'http://search.yahoo.com/mrss/');

        $crawler = $crawler->filterXPath('//m:group/yt:aspectRatio');
        $this->assertCount(1, $crawler, '->filterXPath() uses manually registered namespace');
        $this->assertSame('widescreen', $crawler->text());
    }

    public function testFilterXPathWithAnUrl(): void
    {
        $crawler = $this->createTestXmlCrawler();

        $crawler = $crawler->filterXPath('//media:category[@scheme="http://gdata.youtube.com/schemas/2007/categories.cat"]');
        $this->assertCount(1, $crawler);
        $this->assertSame('Music', $crawler->text());
    }

    public function testFilterXPathWithFakeRoot(): void
    {
        $crawler = $this->createTestCrawler();
        $this->assertCount(0, $crawler->filterXPath('.'), '->filterXPath() returns an empty result if the XPath references the fake root node');
        $this->assertCount(0, $crawler->filterXPath('self::*'), '->filterXPath() returns an empty result if the XPath references the fake root node');
        $this->assertCount(0, $crawler->filterXPath('self::_root'), '->filterXPath() returns an empty result if the XPath references the fake root node');
    }

    public function testFilterXPathWithAncestorAxis(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//form');

        $this->assertCount(0, $crawler->filterXPath('ancestor::*'), 'The fake root node has no ancestor nodes');
    }

    public function testFilterXPathWithAncestorOrSelfAxis(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//form');

        $this->assertCount(0, $crawler->filterXPath('ancestor-or-self::*'), 'The fake root node has no ancestor nodes');
    }

    public function testFilterXPathWithAttributeAxis(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//form');

        $this->assertCount(0, $crawler->filterXPath('attribute::*'), 'The fake root node has no attribute nodes');
    }

    public function testFilterXPathWithAttributeAxisAfterElementAxis(): void
    {
        $this->assertCount(3, $this->createTestCrawler()->filterXPath('//form/button/attribute::*'), '->filterXPath() handles attribute axes properly when they are preceded by an element filtering axis');
    }

    public function testFilterXPathWithChildAxis(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//div[@id="parent"]');

        $this->assertCount(1, $crawler->filterXPath('child::div'), 'A child selection finds only the current div');
    }

    public function testFilterXPathWithFollowingAxis(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//a');

        $this->assertCount(0, $crawler->filterXPath('following::div'), 'The fake root node has no following nodes');
    }

    public function testFilterXPathWithFollowingSiblingAxis(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//a');

        $this->assertCount(0, $crawler->filterXPath('following-sibling::div'), 'The fake root node has no following nodes');
    }

    public function testFilterXPathWithNamespaceAxis(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//button');

        $this->assertCount(0, $crawler->filterXPath('namespace::*'), 'The fake root node has no namespace nodes');
    }

    public function testFilterXPathWithNamespaceAxisAfterElementAxis(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//div[@id="parent"]/namespace::*');

        $this->assertCount(0, $crawler->filterXPath('namespace::*'), 'Namespace axes cannot be requested');
    }

    public function testFilterXPathWithParentAxis(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//button');

        $this->assertCount(0, $crawler->filterXPath('parent::*'), 'The fake root node has no parent nodes');
    }

    public function testFilterXPathWithPrecedingAxis(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//form');

        $this->assertCount(0, $crawler->filterXPath('preceding::*'), 'The fake root node has no preceding nodes');
    }

    public function testFilterXPathWithPrecedingSiblingAxis(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//form');

        $this->assertCount(0, $crawler->filterXPath('preceding-sibling::*'), 'The fake root node has no preceding nodes');
    }

    public function testFilterXPathWithSelfAxes(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//a');

        $this->assertCount(0, $crawler->filterXPath('self::a'), 'The fake root node has no "real" element name');
        $this->assertCount(0, $crawler->filterXPath('self::a/img'), 'The fake root node has no "real" element name');
        $this->assertCount(10, $crawler->filterXPath('self::*/a'));
    }

    public function testFilter(): void
    {
        $crawler = $this->createTestCrawler();
        $this->assertNotSame($crawler, $crawler->filter('li'), '->filter() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->filter() returns a new instance of a crawler');

        $crawler = $this->createTestCrawler()->filter('ul');

        $this->assertCount(6, $crawler->filter('li'), '->filter() filters the node list with the CSS selector');
    }

    public function testFilterWithDefaultNamespace(): void
    {
        $crawler = $this->createTestXmlCrawler()->filter('default|entry default|id');
        $this->assertCount(1, $crawler, '->filter() automatically registers namespaces');
        $this->assertSame('tag:youtube.com,2008:video:kgZRZmEc9j4', $crawler->text());
    }

    public function testFilterWithNamespace(): void
    {
        $crawler = $this->createTestXmlCrawler()->filter('yt|accessControl');
        $this->assertCount(2, $crawler, '->filter() automatically registers namespaces');
    }

    public function testFilterWithMultipleNamespaces(): void
    {
        $crawler = $this->createTestXmlCrawler()->filter('media|group yt|aspectRatio');
        $this->assertCount(1, $crawler, '->filter() automatically registers namespaces');
        $this->assertSame('widescreen', $crawler->text());
    }

    public function testFilterWithDefaultNamespaceOnly(): void
    {
        $crawler = new Crawler('<?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                <url>
                    <loc>http://localhost/foo</loc>
                    <changefreq>weekly</changefreq>
                    <priority>0.5</priority>
                    <lastmod>2012-11-16</lastmod>
               </url>
               <url>
                    <loc>http://localhost/bar</loc>
                    <changefreq>weekly</changefreq>
                    <priority>0.5</priority>
                    <lastmod>2012-11-16</lastmod>
                </url>
            </urlset>
        ');

        $this->assertEquals(2, $crawler->filter('url')->count());
    }

    public function testSelectLink(): void
    {
        $crawler = $this->createTestCrawler();
        $this->assertNotSame($crawler, $crawler->selectLink('Foo'), '->selectLink() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->selectLink() returns a new instance of a crawler');

        $this->assertCount(1, $crawler->selectLink("Fabien's Foo"), '->selectLink() selects links by the node values');
        $this->assertCount(1, $crawler->selectLink("Fabien's Bar"), '->selectLink() selects links by the alt attribute of a clickable image');

        $this->assertCount(2, $crawler->selectLink('Fabien"s Foo'), '->selectLink() selects links by the node values');
        $this->assertCount(2, $crawler->selectLink('Fabien"s Bar'), '->selectLink() selects links by the alt attribute of a clickable image');

        $this->assertCount(1, $crawler->selectLink('\' Fabien"s Foo'), '->selectLink() selects links by the node values');
        $this->assertCount(1, $crawler->selectLink('\' Fabien"s Bar'), '->selectLink() selects links by the alt attribute of a clickable image');

        $this->assertCount(4, $crawler->selectLink('Foo'), '->selectLink() selects links by the node values');
        $this->assertCount(4, $crawler->selectLink('Bar'), '->selectLink() selects links by the node values');
    }

    public function testSelectImage(): void
    {
        $crawler = $this->createTestCrawler();
        $this->assertNotSame($crawler, $crawler->selectImage('Bar'), '->selectImage() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->selectImage() returns a new instance of a crawler');

        $this->assertCount(1, $crawler->selectImage("Fabien's Bar"), '->selectImage() selects images by alt attribute');
        $this->assertCount(2, $crawler->selectImage('Fabien"s Bar'), '->selectImage() selects images by alt attribute');
        $this->assertCount(1, $crawler->selectImage('\' Fabien"s Bar'), '->selectImage() selects images by alt attribute');
    }

    public function testSelectButton(): void
    {
        $crawler = $this->createTestCrawler();
        $this->assertNotSame($crawler, $crawler->selectButton('FooValue'), '->selectButton() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->selectButton() returns a new instance of a crawler');

        $this->assertEquals(1, $crawler->selectButton('FooValue')->count(), '->selectButton() selects buttons');
        $this->assertEquals(1, $crawler->selectButton('FooName')->count(), '->selectButton() selects buttons');
        $this->assertEquals(1, $crawler->selectButton('FooId')->count(), '->selectButton() selects buttons');

        $this->assertEquals(1, $crawler->selectButton('BarValue')->count(), '->selectButton() selects buttons');
        $this->assertEquals(1, $crawler->selectButton('BarName')->count(), '->selectButton() selects buttons');
        $this->assertEquals(1, $crawler->selectButton('BarId')->count(), '->selectButton() selects buttons');

        $this->assertEquals(1, $crawler->selectButton('FooBarValue')->count(), '->selectButton() selects buttons with form attribute too');
        $this->assertEquals(1, $crawler->selectButton('FooBarName')->count(), '->selectButton() selects buttons with form attribute too');
    }

    public function testSelectButtonWithSingleQuotesInNameAttribute(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<body>
    <div id="action">
        <a href="/index.php?r=site/login">Login</a>
    </div>
    <form id="login-form" action="/index.php?r=site/login" method="post">
        <button type="submit" name="Click 'Here'">Submit</button>
    </form>
</body>
</html>
HTML;

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->selectButton("Click 'Here'"));
    }

    public function testSelectButtonWithDoubleQuotesInNameAttribute(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<body>
    <div id="action">
        <a href="/index.php?r=site/login">Login</a>
    </div>
    <form id="login-form" action="/index.php?r=site/login" method="post">
        <button type="submit" name='Click "Here"'>Submit</button>
    </form>
</body>
</html>
HTML;

        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->selectButton('Click "Here"'));
    }

    public function testLink(): void
    {
        $crawler = $this->createTestCrawler('http://example.com/bar/')->selectLink('Foo');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Link::class, $crawler->link(), '->link() returns a Link instance');

        $this->assertEquals('POST', $crawler->link('post')->getMethod(), '->link() takes a method as its argument');

        $crawler = $this->createTestCrawler('http://example.com/bar')->selectLink('GetLink');
        $this->assertEquals('http://example.com/bar?get=param', $crawler->link()->getUri(), '->link() returns a Link instance');

        try {
            $this->createTestCrawler()->filterXPath('//ol')->link();
            $this->fail('->link() throws an \InvalidArgumentException if the node list is empty');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertTrue(true, '->link() throws an \InvalidArgumentException if the node list is empty');
        }
    }

    public function testInvalidLink(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The selected node should be instance of DOMElement');
        $crawler = $this->createTestCrawler('http://example.com/bar/');
        $crawler->filterXPath('//li/text()')->link();
    }

    public function testInvalidLinks(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The selected node should be instance of DOMElement');
        $crawler = $this->createTestCrawler('http://example.com/bar/');
        $crawler->filterXPath('//li/text()')->link();
    }

    public function testImage(): void
    {
        $crawler = $this->createTestCrawler('http://example.com/bar/')->selectImage('Bar');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Image::class, $crawler->image(), '->image() returns an Image instance');

        try {
            $this->createTestCrawler()->filterXPath('//ol')->image();
            $this->fail('->image() throws an \InvalidArgumentException if the node list is empty');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertTrue(true, '->image() throws an \InvalidArgumentException if the node list is empty');
        }
    }

    public function testSelectLinkAndLinkFiltered(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<body>
    <div id="action">
        <a href="/index.php?r=site/login">Login</a>
    </div>
    <form id="login-form" action="/index.php?r=site/login" method="post">
        <button type="submit">Submit</button>
    </form>
</body>
</html>
HTML;

        $crawler = new Crawler($html);
        $filtered = $crawler->filterXPath("descendant-or-self::*[@id = 'login-form']");

        $this->assertCount(0, $filtered->selectLink('Login'));
        $this->assertCount(1, $filtered->selectButton('Submit'));

        $filtered = $crawler->filterXPath("descendant-or-self::*[@id = 'action']");

        $this->assertCount(1, $filtered->selectLink('Login'));
        $this->assertCount(0, $filtered->selectButton('Submit'));

        $this->assertCount(1, $crawler->selectLink('Login')->selectLink('Login'));
        $this->assertCount(1, $crawler->selectButton('Submit')->selectButton('Submit'));
    }

    public function testChaining(): void
    {
        $crawler = new Crawler('<div name="a"><div name="b"><div name="c"></div></div></div>');

        $this->assertEquals('a', $crawler->filterXPath('//div')->filterXPath('div')->filterXPath('div')->attr('name'));
    }

    public function testLinks(): void
    {
        $crawler = $this->createTestCrawler('http://example.com/bar/')->selectLink('Foo');
        $this->assertIsArray($crawler->links(), '->links() returns an array');

        $this->assertCount(4, $crawler->links(), '->links() returns an array');
        $links = $crawler->links();
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Link::class, $links[0], '->links() returns an array of Link instances');

        $this->assertEquals([], $this->createTestCrawler()->filterXPath('//ol')->links(), '->links() returns an empty array if the node selection is empty');
    }

    public function testImages(): void
    {
        $crawler = $this->createTestCrawler('http://example.com/bar/')->selectImage('Bar');
        $this->assertIsArray($crawler->images(), '->images() returns an array');

        $this->assertCount(4, $crawler->images(), '->images() returns an array');
        $images = $crawler->images();
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Image::class, $images[0], '->images() returns an array of Image instances');

        $this->assertEquals([], $this->createTestCrawler()->filterXPath('//ol')->links(), '->links() returns an empty array if the node selection is empty');
    }

    public function testForm(): void
    {
        $testCrawler = $this->createTestCrawler('http://example.com/bar/');
        $crawler = $testCrawler->selectButton('FooValue');
        $crawler2 = $testCrawler->selectButton('FooBarValue');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Form::class, $crawler->form(), '->form() returns a Form instance');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Form::class, $crawler2->form(), '->form() returns a Form instance');

        $this->assertEquals($crawler->form()->getFormNode()->getAttribute('id'), $crawler2->form()->getFormNode()->getAttribute('id'), '->form() works on elements with form attribute');

        $this->assertEquals(['FooName' => 'FooBar', 'TextName' => 'TextValue', 'FooTextName' => 'FooTextValue'], $crawler->form(['FooName' => 'FooBar'])->getValues(), '->form() takes an array of values to submit as its first argument');
        $this->assertEquals(['FooName' => 'FooValue', 'TextName' => 'TextValue', 'FooTextName' => 'FooTextValue'], $crawler->form()->getValues(), '->getValues() returns correct form values');
        $this->assertEquals(['FooBarName' => 'FooBarValue', 'TextName' => 'TextValue', 'FooTextName' => 'FooTextValue'], $crawler2->form()->getValues(), '->getValues() returns correct form values');

        try {
            $this->createTestCrawler()->filterXPath('//ol')->form();
            $this->fail('->form() throws an \InvalidArgumentException if the node list is empty');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertTrue(true, '->form() throws an \InvalidArgumentException if the node list is empty');
        }
    }

    public function testInvalidForm(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The selected node should be instance of DOMElement');
        $crawler = $this->createTestCrawler('http://example.com/bar/');
        $crawler->filterXPath('//li/text()')->form();
    }

    public function testLast(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//ul[1]/li');
        $this->assertNotSame($crawler, $crawler->last(), '->last() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->last() returns a new instance of a crawler');

        $this->assertEquals('Three', $crawler->last()->text());
    }

    public function testFirst(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//li');
        $this->assertNotSame($crawler, $crawler->first(), '->first() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->first() returns a new instance of a crawler');

        $this->assertEquals('One', $crawler->first()->text());
    }

    public function testSiblings(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//li')->eq(1);
        $this->assertNotSame($crawler, $crawler->siblings(), '->siblings() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->siblings() returns a new instance of a crawler');

        $nodes = $crawler->siblings();
        $this->assertEquals(2, $nodes->count());
        $this->assertEquals('One', $nodes->eq(0)->text());
        $this->assertEquals('Three', $nodes->eq(1)->text());

        $nodes = $this->createTestCrawler()->filterXPath('//li')->eq(0)->siblings();
        $this->assertEquals(2, $nodes->count());
        $this->assertEquals('Two', $nodes->eq(0)->text());
        $this->assertEquals('Three', $nodes->eq(1)->text());

        try {
            $this->createTestCrawler()->filterXPath('//ol')->siblings();
            $this->fail('->siblings() throws an \InvalidArgumentException if the node list is empty');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertTrue(true, '->siblings() throws an \InvalidArgumentException if the node list is empty');
        }
    }

    public function testNextAll(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//li')->eq(1);
        $this->assertNotSame($crawler, $crawler->nextAll(), '->nextAll() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->nextAll() returns a new instance of a crawler');

        $nodes = $crawler->nextAll();
        $this->assertEquals(1, $nodes->count());
        $this->assertEquals('Three', $nodes->eq(0)->text());

        try {
            $this->createTestCrawler()->filterXPath('//ol')->nextAll();
            $this->fail('->nextAll() throws an \InvalidArgumentException if the node list is empty');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertTrue(true, '->nextAll() throws an \InvalidArgumentException if the node list is empty');
        }
    }

    public function testPreviousAll(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//li')->eq(2);
        $this->assertNotSame($crawler, $crawler->previousAll(), '->previousAll() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->previousAll() returns a new instance of a crawler');

        $nodes = $crawler->previousAll();
        $this->assertEquals(2, $nodes->count());
        $this->assertEquals('Two', $nodes->eq(0)->text());

        try {
            $this->createTestCrawler()->filterXPath('//ol')->previousAll();
            $this->fail('->previousAll() throws an \InvalidArgumentException if the node list is empty');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertTrue(true, '->previousAll() throws an \InvalidArgumentException if the node list is empty');
        }
    }

    public function testChildren(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//ul');
        $this->assertNotSame($crawler, $crawler->children(), '->children() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->children() returns a new instance of a crawler');

        $nodes = $crawler->children();
        $this->assertEquals(3, $nodes->count());
        $this->assertEquals('One', $nodes->eq(0)->text());
        $this->assertEquals('Two', $nodes->eq(1)->text());
        $this->assertEquals('Three', $nodes->eq(2)->text());

        try {
            $this->createTestCrawler()->filterXPath('//ol')->children();
            $this->fail('->children() throws an \InvalidArgumentException if the node list is empty');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertTrue(true, '->children() throws an \InvalidArgumentException if the node list is empty');
        }

        try {
            $crawler = new Crawler('<p></p>');
            $crawler->filter('p')->children();
            $this->assertTrue(true, '->children() does not trigger a notice if the node has no children');
        } catch (\PHPUnit\Framework\Error\Notice $e) {
            $this->fail('->children() does not trigger a notice if the node has no children');
        } catch (\PHPUnit\Framework\Error\Notice $e) {
            $this->fail('->children() does not trigger a notice if the node has no children');
        }
    }

    public function testParents(): void
    {
        $crawler = $this->createTestCrawler()->filterXPath('//li[1]');
        $this->assertNotSame($crawler, $crawler->parents(), '->parents() returns a new instance of a crawler');
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawler, '->parents() returns a new instance of a crawler');

        $nodes = $crawler->parents();
        $this->assertEquals(3, $nodes->count());

        $nodes = $this->createTestCrawler()->filterXPath('//html')->parents();
        $this->assertEquals(0, $nodes->count());

        try {
            $this->createTestCrawler()->filterXPath('//ol')->parents();
            $this->fail('->parents() throws an \InvalidArgumentException if the node list is empty');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertTrue(true, '->parents() throws an \InvalidArgumentException if the node list is empty');
        }
    }

    /**
     * @dataProvider getBaseTagData
     */
    public function testBaseTag(string $baseValue, string $linkValue, string $expectedUri, string $currentUri = null, string $description = ''): void
    {
        $crawler = new Crawler('<html><base href="'.$baseValue.'"><a href="'.$linkValue.'"></a></html>', $currentUri);
        $this->assertEquals($expectedUri, $crawler->filterXPath('//a')->link()->getUri(), $description);
    }

    public function getBaseTagData(): array
    {
        return [
            ['http://base.com', 'link', 'http://base.com/link'],
            ['//base.com', 'link', 'https://base.com/link', 'https://domain.com', '<base> tag can use a schema-less URL'],
            ['path/', 'link', 'https://domain.com/path/link', 'https://domain.com', '<base> tag can set a path'],
            ['http://base.com', '#', 'http://base.com#', 'http://domain.com/path/link', '<base> tag does work with links to an anchor'],
            ['http://base.com', '', 'http://base.com', 'http://domain.com/path/link', '<base> tag does work with empty links'],
        ];
    }

    /**
     * @dataProvider getBaseTagWithFormData
     */
    public function testBaseTagWithForm(string $baseValue, string $actionValue, string $expectedUri, string $currentUri = null, string $description = null): void
    {
        $crawler = new Crawler('<html><base href="'.$baseValue.'"><form method="post" action="'.$actionValue.'"><button type="submit" name="submit"/></form></html>', $currentUri);
        $this->assertEquals($expectedUri, $crawler->filterXPath('//button')->form()->getUri(), $description);
    }

    public function getBaseTagWithFormData(): array
    {
        return [
            ['https://base.com/', 'link/', 'https://base.com/link/', 'https://base.com/link/', '<base> tag does work with a path and relative form action'],
            ['/basepath', '/registration', 'http://domain.com/registration', 'http://domain.com/registration', '<base> tag does work with a path and form action'],
            ['/basepath', '', 'http://domain.com/registration', 'http://domain.com/registration', '<base> tag does work with a path and empty form action'],
            ['http://base.com/', '/registration', 'http://base.com/registration', 'http://domain.com/registration', '<base> tag does work with a URL and form action'],
            ['http://base.com', '', 'http://domain.com/path/form', 'http://domain.com/path/form', '<base> tag does work with a URL and an empty form action'],
            ['http://base.com/path', '/registration', 'http://base.com/registration', 'http://domain.com/path/form', '<base> tag does work with a URL and form action'],
        ];
    }

    public function testCountOfNestedElements(): void
    {
        $crawler = new Crawler('<html><body><ul><li>List item 1<ul><li>Sublist item 1</li><li>Sublist item 2</ul></li></ul></body></html>');

        $this->assertCount(1, $crawler->filter('li:contains("List item 1")'));
    }

    public function testEvaluateReturnsTypedResultOfXPathExpressionOnADocumentSubset(): void
    {
        $crawler = $this->createTestCrawler();

        $result = $crawler->filterXPath('//form/input')->evaluate('substring-before(@name, "Name")');

        $this->assertSame(['Text', 'Foo', 'Bar'], $result);
    }

    public function testEvaluateReturnsTypedResultOfNamespacedXPathExpressionOnADocumentSubset(): void
    {
        $crawler = $this->createTestXmlCrawler();

        $result = $crawler->filterXPath('//yt:accessControl/@action')->evaluate('string(.)');

        $this->assertSame(['comment', 'videoRespond'], $result);
    }

    public function testEvaluateReturnsTypedResultOfNamespacedXPathExpression(): void
    {
        $crawler = $this->createTestXmlCrawler();
        $crawler->registerNamespace('youtube', 'http://gdata.youtube.com/schemas/2007');

        $result = $crawler->evaluate('string(//youtube:accessControl/@action)');

        $this->assertSame(['comment'], $result);
    }

    public function testEvaluateReturnsACrawlerIfXPathExpressionEvaluatesToANode(): void
    {
        $crawler = $this->createTestCrawler()->evaluate('//form/input[1]');

        $this->assertInstanceOf(Crawler::class, $crawler);
        $this->assertCount(1, $crawler);
        $this->assertSame('input', $crawler->first()->nodeName());
    }

    public function testEvaluateThrowsAnExceptionIfDocumentIsEmpty(): void
    {
        $this->expectException('LogicException');
        (new Crawler())->evaluate('//form/input[1]');
    }

    public function createTestCrawler($uri = null): \Symfony\Component\DomCrawler\Crawler
    {
        $dom = new \DOMDocument();
        $dom->loadHTML('
            <html>
                <body>
                    <a href="foo">Foo</a>
                    <a href="/foo">   Fabien\'s Foo   </a>
                    <a href="/foo">Fabien"s Foo</a>
                    <a href="/foo">\' Fabien"s Foo</a>

                    <a href="/bar"><img alt="Bar"/></a>
                    <a href="/bar"><img alt="   Fabien\'s Bar   "/></a>
                    <a href="/bar"><img alt="Fabien&quot;s Bar"/></a>
                    <a href="/bar"><img alt="\' Fabien&quot;s Bar"/></a>

                    <a href="?get=param">GetLink</a>

                    <a href="/example">Klausi|Claudiu</a>

                    <form action="foo" id="FooFormId">
                        <input type="text" value="TextValue" name="TextName" />
                        <input type="submit" value="FooValue" name="FooName" id="FooId" />
                        <input type="button" value="BarValue" name="BarName" id="BarId" />
                        <button value="ButtonValue" name="ButtonName" id="ButtonId" />
                    </form>

                    <input type="submit" value="FooBarValue" name="FooBarName" form="FooFormId" />
                    <input type="text" value="FooTextValue" name="FooTextName" form="FooFormId" />

                    <ul class="first">
                        <li class="first">One</li>
                        <li>Two</li>
                        <li>Three</li>
                    </ul>
                    <ul>
                        <li>One Bis</li>
                        <li>Two Bis</li>
                        <li>Three Bis</li>
                    </ul>
                    <div id="parent">
                        <div id="child"></div>
                        <div id="child2" xmlns:foo="http://example.com"></div>
                    </div>
                    <div id="sibling"><img /></div>
                </body>
            </html>
        ');

        return new Crawler($dom, $uri);
    }

    protected function createTestXmlCrawler($uri = null): \Symfony\Component\DomCrawler\Crawler
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <entry xmlns="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/" xmlns:yt="http://gdata.youtube.com/schemas/2007">
                <id>tag:youtube.com,2008:video:kgZRZmEc9j4</id>
                <yt:accessControl action="comment" permission="allowed"/>
                <yt:accessControl action="videoRespond" permission="moderated"/>
                <media:group>
                    <media:title type="plain">Chordates - CrashCourse Biology #24</media:title>
                    <yt:aspectRatio>widescreen</yt:aspectRatio>
                </media:group>
                <media:category label="Music" scheme="http://gdata.youtube.com/schemas/2007/categories.cat">Music</media:category>
            </entry>';

        return new Crawler($xml, $uri);
    }

    protected function createDomDocument(): \DOMDocument
    {
        $dom = new \DOMDocument();
        $dom->loadXML('<html><div class="foo"></div></html>');

        return $dom;
    }

    protected function createNodeList(): \DOMNodeList|bool
    {
        $dom = new \DOMDocument();
        $dom->loadXML('<html><div class="foo"></div></html>');

        $domxpath = new \DOMXPath($dom);

        return $domxpath->query('//div');
    }
}
