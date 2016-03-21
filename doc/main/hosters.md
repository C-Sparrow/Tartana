# Tartana Hosters

Tartana supports link downloads for HTTP and FTP downloads as well as one click hosters with premium account support.
Some hosters do need some configuration settings like login credentials, which will be read during runtime from the *app/config/hosters.yml* file. You can copy the dist file as reference. The list below shows the current status of the hosters available in Tartana.

### <i class="fa fa-check-square"></i> http(s)://*
Tartana can download any public HTTP url.

### <i class="fa fa-check-square"></i> ftp(s)://*
Tartana can download any anonymous FTP url. If you want to define a login for a host (eg. github.com) use the following configuration in the hosters.yml file (the host must not have any special character):

```
ftp:
    githubcom:
        username: foo
        password: bar
```

### <i class="fa fa-check-square"></i> dropbox.com
Fully implemented with public and private links support. To be able to download private links, you need to get an access token. An app is required on https://www.dropbox.com/developers/apps. Create a new app and click on *Generate Access token*. Copy that string to the hoster.yml file.

### <i class="fa fa-check-square"></i> share-online.biz
Fully implemented with premium account support.

### <i class="fa fa-check-square"></i> uploaded.net
Fully implemented with premium account support.

### <i class="fa fa-check-square"></i> rapidgator.net
Fully implemented with premium account support.

### <i class="fa fa-minus-square"></i> mega.nz
Is planed for a next release.

### <i class="fa fa-minus-square"></i> drive.google.com
Is planed for a next release.

