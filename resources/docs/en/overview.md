* [**Data Model**](#data_model)
* [**Core Objects**](core_objects)
* [**Auxiliary Objects**](auxiliary_objects)
* [**Trait Objects**](trait_objects)
* [**Data Access Objects**](dataaccess_objects)
* [**API**](api)

# Overview

The OpenDatabBio is an opensource database developed to provide a flexible but robust framework to store, manage and distribute biological data. It is designed to accomodate many data types used in biological sciences and their relationships, particularly biodiversity and ecological studies, and serves as a data repository that allow users to download or request well-organized and documented research data.
<br>

The main features of this database include:

1. The ability to define custom [traits](auxiliary_objects#traits) of diferent types, including some special cases like Spectral Data, Colors and Links. Measurements for these traits can be from marked [plants](core_objects#plants), [vouchers specimens](core_objects#vouchers), [taxonomic entities](core_objects#taxons) and [locations](core_objects#localities).
1. [Taxons](core_objects#locations) can be published or unpublished names, synonyms or valid names, and any node of the tree of life may be stored. Taxon insertion are checked through APIs to different plant, fungi and animal data sources (Tropicos, IPNI, MycoBank,ZOOBANK).
1. [Locations](core_objects#locations) are stored with their Geometries, allowing location parent autodetection and spatial queries. Special location types, such as Plots, can be defined and Conservation Units are treated separetely - because they may transverse different administrative areas.
1. Data are organized in [Datasets](management_objects#datasets) and [Projects](management_objects#projects), entities that have different user-access options, with administrator being able to track downloads and requests histories for datasets. These entities allow different research groups to use the same installation, having total control over their particular research data edition and access, while sharing common libraries such as Taxonomy, Locations,  Bibliographic References and Traits.
1. Tools for data exports and imports are provided through [API services](api), along with a API client in R language, the [OpenDataBio-R package](https://github.com/opendatabio/opendatabio-r).
1. The [Activity Model](activitylog) audits changes in any record and downloads of full datasets, which are logged for history tracking, so you know when and who changed or download something.
1. It is an opensource software that can be customized for any special need (see [License](license)).


<a name="data_model"></a>
<br>
<hr>
## Data Model

The facilitate data model understanding, as it includes many tables and complex relationships, the model is here divided as follows:

<br>
* [Core Objects](core_objects) -  those that recieve measurements from custom traits.
* [Trait Objects](trait_objects) - custom traits and measurements models.
* [Auxiliary Objects](auxiliary_objects) - libraries of common use like Persons and Bibliographic references, and classes related to multilingual translations.
* [Data Access Objects](dataaccess_objects) - objects that facilitate data management and data access permissions definitions
* [Auditing](activitylog) - audits for changes in data values and change history tracking.


<br>
<hr>
Data model pages include Entity Relationships Diagrams (ERD) to facilitate understanding how the models and their tables interact in both the Laravel classes definitions and in the SQL database structure. These diagrams were generated automatically from reading the OpenDataBio script files using [Laravel ER Diagram Generator](https://github.com/beyondcode/laravel-er-diagram-generator), so they express the direct and indirect relationships defined in the Data Model. Some relationships are also expressed for clarity by custom views generated in the PhpMyAdmin designer tool.
