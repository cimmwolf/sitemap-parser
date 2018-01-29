<?php

use DenisBeliaev\SitemapParser\Page;

/**
 * @author: Denis Beliaev
 */
class PageTest extends \PHPUnit\Framework\TestCase
{
    public function testPageParse()
    {
        $Page = new Page(file_get_contents(__DIR__ . '/fixtures/page.html'), 'https://kasok.ru/ballet');
        $this->assertCount(38, $Page->links);
        $this->assertEquals('https://kasok.ru/foo', $Page->base);
    }
}