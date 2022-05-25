# pv-miner-controller

Power and hashboard configurator for ASIC miners which are operated on a photovoltaic system.

This is a first proof of concept and not intended for production environments. PV API queries are currently not implemented, you have to set the available amount of electricity (in watts) manually via the PVMC dashboard. Only one Antminer S9 miner is currently supported.

## Live Demo

https://pvmc.migodi.com/

Please be nice, you controll a real asic miner :) 


## Prerequisites

```
- PHP 8
- Laravel
- ASIC Miner with Braiins-OS+
```

Please see ```composer.json``` and  ```package.json``` for a full list of requirements. 


## Supported ASIC Miner

```
- Antminer S9 with Braiins-OS+
- SSH enabled
```

## Installing

- Install a basic Laravel project and copy all git files into it. 
- Setup .env file

## Setting up .env file

```
#IPv4 of your miner
DEVICE_HOST=0.0.0.0

#SSH Port, default is 22
DEVICE_PORT=22

#SSH Login information
DEVICE_USERNAME=root
DEVICE_PASSWORD=root

##SSH-RSA ID - optional
DEVICE_HOST_KEY="ssh-rsa AAAAB..."
```

## Help / Support

Join our Telegram channel to get latest updates and support.
https://t.me/MigodiOfficial


## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
