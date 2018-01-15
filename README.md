# Taurus
A cryptocurrency trading bot.


## Build/Usage
The bot is Dockerized. To use the bot, build the image and run:

`docker build -t taurus . `

`docker run --name=taurus -p 127.0.0.1:8000:8000 taurus`

After the initial build/run, you just need to start it to use it:

`docker start taurus`


