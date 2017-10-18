<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

class Installer
{
    // Some properties that hold commands that can vary from one installation to another
    protected $apachecmd = null;
    protected $composercmd = null;
    public $dotenv = null; // will hold the environment variables

    // What are the required versions from prerequisite software
    protected function versions()
    {
        return [
            ['name' => 'PHP', 'command' => 'php -v', 'min' => '7.0'],
            ['name' => 'Apache Web Server', 'command' => $this->apachecmd.' -v', 'min' => '2.2', 'recommended' => '2.4'],
            ['name' => 'Pandoc', 'command' => 'pandoc --version', 'min' => '1.10'],
            ['name' => 'ImageMagick', 'command' => 'convert --version', 'min' => '6.7.7', 'recommended' => '6.8.9', 'unsupported' => '7'],
            ['name' => 'Supervisor', 'command' => 'supervisord --version', 'min' => '3.0', 'recommended' => '3.3'],
            // notice MySQL version is set up below
        ];
    }

    // initializer
    public function __construct()
    {
        echo $this->c('Starting OpenDataBio installation! :)', 'success');
        $this->getApacheCmd();
    }

    // checks the availability of the required PHP extensions and configuration options
    public function checkPhpConfig()
    {
        $trouble = 0;
        // checks if the php ini used has "cli" in its path
        exec('php --ini | grep Loaded | cut -c 30-', $result, $status);
        if (empty(trim($result[0]))) {
            $trouble = 1;
            echo $this->c('No php.ini file loaded! Check you PHP configuration...', 'warning');
        } elseif (preg_match('/cli/', $result[0])) {
            $trouble = 1;
            echo $this->c('The loaded php.ini seems to be the CLI version. Check install instructions...', 'warning');
        }

        $extensions = [
            ['name' => 'OpenSSL', 'string' => 'openssl'],
            ['name' => 'PDO', 'string' => 'pdo'],
            ['name' => 'PDO MySQL', 'string' => 'pdo_mysql'],
            ['name' => 'MBstring', 'string' => 'mbstring'],
            ['name' => 'Tokenizer', 'string' => 'tokenizer'],
            ['name' => 'XML', 'string' => 'xml'],
            ['name' => 'DOM', 'string' => 'dom'],
        ];
        foreach ($extensions as $extension) {
            $trouble += $this->checkExtension($extension['name'], $extension['string']);
        }
        $configs = [
            ['name' => 'allow_url_fopen', 'min' => true],
            ['name' => 'memory_limit', 'min' => '256M'],
            ['name' => 'post_max_size', 'min' => '30M'],
            ['name' => 'upload_max_filesize', 'min' => '30M'],
        ];
        foreach ($configs as $config) {
            $trouble += $this->checkPhpIni($config['name'], $config['min']);
        }
        if ($trouble > 0) {
            $this->checkContinue('no');
        }
    }

    public function checkApacheConfig()
    {
        $trouble = 0;
        exec($this->apachecmd.' -M', $result, $status);
        $rewrite = false;
        $userdir = false;
        foreach ($result as $str) {
            if (preg_match('/rewrite/i', $str, $m)) {
                $rewrite = true;
            }
            if (preg_match('/alias/i', $str, $m)) {
                $alias = true;
            }
        }
        if (!$alias) {
            $trouble = 1;
            echo $this->c("The 'mod_alias' Apache module is recommended, but it's not loaded...", 'warning');
        }
        if (!$rewrite) {
            $trouble = 1;
            echo $this->c("The 'mod_rewrite' Apache module is recommended, but it's not loaded...", 'warning');
        }
        if ($trouble > 0) {
            $this->checkContinue('no');
        }
    }

    public function checkAllVersions()
    {
        echo $this->c('Checking versions...', 'success');
        $trouble = 0;
        foreach ($this->versions() as $item) {
            $trouble += $this->checkVersion(
                $item['name'],
                $item['command'],
                $item['min'],
                isset($item['recommended']) ? $item['recommended'] : null,
                isset($item['unsupported']) ? $item['unsupported'] : null
            );
        }
        $trouble = $trouble + $this->checkMysqlVersion('MySQL database', 'mysql --version', '5.7', '5.7.6', '10.1.2', '10.1.23');
        if (0 == $trouble) {
            echo $this->c('All versions compatible!', 'success');
        } else {
            $this->checkContinue('no');
        }
    }

