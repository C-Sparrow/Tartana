# Tartana Requirements

Tartana is a PHP application which can run on the [command line only or with a web frontend](running.md). The minimal requirements of Tartana are:

**Required**
- Linux System
- PHP command line executable
- PHP Sqlite extension
- PHP Curl extension
- PHP Intl extension
- Unzip installed
- Access to the internet

**Optional**
- Unrar installed to extract rar files
- 7z installed to extract .z7 files
- ffmpeg installed for mp4 to mp3 conversion

## Debian/Ubuntu setup

To prepare a bare Ubuntu system run the following commands before you [install Tartana](installation.md):

- sudo apt-get install php5-cli
- sudo apt-get install php5-curl
- sudo apt-get install php5-sqlite
- sudo apt-get install php5-intl
- sudo apt-get install unzip
- sudo apt-get install unrar

## Raspberry Pi setup
On a raspberry pi the unrar command needs to be built by source, becasue of license issues. You can run the following commands after you have done the set up as mentioned above.

- Uncomment the deb-src entry in the file */etc/apt/sources.list*.
- Run `sudo apt-get update`
- Run `mkdir ~/unrar-nonfree && cd ~/unrar-nonfree`
- Run `sudo apt-get build-dep unrar-nonfree`
- Run `sudo apt-get source -b unrar-nonfree` // This command whill take a while, grab a coffe
- Run `sudo dpkg -i unrar*.deb`
- Remove the working directory `cd && sudo rm -rf ~/unrar-nonfree`

## Web server
If you are running Tartana with it's own web server then the basic requirements are enough as the PHP built in web server is part of the PHP cli package. If you have an existing web server like Apache or Nginx, then you can upload Tartana to a web accessible folder.