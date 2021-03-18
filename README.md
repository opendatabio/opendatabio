# Opendatabio Version 0.9.0

The OpenDatabBio is an open-source database developed to provide a flexible but robust framework to store, manage and distribute biological data. It is designed to accomodate many data types used in biological sciences and their relationships, particularly biodiversity and ecological studies, and serves as a data repository that allow users to download or request well-organized and documented research data.
<br>

The main features of this database include:

1. The ability to define custom [traits](https://github.com/opendatabio/opendatabio/wiki/Trait-Objects#traits) of diferent types, including some special cases like Spectral Data, Colors and Links. Measuhttps://github.com/opendatabio/opendatabio/wiki/Trait-Objects#traitsrements for these traits can be from marked [individuals](https://github.com/opendatabio/opendatabio/wiki/Core-Objects#individuals), [vouchers specimens](https://github.com/opendatabio/opendatabio/wiki/Core-Objects#vouchers), [taxonomic entities](https://github.com/opendatabio/opendatabio/wiki/Core-Objects#taxons) and [locations](https://github.com/opendatabio/opendatabio/wiki/Core-Objects#locations).
1. [Taxons](https://github.com/opendatabio/opendatabio/wiki/Core-Objects#taxons) can be published or unpublished names, synonyms or valid names, and any node of the tree of life may be stored. Taxon insertion are checked through APIs to different individual, fungi and animal data sources (Tropicos, IPNI, MycoBank,ZOOBANK).
1. [Locations](https://github.com/opendatabio/opendatabio/wiki/Core-Objects#locations) are stored with their Geometries, allowing location parent autodetection and spatial queries. Special location types, such as Plots, can be defined and Conservation Units are treated separetely - because they may transverse different administrative areas.
1. Data are organized in [Datasets](https://github.com/opendatabio/opendatabio/wiki/Data-Access-Objects#datasets) and [Projects](https://github.com/opendatabio/opendatabio/wiki/Data-Access-Objects#projects), entities that have different user-access options, with administrator being able to track downloads and requests histories for datasets. These entities allow different research groups to use the same installation, having total control over their particular research data edition and access, while sharing common libraries such as Taxonomy, Locations,  Bibliographic References and Traits.
1. Tools for data exports and imports are provided through [API services](https://github.com/opendatabio/opendatabio/wiki/APi), along with a API client in R language, the [OpenDataBio-R package](https://github.com/opendatabio/opendatabio-r).
1. The [Activity Model](https://github.com/opendatabio/opendatabio/wiki/Auditing) audits changes in any record and downloads of full datasets, which are logged for history tracking, so you know when and who changed or download something.
1. It is an opensource software that can be customized for any special need (see [License](license)).

See our [Wiki page](../../wiki) for a full documentation. Is is also included with translations within the App.

## Credits
- Alberto Vicentini (vicentini.beto@gmail.com) - Instituto Nacional de Pesquisas da Amazônia ([INPA](http://portal.inpa.gov.br/)), Manaus, Brazil
- Andre Chalom (andrechalom@gmail.com)
- Rafael Arantes (birutaibm@gmail.com)
- Alexandre Adalardo de Oliveira (adalardo@usp.br) - Universidade de São Paulo (USP), Instituto de Biociências ([IB-USP](http://www.ib.usp.br/en/))

## Funding & Support
This project has received support from [Natura Campus](http://www.naturacampus.com.br/cs/naturacampus/home). Rafael Arantes contribution was supported by a FAPESP TTIV scholarship (#2017/21695-8).


## Install

### Prerequisites and versions

OpenDataBio web-based software supported in Debian, Ubuntu and ArchLinux distributions of Linux and may be implemented in any linux based machine. We have no plans for Windows support.

**Server requirements**:

* Opendatabio is written in [PHP](https://www.php.net) and developed with the [Laravel framework version 8.0](https://laravel.com/).
	* The minimum supported PHP version is 7.3, which is a requirement of the Laravel version;
	* Requires a working web server and a database. It should be possible to install using Nginx
as webserver, or Postgres as database, but our installation script focuses on a Apache/MySQL setup ([MySQL](https://www.mysql.com/) or [MariaDB](https://mariadb.org/)).
	* The image manipulation (thumbnails, etc) is done with [GD](https://libgd.github.io/), so the GD libraries must be installed.
	* [Pandoc](https://pandoc.org/) is used to translate LaTeX code used in the bibliographic references. It is not necessary for the installation, but it is suggested for a better user experience.
	* Requires [Supervisor](http://supervisord.org/), which is needed for background jobs (such as data import/export)

The software is being developed and extensively tested using PHP 7.4.9, Apache 2.4.46 and
MySQL 10.5.8-MariaDB. If you have trouble or questions about other softwares or versions, please
contact our team using the Github repository.

### Installation instructions

#### Prep the Server
First, install the prerequisite software: Apache, MySQL, PHP, Pandoc and Supervisor. On a Debian system, you need to install some PHP extensions as well and enable them:

```
apt-get install apache2 mysql-server php7.3 libapache2-mod-php7.3 php7.3-mysql \
		php7.3-cli pandoc php7.3-mbstring php7.3-xml php7.3-gd \
		supervisor
a2enmod php7.3
phpenmod mbstring
phpenmod xml
phpenmod dom
phpenmod gd

To check if they are installed:
php -m | grep -E "mbstring|cli|xml|gd|mysql|pandoc|supervisord"

```

You will then need to enable the Apache modules 'mod_rewrite' and 'mod_alias'. Add the following to your Apache configuration. You may create a new file in the sites-available folder: `apache2/sites-available/opendatabio.conf` and place the following code in it. This assumes the application will be installed in the Apache accessible folder `/home/odbserver/opendatabio`, ajust as needed:

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

This will cause Apache to redirect all requests for `/opendatabio` to the correct folder, and also allow the provided `.htaccess` file to handle the rewrite rules, so that the URLs will be pretty. If you would like to access the file when pointing the browser to the server root, add the following directive as well:

```
RedirectMatch ^/$ /opendatabio/
```

Configure your php.ini files. The installer may complain about missing PHP extensions, so remember to activate them in both the **cli** and the web **ini** files for PHP! Update the values for the following variables:

```
Find files:
php -i | grep 'Configuration File'

Change in them:
	memory_limit should be at least 512M
	post_max_size should be at least 30M
	upload_max_filesize should be at least 30M

```

Remember to restart the Apache server after editing the files. In Ubuntu would be:

```
sudo systemctl restart apache2.service

```


##### Mysql Charset and Collation

1. If using MariaDB or Mysql you should add the following to your configuration file (mariadb.cnf or my.cnf), i.e. the Charset and Collation you choose for your installation must match that in the 'config/database.php'

```
[mysqld]
character-set-client-handshake = FALSE  #without this, there is no effect of the init_connect
collation-server      = utf8mb4_unicode_ci
init-connect          = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
character-set-server  = utf8mb4

[mariadb]
max_allowed_packet=100M
innodb_log_file_size=300M

```
2. If using MariaDB and you have problems of type **#1267 Illegal mix of collations**, then check [](https://github.com/phpmyadmin/phpmyadmin/issues/15463) on how to fix that,


#### Create Dedicated User
The recommended way to install OpenDataBio is using a **dedicated system user**. In this instructions this user is  **odbserver**.


#### Download software

Login using or Dedicated User and download or clone this software to where you want to install it. Here we assume this is `/home/odbserver/opendatabio`, so that the installation files will reside in this directory.

#### Configure supervisord
Configure Supervisor, which is required for jobs. Create a file name **opendatabio-worker.conf** in the Supervisor configuration folder `/etc/supervisor/conf.d/opendatabio-worker.conf` with the following content:

```
;--------------
[program:opendatabio-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/odbserver/opendatabio/artisan queue:work --sleep=3 --tries=1 --timeout=0 --daemon
autostart=true
autorestart=true
user=odbserver
numprocs=8
redirect_stderr=true
stdout_logfile=/home/odbserver/opendatabio/storage/logs/supervisor.log
;--------------
```

#### Install OpenDataBio

The installation script will download the [Composer](https://getcomposer.org/) dependency manager and all required PHP libraries installed in the `composer.json` file. However, if your server is behind a proxy, you should install and configure Composer independently. We have implemented PROXY configuration, but we are not using it anymore and have not tested properly (if you require adjustments, place an issue on Github).

Move to your `/home/odbserver/opendatabio` folder and run the installer, which will prompt your for various configurations options (see below):

```
php install
```

**Read before installing**:
* The installer will prompt you to insert initial configuration values, which are stored in the `.env` file in the application folder. You may configure this file before running the command above, allowing you to see all possible parameters. File `env.example` illustrates a configuration with values.
* OpenDataBio sends emails to register users, either to inform about a job that has finished or to send data requests to dataset administrators. You may use a Google Email for this, but will need to change the account security options to allow OpenDataBio to use the account to send emails (you need to turn **on** the `Less secure app access` option in the My Account Page). Therefore, create a dedicated email address for your installation. Check the "config/mail.php" file for more options on how to send e-mails.
* OpenDataBio uses GoogleMaps API to show Locations in a map. This will be replaced by a completely free map web server service (e.g. OpenStreeMaps or the like), although Google provides a free number of server requests per site, which may be quite large. Therefore, you need a Google Maps API key if your site will go to production.

If the install script finishes with success, you're good to go! Point your browser to http://localhost/opendatabio. The database migrations come with an administrator account, with login `admin@example.org` and password `password1`. Change the password after installing.

#### Installation issues
There are other countless possible ways to install the application, but they may involve more steps and configurations.

*  If you receive the error "failed to open stream: Connection timed out" while running the installer, this indicates a misconfiguration of your IPv6 routing. The easiest fix is to disable IPv6 routing on the server.
*  Many Linux distributions (most notably Ubuntu and Debian) have different php.ini files for the command line interface and the Apache plugin. It is recommended to use the configuration file for Apache when running the install script, so it will be able to correctly point out missing extensions or configurations. To do so, find the correct path to the ini file, and export it before using the php install command. For example,

```bash
export PHPRC=/etc/php/7.4/apache2/php.ini
php install
```

* If you receive errors during the random seeding of the database, you may attempt to remove
the database entirely and rebuild it. Of course, do not run this on a production server.

```bash
php artisan migrate:fresh && php artisan db:seed
```
### Post-install configs
* If your import/export jobs are not being processed, make sure Supervisor is running `systemctl start supervisord && systemctl enable supervisord`, and check the log files at `storage/logs/supervisor.log`.
* You can change several configuration variables for the application. The most important of those are probably set
by the installer, and include database configuration and proxy settings, but many more exist in the `.env` and
`config/app.php` files. In particular, you may want to change the language, timezone and e-mail settings. Run `php artisan config:cache` after updating the config files.
* In order to stop search engine crawlers from indexing your database, add the following to your "robots.txt" in your server root folder (in Debian, /var/www/html):
```
User-agent: *
Disallow: /
```

* Folders `public/downloads_temp`, `public/upload_pictures`, `storage` and `bootstrap/cache` must be writable by the Server user (usually www-data). See [this link](https://linuxhint.com/how-to-set-up-file-permissions-for-laravel/) for an example of how to do that.

* When considering database backups for an OpenDataBio installation, please remember that the user uploaded images are
stored in the filesystem, and plan your backup accordingly.


## License
Opendatabio is licensed for use under a GPLv3 license.

PHP is licensed under the PHP license. Composer and Laravel framework are licensed under the MIT license.


## Acknowledgements
- Rodrigo Augusto Santinelo Pereira (raspereira@ffclrp.usp.br)
- Lo Almeida
- Rodrigo Pereira
- Ricardo Perdiz
- Renato Lima
