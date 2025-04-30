<?php

require __DIR__.'/vendor/autoload.php';

use App\Commission\Calculator\BaseEuroCommission;
use App\Commission\Calculator\EuropeCountryCommission;
use App\Commission\Calculator\NonEuroCommission;
use App\Commission\Calculator\NonEuropeCountryCommission;
use App\CommissionHandler;
use App\Exception\ExitCode;
use App\Http\ExchangeRateClient;
use App\Http\FakeBinlistClient;
use App\Http\FakeExchangeRateClient;
use App\JsonStreamer;
use App\Parser\ParserFactory;
use App\Services\BinInfo\BinLookup;
use App\Services\ExchangeRate\ExchangeRate;
use App\Validator\ValidatorFactory;

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
        ValidatorFactory::getCommissionLineValidator(),
    );

    $handler->addCommissionCalculator(new BaseEuroCommission());
    $handler->addCommissionCalculator(new NonEuroCommission(
        new ExchangeRate(
            new FakeExchangeRateClient(apiKey: getenv('EXCHANGE_RATE_API_KEY')),
            ValidatorFactory::getExchangeRateValidator(),
        )
    ));
    $binLookup = new BinLookup(
        new FakeBinlistClient(),
        ValidatorFactory::getBinInfoValidator(),
    );
    $handler->addCommissionCalculator(new EuropeCountryCommission($binLookup));
    $handler->addCommissionCalculator(new NonEuropeCountryCommission($binLookup));

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
