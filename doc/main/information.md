# Tartana Information

Tartana is a command line app which has a web frontend to browse the downloads. It works with four simple steps:

1. It scans a directory for files which do contain links to download.
2. Adds the links to the download repository (local or Synology).
3. Downloads the links.
4. Post processing (eg. auto extract) the downloaded files.

This is a quick overview of the process of Tartana. More details about every step can be found in the chapters below.

### Directory scanning

Every time the default command did run it scans the folder [configured in the parameters.yml file](configuration.md) with the value *tartana.links.folder* for files containing links. Actually supported are dlc, rsdf and txt files.

The command to run the scan is:

`php cli/app.php`

### Download repository

After the links are fetched they will be added to the repository. All links of one file are fetched into one folder. Actually Tartana supports two types of repository. The local one manages the downloads locally, the [Synology repository](synology.md) uses the download station app to manage the downloads.

### Downloading

Depending on the repository either way Tartana is downloading the file or the Synology download station. Progress can be seen trough the following command:

`php cli/app.php download:control`

### Post processing

After all downloads are finished of a folder, the files are post processed. Actually Tartana will auto extract archive files or can convert mp4 files to mp3.

To auto extract files some binaries like **unzip**, **7z** or **unrar** must be available. To convert mp4 files **ffmpeg** must be available.