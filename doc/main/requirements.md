# Tartana Requirements

Tartana is a PHP application which can run on the [command line only or with a web frontend](running.md). The minimal requirements of Tartana are:

- Linux System
- PHP command line executable
- PHP Sqlite extension
- PHP Curl extension
- PHP Intl extension
- Unzip installed
- Unrar installed

## Ubuntu setup

To prepare a bare Ubuntu system run the following commands before you install Tartana:

- sudo apt-get install php5-cli
- sudo apt-get install php5-curl
- sudo apt-get install php5-intl
- sudo apt-get install unzip
- sudo apt-get install unrar

## Web server
If you are running Tartana with it's own web server then the basic requirements are enough as the PHP built in web server is part of the PHP cli package. If you have an existing web server like Apache or Nginx, then you can upload Tartana to a web accessible folder.