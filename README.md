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
The minimum supported PHP version is 5.6.4, but PHP 7 is 
strongly recommended. 

It also requires a working web server and a database. It should be possible to install using Nginx 
as webserver, or Postgres as database, but our installation instructions will focus on a Apache/MySQL
setup.

The image manipulation (thumbnails, etc) is done with Imagemagick version 6. Version 7 is not available on 
most Linux distributions official repositories, and is therefore not supported at the moment.

The software is being developed and extensively tested using PHP 7.1.3, Apache 2.4.25, 
MySQL 10.1.22-MariaDB and ImageMagick 6.9.8. If you have trouble or questions about other softwares or versions, please
contact our team using the Github repository.

### Installation instructions
First, install the prerequisite software: Apache, MySQL, PHP and ImageMagick.

Then, install Composer. See [here](https://getcomposer.org/download/) for instructions. After that, use
composer to install the Laravel framework and other dependencies (`composer install`).

This software requires the following PHP extensions:
- intl (TODO: verify)
- mysql (TODO: verify!)
- libxml2 (TODO: verify)
- pdo (because of Propel)
- gd? imagemagick?
- IMAP?

The following PHP extensions are recommended:
- apcu (caching)
- OPcache (caching)

TODO: explain how to install and enable PHP extensions.

On a Debian/Ubuntu system, use

```
apt-get install apache2 mysql-server php7 imagemagick libapache2-mod-php7 php7-mysql php7-imagick php7-intl
```

TODO: Verify command line above

Extract the installation zip or tarball and move it to the public folder on your webserver (in Debian/Ubuntu,
it is probably /var/www). Install composer and Laravel (TODO).

Create a database and user for opendatabio. Using the MySQL command line, (e.g., `mysql -uroot -p`):

```
CREATE DATABASE `opendatabio` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;
CREATE USER `opendatabio`@`localhost` IDENTIFIED BY 'somestrongpassword';
GRANT ALL ON `opendatabio`.* TO `opendatabio`@`localhost`;
```

You may choose another name for your database and user, and you must choose another password. Write them all down.

There are two SQL scripts that create the initial database tables with default configurations and commonly used
objects. These are called `schema.sql` and `seeds.sql`. Run them both:

```
mysql -u opendatabio -psomestrongpassword opendatabio < schema.sql
mysql -u opendatabio -psomestrongpassword opendatabio < seeds.sql
```

Edit the configuration file for the opendatabio, indicating the username, password and database chosen (and the hostname,
in case it is not localhost).  TODO: redo this instructions with migrations instead of schema.sql!!!

And you're good to go! If you have moved your installation files to the /var/www/opendatabio folder, you will probably
be able to access it as http://localhost/opendatabio. The database seeds come with an administrator account, with
username admin and password @dm!n. Edit the file before importing, or change the password after installing.

## Upgrade
A tool for upgrading duckewiki databases to opendatabio is currently being developed.

## License
Opendatabio is licensed for use under a GPLv3 license.
