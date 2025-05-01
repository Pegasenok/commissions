## Run program
```bash
composer install
php app.php example/input.txt
```

## Issues found
1. Environment variable `EXCHANGE_RATE_API_KEY` should be set (https://manage.exchangeratesapi.io/dashboard).
2. You may replace `ExchangeRateClient` with `FakeExchangeRateClient` in app.php to mitigate api key requirement.
3. https://lookup.binlist.net/ has aggressive api limit.
4. https://lookup.binlist.net/41417360 returns invalid response. Fixed with custom cache modification.
5. `var/binlist_cache` folder is commited, to make sure example works smoothly.

## Run tests
1. Set env variable `export XDEBUG_MODE=coverage`, or expect warning.
2. Run tests `./vendor/bin/phpunit tests`
