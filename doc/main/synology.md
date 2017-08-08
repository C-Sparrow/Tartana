# Tartana Synology

Tartana does have it's own download manager system. Buf if you are running it on a Synology NAS then you can use the **Synology Download Station** to handle the downloads.

1. You need to disable the local system (*tartana.local.enabled: false*) and enable Synology (*tartana.synology.enabled: true*) in the [configuration](configuration.md).
2. Configure the web address, username, password and location information.
3. In the download station application configure the file hosters.
4. In the download station application disable auto extract (it is buggy and Tartana will handle it for you anyway)

Now you are able to add files with links to Tartana and Synology will download them for you.

### PHP Version
The default PHP version on Synology has only limited modules activated (for security reasons, pfff). To run successfully Tartana on Synology from the command line you need to create two symbolic links as root. The following commands should be executed on a Shell (Putty).

If you can't log in as root trough SSH, log in as an admin user first.
- `ssh admin@nas-server` // Enter the admin password
- `sudo su` // Enter again the admin password or the root one if it is a different user

The following command points the PHP executable to the one from the app center which has the needed modules enabled when you are working on a shell:

`ln -s /usr/local/bin/php56 /sbin/php`

The following command points the PHP executable to the one from the app center which has the needed modules enabled when you use the task scheduler/cron:

`ln -s /usr/local/bin/php56 /usr/local/bin/php` // 