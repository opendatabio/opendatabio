# opendatabio
A modern system for storing and retrieving plant data - floristics, ecology and monitoring.

This project improves and reimplements code from the Duckewiki project. Duckewiki is a tribute to Adolpho Ducke,
one of the greatest Amazon botanists, and Dokuwiki, an inspiring wiki platform.

A companion R package is being developed [here](https://github.com/opendatabio/opendatabio-r).

## Authors
- Alberto Vicentini (vicentini.beto@gmail.com, alberto.vicentini@inpa.gov.br) - Instituto Nacional de Pesquisas da Amazônia ([INPA](http://portal.inpa.gov.br/)), Manaus, Brazil - coordination
- Andre Chalom (andrechalom@gmail.com) - main developer
- Alexandre Adalardo de Oliveira (adalardo@usp.br) - Universidade de São Paulo (USP), Instituto de Biociências ([IB-USP](http://www.ib.usp.br/en/))

## Funding & Support
This project has recieved support from [Natura Campus](http://www.naturacampus.com.br/cs/naturacampus/home).

## Overview
This project aims to provide a flexible but robust framework for storing, analysing and exporting biological data.
See our [Wiki page](../../wiki) for details.

## Install
### Prerequisites and versions
OpenDataBio software is supported in Debian, Ubuntu and ArchLinux distributions of Linux. You can install it on
a Mac-OS X by following some [custom instructions](../../README_macInstallationNotes.md), but this is not supported.
We have no plans for Windows support.

Opendatabio is written in PHP and developed over the Laravel framework version 5.5. 
The minimum supported PHP version is 7.0, which is available from apt-get in Ubuntu 16.04 and Debian 9.

It also requires a working web server and a database. The minimum required version for MySQL is 5.7.6
(or equivalently MariaDB 10.1.2).
It should be possible to install using Nginx 
as webserver, or Postgres as database, but our installation script focuses on a Apache/MySQL setup.

The image manipulation (thumbnails, etc) is done with GD.

Pandoc is used to translate LaTeX code used in the bibliographic references. It is not necessary for the installation,
but it is suggested for a better user experience. The minimum Pandoc version supported is 1.10.

The background jobs (such as data import/export) may be handled by the program Supervisor. 

The software is being developed and extensively tested using PHP 7.2.1, Apache 2.4.29 and
MySQL 10.1.30-MariaDB. If you have trouble or questions about other softwares or versions, please
contact our team using the Github repository.

### Installation instructions
First, install the prerequisite software: Apache, MySQL, PHP, pandoc and supervisor.

On a Debian 9 system, you need to install some PHP extensions as well. Use:
```
apt-get install apache2 mysql-server php7.0 libapache2-mod-php7.0 php7.0-mysql \ 
		php7.0-cli pandoc php7.0-mbstring php7.0-xml php7.0-gd \
		supervisor

a2enmod php7.0
phpenmod mbstring
phpenmod xml
phpenmod dom
phpenmod gd
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
        Alias /images /home/odbserver/opendatabio/public/images
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

The installation script will download the [Composer](https://getcomposer.org/) dependency manager, and all required PHP libraries. However, if your server is behind a proxy, you should install and configure Composer independently.

Finally, change directory to your opendatabio directory and run 
```
php install
```

If the installer complains about missing PHP extensions, remember to activate them in both the cli and the web ini files for PHP!

If the install script finishes with success, you're good to 
go! Point your browser to 
http://localhost/opendatabio. The database migrations come with an administrator account, with
login 'admin@example.org' and password 'password1'. Edit the file before importing, or change the password after 
installing.

If you have any problems such as a blank page, error 500 or error 403, check the error logs at /var/log/apache and /home/odbserver/opendatabio/storage/logs.

There are other countless possible ways to install the application, but they may involve more steps and configurations.

### Installation Issues

- If you receive the error "failed to open stream: Connection timed out" while running the installer, 
this indicates a misconfiguration of your IPv6 routing. The easiest fix is to disable IPv6 routing on the server.

- Many Linux distributions (most notably Ubuntu and Debian) have different php.ini files for the command line interface
and the Apache plugin. It is recommended to use the configuration file for Apache when running the install script,
so it will be able to correctly point out missing extensions or configurations. To do so, find the correct path
to the ini file, and export it before using the php install command. For example,

```bash
$ export PHPRC=/etc/php/7.0/apache2/php.ini
$ php install
```

### Post-install configurations
You can change several configuration variables for the 
application. The most important of those are probably set
by the installer, and include database configuration and
proxy settings, but many more exist in the ".env" and 
"config/app.php" files. In particular, you may want to change
the language, timezone and e-mail settings. 
Run `php artisan config:cache` after updating the config files.

If your import/export jobs are not being processed, make sure Supervisor is running 
(systemctl start supervisord && systemctl enable supervisord), and check the log files at storage/logs/supervisor.log.

(Optional) In order to stop search engine crawlers from indexing your database, add the following to your "robots.txt"
in your server root folder (in Debian, /var/www/html):
```
User-agent: *
Disallow: /
```

The e-mail system used by OpenDataBio is very flexible, but the installation program supposed you will be using some
form of SMTP (such as smtp.gmail.com). Check the "config/mail.php" file for more options on how to send e-mails.

When considering database backups for an OpenDataBio installation, please remember that the user uploaded images are
stored in the filesystem, and plan your backup accordingly.

## Development

The Laravel-Datatables library is incompatible with `php artisan serve`, so this command should not be used.
The recommended way of running this app in development is by installing it and choosing "development" in the installer.

This system uses Laravel Mix to compile the SASS and JavaScript code used. 
If you would like to contribute to the app development,
remember to run `npm run prod` after making any change to these files.

Notice that "doctrine/instantiator" is being held back to 1.0.5 to avoid the dependency on PHP 7.1.
Notice that "doctrine/annotations" is being held back to 1.4.0 to avoid the dependency on PHP 7.1.

## Upgrade
A tool for upgrading duckewiki databases to opendatabio is currently being developed.

## License
Opendatabio is licensed for use under a GPLv3 license. 

PHP is licensed under the PHP license. Composer and Laravel framework are licensed under the MIT license.
