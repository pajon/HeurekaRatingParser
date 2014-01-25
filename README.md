HeurekaRatingParser
===================

Parser write in PHP language for online service heureka.sk


Example
=======
``` php
`$obj = new HeurekaParser("shop-name");

// Return all ratings as array
$obj->parseAll();

// Return ratings on $pageNumber
$obj->parsePage($pageNumber);
```
