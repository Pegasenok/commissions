<?php

require __DIR__.'/vendor/autoload.php';

use App\CommissionHandler;
use App\Exception\ExitCode;
use App\JsonStreamer;
use App\Parser\ParserFactory;
use App\Validator\CommissionValidatorFactory;

function getFileName($argv): mixed
{
    if (!isset($argv[1])) {
        echo "Path to file is required";
        exit(ExitCode::EX_NOINPUT);
    }
    $fileName = $argv[1];
    if (!file_exists($fileName)) {
        echo "File not found";
        exit(ExitCode::EX_DATAERR);
    }
    return $fileName;
}

try {
    $streamer = new JsonStreamer();
    $handler = new CommissionHandler();
    $streamer->setParser(
        ParserFactory::getParser(
            getFileName($argv)
        )
    );
    $handler->setValidator(
        CommissionValidatorFactory::getValidator()
    );
    $results = $handler->processCommissions($streamer);
} catch (Throwable $throwable) {
    echo $throwable->getMessage();
    exit(ExitCode::EX_GENERAL);
}

foreach ($results as $result) {
    echo $result.PHP_EOL;
}

foreach ($handler->getErrors() as $line) {
    echo 'error:'.$line.PHP_EOL;
}
