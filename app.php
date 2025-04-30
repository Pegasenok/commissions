<?php

require __DIR__.'/vendor/autoload.php';

use App\CommissionHandler;
use App\JsonStreamer;
use App\Parser\NdjsonParserWrapper;
use App\Validator\CommissionLineValidator;
use Sunaoka\Ndjson\NDJSON;

$streamer = new JsonStreamer();
$handler = new CommissionHandler();

$fileName = $argv[1] ?? 'example/input.txt';
$fileArgument = $fileName ?? throw new Exception('No file argument provided');
if (!file_exists($fileArgument)) {
    echo "File not found";
    exit;
}

$streamer->setParser(
    new NdjsonParserWrapper(
        new NDJSON($fileArgument)
    )
);

$handler->setValidator(
    new CommissionLineValidator(
        new JsonSchema\Validator,
        getcwd().'/src/Resources/Schema/line_schema.json'
    )
);
$results = $handler->processCommissions($streamer);

foreach ($results as $result) {
    echo $result.PHP_EOL;
}

foreach ($handler->getErrors() as $line) {
    echo 'error:'.$line.PHP_EOL;
}