    // Checks the version for a given command, and compares it against some standards
    protected function checkVersion($name, $command, $min, $recommended, $unsupported = null)
    {
        exec($command.' 2>&1', $result, $status);
        if (0 != $status) {
            echo $this->c($name.' does not seem to be installed', 'danger');

            return 1;
        }
        preg_match("/([\d\.-]+)/", $result[0], $version);
        if (0 == sizeof($version)) {
            echo $this->c('Error reading the version of '.$name, 'warning');

            return 1;
        }
        if (version_compare($version[0], $min, '<')) {
            echo $this->c($name.' version is not compatible! Please upgrade!', 'danger');

            return 1;
        }
        if (version_compare($version[0], $recommended, '<')) {
            echo $this->c($name.' version is below recommended...', 'warning');

            return 1;
        }
        if ($unsupported and version_compare($version[0], $unsupported, '>=')) {
            echo $this->c($name.' version is currently unsupported! Downgrade if able...', 'danger');

            return 1;
        }

        return 0;
    }

    protected function checkMysqlVersion($name, $command, $mysqlmin, $mysqlrecommended, $mariadbmin, $mariadbrecommended)
    {
        // some tweaking, as MySQL may be MariaDB
        exec($command.' 2>&1', $result, $status);
        if (0 != $status) {
            echo $this->c($name.' does not seem to be installed', 'danger');

            return 1;
        }
        preg_match("/([\d\.-]+).*?([\d\.-]+)/", $result[0], $version);
        preg_match('/MariaDB/', $result[0], $ismariadb);
        if (0 == sizeof($version)) {
            echo $this->c('Error reading the version of '.$name, 'warning');

            return 1;
        }
        if (0 == sizeof($version)) { // we have MySQL
            if (version_compare($version[0], $mysqlmin, '<')) {
                echo $this->c($name.' version is not compatible! Please upgrade!', 'danger');

                return 1;
            }
            if (version_compare($version[0], $mysqlrecommended, '<')) {
                echo $this->c($name.' version is below recommended...', 'warning');

                return 1;
            }
        } else { // we have MariaDB; relevant version is the SECOND string
            if (version_compare($version[2], $mariadbmin, '<')) {
                echo $this->c($name.' version is not compatible! Please upgrade!', 'danger');

                return 1;
            }
            if (version_compare($version[2], $mariadbrecommended, '<')) {
                echo $this->c($name.' version is below recommended...', 'warning');

                return 1;
            }
        }
    }

    // Colorizes output
    protected function c($text, $status)
    {
        // Colorize the output
        switch ($status) {
        case 'danger':
            return "\033[31m".$text."\033[0m\n";
        case 'success':
            return "\033[32m".$text."\033[0m\n";
        case 'warning':
            return "\033[33m".$text."\033[0m\n";
        default:
            return $text."\n";
        }
    }

    // Tries to guess the command to interact with Apache web server.
    protected function getApacheCmd()
    {
        // Determining cmdline for apache2ctl / httpd
        exec('which httpd 2>&1', $result, $status);
        if (0 == $status) {
            // ArchLinux, CentOS, Fedora, Red Hat use:
            $apachecmd = 'httpd';
        } else {
            // is there an user-accessible apachectl?
            exec('which apachectl 2>&1', $result, $status);
            if (0 == $status) {
                $apachecmd = 'apachectl';
            } else { // try a /usr/sbin installation
                $apachecmd = '/usr/sbin/apachectl';
            }
        }
        $this->apachecmd = $apachecmd;
    }

