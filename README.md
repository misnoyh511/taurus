# Taurus
A cryptocurrency trading bot.


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


