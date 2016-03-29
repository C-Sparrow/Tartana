# Tartana Command Line

Tartana is made to run on command line without any interaction trough the web interface. Trough the [configuration](configuration.md) you can fully configure Tartana.

Tartana is designed to be controlled trough an external daeomon preferably a cron job. The command `php cli/app.php` is running all the neccessary tasks to full run Tartana.

1. It scans the folder for link files (txt or dlc).
2. It starts downloads in the queue, if run in local mode.
3. It scans the processed downloads and extracts them.

## Commands

The *cli/app.php* PHP script accepts the two following main commands:

- **default**

  The main command which runs all the tasks needed from Tartana.
  
  Example: `php cli/app.php default`

- **server**

  Starts the built in web server as described (here)[running.md]. You can add the following arguments *start* or *stop* and *--port*.
  
  Example: `php cli/app.php server start --port=9009`

- **update**

  Updates Tartana to the latest version. As default it updates from the latest release from Github. If you want to provide your own update server, set the parameter in the configuration.
  
  Example: `php cli/app.php update`

- **download:control**

  Allows to control the downloads. The following actions are available:

  - **status**<br/>Displays a table of the downloads.

  - **details**<br/>Displays a table with details from a download, needs the *id=* parameter.

  - **clearall**<br/>Removes all downloads.

  - **clearcompleted**<br/>Removes all successfully processed downloads.

  - **resumeall**<br/>Resumes all downloads.
    
  - **resumefailed**<br/>Resumes the downloads which have failed downloading or processing.

  - **reprocess**<br/>Reprocesses the completed or processed downloads. This is handy if you want to extract the downloads again.

  The status and the other actions do support a *--destination* (eg. --destination=demo) option. This options filters downloads by the destination string and runs the action only on that subset. The *--compact* (eg. --compact=1) option shows the status action in compact mode.

  Example: `php cli/app.php download:control status destination=test`

- **fos:user:**

  Manages the users. More information can be found in the [user](users.md) documentation.
  
  Example: `php cli/app.php fos:user:create john`

## Options
You can add some options when running a command:

- **-q, --quit**

  Do not output any message.
  
- **-e, --env=ENV**

  The Environment name. [default: "prod"]

- **-v|-vv|-vvv, --verbose**

  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug.