### Instructions for a Mac-OSX localhost installation

Tested on High Sierra 10.13.3.

#### Necessary software 
Commands from [these instructions](https://getgrav.org/blog/macos-sierra-apache-multiple-php-versions) to prep your machine:
- Install xcode using software update, open and accept license
```
sudo xcodebuild -license accept
```
- Install xcode command line
```
xcode-select --install
```
- Install [Home Brew] (https://brew.sh/index_pt-br.html)
```
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
#if under proxy (you may try)
/usr/bin/ruby -e "$(curl -x username:userpassword@proxy.mydomain.com:port -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
```
- See message at the end of installation regarding permission and run it again if that is the case. DON'T USE `sudo` FOR BREW COMMANDS. Brew does not work under sudo.
```
ls -l /usr/ | grep local
#make sure owner and group is your username:admin
sudo chown -R $(whoami):admin  /usr/local/homebrew
```
- Check installation of homebrew
```
#check if running
brew -v
brew config
brew doctor
brew prune
#follow instructions if needed
```
- Install homebrew apache
```
#stop and unlink system installation (version that comes with OSX)
sudo apachectl stop
sudo launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist 2>/dev/null

#case you already have a home brew version, then remove old versions before
brew list | grep php
brew remove --force --ignore-dependencies httpd
brew remove --force --ignore-dependencies php70-xdebug php71-xdebug
brew remove --force --ignore-dependencies php70-imagick php71-imagick
brew remove --ignore-dependencies --force php70 php71 php72

#install apache (which will run now as your user) and php 
brew install httpd

#Apache is now running under your user.
#If you need it to bind to port 80 (root access and default to localhost), then find and update `Listen` directive. 
#You will also have to use `sudo` in front of apachectl start and brew servcies below. (Homebrew recommend you now use a different port and run under your user)

#see running services
brew services list
brew services stop --all
brew services start --all
brew services list

#services working will have a "started" in green (else check permissions of folders under /usr/local
#should be user:admin

#check version installed
httpd -v
```
- Edit apache httpd.conf file, which is now the new homebrew version
```
nano /usr/local/etc/httpd/httpd.conf
```
- and change the following lines:
```
Listen 8080 #this the option brew will have changed for you (if it is not a port different from 80, then it may fail as apache now is running under youruser and group)

#change this according to the user and group that has permissions
User yourusername
Group admin

ServerName localhost

DocumentRoot "/Users/yourusername/Sites"
<Directory "/Users/yourusername/Sites" >
    # AllowOverride controls what directives may be placed in .htaccess files.
    # It can be "All", "None", or any combination of the keywords:
    #   AllowOverride FileInfo AuthConfig Limit
    #
    AllowOverride All
</Directory>

#and uncomment the following lines
LoadModule alias_module lib/httpd/modules/mod_alias.so
LoadModule rewrite_module lib/httpd/modules/mod_rewrite.so
```
- Restart the service 
```
brew services stop --all
brew services start --all
brew services list
```
- Make sure you have the folder /Users/yourusername/Sites or create it
```
mkdir ~/Sites
#add a simple html file to this 
echo "<h1>My User Web Root</h1>" > ~/Sites/index.html
```
- Check if working: [localhost](http://localhost:8080)
- Install PHP
```
brew update
brew install php72 --with-httpd

#check to see if php is running
php -v
```
- The installation will indicate to you with a message of changes you need to make in the apache httpd.conf:
```
#To enable PHP in Apache add the following to httpd.conf and restart Apache:

LoadModule php7_module /usr/local/opt/php/lib/httpd/modules/libphp7.so

<FilesMatch .php$>
	SetHandler application/x-httpd-php
</FilesMatch>

#Finally, check DirectoryIndex includes index.php

<IfModule dir_module>
    DirectoryIndex index.php index.html 
</IfModule>

#The php.ini and php-fpm.ini file can be found in:
    /usr/local/etc/php/7.2/
```
- Test if PHP is running
```
#create file in the sites folder:
echo "<?php phpinfo();" > ~/Sites/index.php
```
- Check if working with phpinfo: [localhost](http://localhost:8080)
- Modify  php.ini  (/usr/local/etc/php/7.2/php.ini)
```
nano /usr/local/etc/php/7.2/php.ini
```
- Add the minimum OpenDataBio requirements:
```
memory_limit should be at least 300M
post_max_size should be at least 30M
upload_max_filesize should be at least 30M
```
- Php extensions
```
#check to see if you have the following extensions to php installed
php -m | grep -E "mbstring|cli|xml|gd|mysql"
#show which extensions are available
brew search php72
#install if needed
```
- Install [MYSQL mariadb](http://brewformulas.org/Mariadb) - may conflict with Oracle's mysql (have either one). Rerun if fails.
- Install [Pandoc](http://brewformulas.org/Pandoc)
- Install [Supervisor](http://brewformulas.org/supervisor)
```
brew install mariadb
brew install pandoc
brew install supervisor
```
-  Change settings for mysql required by opendatabio:
```
#check which config file mariadb is using
mysqld --help --verbose | grep my.cnf

#in this file an include option indicates where you may add your config changes (no default explicit in this file)
cat /usr/local/etc/my.cnf

#create a file in the specified folder and add:
nano /usr/local/etc/my.cnf.d/mariadb.cnf

[mariadb]
max_allowed_packet=100M
innodb_log_file_size=300M

```
- Install [Imagemagick](http://brewformulas.org/Imagemagick)
```
brew install imagemagick
```
- Stop and restart services (started must be green if everything is working correctly)
```
brew services stop --all
brew services start --all
brew services list
```
#### OpenDataBio
- Pull or download latest version of [opendatabio](https://github.com/opendatabio/opendatabio) into your webroot  `/Users/YOURUSERNAME/Sites/opendatabio`
- Edit `/usr/local/etc/httpd/httpd.conf` add (adjust according to your installation path)
```
<IfModule alias_module>
        Alias /opendatabio /Users/YOURUSERNAME/Sites/opendatabio/public
        Alias /fonts /Users/YOURUSERNAME/Sites/opendatabio/public/fonts
        Alias /images /Users/YOURUSERNAME/Sites/opendatabio/public/images
        <Directory "/Users/YOURUSERNAME/Sites/opendatabio/public">
                Require all granted
                AllowOverride All
        </Directory>
</IfModule>
```
- Restart apache and install. Check all configuration steps carefully, specially supervisor configuration, see below. If you do not have mysql user, it will create one for you.

```
brew services restart httpd
cd /Users/YOURUSERNAME/Sites/opendatabio
php install
```
- If you get the message ''This user seems to be a system or a privileged user! The installation script should be executed as a non-privileged user!''. Ignore and continue, as this is not an issue in your localhost installation.
- You will also get a message saying the ''supervisor worker file was not found. Supervisor is required for submitting Jobs and importing data. So, you need to config this properly. If the service is working (''brew services list''), then simply ignore this message (config for linux), but check the supervisor configuration block informed during the process. Something like:
```
;--------------
[program:opendatabio-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /Users/YOURUSERNAME/opendatabio/artisan queue:work --sleep=3 --tries=1 --timeout=0 --daemon
autostart=true
autorestart=true
user=beto
numprocs=8
redirect_stderr=true
stdout_logfile=/Users/YOURUSERNAME/opendatabio/storage/logs/supervisor.log
;--------------
```
- Add this block to the a supervisor config file and restart supervisor service
```
#create folder as indicated at the end of file /usr/local/etc/supervisord.ini
mkdir usr/local/etc/supervisor.d
#add a opendatabio file and add the block
nano /usr/local/etc/supervisor.d/opendatabio-worker.ini
#restart all services
brew services stop --all
brew services start --all
```
 - Check if working [opendatabio](http://localhost:8080/opendatabio)
