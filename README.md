# opendatabio
A modern system for storing and retrieving plant data - floristics, ecology and monitoring.

This project improves and reimplements code from the Duckewiki project. Duckewiki is a tribute to Adolpho Ducke,
one of the greatest Amazon botanists, and Dokuwiki, an inspiring wiki platform.

## Overview
This project aims to provide a flexible but robust framework for storing, analysing and exporting biological data.
See our [Wiki page](wiki) for details.

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

## License
Opendatabio is licensed for use under a GPLv3 license. The installation packages include the files for CodeIgniter and Propel,
which are licensed under an MIT license.

## Data model

### Other entities
- Person
A Person entry represents one person which may or may not be directly involved with the database. It is used to store
information about plant and voucher collectors, specialists, and database users. When registering a new person,
the system suggests the name abbreviation, but the user is free to change it to better adapt it to the usual abbreviation
used by each person. The abbreviation should be unique for one Person. (ATTENTION)

The Person_Taxon table represents taxonomic groups for which a person is a specialist. The Herbarium foreign key is used
to list to which herbarium a person is associated.

- BibReference
The BibReference should contain only the bibtex reference. The BibKey, authors and other relevant fields should be extracted 
from it and may be used in index-based functions for searching. The Bibkey should be unique in the database.

- Herbarium

The Herbarium object stores basic information about a herbarium, plus the identification code for this herbarium
in the Index Herbariorum (http://sweetgum.nybg.org/science/ih/).

- Image
Images are similar to Trait measurements in that they might be associated with all types of objects. It is possible to
organize them in tags, such as "leaves" or "seeds" to indicate what are depicted in the image. It is still an open question
whether the images will be stored as binary data inside the database, or in the server storage. Some sample tags should
be provided on the database seed.

- Census
- Project
- DataAccess 
The Census and Project tables refer to grouping of objects or measurements. Each of them has a Person who is the 
project/census "owner", and may have one or more related Persons. The data pertaining to a Project or Census may have a
privacy directive: public access; restricted access (only allowed Users may access it); free access after registering;
free access after admin approval. These objects need to be documented; it is currently unclear how clashing directives should
be applied. A census or project may have an associated BibReference for citation. An object or measurement may be part
of zero, one or more than one projects/censuses. (TODO: data models)

### Interface 
- User (connects with Person)
- Role ("normal" ou "admin")
The User table stores information about the database users and administrators. It is connected with the Role table,
which is in turn used to specify which operations are allowed in the Access table. (TODO: data models)

- Language
- Translation
- UserTranslation 

The Translation table stores information to translate the interface to other languages. The UserTranslation translates
user data, such as Trait names and categories, to other languages.

- Plugin
There should be a structure to store which plugins are installed on a given database and which are the compatible
system versions?

- Access (Role x privileges for ACL)
- SiteConfig

The Site_Config table is a key/value relation, with configurations pertaining to this 
installation. It can have as keys "title", "tag" (for the main page), "proxy url", "proxy port", "proxy user" and "proxy password".

### Query and insertion
- Form
A Form is an organized group of variables, defined by a User in order to create a custom form that can be 
filled in in the system. A Form consists of a group of ordered Traits, which can be marked as "mandatory".

- Filter
To be detailed later

- Report
To be detailed later

- Job
A Job system may be put in place to deal with background tasks. An user should be allowed to create a job; cancel a job;
list all past and active jobs; see the details of a finished job (including possibly the data imported by it or the 
data selected by it, in case the job is a query or data import).

### AUDIT
An audit module should be developed and will be detailed at a later date. It is fundamental that the Identification
table has full audit history, easily accessed.

### AUXILIARY TABLES for data import and reports

