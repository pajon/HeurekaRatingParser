<?php

class Rating {

    public $id;
    public $date;
    public $rating;
    public $rateDeliver;
    public $rateTransparency;
    public $rateQuality;
    public $coment;
    public $plus = array();
    public $minus = array();
    public $user;

}

class HeurekaParser {

    private $shopUrl;

    function __construct($url) {
        $this->shopUrl = $url;

        if ($url === null || $url == "")
            throw new Exception("URL cannot be empty");
    }

    // CISTI HTML SUBOR
    private function clearScript($data) {
        $data = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $data);
        $data = preg_replace('#<noscript(.*?)>(.*?)</noscript>#is', '', $data);
        $data = preg_replace('#<form(.*?)>(.*?)</form>#is', '', $data);

        $tidy = new tidy;
        $tidy->parseString($data, array(), 'utf8');
        $tidy->cleanRepair();

        return $tidy;
    }

    // VRACIA HODNOTU AKOU POUZIVATEL HODNOTIL
    private function getRate($text, $index) {
        if ($text->length <= $index)
            return null;


        preg_match('$Hodnotenie: ([0-9].[0-9])\nhviezdičky z 5$', $text->item($index)->nodeValue, $match);
        return $match[1];
    }

    private function getComment($val) {
        if ($val->length == 0)
            return null;
        else
            return $val->item(0)->nodeValue;
    }

    private function getId($val) {
        $tmp = explode("-", $val);
        return $tmp[count($tmp) - 1];
    }

    // NACITAVA DATA A CACHUJE
    private function getUrlData($url) {
        return $this->clearScript(file_get_contents($url));
    }

    function parseAll() {
        $rates = array();
        $dom = new DOMDocument('1.0', 'UTF-8');

        $link = sprintf("http://obchody.heureka.sk/%s/recenze/", $this->shopUrl);

        @$dom->loadHTML($this->getUrlData($link));

        $xpath = new DOMXpath($dom);


        $maxPage = $xpath->query('//a[@title="Posledná stránka"]')->item(0)->nodeValue;

        for ($i = 1; $i <= $maxPage; $i++) {
            $rates = array_merge($rates, $this->parsePage($i));
        }

        return $rates;
    }

    function parsePage($id) {
        $rates = array();

        $dom = new DOMDocument('1.0', 'UTF-8');

        $link = sprintf("http://obchody.heureka.sk/%s/recenze/?f=%d", $this->shopUrl, $id);

        @$dom->loadHTML($this->getUrlData($link));

        $xpath = new DOMXpath($dom);
        $nodeList = $xpath->query('//div[@class="review"]');

        foreach ($nodeList as $val) {
            $rate = new Rating;
            $rate->id = $this->getId($val->getAttribute('id'));
            $rate->date = explode(" ", $xpath->query('./div/p[@class="date"]', $val)->item(0)->nodeValue, 2)[1];

            $obj = $xpath->query('./div/ul[@class="stars"]/li[not(@class)]/span[@class="rating"]/span[@class="hidden"]', $val);


            $rate->rating = $xpath->query('./div[@class="revtext shoprev"]/h3[@class="eval"]/big', $val)->item(0)->nodeValue;
            $rate->rateDeliver = $this->getRate($obj, 0);
            $rate->rateTransparency = $this->getRate($obj, 1);
            $rate->rateQuality = $this->getRate($obj, 2);
            $rate->comment = $this->getComment($xpath->query('./div[@class="revtext shoprev"]/p', $val));

            $rate->user = $xpath->query('./div/p/strong', $val)->item(0)->nodeValue;


            foreach ($xpath->query('./div/div[@class="plus"]/ul/li', $val) as $node) {
                $rate->plus[] = $node->nodeValue;
            }

            foreach ($xpath->query('./div/div[@class="minus"]/ul/li', $val) as $node) {
                $rate->minus[] = $node->nodeValue;
            }

            $rates[] = $rate;
        }

        return $rates;
    }

}