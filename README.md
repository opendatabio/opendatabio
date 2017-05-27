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

### Installation instructions
First, install the prerequisite software: Apache, MySQL, PHP and ImageMagick.
On a Debian system, use

```
apt-get install apache2 mysql-server php5 imagemagick php5-mysql
```

Download the OpenDataBio install files from our [releases page](../../releases).
**NOTE**: code from the Github master branch should be considered unstable! Always install from a release zip!
Extract the installation zip or tarball and move it to the public folder on your webserver (in Debian/Ubuntu,
it is probably /var/www/html), and rename the directory to "opendatabio". Change directory to your opendatabio folder.

TODO: better instructions for a per-user directory??
https://httpd.apache.org/docs/2.4/howto/public\_html.html

Make sure that the installation directory is owned by the user running apache (probably www-data).

It is also recommended that you use the webserver rewriting rules to create friendlier URLs.
Run `a2enmod rewrite` and edit the apache configuration file (TODO: give detailed instructions!)

Change directory to your opendatabio directory and run 
```
php install
```

And you're good to go! If you have moved your installation files to the /var/www/opendatabio folder, you will probably
be able to access it as http://localhost/opendatabio. The database migrations come with an administrator account, with
login 'admin@example.org' and and password 'password1'. Edit the file before importing, or change the password after 
installing.

### Post-install configurations
Include instructions for app config: language, locale and timezone!!! 

TODO: abbreviation format in config/app

TODO: mail settings; defaults to SMTP??

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
