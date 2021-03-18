* [**Core Objects**](#)
    * [Location](#locations)
    * [Individual](#individuals)
    * [Taxons](#taxons)
    * [Voucher](#vouchers)
* [**Auxiliary Objects**](Auxiliary-Objects)
* [**Trait Objects**](Trait-Objects)
* [**Data Access Objects**](Data-Access-Objects)
* [**API**](API)

# Core Objects
Core objects are: [Location](#location), [Voucher](#voucher), [Individual](#individual) and [Taxon](#taxon). These entities are considered "Core" because they may have [Measurements](Trait-Objects#measurements), i.e. you may register values for any custom [Trait](Trait-Objects#traits).

* The [Individual](#individuals) object refer to Individual organism that have been observed (an occurence), collected (a specimen) or tagged for monitoring purposed (a banded bird, a radiotracked bat, a tree tagged with an aluminium plate). Individuals may have one or more [Vouchers](#vouchers) and one or multiple [Locations](#locations), and will have a taxonomic  [Identification](Auxiliary-Objects#identifications). Any attribute measured or taken for individual organism may be associated with this object through the [Measurement Model](Trait-Objects#measurements) model.

* The [Voucher](#vouchers) object is for records of samples (or specimens) from [Individuals](#individuals) deposited in a [Biological Collection](Data-Access-Objects#biocollections). The taxonomic Identification and the Location of a Voucher is that of the Individual it belogns to. Measurements may be linked to a Voucher when you want to explicitly register the data to that particular sample (e.g. morphological measurements; a molecular marker from an extraction of a sample in a tissue collection). Otherwise you could just record the Measurement for the Individual the Voucher belongs to.

* The [Location](#locations) object contains spatial geometries, like points and polygons, and include `plots` as a special case. An [Individual](#individuals) may have one location (e.g. a plant) or more locations (e.g. a monitored animal). The relative position in cartesian (meters) units of the Individual in relation to the Location may be added when the location is a geographical coordinate POINT or a Plot. Ecological relevant measurements, such as soil or climate data are examples of measurements that may be linked to locations [Measurement Model](Trait-Objects#measurements).

* The [Taxon](#taxons) object in addition to its use for the Identification of Individuals, is also a core object, which allows the organization of secondary, published data, or any kind of information linked to a Taxonomic name, and a [BibReference](Auxiliary-Objects#bibreferences) can be included to indicate the data source. Moreover, the Taxon model is available as special type of [Trait](Trait-Objects#traits), the LinkType, making it possible to record counts for Taxons at a particular Location as measurements.
<br>
<br>
![](https://github.com/opendatabio/datamodel/blob/master/model_coreobjects.png)
<img src="{{ asset('images/docs/model_coreobjects.png') }}" alt="Core Objects model" with=350>*This figure show the relationships among the `Core objects` and with the [Measurement Model](Trait-Objects#measurements). The [Identification Model](Auxiliary-Objects#identifications) is also included for clarity. Solid links are direct relationships, while dashed links are indirect relationships (e.g. Taxons has many Vouchers through Individuals, and have many Individuals through identifications). The red solid lines link the `Core objects` with the Measurement model through [polymorphic relationships](#polymorphicrelationships). The dotted lines on the Measurement model just allow access to the measured core-object and to the models of link type traits.*
<br>
<br>

<a name="locations"></a>
***
## Location Model
The **Locations** table stores data representing real world locations. They may be countries, cities, conservation units, any spatial polygon, or points on the surface of Earth. These objects are hierarchical and have a parent-child relationship that allow to query ancestors and descendants as well, implemented using the Nested Set Model for hierarchical data of the Laravel library [Baum](https://github.com/etrepat/baum). The main exception to this rule are Conservation Units (UCs), which may span several cities or states, and have a different column for pertaining.

![](https://github.com/opendatabio/datamodel/blob/master/location_model.png)
<img src="{{ asset('images/docs/location_model.png') }}" alt="Location model" with=350>
*This figure shows the relationships of the `Location` model throught the methods implemented in the shown classes. The pivot table linking Location to Individual allow an individual to have multiple locations and each location for the individual to have specific attributes like date_time, altitude, relative_position and notes.*
<br><br>
![](https://github.com/opendatabio/datamodel/blob/master/location_model_phpadmin.png)
<img src="{{ asset('images/docs/location_model_phpadmin.png') }}" alt="Location model" with=350>
*The same tables related with the Location model with the direct and non-polymoprhic relationships indicated*.

###Location Table Columns

- Columns `parent_id` together with `rgt`, `lft` and `deph` are used to define the Nested Set Model to query ancestors and descendants in a fast way. Only `parent_id` is specified by the user, the other columns are calculated by the Baum library from the id\+parent_id values that define the hierarchy. The same hierarchical model is used for the [Taxon Model](#taxons), but for Locations there is a **spatial constraint**, i.e. a children must fall within a parent geometry.
- The `adm_level` column should indicate the administrative level of a location. By default, the following `adm_level` are configured in OpenDataBio:
  - `0` for country, `1` for first division within country (province, state), `2` for second division (e.g. municipality),...  up to `adm_level=6` as administrative areas;
  - `99` is the code for *Conservation Units* (`uc`) - a conservation unit is a `location` that may be linked to multiple other locations (any location may belong to a single UC). A UC should be stored under the appropriate level - a federal UC should have a country as "parent", a state reserve should have a "state" as "parent", and so on. Thus, one Location may have as parent a city and as uc_id the conservation unit where it belongs.
  - `100` is the code for `plots` and subplots - plots are a special case of location in OpenDataBio. Plot locations allows you to also register cartesian dimensions for the location in addition to a geographical geometry. The cartesian dimensions of a plot location can be combined with cartesian positions of subplots (i.e. a plot location whose parent is also a plot location) and/or of individuals within such plots, allowing individuals and subplots to be mapped within such locations. In other words, if the spatial geometry of the plot is unknown, it may have as geometry a single GPS point rather than a polygon, plus its `x` and `y` dimensions. A subplot is location plot inside a location plot and must consist of a point marking the start of the subplot plus its X and Y cartesian dimensions. If the geomtry of the start of the subplot is unknown, it may be stored as a relative position to parent plot using the `startx` and `starty`. You may use both, cartesian dimensions and spatial geometries for plot locations. Columns `x`,`y`,`startx` and `starty` are therefore exclusive for locations with `adm_level=100`.
  - `999` for 'POINT' locations like GPS waypoints - this is for registration of any point in space
  - `101` for transects **NOT YET IMPLEMENTED**
  - The administrative levels may be configured in an OpenDataBio **before** importing any data to the database, see the [installation guide](installation) for details on that.
- Column `datum` may record the geometry datum property, if known. If left blank, the location is considered to be stored using [WGS84](https://en.wikipedia.org/wiki/World_Geodetic_System) datum. However, there is no built-in conversor from other types of data, so the maps displayed may be incorrect if different datums are used.
- Column `geom` stores the location geometry in the database, allowing spatial queries in SQL language, such as `parent autodetection`. The geometry of a location may be `POINT`, `POLYGON` or a `MULTIPOLYGON` and must be formatted using [Well-Known-Text](https://en.wikipedia.org/wiki/Well-known_text) geometry representation of the location. A location with a POINT geometry represent a simple lat/long point in space and should be avoided for locations that have area, which should be registered as POLYGON or MULTIPOLYGON, otherwise these will not be usefull for spatial queries. MULTIPOLYGON is less common, but it is useful if the entity is not continuous (e.g., the object representing a Country may have several islands). When  a POLYGON is informed, the **first point within the geometry string is privileged**, i.e. it may be used as a reference for relative markings. For example, such point will be the reference for the `startx` and `starty` columns of a subplot location. So for plot geometries, it matters which point is listed first in the POLYGON WKT.


**Data access** [full users](Data-Access-Objects#users) may register new locations, edit locations details and remove locations records that have no associated data. Locations have public access!


<a name="individuals"></a>
***
## Individual Model

The **Individual** object represents the record for an individual. It may be an occurence, a record for a Biocollection specimen or an individual that is monitored through time, such as a plant in a forest plot or a bird in capture-recapture experiment or a radiotracked animal. Any record representing individual organisms. An **Individual** may have one or more [Vouchers](#voucher) representing physical samples of the individual stored in one or more [Biological Collection](Data-Access-Objects#biocollections), and it may have one or more [Locations](#voucher), representing the place or places where the individual has been recorded.  Individual objects may also have a **self** taxonomic [Identification](Auxiliary-Objects#identifications), or its taxonomic identity may depend on that of another individual (**non-self** identification). The Individual identification is inherited by all the Vouchers registered for the Individual.


![](https://github.com/opendatabio/datamodel/blob/master/individual_model.png)
<img src="{{ asset('images/docs/individual_model.png') }}" alt="Individual model" with=350>*This figure shows the Individual Model and the models it relates to, except the Measurement and Location models, as their relationships with Individuals is shown elsewhere in this page. Lines linking models indicate the `methods` or functions implemented in the classes to access the relationships. Dashed lines indicate indirect relationships and the colors the different types of Laravel Eloquent methods.*



![](https://github.com/opendatabio/datamodel/blob/master/individual_model_phpadmin.png)
<img src="{{ asset('images/docs/individual_model_phpadmin.png') }}" alt="Individual model" with=350>*The same tables related with the Individual model, their columns and the direct and non-polymoprhic relationships among them*.

###Individual Table Columns
- A Individual record **must** specify at least one [Location](#location) where it was registered, the `date` of registration, the field `tag` and the `project_id` the individual belongs to.
- The Location may be any location registered, but will be mostly used for PLOT and POINT locations, but for historical records an administrative location such as a Province or Country may be used. Individual locations are stored in the `individual_location` pivot table, having columns `date_time`, `altitude`, `notes` and `relative_position` for the individual location record. The column `relative_position` is where the cartesian coordinates of the Individual in relation to its Location is stored. This is only for  individuals located in PLOT or POINT geometries (see [Location](#locations)). For example, a Plot location with dimensions 100x100 meters (1ha), may have an Individual with `relative position=POINT(50 50)`, which will place the individual in the center of the location (this is shown graphically in the web-interface). Moreover, if the location has 'startx' and 'starty' values and is a subplot, then the position within the parent plot may also be calculated (this was designed with ForestGeo plots in mind and is a column in the [Individual GET API](API#endpoint_individuals). If the location is a POINT, the relative_position may be informed as  `angle` (= azimuth) and `distance`.
- The `date` field in the Individual, Voucher, Measurement and Identification models may be an [Incomplete Date](Auxiliary-Objects#incompletedate), e.g. only the year or year+month may be recorded.
- The **Collector** table represents collectors for an Individual or Voucher, and is linked with the [Person Model](Auxiliary-Objects#persons). The collector table has a polymorphic relationship with the Voucher and Individual objects, defined by columns `object_id` and `object_type`, allowing multiple collectors for each individual or voucher record. The main_collector indicated in the figure links, is just the first collector listed for these entities.
- The `tag` field is a user code or identifier for the Individual. It may be the number written on the aluminium tag of a tree in a forest plot, the number of a bird-band, or  `collector-number` of specimen. The combination of `main_collector`+`tag`+`first_location` is constrained to be unique in OpenDataBio.
- The **taxonomic identification** of an Individual may be defined in two ways:
  - for *self* identifications an [Identification](Auxiliary-Objects#identifications) record is created in the *identifications* table, and the column `identification_individual_id` is filled with the Individual own `id`
  - for *non-self* identifications, the id of the Individual having the actual Identification is stored in column `identification_individual_id`.
  - Hence, the Individual class contain two methods to relate to the Identification model: one that sets *self identifications* and another that retrieves the actual taxonomic identifications by using column `identification_individual_id`.
- Individuals may have one or more [Vouchers](#vouchers) deposited in a [Biocollection]($Auxiliary-Objects#biocollections).
<br><br>
**Data access** Individuals belong to [Projects](Data-Access-Objects#projects), so Project access policy apply to the individuals in it. Only project collaborators and administrators may insert or edit individuals in a project, even if project is of public access.


<a name="taxons"></a>
***
## Taxon Model
The general idea behind the **Taxon** model is to present tools for easily incorporating valid taxonomic names from Online Taxonomic Repositories (currently Tropicos.org, IPNI.org, MycoBank.org, Zoobank.org and GBIF are implemented), but allowing for the inclusion of names that are not considered valid, either because they are still **unpublished** (e.g. a morphotype), or the user disagrees with published synomizations, or the user wants to have all synomyms registered as invalid taxons in the system.  Moreover, it allows one to define custom `clade` level taxons, basically allowing one to store, in addition to taxonomic rank categories, any node of the tree of life.  Any registered Taxon can be used in Individual identifications, and Measurements may be linked to taxonomic names.

![](https://github.com/opendatabio/datamodel/blob/master/taxon_model.png)
<img src="{{ asset('images/docs/taxon_model.png') }}" alt="Taxon model" with=350>*Taxon model and its relationships. Lines linking tables indicate the `methods` implemented in the shown classes, with colors indicating different Eloquent relationships*

### Taxon table explained

- Like, Locations, the Taxon model has a **parent-child relationship**, implemented using the Nested Set Model for hierarchical data of the [Laravel library Baum](https://github.com/etrepat/baum) that allows to query ancestors and descendants. Hence, columns `rgt`, `lft` and `deph` of the taxon table are automatically filled by this library upon data insertion or update.
- For both, Taxon `author` and Taxon `bibreference` there are two options:
  - For published names, the string authorship retrieved by the external taxon APIs will be placed in the `author=string` column. For unpublished names, author is a [Person](Auxiliary-Objects#persons) and will be stored in the `author_id` column.
  - Only published names may have relation to BibReferences. The `bibreference` string field of the Taxon table stores the strings retrieved through the external APIs, while the `bibreference_id` links to a [BibReference](Auxiliary-Objects#bibreferences) object. These are used to store the Publication where the Taxon Name is described and may be entered in both formats.
  - In addition, a Taxon record may also have many other BibReferences through a pivot table (`taxons_bibreference`), permitting to link any number of bibliographic references to a Taxon name.
* Column `level` represents the taxonomic level (such as order, genera, etc). It is numerically coded and standardized following the [IAPT general rules](http://www.iapt-taxon.org/nomen/main.php?page=art3), but should accomadate also animal related taxon level categories. See the available codes in the [Taxon API](API#endpoint_taxons) for the list of codes.
* Column `parent_id` indicates the parent of the taxon, which may be several levels above it. The parent level should be strictly higher than the taxon level, but you do not need to follow the full hierarchy. It is possible to register a taxon without parents, for example, an unpublished morphotype for which both genera and family are unknown may have an `order` as parent. The **clade** level is a special OpenDataBio level, that allows any phylogenetic clade to be recorded as long as you provide a name for it.
* Names of the taxonomic levels are translated according to the system defined `locale` that also translates the web interface (currently only portuguese and english implemented; see [web interface](webinterface).
* The `name` field of the taxon table contain only the specific part of name (in case of species, the specific epithet), but the insertion and display of taxons through the [API](API) or [web interface](webinterface) should be done with the fullname combination.
* It is possible to include **synonyms** in the Taxon table. To do so, one must fill in the `senior` relationship, which is the `valid` name for an `invalid` Taxon. If the `senior field` is left blank, it is understood that this taxon is the senior synonym, or a valid name. If it is filled, then the taxon is a `junior` synonym and must be flagged as **invalid** and have a `senior` value.
* When inserting a new taxon, the user may check its validity, spelling, authorship and string bibreference through APIs of the following open data sources when using the [web interface](webinterface), which are called following the order below:
  1. [Tropicos](http://tropicos.org) - the Missouri Botanical Garden nomenclatural database is robust for plant names and synonyms.
  1. [IPNI](http://www.ipni.org) - the International Individual Names Index is another database used to validate individual names;
  1. [MycoBank](http://www.mycobank.org) - this used to validate a name if not found by the Tropicos nor IPNI apis, and used to validate names for Fungi. **Temporarily removed because MycoBank is updating its API infrastructure**
  1. [ZOOBANK](http://zoobank.org) - when Tropicos, IPNI and MycoBank fails to find a name, then the name is tested against the ZOOBANK api, which validates animal names. Does not provide taxon publication, however.
  1. [GBIF BackBone Taxonomy](https://www.gbif.org/dataset/search) - because in ZOOBANK there are still many missing names, if the api taxon checker do not find the taxon name in any of the above, it will further searches GBIF to find at least the name authorship.
* If a Taxon name is found with the taxon apis, the respective TaxonID of the repository is stored in the `taxon_external` tables, creating a link between the OpenDataBio taxon record and the external nomenclatural database.
* A [Person](Auxiliary-Objects#persons) may be defined as one or more taxon specialist through a pivot table. So, a Taxon object may have many taxonomic specialist registered in OpenDataBio as persons.

<br><br>
**Data access**: [Full users](Data-Access-Objects#users) are able to register new taxons and edit existing records if not used. Currently it is impossible to remove a taxon from the database. Taxons have public access.

<a name="vouchers"></a>
***
## Voucher Model
The **Voucher** model is used to store records of specimens or samples from [Individuals](#individuals) deposited in [Biological Collections](Data-Access-Objects#biocollections). Therefore, the only mandatory information required to register a Voucher are `individual`, `biocollection` and whether the specimen is a nomenclatural type (which defaults to non-type if not informed).

![](https://github.com/opendatabio/datamodel/blob/master/voucher_model.png)
<img src="{{ asset('images/docs/voucher_model.png') }}" alt="Voucher model" with=350>*Voucher model and its relationships. Lines linking tables indicate the `methods` implemented in the shown models, with colors indicating different Eloquent relationships. Not that Identification nor Location are show because Vouchers do not have their own records for these two models, they are just inherited from the Individual the Voucher belongs to*

### Vouchers table explained

- A Voucher belongs to an Individual and a Biocollection, so the `individual_id` and the `biocollection_id` are mandatory in this table;
- `biocollection_number` is the alphanumeric code of the Voucher in the Biocollection, it may be null for users that just want to indicate that a registered Individual have Vouchers in a particular Bicollection, or to registered Vouchers for biocollections that do not have an identifier code;
- `biocollection_type` - is a numeric code that specify whether the Voucher in the BioCollection is a nomenclatural type. Defaults to 0 (Not a Type); 1 for just 'Type', a generic form, and other numbers for other nomenclatural type names (see the [API Vouchers Endpoint](API#endpoint_vouchers) for a full list of options).
- `collectors`, one or multiple, are optional for the Vouchers, required only if they are different from the Individual collectors. Otherwise the Individual collectors are inherited by the Voucher.  Like for [Individuals](#individuals), these are implemented through a [polymorphic relationship](#polymorphicrelationship) with the *collectors* table and the first collector is the main_collector for the voucher, i.e. the one that relates to `number`.
- `number`, this is the *collector number*, but like collectors, should only be filled if different from the [Individual's](#individuals) `tag` value. Hence, `collectors`,  `number` and `date` are useful for registering Vouchers for Individuals that have Vouchers collected at different times by different people.
- `date` field in the Individual and Voucher models may be an [incomplete date](Auxiliary-Objects#incompletedate). Only required if different from that of the Individual the Voucher belongs to.
- `project_id` the Voucher belongs to a Project, which controls the access policy.
- `notes` any text annotation for the Voucher.

<br><br>

**Data access** Vouchers belong to [Projects](Data-Access-Objects#projects), so Project access policy apply to the vouchers in it. Vouchers may have a different Project than their Individuals.  If Voucher project policy is free access and that of the Individual project is not, then access to voucher data will grant access to the Individual taxonomic identity and geographical location. Only project collaborators and administrators may insert or edit vouchers in a project, even if the project is of public access.

<a name="polymorphicrelationships"></a>
## Polymorphic relations
Some of the foreign relations within OpenDataBio are mapped using [Polymorphic relations](https://laravel.com/docs/8/eloquent-relationships#polymorphic-relations). These are indicated in a model by having a field ending in `_id` and a field ending in `_type`. For instance, all Core-Objects may have [Measurements](Trait-Objects#measurements), and these relationships are established in the Measurements table by the `measured_id` and the `measured_type`  columns, the first storing the related model unique `id`, the second the measured model class in strings like 'App\Individual', 'App\Voucher', 'App\Taxon', 'App\Location'.
