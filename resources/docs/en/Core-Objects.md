* [**Core Objects**](#)
    * [Location](#locations)
    * [Plant](#plants)
    * [Taxons](#taxons)
    * [Voucher](#vouchers)
* [**Auxiliary Objects**](Auxiliary-Objects)
* [**Trait Objects**](Trait-Objects)
* [**Data Access Objects**](Data-Access-Objects)
* [**API**](API)

# Core Objects
Core objects are: [Location](#location), [Voucher](#voucher), [Plant](#plant) and [Taxon](#taxon).

These entities are considered "Core" because they may have [Measurements](Trait-Objects#measurements), i.e. you may register values for any custom user [Traits](Trait-Objects#traits), which basically acomodate any kind of biodiversity related data.

* The [Voucher](#vouchers) object is for records of **physical specimens** of any kind of organism deposited in [Herbaria or Biological Collection](Data-Access-Objects#herbaria) or for **species occurences** that have no physical specimen. Duplicated specimens of the same individual, common in plant related-work, may be assigned in the related Herbaria model, so a Voucher represents a single individual, i.e. the record for all duplicates of the individual. Taxonomic relevant trait measurements are example of type of measurements to be linked with vouchers.
* The [Location](#locations) object contains spatial geometries, like points and polygons, and include `plots` as a special case. Vouchers and Plants have their locations. Ecological relevant measurements, such as soil or climate data are examples of trait measurements that may be linked to locations.
* The [Plant](#plants) object refer to plants usually tagged and monitored in the field, but may also be plant occurences that are not monitored nor tagged. Plants may contain one or more Vouchers. Plot census and phenological monitoring are  among the most common trait measurements associated with a Plant object.
* The [Taxon](#taxons) object in addition to its use for Plant and Voucher identifications, is included as core for the organization of secondary, published data, like a taxonomic description, or the variables extracted from it, as measurements linked to a taxonomic name. Such measurements may also be linked to a [BibReference](Auxiliary-Objects#bibreferences) to indicate the data source.


The figure below exemplify the relations among the `Core objects` and with the [Measurement Model](Trait-Objects#measurements). Solid links are direct relationships, while dashed links are indirect relationships. Red dotted links show [polymorphic relationships](#polymorphicrelationships) which are also indicated by the red solid lines for each the `MorphTo` relationships, ex: the four solid lines linking core objects to measurements express the dotted line looping within the measurements table.

![](https://github.com/opendatabio/datamodel/blob/master/model_coreobjects.png)
<img src="{{ asset('images/docs/model_coreobjects.png') }}" alt="Core Objects model" with=350>

<a name="polymorphicrelationships"></a>
### Polymorphic relations
Some of the foreign relations within OpenDataBio are mapped using [Polymorphic relations](https://laravel.com/docs/8/eloquent-relationships#polymorphic-relations). These are indicated in the model by having a field ending in `_id` and a field ending in `_type`. For instance, a [Voucher](#vouchers) may be related to a marked [Plant](#plants) or to a [Location](#locations), or a [Measurement](Trait-Objects#measurements) may be related to any of the core object. Ex: if one particular Voucher is related to the plant with `id=213`, the `parent_id` of this voucher will be 213, and the `parent_type` will be "App\Plant", which is the class name for the plant object. If another Voucher is related to Location with `id=12`, its `parent_id` will be 12, and its `parent_type` will be "App\Location".


<a name="locations"></a>
***
## Location Model
The **Locations** table stores data representing real world locations. They may be countries, cities, conservation units, any spatial polygon, or points on the surface of Earth. These objects are hierarchical and have a parent-child relationship that allow to query ancestors and descendants as well, implemented using the Nested Set Model for hierarchical data of the Laravel library [Baum](https://github.com/etrepat/baum). The main exception to this rule are Conservation Units (UCs), which may span several cities or states, and have a different column for pertaining.

![](https://github.com/opendatabio/datamodel/blob/master/location_model.png)
<img src="{{ asset('images/docs/location_model.png') }}" alt="Location model" with=350>


The tables structures and their direct links:
![](https://github.com/opendatabio/datamodel/blob/master/location_model_phpadmin.png)
<img src="{{ asset('images/docs/location_model_phpadmin.png') }}" alt="Location model" with=350>


Columns `parent_id` together with `rgt`, `lft` and `deph` are used to define the Nested Set Model to query ancestors and descendants in a fast way. Only `parent_id` is specified by the user, the other columns are calculated by the Baum library from the id\+parent_id values that define the hierarchy. The Nested Model may be redefined by the server administrator (see documentation of the [Baum](https://github.com/etrepat/baum) library).

The `adm_level` column should indicate the administrative level of a location. By default, the following `adm_level` are configured in OpenDataBio:
  - `0` for country. **1** for first division within country (province, state);  **2** for second division (e.g. municipality). up to `adm_level=6` as administrative areas;
  - `99` is the code for *Conservation Units* (`uc`) - a conservation unit is a `location` that may be linked to multiple other locations. A UC should be stored under the appropriate level - a federal UC should have a country as "parent", a state reserve should have a "state" as "parent", and so on. Thus, one Location may have as parent a city and as uc_id the conservation unit location where it belongs.
  - `100` is the code for `plots` and subplots - plots are a special case of location in OpenDataBio. Plot locations allows you to also register cartesian dimensions for the location in addition to a geographical geometry. The cartesian dimensions of a plot location can combined with cartesian positions of subplots (i.e. a plot location whose parent is also a plot location) and/or of plants within such plots, allowing plants and subplots to be mapped within such locations. In other words, if the spatial geometry of the plot is unknown, it may have as geometry a single GPS point rather than a polygon, plus its `x` and `y` dimensions. A subplot is location plot inside a location plot and must consist of a point marking the start of the subplot plus its X and Y cartesian dimensions. If the geomtry of the start of the subplot is unknown, it may be stored as a relative position to parent plot using the `startx` and `starty`. You may use both, cartesian dimensions and spatial geometries for plot locations. Columns `x`,`y`,`startx` and `starty` are therefore exclusive for locations with adm_level=Plot.
  - `999` for 'POINT' locations like GPS waypoints - this is for registration of any point in space
  - `101` for transects **NOT YET IMPLEMENTED**
  - The administrative levels may be configured in an OpenDataBio **before** importing any data to the database, see the [installation guide](installation) for details on that.

Column `datum` may record the geometry datum property, if known. If left blank, the location is considered to be stored using [WGS84](https://en.wikipedia.org/wiki/World_Geodetic_System) datum. However, there is no built in conversor from other types of data, so the maps displayed may be incorrect.

Column `geom` stores the location geometry in the database, allowing spatial queries in SQL language, such as `parent autodetection`. The geometry of a location may be `POINT`, `POLYGON` or `MULTIPOLYGON` and must be formatted using [Well-Known-Text](https://en.wikipedia.org/wiki/Well-known_text) geometry representation of the location. A location with a POINT geometry represent a simple lat/long point in space and should be avoided for locations that have area, which should be registered as POLYGON or MULTIPOLYGON, otherwise these will not be usefull for spatial queries. MULTIPOLYGON is less common, but it is useful if the entity is not continuous (e.g., the object representing a Country may have several islands).

When  a POLYGON is informed, the **first point within the geometry string is privileged**, i.e. it may be used as a reference for relative markings. For example, such point will be the reference for the `startx` and `starty` columns of a subplot location.


**Data access** [full users](Data-Access-Objects#users) may register new locations, edit locations details and remove locations records that have no associated data. Locations have public access!

<a name="plants"></a>
***
## Plant Model
The **Plant** object represents a plant that is has been tagged in the forest. Each plant in OpenDataBio is identified by a unique combination of `location` and `tag`, where the tag is the field tag code or number. This is the main restriction of the plants table, which prevent two plants in the same location with the same code.

A **Plant** may have one or more [Vouchers](#voucher), representing physical samples of the plant stored in one or more [Biological Collection](Data-Access-Objects#herbaria). These vouchers have as `parent` the Plant object and their taxonomic [Identification](Auxiliary-Objects#identifications) and [location](#locations) are those of the Plant they belong to. Otherwise, vouchers having Location as parent will have their own identification. So a plant identification controls the identification of its direct sample vouchers, but a Plant may have an identification regardless of having a voucher or not.

An improvement is planned to allow a plant to have its identification from a voucher of a different plant. In this case, the plant identification will be the linked voucher identification. This adds complexity, but will permit  uncollected plants to have a voucher deposited in a Biological Collection, which is where identifications are updated and conducted and represents the case of many plot related work for which a sampled species may have only few vouchers for many sampled plants. This facilitates identification updates and their history tracking.


![](https://github.com/opendatabio/datamodel/blob/master/plant_model.png)
<img src="{{ asset('images/docs/plant_model.png') }}" alt="Plant model" with=350>


The tables structures and their direct links:
![](https://github.com/opendatabio/datamodel/blob/master/plant_model_phpadmin.png)
<img src="{{ asset('images/docs/plant_model_phpadmin.png') }}" alt="Plant model" with=350>

* A Plant record **must** specify a [Location](#location) where it was collected, the `date` of collection, the field `tag` and the `project` the plant belongs to.
* The Location may be any location registered, but will be mostly used for Plot or perhaps Point locations. In these cases, you may also specify a cartesian (local coordinate in meters) in the column `relative position`, which is the X and Y coordinates of the plant relative to the Location geometry first point (see [Location](#locations)). It also refers to the 0,0 vertice of a Plot Location with X and Y dimensions specified. For example, a Plot location of 100x100 meters (1ha) may have a plant with `relative position=POINT(50 50)`, which will place the plant in the center of the location (this is shown graphically in the web-interface). Moreover, if the location has 'startx' and 'starty' values and is a subplot, then the position within the parent plot may also be calculated (this was designed with ForestGeo plots in mind and is a column in the [Plant GET API](API#endpoint_plants).
* The `date` field in the Plant and Voucher tables represents the collection date. This date may be [incomplete](Auxiliary-Objects#incompletedate), e.g. only the year or year+month is recorded.
* The **Collector** table represents collectors for a Plant or Voucher, and is linked with [Persons](Auxiliary-Objects#persons). This table has a polymorphic relationship with the Voucher and Plant objects, defined by columns `object_id` and `object_type`, allowing multiple collectors for each plant or voucher record. In the Voucher case, however, the main collector is also specified through the `person_id` column as it is an important identifier.
* All registered plants should have an [Identification](Auxiliary-Objects#identifications) object, which store the plant taxonomic identification, linking the plant to [Taxon](#taxons).

**Data access** Plants belong to [Projects](Data-Access-Objects#projects), so Project access policy apply to the plants in it. Only project collaborators and administrators may insert or edit plants in a project, even if project is of public access.


<a name="taxons"></a>
***
## Taxon Model
The general idea behind the **Taxon** model is to present tools for easily incorporating valid taxonomic names from Online Taxonomic Repositories, with spell-checking and added metadata, but allowing for the inclusion of names that are not considered valid, either because they are still **unpublished**, or the user disagrees with published synomizations, or the user wants to have all synomyms registered as invalid taxon in the system.

The main differences of the OpenDataBio Taxon model with other system is the hability to explicitly treat unpublished names, by linking such names with [Person](Auxiliary-Objects#persons) object as the author, making author mandatory for all taxons. Moreover, it allows one to define custom `clade` level taxons, basically allowing one to store, in addition to taxonomic rank categories, any node of the tree of life.


![](https://github.com/opendatabio/datamodel/blob/master/taxon_model.png)
<img src="{{ asset('images/docs/taxon_model.png') }}" alt="Taxon model" with=350>


* Like, Locations, the **Taxon** model has a parent-child relationship, implemented using the Nested Set Model for hierarchical data of the [Laravel library Baum](https://github.com/etrepat/baum) that allows to query ancestors and descendants. Hence, columns `rgt`, `lft` and `deph` of the taxon table are automatically filled by this library upon data insertion or update.
* For both Taxon `author` and `bibreference` there are two options:
  * For published names, the string authorship retrieved by the external taxon APIs will be placed in the `author=string` column. For unpublished names, author is a [Person](Auxiliary-Objects#persons) and will be stored in the `author_id` column.
  * Only published names may have relation to BibReferences. The `bibreference` string field of the Taxon table stores the strings retrieved through the external APIs, while the `bibreference_id` links to a [BibReference](Auxiliary-Objects#bibreferences) object. These are used to store the Publication where the Taxon Name is described and may be entered in both formats.
  * In addition, a Taxon record may also have many other BibReferences through a pivot table (`taxons_bibreference`), permitting to link any number of bibliographic references to a Taxon object.
* Column `level` in the **Taxon** table represents the taxonomic level (such as order, genera, etc). It is numerically coded and standardized following the [IAPT general rules](http://www.iapt-taxon.org/nomen/main.php?page=art3), but should accomadate also animal related taxon level categories. See the available codes in the [Taxon API](API#endpoint_taxons) for the list of codes.
* Column `parent_id` indicates the parent of the taxon, which may be several levels above it. The parent level should be strictly higher than the taxon level, but you do not need to follow the full hierarchy. It is possible to register a taxon without parents, for example, an unpublished morphotype for which both genera and family are unknown by have an order as parent. The **clade** level is a special OpenDataBio level (see above).
* Names of the taxonomic levels are translated according to the system defined locale that also translates the web interface (currently only portuguese and english implemented; see [web interface](webinterface).
* The `name` field of the taxon table contain only the specific part of name (in case of species, the specific epithet), but the insertion and display of taxons through the [API](API) or [web interface](webinterface) should be done with the full combination name.
* It is possible to include **synonyms** in the Taxon table. To do so, one must fill in the `senior` relationship, which is the `valid` name of an `invalid` Taxon. If the `senior field` is left blank, it is understood that this taxon is the senior synonym, or valid name. If it is filled, then the taxon is a `junior` synonym and must be flagged as **invalid**.
* When inserting a new taxon, the user may check its validity, spelling, authorship and string bibreference through APIs of the following open data sources when using the [web interface](webinterface), which are implemented in the order below:
  1. [Tropicos](http://tropicos.org) - the Missouri Botanical Garden nomenclatural database is robust for most plant names and synomyms.
  1. [IPNI](http://www.ipni.org) - the International Plant Names Index is another database used to validate plant names;
  1. [MycoBank](http://www.mycobank.org) - this used to validate a name if not found by the Tropicos nor IPNI apis, and used to validate names for Fungi. **Temporarily removed because MycoBank is updating its API infrastructure**
  1. [ZOOBANK](http://zoobank.org) - when Tropicos, IPNI and MycoBank fails to find a name, then the name is tested against the ZOOBANK api, which validates animal names. Does not provide taxon publication.
  1. [GBIF BackBone Taxonomy](https://www.gbif.org/dataset/search) - because in ZOOBANK there are still many missing names, if the api taxon checker do not find the taxon name in any of the above, it will further searches GBIF to find at least the name authorship.
* If a Taxon name is found with the taxon apis, the respective TaxonID of the repository is stored in the `taxon_external` tables, creating a link between the OpenDataBio taxon record and the external nomenclatural database
* A [Person](Auxiliary-Objects#persons) may be defined as one or more taxon specialist through a pivot table. So, a Taxon object may have many taxonomic specialist registered in OpenDataBio as persons.

**Data access**: [Full users](Data-Access-Objects#users) are able to register new taxons and edit existing records if not used. Currently it is impossible to remove a taxon from the database. Taxons have public access!


<a name="vouchers"></a>
***
## Voucher Model
The **Voucher** object is used mostly to store records of individuals deposited as specimens or samples in [Biological Collections or Herbaria](Data-Access-Objects#herbaria). However, it may also be used to store species occurences that do not have a physical specimen or sample in a Museum or Herbarium. This is also accomplished by the [Plant](#plants) object, which is mostly designed for forest plot related work. Animal occurrences and specimens should only be registered as Vouchers.

Most relationships of the **Voucher** model are illustrated in the [Plant ER Diagram](#plant_erdiagram), and the relationships of Voucher with the [Collector](Auxiliary-Objects#persons) and [Identification](Auxiliary-Objects#identifications) objects are explained under the [Plant](#plants), as Plant and Vouchers share the same relationships with them. Also, the `date` field in the Plant and Voucher tables may be an [incomplete date](Auxiliary-Objects#incompletedate).

**Voucher** may have two types of `parent`, established through a [polymorphic relationship](#polymorphicrelationships): `parent_type` may be either a [Plant](#plants) or a [Location](#locations). If parent is Plant, then the `location` and `identification` attributes of the Voucher are those of the parent plant (`parent_id`).

A Voucher may belong to one or more [Herbaria or Biological Collections](Data-Access-Objects#herbaria), and this relationship is established through the pivot table `herbarium_voucher`, which has two fields informed by the user:
  * `herbarium_number` - this is the unique id of the voucher in the Herbarium or Collection;
  * `herbarium_type` - this is a numeric code that specify whether the specimen in the Collection is a nomenclatural type. Defaults to 0 (Not a Type); 1 for just 'Type', a generic form, and other numbers for other nomenclatural type names (see full list in [API Vouchers Endpoint](API#endpoint_vouchers) documentation)


![](https://github.com/opendatabio/datamodel/blob/master/voucher_model.png)
<img src="{{ asset('images/docs/voucher_model.png') }}" alt="Voucher model" with=350>


**Data access** Vouchers belong to [Projects](Data-Access-Objects#projects), so Project access policy apply to the vouchers in it. Only project collaborators and administrators may insert or edit vouchers in a project, even if the project is of public access.