    // asks the user if he wants to continue the install process. Usually called after errors or severe warnings
    public function checkContinue($default = 'no')
    {
        if ('no' === $default) {
            echo 'Continue? yes/[no] ';
            $line = trim(fgets(STDIN));
            if ('yes' != $line and 'y' != $line) {
                exit($this->c('Exiting...', 'danger'));
            }
        } else { //defaults to yes
            echo 'Continue? [yes]/no ';
            $line = trim(fgets(STDIN));
            if ('no' == $line or 'n' == $line) {
                exit($this->c('Exiting...', 'danger'));
            }
        }
    }

    // checks if the current user is privileged and if the current user owns the install file
    public function checkCurrentUser()
    {
        $uid = posix_geteuid();
        if ($uid < 1000) {
            echo $this->c('The current user is: '.posix_getpwuid($uid)['name'], 'danger');
            echo $this->c('This user seems to be a system or a privileged user!', 'danger');
            echo $this->c('The installation script should be executed as a non-privileged user!', 'danger');
            $this->checkContinue('no');
        }
        if ($uid != getmyuid()) {
            echo $this->c('The current user is: '.posix_getpwuid($uid)['name'], 'danger');
            echo $this->c('The owner of the installation script is: '.posix_getpwuid(getmyuid())['name'], 'danger');
            echo $this->c('The installation script should be executed by the same user that own the files!', 'danger');
            $this->checkContinue('no');
        }
    }

    protected function checkExtension($name, $string)
    {
        if (extension_loaded($string)) {
            return 0;
        }
        echo $this->c("PHP extension $name is required, but is not loaded. Check your phpinfo()", 'danger');

        return 1;
    }

    protected function checkPhpIni($name, $min)
    {
        if (ini_get($name) and $this->convertToBytes(ini_get($name)) >= $this->convertToBytes($min)) {
            return 0;
        }
        echo $this->c("The PHP initialization directive $name should be set to $min!", 'danger');

        return 1;
    }

    //# From https://stackoverflow.com/questions/11807115 adapted with
    // http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes
    protected function convertToBytes($from)
    {
        $number = substr($from, 0, -1);
        switch (strtoupper(substr($from, -1))) {
        case 'K':
            return $number * 1024;
        case 'M':
            return $number * pow(1024, 2);
        case 'G':
            return $number * pow(1024, 3);
        case 'T':
            return $number * pow(1024, 4);
        default:
            return $from;
        }
    }

    // determines the right command to use to invoke composer
    protected function getComposerCmd()
    {
        // Do we have a system-wide composer, system-wise composer.phar or local composer.phar?
        exec('composer 2>&1', $result, $status);
        if (0 == $status) {
            $this->composercmd = 'composer';
            echo 'Using system-wide composer...';
        } else {
            exec('composer.phar 2>&1', $result, $status);
            if (0 == $status) {
                $this->composercmd = 'composer.phar';
                echo 'Using system-wide composer.phar...';
            } else {
                if (!file_exists('composer.phar')) {
                    $this->installComposer();
                }
                $this->composercmd = 'php composer.phar';
                echo 'Using local composer.phar...';
            }
        }
    }

    // Tries to programatically install composer
    protected function installComposer()
    {
        echo $this->c('Attempting to download and install Composer... this may take a while...', 'success');
        $sig = file_get_contents('https://composer.github.io/installer.sig');
        if (false === $sig) {
            exit($this->c('Error downloading Composer! Try installing Composer manually...', 'danger'));
        }
        $ret = copy('https://getcomposer.org/installer', 'composer-setup.php');
        if (false === $ret) {
            exit($this->c('Error downloading Composer! Try installing Composer manually...', 'danger'));
        }
        if (trim($sig) != hash_file('SHA384', 'composer-setup.php')) {
            unlink('composer-setup.php');
            exit($this->c('Error downloading Composer installer! Try installing Composer manually...', 'danger'));
        }
        exec('php composer-setup.php --quiet', $result, $status);
        unlink('composer-setup.php');
        if (0 != $status) {
            foreach ($result as $line) {
                echo $line."\n";
            }
            exit($this->c('Error installing Composer! Try installing Composer manually...', 'danger'));
        }
        echo $this->c('Composer successfully installed!', 'success');
    }

