# Tartana Running

Before you can run Tartana make sure you have [installed](installation.md) it correctly.
You can run Tartana in threee different modes:

- **Command line only**

  This mode doesn't require any web server.

- **Built in web server**

  This mode offers a web server which doesn't require any set up.

- **Web server infrastructure**

  This mode allows to run Tartana in an existing web server infrastructure.

We are going to explain the threee different modes more deeply in the next chapters.

## Command line
The simplest way to run Tartana is trough command line. The only thing you have to set up is a cron job which runs the default script. The following example runs tartana every minute and can be placed directly into crontab:

`* * * * * /path/to/php /path/to/tartana/cli/app.php default`

## Built in web server
Since PHP 5.4 a stand alone web server is shipped with the default PHP installation. You can run Tartana with that web server. We are offering a simplified command to start it.

`php cli/app.php server start`

You can stop it at any time with the command

`php cli/app.php server stop`

The default port the web server is listening to is 8000. If you want to change it, add the *--port=9000* option to the start command.

If you want to start it in the backgound, add the *--background* option to the start command.

## Web server infrastructure
You can unzip Tartana on your web root and opening the web directory. You will see then the web interface of Tartana. As Tartana is mainly doing stuff in the background you need to set up a cron as described in the command line chapter:

`* * * * * /path/to/php /path/to/tartana/cli/app.php default`

This is needed because of the long running proceses which are downloading the files or extracting them. If they would be triggered during a web request, the process would die.
For better security, point your virtual host to the web folder of Tartana.