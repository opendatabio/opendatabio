# opendatabio
A modern system for storing and retrieving plant data - floristics, ecology and monitoring.

This project improves and reimplements code from the Duckewiki project. Duckewiki is a tribute to Adolpho Ducke,
one of the greatest Amazon botanists, and Dokuwiki, an inspiring wiki platform.

## Overview
This project aims to provide a flexible but robust framework for storing, analysing and exporting biological data.
See our [Wiki page](../../wiki) for details.

## Install
### Prerequisites and versions
Opendatabio is written in PHP and developed over the Laravel framework. 
The minimum supported PHP version is 5.6.4, but PHP 7 is strongly recommended. 

It also requires a working web server and a database. It should be possible to install using Nginx 
as webserver, or Postgres as database, but our installation script focuses on a Apache/MySQL setup.

The image manipulation (thumbnails, etc) is done with Imagemagick version 6. Version 7 is not available on 
most Linux distributions official repositories, and is therefore not supported at the moment.

The software is being developed and extensively tested using PHP 7.1.5, Apache 2.4.25, 
MySQL 10.1.22-MariaDB and ImageMagick 6.9.8. If you have trouble or questions about other softwares or versions, please
contact our team using the Github repository.

TODO: Install supervisor, configure queue workers + TIMEOUT, RETRIES

### Installation instructions
First, install the prerequisite software: Apache, MySQL, PHP and ImageMagick.
On a Debian system, use

```
apt-get install apache2 mysql-server php5 imagemagick php5-mysql
```

The recommended way to install OpenDataBio is using a dedicated
system user. Create a user called, for example, "odbserver".

Download the OpenDataBio install files from our [releases page](../../releases).
**NOTE**: code from the Github master branch should be considered unstable! Always install from a release zip!
Extract the installation zip to the user's home, so that the 
installation files will reside on directory "/home/odbserver/opendatabio".

You will then need to enable the Apache modules 'mod_rewrite' and 'mod_alias', and add the following to your Apache configuration file:
```
<IfModule alias_module>
        Alias /opendatabio /home/odbserver/opendatabio/public
	Alias /fonts /home/odbserver/opendatabio/public/fonts
        <Directory "/home/odbserver/opendatabio/public">
                Require all granted
                AllowOverride All
        </Directory>
</IfModule>
```

This will cause Apache to redirect all requests for /opendatabio to the correct folder, and also allow the provided .htaccess file to handle the rewrite rules, so that the URLs will be pretty. If you would like to access the file when pointing the browser to the server root, add the following directive as well:
```
RedirectMatch ^/$ /opendatabio/
```

Remember to restart the Apache server after editing the files.

Finally, change directory to your opendatabio directory and run 
```
php install
```

If the install script finishes with success, you're good to 
go! Point your browser to 
http://localhost/opendatabio. The database migrations come with an administrator account, with
login 'admin@example.org' and and password 'password1'. Edit the file before importing, or change the password after 
installing.

If you have any problems such as a blank page, error 500 or error 403, check the error logs at /var/log/apache and /home/odbserver/opendatabio/storage/logs.

There are other countless possible ways to install the application, but they may involve more steps and configurations.

### Post-install configurations
You can change several configuration variables for the 
application. The most important of those are probably set
by the installer, and include database configuration and
proxy settings, but many more exist in the ".env" and 
"config/app.php" files. In particular, you may want to change
the language, timezone and e-mail settings. 
Run `php artisan config:cache` after updating the config files.

## Development

When running this app in development mode, you may access the app at http://localhost:8000 by running

```
php artisan serve
```

This system uses Laravel Mix to compile the SASS and JavaScript code used. 
If you would like to contribute to the app development,
remember to run `npm run prod` after making any change to these files.

## Upgrade
A tool for upgrading duckewiki databases to opendatabio is currently being developed.

## License
Opendatabio is licensed for use under a GPLv3 license. 

PHP is licensed under the PHP license. Composer and Laravel framework are licensed under the MIT license.
