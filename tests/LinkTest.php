<?php

use DenisBeliaev\SitemapParser\Link;

/**
 * @author: Denis Beliaev
 */
class LinkTest extends \PHPUnit\Framework\TestCase
{
    public function testCreation()
    {
        $link = new Link('#');
        $this->assertEquals('', (string)$link);

        $link = new Link('#header');
        $this->assertEquals('', (string)$link);

        $link = new Link('/');
        $this->assertEquals('', (string)$link);
        $link = new Link('/', 'http://example.com/page');
        $this->assertEquals('http://example.com', (string)$link);

        $link = new Link('mailto:mail@vistro.ru');
        $this->assertEquals('', (string)$link);

        $link = new Link('tel:+79998887766');
        $this->assertEquals('', (string)$link);

        $link = new Link('/page/subpage');
        $this->assertEquals('', (string)$link);
        $link = new Link('/page/subpage', 'http://example.com/category');
        $this->assertEquals('http://example.com/page/subpage', (string)$link);

        $link = new Link('//vistro.ru');
        $this->assertEquals('http://vistro.ru', (string)$link);
        $link = new Link('//vistro.ru', 'https://example.com');
        $this->assertEquals('https://vistro.ru', (string)$link);

        $link = new Link('http://chopacho.ru');
        $this->assertEquals('http://chopacho.ru', (string)$link);
        $link = new Link('http://chopacho.ru', 'https://example.com');
        $this->assertEquals('http://chopacho.ru', (string)$link);

        $link = new Link('https://chopacho.ru');
        $this->assertEquals('https://chopacho.ru', (string)$link);
        $link = new Link('https://chopacho.ru', 'http://example.com');
        $this->assertEquals('https://chopacho.ru', (string)$link);
    }
}