* [**Installation**](#)
    * [Pre-install](#preinstall)
    * [Install with docker](#usingdocker)
    * [Install without docker](#withoutdocker)

# Installation

OpenDataBio is a web-based software supported in Debian, Ubuntu and ArchLinux distributions of Linux and may be implemented in any linux based machine. We have no plans for Windows support, but it may be easy to install in a windows machine using docker (see below).
<br>
<br>

Opendatabio is written in [PHP](https://www.php.net) and developed with the [Laravel framework](https://laravel.com/). It requires a web server (apache or nginx), PHP and a SQL database (tested only with [MySQL](https://www.mysql.com/) and [MariaDB](https://mariadb.org/))
<br>
<br>

You may install easily using docker, but **docker files provided are for development only**.

<a name="preinstall"></a>
***
## Pre-install steps

1. A [Tropicos.org API key](https://services.tropicos.org/help?requestkey), for the OpenDataBio ExternalAPI model to be able to retrieve taxonomic data from the Tropicos.org database;
1. A [Google Map API key](https://developers.google.com/maps/documentation/javascript/get-api-key) to correctly display Locations on the google maps service. This service will be replaced by a completely free map web server service (e.g. OpenStreeMaps or the like), although Google provides a free number of server requests per site, which may be quite large. Therefore, you need a Google Maps API key if your site will go to production.
1. OpenDataBio sends emails to register users, either to inform about a job that has finished or to send data requests to dataset administrators. You may use a Google Email for this, but will need to change the account security options to allow OpenDataBio to use the account to send emails (you need to turn **on** the `Less secure app access` option in the Gmail My Account Page). Therefore, create a dedicated email address for your installation. Check the "config/mail.php" file for more options on how to send e-mails.

### Configuring the environment file

Configuration variables are stored in the environment `.env` file in the application folder. You must configure this file before running the installer or build a docker container.
1. If using docker, just edit the `.env.docker`  file that will be copied as the app container `.env` file;
1. If not using docker, create a `.env` file with the contents of the provided `env.example`
1. Follow the comments in this file and adjust accordingly

<a name="usingdocker"></a>
***
## Install using Docker

The easiest way to install and run OpenDataBio is using [Docker](https://www.docker.com/) and the docker configuration files provided, which contain all the needed configurations to run ODB. Uses nginx and mysql, and supervisor for queues. May be optionally configured for redis for queue jobs.

### Docker files
```bash
laraverl-app/
----docker/*
----./env.docker
----docker-compose.yml
----Dockerfile
----Makefile
```
These are modified from [](https://github.com/dimadeush/docker-nginx-php-laravel), where you find a production setting as well.

#### Installation
1. Make sure you have [Docker](https://www.docker.com/) and [Docker-compose](https://docs.docker.com/compose/install/) installed in your system;
1. Check if your user is in the [docker group](https://github.com/sindresorhus/guides/blob/main/docker-without-sudo.md) created during docker installation;
1. Download or clone the OpenDataBio in your machine;
1. Make sure your user is the owner of files and folders, else, change ownership and user to your user
1. Enter the opendatabio directory created;
1. Edit the environment file name `.env.docker`
1. To install locally for development just adjust the following variables in the Dockerfile, which are needed to map the files owners to a docker user;
    1. `UID` the numeric user your are logged in and which is the owner of all files and directories in the app directory.
    1. `GDI` the numeric group the user belongs, usually same as UID.
1. File `Makefile` containes shortcuts to the docker-compose commands used to build the services configured in the `docker-compose.yml` and auxiliary files in the `docker` folder.
1. Build the docker containers using the shortcuts (read the Makefile to undersand the commands)
```bash
make build
```
1. Start the implemented docker Services
```bash
make start
```
1. See the containers and try log into the laravel container
```bash
docker ps
make ssh #to enter the container shell
make ssh-mysql #to enter the mysql container, where you may access the database shell using `mysql -uroot -p` or use the laravel user
```

1. Install composer dependencies
```bash
make composer-install
```

1. Migrate the database. It will be stored as a local volume called odb_odbmysqldata
```bash
make migrate
```

1. If worked, then Opendatabio will be available in your browser [](http::/localhost:8080).
1. Login with superuser `admin@example.org` and password `password1`
1. Additional configurations in these files are required for a production environment and deployment;

### Data persistence

The docker images may be deleted without loosing any data.
The mysql tables are stored in a volume. You may change to a local path bind.
```bash
docker volume list
```

<a name="withoutdocker"></a>
***
## Installing without Docker

If **not** using [Docker](https://www.docker.com/) and [Docker-compose](https://docs.docker.com/compose/install/), follow below for an [apache](https://httpd.apache.org)-based installation.

### Server requirements

1. The minimum supported PHP version is 7.4, required by the Media Laravel package included;
1. The web server may be [apache](https://httpd.apache.org) or [nginx](https://www.nginx.com). This instructions are based on apache only. For nginx, check configuration in the docker files.
1. It requires a SQL database, either [MySQL](https://www.mysql.com/) or [MariaDB](https://mariadb.org/)). Tested on MYSQL.v8 and MariaDB.v15.1. It may work with Postgres, but we have not tested.
1. PHP extensions required 'openssl', 'pdo', 'pdo_mysql', 'mbstring', 'tokenizer', 'xlm', 'dom', 'gd', 'exif'
1. [Pandoc](https://pandoc.org/) is used to translate LaTeX code used in the bibliographic references. It is not necessary for the installation, but it is suggested for a better user experience.
1. Requires [Supervisor](http://supervisord.org/), which is needed for background jobs (run queue:workers)

### Prep the Server

First, install the prerequisite software: Apache, MySQL, PHP, Pandoc and Supervisor. On a Debian system, you need to install some PHP extensions as well and enable them:

```bash
apt-get install apache2 mysql-server php7.4 libapache2-mod-php7.4 php7.4-mysql \
		php7.4-cli pandoc php7.4-mbstring php7.4-xml php7.4-gd \
		supervisor
a2enmod php7.4
phpenmod mbstring
phpenmod xml
phpenmod dom
phpenmod gd
#To check if they are installed:
php -m | grep -E 'mbstring|cli|xml|gd|mysql|pandoc|supervisord'
```

Enable the Apache modules 'mod_rewrite' and 'mod_alias'. Add the following to your Apache configuration.

* Change `/home/odbserver/opendatabio` to your path (the files must be accessible by apache)
* You may create a new file in the sites-available folder: `/etc/apache2/sites-available/opendatabio.conf` and place the following code in it.


```bash
<IfModule alias_module>
        Alias /      /home/odbserver/opendatabio/public
        Alias /fonts /home/odbserver/opendatabio/public/fonts
        Alias /images /home/odbserver/opendatabio/public/images
        <Directory "/home/odbserver/opendatabio/public">
                Require all granted
                AllowOverride All
        </Directory>
</IfModule>
```

This will cause Apache to redirect all requests for `/` to the correct folder, and also allow the provided `.htaccess` file to handle the rewrite rules, so that the URLs will be pretty. If you would like to access the file when pointing the browser to the server root, add the following directive as well:

```bash
RedirectMatch ^/$ /
```

Configure your **php.ini** file. The installer may complain about missing PHP extensions, so remember to activate them in both the **cli** and the web **ini** files for PHP! Update the values for the following variables:

```bash
Find files:
php -i | grep 'Configuration File'

Change in them:
	memory_limit should be at least 512M
	post_max_size should be at least 30M
	upload_max_filesize should be at least 30M

```
Something like:

```bash
[PHP]
allow_url_fopen=1
memory_limit = 512M

post_max_size = 100M
upload_max_filesize = 100M

```

Remember to restart the Apache server after editing the files. In Ubuntu would be:

```bash
sudo systemctl restart apache2.service
```


### Mysql Charset and Collation

1. If using MariaDB or Mysql you should add the following to your configuration file (mariadb.cnf or my.cnf), i.e. the Charset and Collation you choose for your installation must match that in the 'config/database.php'

```bash
[mysqld]
character-set-client-handshake = FALSE  #without this, there is no effect of the init_connect
collation-server      = utf8mb4_unicode_ci
init-connect          = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
character-set-server  = utf8mb4
log-bin-trust-function-creators = 1 #this is required in a docker installation
sort_buffer_size = 4294967295  #this is needed for geometry (bug in mysql:8)


[mariadb]
max_allowed_packet=100M
innodb_log_file_size=300M  #no use for mysql

```
2. If using MariaDB and you still have problems of type **#1267 Illegal mix of collations**, then [check here](https://github.com/phpmyadmin/phpmyadmin/issues/15463) on how to fix that,


### Create Dedicated User
The recommended way to install OpenDataBio is using a **dedicated system user**. In this instructions this user is  **odbserver**.


### Download OpenDataBio

Login using or Dedicated User and download or clone this software to where you want to install it. Here we assume this is `/home/odbserver/opendatabio`, so that the installation files will reside in this directory.

### Configure supervisord
Configure Supervisor, which is required for jobs. Create a file name **opendatabio-worker.conf** in the Supervisor configuration folder `/etc/supervisor/conf.d/opendatabio-worker.conf` with the following content:

```bash
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

### Install OpenDataBio

The installation script will download the [Composer](https://getcomposer.org/) dependency manager and all required PHP libraries installed in the `composer.json` file. However, if your server is behind a proxy, you should install and configure Composer independently. We have implemented PROXY configuration, but we are not using it anymore and have not tested properly (if you require adjustments, place an issue on Github).

Move to your `/home/odbserver/opendatabio` folder and run the installer, which will prompt your for various configurations options (see below):

```bash
cd /home/odbserver/opendatabio
php install
```

If the install script finishes with success, you're good to go! Point your browser to http://localhost/. The database migrations include an administrator account, with login `admin@example.org` and password `password1`. Change the password after installing.


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
php artisan migrate:fresh
```

### Post-install configs
* If your import/export jobs are not being processed, make sure Supervisor is running `systemctl start supervisord && systemctl enable supervisord`, and check the log files at `storage/logs/supervisor.log`.
* You can change several configuration variables for the application. The most important of those are probably set
by the installer, and include database configuration and proxy settings, but many more exist in the `.env` and
`config/app.php` files. In particular, you may want to change the language, timezone and e-mail settings. Run `php artisan config:cache` after updating the config files.
* In order to stop search engine crawlers from indexing your database, add the following to your "robots.txt" in your server root folder (in Debian, /var/www/html):
```bash
User-agent: *
Disallow: /
```

### Storage & Backups

You may change storage configurations in `config/filesystem.php`, where you may define cloud based storage, which may be needed if have many users submitting media files, requiring lots of drive space.

1. **Data downloads** are queue as jobs and a file is written in a temporary folder, and the file is deleted when the job is deleted by the user. This folder is defined as the `download disk` in filesystem.php config file, which point to `storage/app/public/downloads`. UserJobs web interface difficult navegation will force users to delete old jobs, but a cron cleaning job may be advisable to implement in your installation;
2. **Media files** are by default stored in the `media disk`, which place files in folder `storage/app/public/media`;
3. Remember to include media folder in a backup plan;

### Folder permissions

* Folders `storage` and `bootstrap/cache` must be writable by the Server user (usually www-data). See [this link](https://linuxhint.com/how-to-set-up-file-permissions-for-laravel/) for an example of how to do that. Set `0775` permission to these directories.
