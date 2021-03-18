* [**Home**](#)
* [**Core Objects**](Core-Objects)
* [**Auxiliary Objects**](Auxiliary-Objects)
* [**Trait Objects**](Trait-Objects)
* [**Data Access Objects**](Data-Access-Objects)
* [**API**](API)

# OpenDataBio Documentation

The OpenDatabBio is an opensource web-based software developed to provide a flexible but robust framework to store, curate and distribute biodiversity related data.  It is designed to accomodate many data types used in biological sciences and their relationships, particularly biodiversity and ecological studies, and serves as a data repository that allow users to download or request well-organized and documented research data.

<br><br>
The main features of this database include:

1. **Custom variables** - the ability to define custom [Traits](Trait-Objects#traits), i.e. user defined variables of diferent types, including some special cases like Spectral Data, Colors and Links. Having traits defined, [Measurements](Trait-Objects#measurements) can be recorded for [Individuals](Core-Objects#individuals), [Vouchers](Core-Objects#vouchers), [Taxons](Core-Objects#taxons) and [Locations](Core-Objects#localities).
1. [Taxons](Core-Objects#locations) can be **published or unpublished names** (e.g. a morphotype), synonyms or valid names, and any node of the tree of life may be stored. Taxon insertion are checked through APIs to different nomenclatural data sources (*Tropicos, IPNI, MycoBank,ZOOBANK, GBIF*).
1. [Locations](Core-Objects#locations) are stored with their **spatial Geometries**, allowing location parent autodetection and spatial queries. Special location types, such as Plots, can be defined and Conservation Units are treated separetely - because they may transverse different administrative areas.
1. **Data access control** - [Measurements](Trait-Objects#measurements) data are organized in [Datasets](Data-Access-Objects#datasets); and [Individuals](Core-Objects#individuals) and [Vouchers](Core-Objects#vouchers) into [Projects](Data-Access-Objects#projects). These entities have different user-access options and allows users to define data policy and license for distribuition. Different research groups may use a single OpenDataBio installation, having total control over their particular research data edition and access, while sharing common libraries such as Taxonomy, Locations, Bibliographic References and Traits.
1. **API to access data programatically** - Tools for data exports and imports are provided through [API services](API), along with a API client in the R language, the [OpenDataBio-R package](https://github.com/opendatabio/opendatabio-r).
1. **Autiting** - the [Activity Model](Auditing) audits changes in any record and downloads of full datasets, which are logged for history tracking.
1. It is an **opensource** software that can be customized for any special needs.


<a name="data_model"></a>
***
## Data Model

The facilitate data model understanding, as it includes many tables and complex relationships, the model is here divided as follows:

* [Core Objects](Core-Objects) -  those that recieve measurements from custom traits.
* [Trait Objects](Trait-Objects) - custom traits and measurements models.
* [Auxiliary Objects](Auxiliary-Objects) - libraries of common use like Persons and Bibliographic references, and classes related to multilingual translations.
* [Data Access Objects](Data-Access-Objects) - objects that facilitate data management and data access permissions definitions
* [Auditing](Auditing) - audits for changes in data values and change history tracking.

<br><br>
***
**OBS**: Data model pages include Entity Relationships Diagrams (ERD) to facilitate understanding how the models and their tables interact in both the Laravel classes methods and in the SQL database structure. These diagrams were generated automatically from reading the OpenDataBio script files using [Laravel ER Diagram Generator](https://github.com/beyondcode/laravel-er-diagram-generator), so they express the direct and indirect relationships defined in the Data Model through the implemented `methods` within the classes. Some relationships are also expressed for clarity by custom views generated in the PhpMyAdmin designer tool.

<br><br><br><br><br>
