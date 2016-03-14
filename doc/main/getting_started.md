# Tartana Getting started

To have Tartana up and running in no time follow the steps below on a SSH console:

### Installation
- `mkdir tartana`
- `cd tartana`
- `curl -s https://api.github.com/repos/c-sparrow/tartana/releases/latest | grep browser_download_url | head -n 1 | cut -d '"' -f 4 | wget --base=http://github.com/ -i -`
- `unzip tartana.zip`

### Download the first file
- `echo http://c-sparrow.github.io/Tartana/doc/images/downloads-list.png > var/data/Links/test.txt`
- `php cli/app.php -vvv`
- `php cli/app.php download:control`
- `ls var/data/Downloadsjob-{ts}`

There is a file created in the folder *var/data/Downloadsjob-{ts}/tmp-1.bin*, you can rename it to downloads-list.png. This is due the fact that Github doesn't send the correct headers for the file. But on a proper file hoster like share-online.biz or uploaded.net the files got renamed correctly after download.

### Start built in web server
These steps are only required if you want to access the web interface.
- `php cli/app.php server start`
- Open your browser with the url http://{{server-ip}}:8000
- Log in with admin/admin
- Add a .txt or .dlc file with links to download
- Check the progress on the browser

You can also upload Tartana trough FTP/S if you don't have a SSH shell available. How to run Tartana in a proper web server infrastructure can be found in the [running documentation](running.md).