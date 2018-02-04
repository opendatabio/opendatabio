### Instructions for a Mac-OSX localhost installation
Tested on Sierra 10.12.6.
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
- Check permissions before installing brew (and may need to fix yours)
```
ls -l /usr/ | grep local
#make sure owner and group is your username:staff
sudo chown -R $(whoami):staff  /usr/local/
```
- Install [Home Brew] (https://brew.sh/index_pt-br.html)
```
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
#if under proxy (you may try)
/usr/bin/ruby -e "$(curl -x username:userpassword@proxy.mydomain.com:port -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"

#check if running
brew -v
brew doctor
brew prune
#follow instructions if needed
```
- Install Apache
```
#stop and unlink system installation
sudo apachectl stop
sudo launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist 2>/dev/null
#install apache
brew install httpd
#start
brew services restart httpd
#check version installed
httpd -v
```
- Edit apache httpd.conf file
```
sudo nano /usr/local/etc/httpd/httpd.conf
```
- and change the following lines:
```
Listen 80

User yourusername
Group staff

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
- Restart the service to make changes one
```
sudo apachectl -k stop
sudo apachectl start
```
- Make sure you have the folder /Users/yourusername/Sites or create it
```
mkdir ~/Sites
#add a simple html file to this 
echo "<h1>My User Web Root</h1>" > ~/Sites/index.html
```
- Check if working: [localhost](http://localhost)
- Install PHP
```
brew tap homebrew/homebrew-php
brew update
brew install php72 --with-httpd

#check to see if php is running
php -v
```
- Edit apache httpd.conf file again and change the following lines:
```
#a line similar to this should have been added to the load block by brew (if not add manually)
LoadModule php7_module  /usr/local/Cellar/php72/7.2.1_12/libexec/apache2/libphp7.so

#add php file
<IfModule dir_module>
    DirectoryIndex index.php index.html 
</IfModule>

#add this block
<FilesMatch \.php$>
    SetHandler application/x-httpd-php
</FilesMatch>
```
- Stop and restart apache
```
sudo apachectl -k stop
sudo apachectl start
```
- Test if PHP is running
```
#create file in the sites folder:
echo "<?php phpinfo();" > ~/Sites/index.php
```
- Check if working with phpinfo: [localhost](http://localhost)
- Install [MYSQL mariadb] (http://brewformulas.org/Mariadb) - conflicts with oracle mysql (have either one)
- Install [PANDOC] (http://brewformulas.org/Pandoc)
- Install [Imagemagick] (http://brewformulas.org/Imagemagick)
- Install Supervisor
```
brew install mariadb
brew install pandoc
brew install imagemagick
brew install supervisor
```
- Modify  php.ini  (/usr/local/etc/php/7.2/php.ini)
```
sudo nano /usr/local/etc/php/7.2/php.ini
```
- Add the minimum OpenDataBio requirements:
```
memory_limit should be at least 256M!
post_max_size should be at least 30M!
upload_max_filesize should be at least 30M!
```
#### OpenDataBio
- Install opendatabio, carefully. Check all configuration steps:
```
/Users/YOURUSERNAME/opendatabio/php install
```
- In /usr/local/etc/httpd/httpd.conf add (adjust according to your installation path)
```
<IfModule alias_module>
        Alias /opendatabio /Users/yourusername/opendatabio/public
        Alias /fonts /Users/yourusername/opendatabio/public/fonts
        Alias /images /Users/yourusername/opendatabio/public/images
        <Directory "/Users/yourusername/opendatabio/public">
                Require all granted
                AllowOverride All
        </Directory>
</IfModule>
```
- Restart apache
- Check if working [opendatabio](http://localhost/opendatabio)
#### Supervisor configuration
- Supervisor is required for submitting Jobs and importing data. You may get a problem during the OpenDataBio installation saying the supervisor worker file was not found, try the following and then run ''php install'' step again:
     - the default configuration file o supervisor will be  ''/usr/local/etc/supervisord.ini''. Check the last line of this filed and where it points to 
     - create a symlink to where opendatabio expects to find this file (created the folder supervisor.d as needed):
     
            ln -s /usr/local/etc/supervisord.conf /etc/supervisor.d/supervisord.ini
            
     - create a opendatabio supervisord conf file:
     
            mkdir /usr/local/etc/supervisord.d  #if not present
            sudo nano /usr/local/etc/supervisord.d/opendatabio-worker.ini #will open an empty file
            
     - add the following content to this file (adjust accordingly)
     
            ;--------------
            [program:opendatabio-worker]
            process_name=%(program_name)s_%(process_num)02d
            command=php /Users/YOURUSERNAME/opendatabio/app/artisan queue:work --sleep=3 --tries=1 --timeout=0 --daemon
            autostart=true
            autorestart=true
            user=beto
            numprocs=8
            redirect_stderr=true
            stdout_logfile=/Users/YOURUSERNAME/opendatabio/app/storage/logs/supervisor.log
            ;--------------
            
     - Add symlink to where opendatabio will look for this file
     
            ln -s /usr/local/etc/supervisord.conf /etc/supervisor.d/supervisord.ini
            
     - You may need to create the log folder:
     
            mkdir /Users/YOURUSERNAME/opendatabio/app/storage
            mkidr /Users/YOURUSERNAME/opendatabio/app/storage/logs
            
