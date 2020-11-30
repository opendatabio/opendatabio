* [**Home**](#)
* [**Core Objects**](Core-Objects)
* [**Auxiliary Objects**](Auxiliary-Objects)
* [**Trait Objects**](Trait-Objects)
* [**Data Access Objects**](Data-Access-Objects)
* [**API**](API)

# OpenDataBio Documentation

The OpenDatabBio is an opensource database developed to provide a flexible but robust framework to store, manage and distribute biological data. It is designed to accomodate many data types used in biological sciences and their relationships, particularly biodiversity and ecological studies, and serves as a data repository that allow users to download or request well-organized and documented research data.

The main features of this database include:

1. The ability to define custom [traits](Trait-Objects#traits) of diferent types, including some special cases like Spectral Data, Colors and Links. Measurements for these traits can be from marked [plants](Core-Objects#plants), [vouchers specimens](Core-Objects#vouchers), [taxonomic entities](Core-Objects#taxons) and [locations](Core-Objects#localities).
1. [Taxons](Core-Objects#locations) can be published or unpublished names, synonyms or valid names, and any node of the tree of life may be stored. Taxon insertion are checked through APIs to different plant, fungi and animal data sources (Tropicos, IPNI, MycoBank,ZOOBANK).
1. [Locations](Core-Objects#locations) are stored with their Geometries, allowing location parent autodetection and spatial queries. Special location types, such as Plots, can be defined and Conservation Units are treated separetely - because they may transverse different administrative areas.
1. Data are organized in [Datasets](Data-Access-Objects#datasets) and [Projects](Data-Access-Objects#projects), entities that have different user-access options, with administrator being able to track downloads and requests histories for datasets. These entities allow different research groups to use the same installation, having total control over their particular research data edition and access, while sharing common libraries such as Taxonomy, Locations,  Bibliographic References and Traits.
1. Tools for data exports and imports are provided through [API services](API), along with a API client in R language, the [OpenDataBio-R package](https://github.com/opendatabio/opendatabio-r).
1. The [Activity Model](Auditing) audits changes in any record and downloads of full datasets, which are logged for history tracking, so you know when and who changed or download something.
1. It is an opensource software that can be customized for any special need.


<a name="data_model"></a>
***
## Data Model

The facilitate data model understanding, as it includes many tables and complex relationships, the model is here divided as follows:

* [Core Objects](Core-Objects) -  those that recieve measurements from custom traits.
* [Trait Objects](Trait-Objects) - custom traits and measurements models.
* [Auxiliary Objects](Auxiliary-Objects) - libraries of common use like Persons and Bibliographic references, and classes related to multilingual translations.
* [Data Access Objects](Data-Access-Objects) - objects that facilitate data management and data access permissions definitions
* [Auditing](Auditing) - audits for changes in data values and change history tracking.


Data model pages include Entity Relationships Diagrams (ERD) to facilitate understanding how the models and their tables interact in both the Laravel classes definitions and in the SQL database structure. These diagrams were generated automatically from reading the OpenDataBio script files using [Laravel ER Diagram Generator](https://github.com/beyondcode/laravel-er-diagram-generator), so they express the direct and indirect relationships defined in the Data Model. Some relationships are also expressed for clarity by custom views generated in the PhpMyAdmin designer tool.


<br><br><br><br><br>