    // Performs a composer install when necessary, switching "--no-dev" when appropriate
    public function composerInstall($first_pass = true)
    {
        if ($first_pass and file_exists('./vendor/autoload.php')) { // first time install done, nothing to do here
            return;
        }
        // makes sure we have composer installed and the composercmd variable set
        if (!$this->composercmd) {
            $this->getComposerCmd();
        }

        if (empty(getenv('APP_ENV')) or 'production' == getenv('APP_ENV')) {
            exec($this->composercmd.' install --no-dev', $result, $status);
        } else {
            exec($this->composercmd.' install', $result, $status);
        }
        if (0 == $status) {
            echo $this->c('PHP dependencies installed dependencies successfully!', 'success');
        } else {
            foreach ($result as $line) {
                echo $line."\n";
            }
            exit($this->c("Running 'composer install' failed!", 'danger'));
        }
    }

    public function clearCaches()
    {
        echo $this->c('Clearing caches...', 'success');
        system('php artisan config:clear');
        system('php artisan route:clear');
        system('php artisan view:clear');
        system('php artisan cache:clear');
        system("$this->composercmd dump-autoload");
    }

    // Writes a single entry in dotenv
    protected function writeDotEnv($key, $value)
    { // edited from Laravel Framework @ KeyGenerateCommand.php
        file_put_contents('.env', preg_replace(
            "/^{$key}=.+/m",
            "{$key}=".$value,
            file_get_contents('.env'),
            -1,
            $counter
        ));
        if ($counter > 0) {
            return;
        } // all is well
        file_put_contents('.env',
            file_get_contents('.env')."\n".
            '### ADDED BY install SCRIPT '.date('Y-m-d')."\n".
            $key.'='.$value."\n"
        );
    }

    public function getOrCreateEnv()
    {
        // Process DotEnv file
        if (file_exists('.env')) {
            echo $this->c('Using .env environment file', 'success');
        } else {
            echo $this->c('Creating new .env environment file', 'success');
            $ret = copy('.env.example', '.env');
            if (false === $ret) {
                exit($this->c('Error creating .env file! Check that you have write permission on current directory!', 'danger'));
            }
            system('php artisan key:generate');
        }
    }

    public function testProxy()
    {
        if ('' != getenv('PROXY_URL')) {
            $proxystring = '';
            if ('' != getenv('PROXY_USER')) {
                $proxystring = getenv('PROXY_USER').':'.getenv('PROXY_PASSWORD').'@';
            }
            $proxystring .= getenv('PROXY_URL').':'.getenv('PROXY_PORT');

            echo 'Testing proxy settings...';
            $client = new \GuzzleHttp\Client(['base_uri' => 'http://www.example.com', 'proxy' => $proxystring]);
            try {
                $response = $client->request('GET', '');
                if (200 == $response->getStatusCode()) {
                    echo $this->c('Proxy configuration successfull!', 'success');
                } else {
                    echo $this->c('Unable to connect to external providers using the provided proxy (Wrong status code)!', 'danger');
                }
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                echo $this->c('Unable to connect to external providers using the provided proxy (ConnectException)!', 'danger');
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                echo $this->c('Unable to connect to external providers using the provided proxy (ClientException)!', 'danger');
            }
        }
    }

    public function checkSupervisor()
    {
        // Tests if there is a opendatabio-worker.ini file in common places
        if ((!file_exists('/etc/supervisor/conf.d/opendatabio-worker.conf')) and
            (!file_exists('/etc/supervisor.d/opendatabio-worker.ini'))) {
            echo $this->c('Could not find supervisor worker file!', 'warning');
        }
        // Test is supervisor is running
        exec('ps ahxwwo command', $result);
        $running = false;
        foreach ($result as $line) {
            if (strpos($line, 'supervisord')) {
                $running = true;
            }
        }
        if (!$running) {
            echo $this->c('Supervisor daemon does not seem to be running!', 'warning');
        }
        echo 'Do you wish to review the sample Supervisor worker file? yes/[no] ';
        $line = trim(fgets(STDIN));
        if ('y' == $line or 'yes' == $line) {
            echo "You should store the following lines in a file called
                /etc/supervisor/conf.d/opendatabio-worker.conf (Debian/Ubuntu) or 
                /etc/supervisor.d/opendatabio-worker.ini (ArchLinux)\n(You will need root access for that)\n\n";
            echo ";--------------\n";
            echo"[program:opendatabio-worker]\n";
            echo "process_name=%(program_name)s_%(process_num)02d\n";
            echo 'command=php '.__DIR__."/artisan queue:work --sleep=3 --tries=1 --timeout=0 --daemon\n";
            echo "autostart=true\n";
            echo "autorestart=true\n";
            echo 'user='.posix_getpwuid(getmyuid())['name']."\n";
            echo "numprocs=8\n";
            echo "redirect_stderr=true\n";
            echo 'stdout_logfile='.__DIR__."/storage/logs/supervisor.log\n";
            echo ";--------------\n\n";
        }
    }

