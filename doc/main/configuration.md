# Tartana Configuration

Before you can configure Tartana make sure you have [installed](installation.md) it correctly.
In the folder *app/config* you will find the whole configuration for Tartana. If you have started Tartana already then the file *app/config/parameters.yml* will be auto generated. If not you can copy it from **parameters.dist.yml** and adapt it to your needs. You can change the parameters also trough the web interface.

The following list describes the parameters in more detail:

- **tartana.dateFormat** [m.d.Y h:i a]

  The date format to be displayed on the front.

- **tartana.links.folder** [var/data/Links]

  The folder which will be monitored for the link files (txt or dlc).

- **tartana.links.convertToHttps** [true]

  Should the links to download being converted to https.

- **tartana.links.hostFilter** []

  Should only links being added which belong to the host (example** [drive.google.com).

- **tartana.extract.destination** [var/data/New]

  Where to extract the downloads to.

- **tartana.extract.passwordFile** [app/config/passwords.dist.txt]

  The password file which should be used during extract.

- **tartana.extract.deleteFiles** [true]

  After the files have been extracted, should they be deleted.

- **tartana.local.enabled** [true]

  If Tartana should run without a Synology back end set it to true (set tartana.synology.enabled to false!).

- **tartana.local.downloads** [var/data/Downloads]

  When running the local download repository where should the downloads being saved to.

- **tartana.local.downloads.speedlimit** [0]

  The total max speed of the downloads in kb, if it is < 1 no download speed will be set.

- **tartana.synology.enabled** [false

  Should Tartana run with a synology back end  (set tartana.local.enabled to false!).

- **tartana.synology.address** [https://localhost:5001/webapi]

  The address of the Synology server to connect to.

- **tartana.synology.username** [admin]

  The username of the Synology server user to connect to.

- **tartana.synology.password** [admin]

  The password of the Synology server user to connect to.

- **tartana.synology.downloads** [/volume1/shares/Downloads]

  Where can the downloads being found.

- **tartana.synology.downloadShare** [shares/Downloads]

  Synology needs a share folder to create the downloads in, this path
  should point to the same location as the downloads path above.

- **tartana.log.path** ["%kernel.logs_dir%/%kernel.environment%.log"]

  The path to the log file.

- **tartana.log.level** [debug]

  The log level.

- **tartana.update.url:** [github]

  The update url to fetch the latest Tartana version from. If the path is a file on the server, you can define it also relative to the install directory.

- **secret** [457152e95295f63116eb776d43ac3d0c41e58905]

  http://symfony.com/doc/current/reference/configuration/framework.html#secret