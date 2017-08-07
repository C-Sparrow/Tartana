# Tartana Downloads

Tartana works the way that it scans a directory, specified in the [configuration](configuration.md), for files which do contain links to download. At the moment, Tartana supports the following file extensions:
- txt: Text files where every line is a link
- dlc: Container files with links
- rsdf: Container files with links

This means, every time the default command is invoked on the command line, the folder is checked if it contains files with links to download.

## Direct upload
The most easy way is to directly upload files int the folder which get scanned. This can be done trough File Browsers, FTP, Samba Shares or whatever tool you prefer to access the Tartana folder.

## Upload web interface
It is possible to upload files trough the web interface. How to access the web interface is described in this [page](running.md).

## Click'n Load
Some one click hoster do have a convenient way to add files directly trough a click'n load button. This works out of the box for JDownloader, but for Tartana you need to set up your environment properly before you can use it.

### Run the Tartana server
Start the built in web server as described [here](running.md). This is needed as Click'n Load needs a running web server to post the links to.

### Forward ports
You need to forward the port of click'n load is calling on your local computer to the Tartana web server. If you Tartana server has the IP 192.168.1.50 and is running on port 8080, then you need to forward it from your local port 9666 to 196.168.1.50:8080. How to do that on your prefered OS, read the following chapters.

#### Windows
On Windows you can do in the command shell as admin:

`netsh interface portproxy add v4tov4 listenport=9666 connectaddress=192.168.1.50 connectport=8080 listenaddress=127.0.0.1`

To stop it, run 

`netsh interface portproxy delete v4tov4 listenport=9666 listenaddress=127.0.0.1`.

#### Linux
On Linux you can do a port forwarding like:

`ssh -L 127.0.0.1:9666:192.168.1.50:8080 -N 127.0.0.1`

#### Proxy apps
There are various apps or applications out there which can do port forwarding trough a graphical interface on your device.