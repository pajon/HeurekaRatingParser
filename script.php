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
    private $shopLang;
    private $lang = array(
        'link' => array(
            'sk' => 'http://obchody.heureka.sk/%s/recenze/',
            'cz' => 'http://obchody.heureka.cz/%s/recenze/'
        ),
        'pagelink' => array(
            'sk' => 'http://obchody.heureka.sk/%s/recenze/?f=%d',
            'cz' => 'http://obchody.heureka.cz/%s/recenze/?f=%d'
        ),
        'rate' => array(
            'sk' => '$Hodnotenie: ([0-9].[0-9])\nhviezdičky z 5$',
            'cz' => '$Hodnocení: ([0-9].[0-9])\nhvězdičky z 5$'
        ),
        'lastpage' => array(
            'sk' => 'Posledná stránka',
            'cz' => 'Poslední stránka'
        )
    );

    function __construct($url, $lang = 'sk') {
        $this->shopUrl = $url;
        $this->shopLang = (in_array($lang, array('sk', 'cz')) ? $lang : 'sk');

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


        preg_match($this->lang['rate'][$this->shopLang], $text->item($index)->nodeValue, $match);
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

        $link = sprintf($this->lang['link'][$this->shopLang], $this->shopUrl);

        @$dom->loadHTML($this->getUrlData($link));

        $xpath = new DOMXpath($dom);


        $maxPage = $xpath->query('//a[@title="'.$this->lang['lastpage'][$this->shopLang].'"]')->item(0)->nodeValue;

        for ($i = 1; $i <= $maxPage; $i++) {
            $rates = array_merge($rates, $this->parsePage($i));
        }

        return $rates;
    }

    function parsePage($id) {
        $rates = array();

        $dom = new DOMDocument('1.0', 'UTF-8');

        @$dom->loadHTML($this->getUrlData(sprintf($this->lang['pagelink'][$this->shopLang], $this->shopUrl, $id)));

        $xpath = new DOMXpath($dom);
        $nodeList = $xpath->query('//div[@class="review"]');

        foreach ($nodeList as $val) {
            $rate = new Rating;
            $rate->id = $this->getId($val->getAttribute('id'));
            $rate->date = explode(" ", $xpath->query('./div/p[@class="date"]', $val)->item(0)->nodeValue, 2)[1];

            $obj = $xpath->query('./div[@class="revtext shoprev"]/h3[@class="eval"]/big', $val);
            $rate->rating = ($obj->length > 0 ? $obj->item(0)->nodeValue : null);
            
            $obj = $xpath->query('./div/ul[@class="stars"]/li[not(@class)]/span[@class="rating"]/span[@class="hidden"]', $val);
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

$obj = new HeurekaParser("agen-cz", "cz");

var_dump($obj->parseAll());