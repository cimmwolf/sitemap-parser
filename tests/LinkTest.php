<?php

use DenisBeliaev\SitemapParser\Link;

/**
 * @author: Denis Beliaev
 */
class LinkTest extends \PHPUnit\Framework\TestCase
{
    public function testCreation()
    {
        $link = Link::normalize('#', 'http://example.com');
        $this->assertEquals('', $link);

        $link = Link::normalize('#header', 'https://example.com');
        $this->assertEquals('', $link);

        $link = Link::normalize('/', 'http://example.com/page');
        $this->assertEquals('http://example.com', $link);

        $link = Link::normalize('mailto:mail@vistro.ru', 'http://domain.name');
        $this->assertEquals('', $link);

        $link = Link::normalize('tel:+79998887766', 'http://domain.name');
        $this->assertEquals('', $link);

        $link = Link::normalize('/page/subpage', 'http://example.com/category');
        $this->assertEquals('http://example.com/page/subpage', $link);

        $link = Link::normalize('//vistro.ru', 'https://example.com');
        $this->assertEquals('https://vistro.ru', $link);

        $link = Link::normalize('http://chopacho.ru', 'https://example.com');
        $this->assertEquals('http://chopacho.ru', $link);

        $link = Link::normalize('https://chopacho.ru', 'http://example.com');
        $this->assertEquals('https://chopacho.ru', $link);

        $link = Link::normalize('foo/bar', 'http://example.com/page');
        $this->assertEquals('http://example.com/page/foo/bar', $link);

        $link = Link::normalize('<a href="/foo/bar" class="link">Link text</a>', 'http://example.com');
        $this->assertEquals('http://example.com/foo/bar', $link);
    }
}