    public function processEnvFile()
    {
        echo $this->c('Setting the environment variables... for more options, edit the .env file later!', 'success');
        echo 'Do you wish to review the configuration for your app? yes/[no] ';
        $line = trim(fgets(STDIN));
        if ('y' != $line and 'yes' != $line) {
            return;
        }

        $prod = getenv('APP_ENV');
        if ('production' == $prod) {
            echo 'Is this installation for development or production? [production]/development ';
        } else {
            echo 'Is this installation for development or production? production/[development] ';
        }
        $line = trim(fgets(STDIN));
        if ('p' == substr($line, 0, 1)) {
            $prod = 'production';
        }
        if ('d' == substr($line, 0, 1)) {
            $prod = 'development';
        }
        if ('production' == $prod) {
            echo "Using production settings!\n";
            $this->writeDotEnv('APP_ENV', 'production');
            $this->writeDotEnv('APP_DEBUG', 'false');
            $this->writeDotEnv('QUEUE_DRIVER', 'database');
        } else {
            echo "Using development settings!\n";
            $this->writeDotEnv('APP_ENV', 'local');
            $this->writeDotEnv('APP_DEBUG', 'true');
            $this->writeDotEnv('QUEUE_DRIVER', 'sync');
        }
        $envfields = [
            'APP_URL' => 'What should the base URL for your app be?',
            'DB_USERNAME' => 'Database username?',
            'DB_PASSWORD' => 'Database password?',
            'DB_DATABASE' => 'Database name?',
            'DB_HOST' => 'Database hostname?',
            'PROXY_URL' => 'Proxy host? (Leave blank for no proxy)',
            'PROXY_PORT' => 'Proxy port?',
            'PROXY_USER' => 'Proxy username? (Leave blank if not required)',
            'PROXY_PASSWD' => 'Proxy password?',
            'GMAPS_API_KEY' => 'Google Maps API key?',
            'MOBOT_API_KEY' => 'Tropicos.org API key?',
        ];
        foreach ($envfields as $key => $message) {
            echo $message.' ['.getenv($key).'] ';
            $line = trim(fgets(STDIN));
            if ('' != $line) {
                $this->writeDotEnv($key, $line);
            }
        }
        // Reloads the environment file
        $this->dotenv->overload();
    }

    protected function runDbUser($query)
    {
        exec('mysql -u'.getenv('DB_USERNAME').
            ' -h'.getenv('DB_HOST').
            ' -p'.getenv('DB_PASSWORD').
            ' '.getenv('DB_DATABASE')."<<EOF\n$query\nEOF", $result, $status);
        if (0 != $status) {
            return false;
        }

        return implode($result, "\t");
    }

    protected function runDbRoot($query)
    {
        exec('mysql -uroot -p '.
            ' -h'.getenv('DB_HOST').
            " <<EOF\n$query\nEOF", $result, $status);
        if (0 != $status) {
            return false;
        }

        return implode($result, "\t");
    }

