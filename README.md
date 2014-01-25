HeurekaRatingParser
===================

Parser write in PHP language for online service heureka.sk and heureka.cz


Example
=======
``` php

// $lang = [sk|cz]
$obj = new HeurekaParser("shop-name", $lang ='sk');

// Return all ratings as array
$obj->parseAll();

// Return ratings on $pageNumber
$obj->parsePage($pageNumber);
```
