# Ox
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2VATG7M5GNZ6Q)
**Ox currently supports:**
- Ubuntu 16.04
## Install
```bash
wget ox.oxboot.com/ox && sudo bash ox
```
## Commands
### Create PHP website
```bash
ox site:create domain.dev
```
### Create PHP+MySQL website
```bash
ox site:create domain.dev --mysql
```
### Create website with preconfigured packages
```bash
ox site:create domain.dev --package=default
ox site:create domain.dev --package=wordpress
ox site:create domain.dev --package=oxboot
ox site:create domain.dev --package=bedrock
ox site:create domain.dev --package=grav
```

=======
### Delete Website with Database & all configs
```bash
ox site:delete domain.dev
```
### Print information about website
```bash
ox site:info domain.dev
```
### List all available websites
```bash
ox site:list
```