    public function checkMySQLConnection()
    {
        $createcmd = '
        CREATE DATABASE \`'.getenv('DB_DATABASE').'\`;
        CREATE USER \`'.getenv('DB_USERNAME').'\`@\`localhost\` IDENTIFIED BY \''.getenv('DB_PASSWORD').'\';
        GRANT ALL ON \`'.getenv('DB_DATABASE').'\`.* TO \`'.getenv('DB_USERNAME').'\`@\`localhost\`;';
        echo "Testing database connection...\n";
        if (false !== $this->runDbUser('SELECT 1;')) {
            echo $this->c('Database connection successfull!', 'success');

            return;
        }
        echo $this->c('Database connection failed!', 'warning');
        echo $this->c('Do you want to create the database user and schema? [yes]/no ', 'warning');
        $line = trim(fgets(STDIN));
        if ('' != $line and 'y' != $line and 'yes' != $line) {
            exit($this->c("Unable to complete installation. Please create the database and database user. Suggested commands:\n$createcmd", 'danger'));
        } else {
            echo $this->c("You will be prompted now for the MySQL root password:\n", 'warning');
            $this->runDbRoot($createcmd);
            if (false === $this->runDbUser('SELECT 1;')) {
                exit($this->c("Unable to create database! Try to create the user and database manually... Suggested commands:\n$createcmd", 'danger'));
            }
        }
        echo $this->c('Database connection successfull!', 'success');
    }

    public function checkMySQLConfig()
    {
        $trouble = 0;
        $mycnf = $this->runDbUser("
        SHOW VARIABLES LIKE 'log_bin';
        SHOW VARIABLES LIKE 'log_bin_trust_function_creators';
        SHOW VARIABLES LIKE 'max_allowed_packet';
        SHOW VARIABLES LIKE 'innodb_log_file_size';
        ");
        $logbin = false;
        if (preg_match('/log_bin\s+(\w*)/', $mycnf, $matches) and 'ON' == $matches[1]) {
            $logbin = true;
        }
        if ($logbin and !(preg_match('/log_bin_trust_function_creators\s+(\w*)/', $mycnf, $matches) and 'ON' == $matches[1])) {
            echo $this->c('Your current MySQL settings have binary logging activated, but not log_bin_trust_function_creators.', 'danger');
            echo $this->c('OpenDataBio needs the parameter log_bin_trust_function_creators set to 1 in your my.cnf file.', 'danger');
            echo $this->c('Remember to restart mysql afterwards', 'danger');
            exit();
        }
        if (!preg_match('/max_allowed_packet\s+(\d*)/', $mycnf, $matches) or $matches[1] < 100 * 1024 * 1024) {
            $trouble = 1;
            echo $this->c('Your MySQL settings have max_allowed_packet lower than the recommended 100M. This can break large data imports!', 'warning');
        }
        if (!preg_match('/innodb_log_file_size\s+(\d*)/', $mycnf, $matches) or $matches[1] < 300 * 1024 * 1024) {
            $trouble = 1;
            echo $this->c('Your MySQL settings have innodb_log_file_size lower than the recommended 300M. This can break large data imports!', 'warning');
        }
        if (0 == $trouble) {
            echo $this->c('All database configurations compatible!', 'success');
        }
    }

    public function migrate()
    {
        echo $this->c('Running database migrations... This may take a while...', 'success');
        system('php artisan migrate', $status);
        if (0 != $status) {
            exit(c("running 'php artisan migrate' failed!\n", 'danger'));
        }

        echo 'Do you wish to seed the database with randomly generated test data? yes/[no] ';
        $line = trim(fgets(STDIN));
        if ('y' == $line or 'yes' == $line) {
            system('php artisan db:seed', $status);
            if (0 != $status) {
                exit(c("running 'php artisan db:seed' failed!\n", 'danger'));
            }
        }
    }

    public function postMigrate()
    {
        if ('production' == getenv('APP_ENV')) {
            echo $this->c('Running code optimizations for production environment...', 'success');
            system('php artisan config:cache');
            system('php artisan route:cache');
        }

        echo "Changing storage area permissions...\n";
        exec('chmod -fR 777 storage 2>&1');
        exec('chmod -fR 777 bootstrap/cache 2>&1');

        echo $this->c("********************************************\n", 'success');
        echo $this->c("OpenDataBio has been successfully installed!\n", 'success');
    }
}
