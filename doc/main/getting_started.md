# Tartana Getting started

To have Tartana up and running in no time follow the steps below on a SSH console:

- `mkdir tartana`
- `cd tartana`
- `curl -s https://api.github.com/repos/c-sparrow/tartana/releases/latest | grep browser_download_url | head -n 1 | cut -d '"' -f 4 | wget --base=http://github.com/ -i -`
- `unzip tartana.zip`
- `php cli/app.php server start`
- Open your browser with the url http://{{server-ip}}:8000
- Log in with admin/admin
- Add a .txt or .dlc file with links to download
- Check the progress on the browser

You can also upload Tartana trough FTP/S if you don't have a SSH shell available. How to run Tartana in a proper web server infrastructure can be found in the [running documentation](running.md).