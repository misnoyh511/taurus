# Taurus
A cryptocurrency trading bot. It is currently designed to trade BTC/USD on GDAX, but is planned to trade multiple symbols on multiple exchanges.


## Build/Usage
The bot is Dockerized, and makes use of Docker secrets (https://docs.docker.com/engine/swarm/secrets/).
Therefore, before you can use the bot, you have to create your secrets (note the trailing dash!):

`docker swarm init`

`echo "<YourMySqlPassword>" | docker secret create MYSQL_PASSWORD -`

`echo "<YourMySqlRootPassword> | docker secret create MYSQL_PASSWORD -` 
 
To use the bot, build the images and run:

`docker image build -t web -f web.docker .`

`docker image build -t app -f app.docker .`

`docker stack deploy -c docker-compose.yml taurus`

After the initial build/run, you just need to deploy it to use it:

`docker stack deploy -c docker-compose.yml taurus`

This stack-based deployment is production-ready.


## Architecture
Taurus uses the CryptoCurrency eXchange Trading Library (CCXT) (https://github.com/ccxt/ccxt) to retrieve trading/market data and conduct trades.

The system has 4 main components:
* PHP Application (app)
* NGINX Web Server (web)
* MySQL Database (database)
* Redis Server (cache)

The app server runs a script continuously via supervisord that pulls market and order data from Bitfinex. Data less than 24 hours old is stored in the cache, and data older than 24 hours is stored in the database.
Taurus feeds the data into TA-Lib signals and indicators, and makes trades based on the results on GDAX.

## Current Development
* Data feed
* Signals processing
* Trade execution

