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
    private $url = '';
    private $components;

    /**
     * Link constructor.
     * @param string $link URL or <a> tag with href property
     * @param string $base Full URL to base page including protocol.
     */
    public function __construct($link, $base = '')
    {
        if(!empty($base)) {
            $this->components = parse_url($base);
        }
        $link = explode('#', $link)[0];

        if ($link == '/') {
            if (!empty($this->components['scheme']) && !empty($this->components['host'])) {
                $this->url = $this->components['scheme'] . '://' . $this->components['host'];
            }
        } elseif (preg_match('~^//[^/].*~', $link)) {
            $scheme = $this->components['scheme'] ?? 'http';
            $this->url = $scheme . ':' . $link;
        } elseif (preg_match('~^/[^/].*~', $link)) {
            if (!empty($this->components['scheme']) && !empty($this->components['host'])) {
                $this->url = $this->components['scheme'] . '://' . $this->components['host'] . $link;
            }
        } elseif (preg_match('~^https?://[^/].*~', $link)) {
            $this->url = $link;
        }
    }

    public function __toString()
    {
        return $this->url;
    }
}