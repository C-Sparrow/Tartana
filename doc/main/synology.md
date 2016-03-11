# Tartana Synology

Tartana does have it's own download manager system. Buf if you are running it on a Synology NAS then you can use the **Synology Download Station** to handle the downloads.

1. You need to disable the local system (*tartana.local.enabled: false*) and enable Synology (*tartana.synology.enabled: true*) in the [configuration](configuration.md).
2. Configure the web address, username, password and location information.
3. In the download station application configure the file hosters.
4. In the download station application disable auto extract (it is buggy and Tartana will handle it for you anyway)

Now you are able to add files with links to Tartana and Synology will download them for you.