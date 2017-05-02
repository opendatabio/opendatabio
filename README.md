# opendatabio
A modern system for storing and retrieving plant data - floristics, ecology and monitoring.

This project improves and reimplements code from the Duckewiki project. Duckewiki is a tribute to Adolpho Ducke,
one of the greatest Amazon botanists, and Dokuwiki, an inspiring wiki platform.

## Overview
This project aims to provide a flexible but robust framework for storing, analysing and exporting biological data.
It is specially focused on plant biology, but it can also be used for other taxons as well. The main features of this
database include the ability to define arbitrary Traits that can be measured on individual marked plants. These
traits may then be used to analyse these plants and generate reports.

The project should have a main module for querying the database directly, including new data and editing or deleting
existing data. The data entry on this module will consist on predetermined pages for the base objects and
on flexible Forms to enter used defined variables. It should also allow for flexible searches
on all database objects, using the defined fields or user supplied Traits. These searches may be saved as 
user defined data filters.  These should be detailed later (TODO). 

There will also be modules for the bulk import and export of data.

The data import should accept csv and Excel spreadsheets, and must handle different field and numeric separators,
date formats, encodings and line endings.

The data export module should allow for taxonomic filters, as well as filters by censuses, date, location and
should have a helper to include all the traits contained in a Form. The data export should de-normalize measurements
for categorical traits with multiple select. There should be a tool for exporting all types of objects, including
taxonomic reports, plant data and voucher data.

The data access API should be documented and allow for recovery and insertion of data. This is specially relevant
for the companion R package, and for importing data via Open Data Kit forms (see below).

There should be a provision for including spectral data (one value of reflection or absorption for each wavelength) 
and molecular data (one id (genebank?), one marked and the sequence) in the database.

Other than the core facilities of the system, more functionality may be provided as plugins. An extensive documentation
will be provided on how to specify a plugin.

A companion R package is also being developed to improve the data import and export routines. The data validation 
for importing should consider: ignoring whitespace difference; ignoring accents; ignoring case; stemming of words and
fuzzy matching, with the later being suggested as edits. It should be allowed to save the import job in any step and
returning later with no data loss.

Finally, an audit module should be developed to keep the history of editing of each relevant database object.

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

## Programing directives and naming conventions
This software should adhere to Semantic Versioning, starting from version 0.1.0-alpha1. The companion R package 
should follow a similar versioning scheme.

In order to simplify the development cycle and install procedure, CI, Propel and their dependencies are being commited
to this repository instead of being managed only by Composer. In order for this to work, it is imperative that only
tagged releases are specified in composer.json - otherwise, this will lead to problems with git mistaking the dependencies
for submodules.

All variables and functions should be named in English, with entities and fields related to the database being 
named in the singular form. All tables (where apropriate) should have an "id" column, and foreign keys should reference
the base table with "_id" suffix, except in cases of self-joins (such as "taxon.parent_id") or foreign keys that
reference a subclass of the Object class. The id of each table has type INT, except the Language table in which the id
is the IETF language tag (such as en-US or pt-BR).

## License
Opendatabio is licensed for use under a GPLv3 license. The installation packages include the files for CodeIgniter and Propel,
which are licensed under an MIT license.

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

Each Object may have an associated note, regarding sampling procedure, location details, etc.

TODO: study whether we should use the Nested Set Model for hierarquical data: http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/ OR storage procedures to emulate CONNECT BY

---

The general idea behind the taxon model is that is should present tools for easily incorporating valid taxonomic names,
with spell checking and added metadata, but allowing for the inclusion of names that are not considered valid 
(because they are still unpublished, are rare and thus not found in online databases, or because the user
disagrees with the validity status of the databases).

In the taxon table, the column "level" represents the taxonomic level (such as order, genera, etc). 
It should be standardized. The column "parent_id" indicates the parent of this taxon, which may be several levels 
above it. Check: the parent level should be strictly higher. One special node is the "root" of the taxonomic tree.

The only exception is the "clade" level, which is not taxonomic. It may represent subspecies, 
infraspecies, categories, varieties, or morphospecies (which is selected in column "category_id"), or it may represent
any larger clade. 
The parent level checking must be done to 
ensure that the parent of each valid child of a clade has a strictly higher level.

In the case that an intermediate level is registered, the system should check and allow the user to register the relevant
children. (TODO: clarify)

The name of the taxon should contain only the specific part of name (in case of species, the epithet). 
There should be a helper to "assemble" the full name from the epithet and its generum name.

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

There are two distinct columns to help identify the publication (usually a research article) in which the taxon was
described. It may be a bibliographic reference (in which case, use "bibreference_id") or a descriptive text (use the
"bibreference" column). Both fields may be null, and both fields may be present for the same taxon.

The database seeds should include a basic classification of plants.

TODO: level should be a FK to a detailed table? Maybe taxonomic_category?? Should that table be Translatable?
TODO: is it necessary to specify the "type" of clade (infrasp, subsp, etc)? Should all children of a clade be of the same level? (ATTENTION)

--- 

The Location table stores Objects representing real world locations. They may be countries, cities, conservation units,
or points in the surface of Earth. These objects are optionally hierarquical, but most units should have a parent. 
The main exception to this rule are conservation units, which may span several cities or states, and have a different
column for pertaining. Thus, one plot (Location) may have as parent a city, and as uc_id the conservation unit where it
belongs. The "level" column should indicate the administrative level of the location, if applicable.

