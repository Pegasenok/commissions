## Issues found
1. Environment variable `EXCHANGE_RATE_API_KEY` should be set (https://manage.exchangeratesapi.io/dashboard).
2. https://lookup.binlist.net/ has aggressive api limit.
3. https://lookup.binlist.net/41417360 returns invalid response. Fixed with custom cache modification.

## Run tests
1. Set env variable `export XDEBUG_MODE=coverage`, or expect warning.
2. Run tests `./vendor/bin/phpunit tests`
