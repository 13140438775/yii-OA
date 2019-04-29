<?php

use likingfit\Workflow\Parser\Parser;

$path = "xml.xml";
$parser = new Parser();
var_dump($parser->parse($path));