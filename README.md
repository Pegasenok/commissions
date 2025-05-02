## Run program
### Locally
```bash
composer install
php app.php example/input.txt
```
```bash
./vendor/bin/phpunit tests
```
```bash
export XDEBUG_MODE=coverage
./vendor/bin/phpunit --configuration phpunit-coverage.xml
```

### Docker path
```bash
docker build -t task-commissions .
```
```bash
docker run -it --rm \
  task-commissions vendor/bin/phpunit tests
```
```bash
docker run -it --rm \
  -v $(pwd)/app.php:/app/app.php \
  -v $(pwd)/src:/app/src \
  -v $(pwd)/example:/app/example \
  task-commissions php app.php example/input.txt
```

## Issues found
1. You may replace `FakeExchangeRateClient` with `ExchangeRateClient` in app.php to run actual endpoint, but in that case
2. Environment variable `EXCHANGE_RATE_API_KEY` should be set (https://manage.exchangeratesapi.io/dashboard).
3. https://lookup.binlist.net/ has aggressive api limit.
4. https://lookup.binlist.net/41417360 returns invalid response. Fixed with custom cache modification.
5. `var/binlist_cache` folder is commited, to make sure example works smoothly.

## Run tests
1. Set env variable `export XDEBUG_MODE=coverage`, or expect warning.
2. Run tests `./vendor/bin/phpunit tests`
