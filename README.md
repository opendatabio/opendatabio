# opendatabio
A modern system for storing and retrieving plant data - floristics, ecology and monitoring.

This project improves and reimplements code from the Duckewiki project - a tribute to Adolpho Ducke,
one of the greatest Amazon botanists, and Dokuwiki, an inspiring wiki platform.

## Overview
TODO: detail modules (data import, data export, forms, audit, query API, ???)

A companion R package is also being developed to improve the data import and export routines.

## Install
### Prerequisites and versions
Opendatabio is written in PHP and developed over the CodeIgniter framework. To facilitate version dependency 
and system administration, the installation package
includes a full CodeIgniter 3.1.4 install and a full Propel ORM 2.0.0-alpha7 install. 
Managing the CodeIgniter and Propel versions with Composer is also fully supported, but currently undocumented.
The minimum supported PHP version is 5.6, but PHP 7 is 
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

This software requires the following PHP extensions:
- intl (because of CodeIgniter)
- mysql (TODO: verify!)
- libxml2 (because of Propel)
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
it is probably /var/www). 

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
in case it is not localhost). 

And you're good to go! If you have moved your installation files to the /var/www/opendatabio folder, you will probably
be able to access it as http://localhost/opendatabio. The database seeds come with an administrator account, with
username admin and password @dm!n. Edit the file before importing, or change the password after installing.

## Upgrade
A tool for upgrading duckewiki databases to opendatabio is currently being developed.

## Programing directives
In order to simplify the development cycle and install procedure, CI, Propel and their dependencies are being commited
to this repository instead of being managed only by Composer. In order for this to work, it is imperative that only
tagged releases are specified in composer.json - otherwise, this will lead to problems with git mistaking the dependencies
for submodules.

All variables and functions should be named in English, with entities and fields related to the database being 
named in the singular form. All tables (where apropriate) should have an "id" column, and foreign keys should reference
the base table with "_id" suffix, except in cases of self-joins (such as "taxon.parent_id") or foreign keys that
reference a subclass of the Object class.

## License
Opendatabio is licensed for use under a GPLv3 license. The installation packages include the files for CodeIgniter and Propel,
which are licensed under an MIT license.

## TODO
TODO: Verify CodeIgniter / Propel integration, as:
https://github.com/bcit-ci/CodeIgniter/wiki/Using-Propel-as-Model
Data models
https://dev.mysql.com/doc/workbench/en/wb-getting-started-tutorial-creating-a-model.html

Incomplete dates may be given:
https://dev.mysql.com/doc/refman/5.7/en/date-and-time-functions.html

## Data model
### Focal objects

- Object:
  - Location
  - Taxon
  - Plant
  - Voucher
  - Sample
- Identification (rel Plant x Taxon)
- Trait (+ Category, Measurement)

All focal objects are of the base class "Object". This allows for a single foreign key to handle the different 
types of relations (such as: a trait can be associated with a location, a taxon, a plant, a voucher or a sample).

The general idea behind the taxon model is that is should present tools for easily incorporating valid taxonomic names,
with spell checking and added metadata, but allowing for the inclusion of names that are not considered valid 
(because they are still unpublished, are rare and thsus not found in online databases, or because the user
disagrees with the validity status of the databases).

In the taxon table, the column "level" represents the taxonomic level (such as order, genera, etc). 
It should be standardized. The column "parent_id" indicates the parent of this taxon, which may be several levels 
above it. Check: the parent level should be strictly higher. One special node is the "root" of the taxonomic tree.

The only exception is the "clade" level, which is not taxonomic. It may represent subspecies, 
infraspecies, categories, varieties, or morphospecies (which is selected in column "category_id"). 
The parent level checking must be done to 
ensure that the parent of each valid child of a clade has a strictly higher level.

In the case that an intermediate level is registered, the system should check and allow the user to register the relevant
children. (TODO: clarify)

The name of the taxon should be only the specific part of name (in case of species, the epithet). There should be a helper
to "assemble" the full name from the epithet and its parents name.

When introducing a new taxon, there should be options to check its validity, spelling, and authorship in
open databases (IPNI, ITIS, MOBOT, etc). It should be possible to enter an invalid name, or a name not checked in the bank,
but it should be registered in the "valid" column, and the "validreference" column should contain a reference to the validator
entity (link in IPNI, ITIS, etc?)

It is possible to include synonym data in the taxonomic table. To do so, one must fill in the "senior" relationship. If
the senior field is left blank, it is understood that this taxon is the senior synonym. Junior synonyms should not be
flagged as "valid".

The authorship of the taxon is usually given in the text field "author", when it is taken from the literature. In
case the taxon is a morphotype, the author is instead the person responsible for the identification, and the "author_id" 
column should be used instead. Either author or author_id may be null, but not both.

The database seeds should include a basic classification of plants.

### Entidades secundárias
- Person
- Reference
- Herbarium
- Image
- Census
- Project

### Interface / acesso
- User (conecta com Person)
- Role (a principio, "normal" ou "admin")
- Language
- Translation
- DataTranslation (TALVEZ, para traduzir dados de variável de usuário?)
- Plugin
- Access (Relação Role x privilégio)
- DataAccess (Para guardar autorização do tipo "cadastre seu e-mail")
- SiteConfig

The Site_Config table is a key/value relation, with configurations pertaining to this 
installation. It can have as keys "title", "tag" (for the main page), "proxy url", "proxy port", "proxy user" and "proxy password".
 (configurações de site - titulo, tag, proxy, etc)

### Busca e inserção
- Form
- Filter
- Report
- Job (para realizar tarefas em background)

### AUDIT
An audit module should be developed and will be detailed at a later date

### TABELAS AUXILIARES (importação de dados; relatórios)

