# Tartana Installation

To install Tartana execute the following steps in an SSH console (Putty on Windows):

- `mkdir tartana`
- `cd tartana`
- `curl -s https://api.github.com/repos/c-sparrow/tartana/releases/latest | grep browser_download_url | head -n 1 | cut -d '"' -f 4 | wget --base=http://github.com/ -i -`
- `unzip tartana.zip`

Alternatively you can also download it from [here](https://github.com/C-Sparrow/Tartana/releases), extract it on your computer and upload it trough an FTP client to your web space. When you have downloaded and extracted Tartana then it is time to [configure](configuration.md) it.