The database seeding should have a list of countries, administrative regions, Brazilian municipalities and conservation units;
this will probably be downloaded from www.diva-gis.org.

The Location table also allows the input of the GPS datum and resolution, if known.

Some marked plants have a GPS point associated, but otherwise no location registered. These points should be stored as
a Location. (ATTENTION!)

Locations are stored in the database as a Polygon, or a series of lat/long points. The first of these points to be
stored is privileged, as it may be used as a reference for relative markings.

TODO: plots have x, y dimension? Sub-plots have start-x, start-y?

TODO: plugin for point-quadrant method

---

The Plant table represents a marked plant. Each plant is identified by a unique combination of location and tag.
One Voucher represents a voucher stored in a herbarium. It may have been taken from a marked plant, in which case the
"parent_id" of the voucher should point to this plant object, but this is not mandatory. Each voucher is identified
by a unique combination of collector and number.
One Sample represents a sample taken from either a marked plant, a voucher or a location (eg, soil sample). The "parent_id"
from the sample should point to the apropriate parent. ATTENTION: should samples have unique identifiers?

The Plant table must specify a Location object in which it was collected. This Location may be a GPS point (with revelant
metadata). In addition to this location, it also may specify either a GPS position 
(which is suposed to be inside the given location) or a relative position
with a local coordinate, which is relative to the Location first point. (ATTENTION: this absolute coordinate has no datum/res??)

The Collector table represents collectors for a plant or sample, or additional collectors for a given voucher. 
The primary collector should be specified as 
"collector_id" in the Voucher table, instead of in the Voucher_Person table. In the case of plants or samples, there is 
no such distinction as the primary collector, so only the Collector table is used (TODO: verify!) (TODO: normalize?)

Each Voucher may be stored in several Herbariums. Each of these storages may generate an identification number,
which should be stored in the Herbarium_Voucher table ("número de tombo").

The date field in the Plant, Voucher and Sample tables represents the collection date. This date may be incomplete,
eg, only the year is recorded. In this case, mark zero for the unknown fields
(https://dev.mysql.com/doc/refman/5.7/en/date-and-time-functions.html). The helper forms for these entities must allow this.

All registered plants should have an Identification object. Vouchers should be linked to either a Plant or an Identification.

Samples must have a "type", which can be "seeds", "leaves", "soil". The database seed should have some sample types of sample.

---

The Identification table represents the identification data for a plant or voucher. It includes several optional fields,
but the person responsible for the identification and identification date should be mandatory when entering data manually
(ie, except when importing batch). The identification may be given with modifiers such as "confer" (cf.) or "affinis" (aff.).
The identification information may also include the herbarium identification number for the item to which the object
was compared.

The database seed should include the relevant modifiers.

---

The Traits table represent user defined traits that can be associated with an Object. These can refer to Plants,
Vouchers, Samples, Locations or even Taxons. Each trait should have a category: quantitative, categorical (qualitative), 
ordinal, color, text, database object (plus number). 
Quantitative variables must have a default measurement unit. Helper functions may be defined (TODO: how?) to convert
from one unit to another in data import. Categorical traits should be suplemented by a list of categories (including
a "Not available"; TODO!); ordinal traits should include the list of categories and the ordinal scale. Each rank should
be unique for a trait of ordinal type.

If the variable type is a database object, it should specify which type of object (Taxon, Location, Voucher); it can be used
to specify the host of a parasite, for example, by specifying a Plant, or the number of predator insects, by specifying a
Taxon and a number. 

The Trait definition should include a "export_name" to register the name for which this trait will be converted on exported
tables.

OBS: the type column should be ENUM! TODO: the type column should be a foreign key, to allow plugins for spectral/molecular?

The database seed should include common traits definitions, such as habit, habitat, height and Diameter at Breast Height
(DBH).

In the screens that require a trait measurement to be entered, the system should determine the best form object to do so.

The Trait definition should be as specific as needed. The measurement of tree heights using direct measurement or a 
clinometer, for example, may not be easily converted from each other, and may be stored in different Traits. Thus,
it is strongly recommended that the Trait definition field include information such as measurement instrument.

A Trait name, definition, unit and categories may not be updated or removed if there is any measurement
of this trait registered in the database. The only exception is that is is permissible to add new categories to categorical
(but not to ordinal) variables.

There are two validation measures that can be enforced on Trait measurements: one Trait may have minimun and maximum values,
and it may only allow integer values (counting variables may be defined as needing to be integer, with a minimum of zero).

Categorial measurements may be single-select or multiple-select. If a Trait may receive more than one category in a measure,
the field "multiple" should be set to true.

---

The Measurement table represents a Trait measurement for a given object. Thus, it needs a trait_id and object_id
of the correct type (as specified by the allowed object types in the Trait definition). If the object type is a Taxon,
the BibReference is mandatory; for Plants, the date and person_id are mandatory. In all cases, Bibreference, date and
person_id may be supplied. For quantitative traits, fill in the "value" field; for text and color, the "value_a"; for
qualitative and ordinal measures, fill the Measurement_Category table. This proccess should validate that categorical Traits
that only accept a single selected category should have no more than one category selected per measurement.


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

