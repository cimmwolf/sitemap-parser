<?php
/**
 * @author: Denis Beliaev
 */

namespace DenisBeliaev\SitemapParser;

/**
 * Class Link
 * @package DenisBeliaev\SitemapParser
 */
class Link
{
    static function normalize($link, $base)
    {
        if (substr($link, 0, 2) == '<a' && substr($link, -4, 4) == '</a>') {
            preg_match('~href=[\'"](.*?)[\'"]~i', $link, $matches);
            $link = $matches[1] ?? '';
        }

        $link = urldecode($link);
        $link = explode('#', $link)[0];

        if (empty($link)) {
            return '';
        }

        if (preg_match('~^mailto:|tel:.*~i', $link)) {
            return '';
        } elseif (preg_match('~^https?://[^/].*~i', $link)) {
            return $link;
        }

        $isProtocolRelative = preg_match('~^//[^/].*~', $link);

        $isSiteRelative = preg_match('~^/[^/].*~', $link);
        $isMainPage = $link == '/';

        $components = parse_url($base);
        if (empty($components['scheme']) || empty($components['host'])) {
            throw new \InvalidArgumentException('Wrong base value');
        }
        $scheme = $components['scheme'];
        $host = $components['host'];

        if ($isMainPage) {
            $link = $scheme . '://' . $host;
        } elseif ($isProtocolRelative) {
            $link = $scheme . ':' . $link;
        } elseif ($isSiteRelative) {
            $link = $scheme . '://' . $host . $link;
        } else {
            $link = $base . '/' . $link;
        }

        return $link;
    }
}