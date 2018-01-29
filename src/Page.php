<?php
/**
 * @author: Denis Beliaev
 */

namespace DenisBeliaev\SitemapParser;


class Page
{
    public $links;
    public $base;
    private $html;

    /**
     * Page constructor.
     * @param $html
     * @param $address
     */
    public function __construct($html, $address)
    {
        $this->html = $html;

        preg_match_all("~<a\s.*?>.*?</a>~imus", $html, $matches);
        foreach ($matches[0] as &$match) {
            $match = str_replace(["\n", "\r\n"], ' ', $match);
            $match = preg_replace('~\s+~', ' ', $match);
        }
        $this->links = array_unique($matches[0]);

        preg_match('~<base[^>]+href=[\'"](.*?)[\'"]~i', $html, $matches);
        $this->base = !empty($matches[1]) ? Link::normalize($matches[1], $address) : $address;
    }
}