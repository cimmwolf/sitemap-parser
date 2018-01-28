Sitemap.xml parser
==================
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cimmwolf/sitemap-parser/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/cimmwolf/sitemap-parser/?branch=master)

This utility for parse sites sitemap.xml and do something with urls.

Commands: 
--------
  1. `sp check SITE_URL` - checking HTTP response code for each link in sitemap.xml;
  1. `sp links SITE_URL` - parsing all pages from sitemap.xml and checking HTTP response codes for links which where founded. Also this command makes all checking from command 1;
  2. `sp metadata SITE_URL`;
  
Requirements
------------
* PHP (with sqlite3 and curl) >= 7;
* Composer;

--------------------------------------------------------------

Инструмент для обхода страниц из sitemap.xml.

Возможности:
-----------
  1. `sp check SITE_URL` - проверяет HTTP-код ответа для каждой ссылки из sitemap.xml;
  1. `sp links SITE_URL` - проверяет "битые" ссылки: собирает все ссылки со всех страниц сайта и проверяет для каждой из них HTTP-код ответа. Кроме этого, проверяет все ссылки из sitemap.xml;
  2. `sp metadata SITE_URL`;