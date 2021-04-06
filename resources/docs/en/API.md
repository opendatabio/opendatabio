- [OpenDataBio API and R client](#)
- [API Tester](API-tester)
- [API EndPoints](#endpoints)
  - [BibReferences EndPoint](#endpoint_bibreferences)
  - [Datasets EndPoint](#endpoint_datasets)
  - [Biocollections EndPoint](#endpoint_biocollections)
  - [Locations EndPoint](#endpoint_locations)
  - [Language EndPoint](#endpoint_languages)
  - [Measurements EndPoint](#endpoint_measurements)
  - [Persons EndPoint](#endpoint_persons)
  - [Individuals EndPoint](#endpoint_individuals)
  - [Projects EndPoint](#endpoint_projects)
  - [Taxons EndPoint](#endpoint_taxons)
  - [Traits EndPoint](#endpoint_traits)
  - [Vouchers EndPoint](#endpoint_vouchers)
  - [UserJobs EndPoint](#endpoint_jobs)
- [Possible errors](#possible_errors)


## OpenDataBio API and R Client

The OpenDataBio API ([Application Programming Interface -API](https://en.wikipedia.org/wiki/API)) allows to programatically interact with an OpenDataBio database for exporting and importing data. The [OpenDataBio R package](https://github.com/opendatabio/opendatabio-r) is a  **client ** for this API, allowing the interaction with the data repository directly from R and illustrating the API capabilities so that other clients can be easily built.


The API allows easy querying of the database and easy data importation through a [REST](https://en.wikipedia.org/wiki/Representational_state_transfer) inspired interface. All API requests and responses are formatted in [JSON](https://en.wikipedia.org/wiki/JSON). The import and export tools of the web interface use the implemented APIs, so data exports and imports have standard formats.

<a name="api_call"></a>
***
## API call

A simple call to the OpenDataBio API has four independent pieces:

1. **HTTP verb** -  either `GET` for exports, or  `POST` for imports, related to the action that you want to accomplish. Other verbs, `PUT`, `DELETE`, etc are not implemenented and deletes and updates can only be made through the interface and have several restrictions for data preservation.
1. **base URL** - is the URL used to access your OpenDataBio server, plus `/api/v0`. For, example, `http://opendatabio.inpa.gov.br/opendatabio/api/v0`
1. **[endpoint](#endpoints)** - represents the object or collection of objects that you want to access, for example, for querying taxonomic names, the endpoint is "taxons"
1. **request parameters** - represent filtering and processing that should be done with the objects, and are represented in the API call after a question mark. For example, to retrieve only valid taxonomic names (non synonyms) end the request with `?valid=1`.

The API call exemplified above can be entered in a browser, by using the full URL `http://opendatabio.inpa.gov.br/api/v0/taxons?valid=1`. When using the OpenDataBio-R client for API calls, this example would be called as  `odb_get_taxons(list(valid=1))`.

<a name="api_authentication"></a>
***
## API Authentication

Authentication is done using an `API token`, that can be found under your user profile on the web interface. Authentication is required, so only registered users can access the API. The token is assigned to a single database user, and should not be shared, exposed, e-mailed or stored in version controls. To authenticate against the OpenDataBio API, use the token in the "Authorization" header of the API request. When using the R client, pass the token to the `odb_config` function `cfg = odb_config(token="your-token-here")`.


Users will only have access to the data for which the user has permission and to, or any data with public access in the database, which by default includes locations, taxons, bibliographic references, persons and traits. Measurements, individuals, and Vouchers access depends on permissions understood by the users token.

<a name="api_versions"></a>
***
## API versions

The OpenDataBio API follows its own version number. This means that the client can expect to use the same code and get the same answers regardless of which OpenDataBio version that the server is running. All changes done within the same API version should be backward compatible. Our API versioning is handled by the URL, so to ask for a specific API version, use the version number between the base URL and endpoint, such as `http://opendatabio.inpa.gov.br/api/v0/taxons`

<a name="endpoints"></a>
***
## API EndPoints - v0 - unstable API version

The API version 0 (v0) is an unstable version. The first stable version will be the API version 1.

<a name="endpoint_common"></a>
### Common parameters

Most Endpoints accept all the common parameters described below. They may also accept specific parameters detailed below each endpoint. Note that most conditions are combined with an "AND" operator, when multiple parameters are sent.
- `id=number` return only the specified resource. This can be a list joined by commas. Example: `api/locations?id=1,50,124`
- `limit`: the number of items that should be returned (must be greater than 0). Example: `api/taxons?limit=10`
- `offset`: the initial record to extract, to be used with limit when trying to download a large amount of data. Example: `api/taxons?offset=10000&limit=10000` returns 10K records starting from the 10K position of the current query.
- `fields`: the fields that should be returned with object. This can be a list joined by commmas. The order in which fields are specified is ignored. See specific below for fields supported by each endpoint. There are two special words for the fields parameters: "simple" (default), which returns a reasonable collection of fields, and "all", which returns all fields. `fields=all` should be used with care, as it may return sub-objects for each object. Most objects accept the `created_at` and `updated_at` fields, which return dates. Example: `api/taxons?fields=id,fullname,valid`

- Notice that some parameters accept an asterisk as wildcard, so `api/taxons?name=Euterpe` will return taxons with name exactly as "Euterpe", while `api/taxons?name=Eut*` will return names starting with "Eut".

### Quick reference for GET verb

| Endpoint |  HTTP verb | Description | Possible parameters |
| --- | --- | --- | --- |
| [/](#endpoint_test) | `GET` | Tests your access | none |
| [bibreferences](#endpoint_bibreferences) | `GET` | Lists of bibliographic references | `id`, `bibkey` |
| [datasets](#endpoint_datasets) | `GET` | Lists registered datasets | `id` only|
| [biocollections](#endpoint_biocollections) | `GET` | List of Biocollections and other vouchers Repositories |  `id` only |
| [measurements](#endpoint_measurements) | `GET` | Lists Measurements | `id`, `taxon`,`dataset`,`trait`,`individual`,`voucher`,`location`,`limit`,`offset`|
| [locations](#endpoint_locations) | `GET` | Lists locations | `root`, `id`, `parent_id`,`adm_level`, `name`, `limit`, `querytype`, `lat`, `long` |
| [persons](#endpoint_persons) | `GET` | Lists registered people |`id`, `search`, `name`, `abbrev`, `email`, `limit` |
| [individuals](#endpoint_individuals) | `GET` | Lists registered individuals |`id`, `location`, `taxon`, `tag`,`project`, `dataset`, `limit`, `offset`|
| [projects](#endpoint_projects) | `GET` | Lists registered projects | `id` only|
| [taxons](#endpoint_taxons) | `GET` | Lists taxonomic names |`root`, `id`, `name`,`level`, `valid`, `external`, `limit` |
| [traits](#endpoint_traits) | `GET` | Lists variables (traits) list |`id`, `name`,`limit`,`offset`|
| [vouchers](#endpoint_vouchers) | `GET` | Lists registered voucher specimens | `id`, `number`, `individual`, `location`, `collector`, `taxon`, `project`, `dataset` |
| [userjobs](#endpoint_jobs) | `GET` | Lists user Jobs | `id`, `status`|



### Imports or POST verbs
The [OpenDataBio R package]()
  A **Import Data Tutorial** is available as vignette of the [OpenDataBio-R Client](https://github.com/opendatabio/opendatabio-r). Below each endpoint there is an explanation of the specific `POST` variables and their requirements for importing data. Batch imports of  [Bibliographic References](Auxiliary-Objects#bibreferences) and [MediaFiles](Auxiliary-Objects#mediafiles) are possible but only through the web interface. The available `POST` APIs are listed below. If not using the R Client that does it for you, the data to be imported must be in the `request body`, formated as a JSON array. Each item in the array must be named and may contain several fields.

**The Import Data tools of the Web Interface use the POST API methods**

| Endpoint |  HTTP verb | Description |
| --- | --- | --- |
| [measurements](#endpoint_measurements) | `POST` | Importo Measurements to Datasets |
| [locations](#endpoint_locations) | `POST` | Imports locations |
| [persons](#endpoint_persons) | `POST` | Imports a list of people |
| [individuals](#endpoint_individuals) | `POST` | Imports a list of individuals |
| [traits](#endpoint_traits) | `POST` | Import traits |
| [taxons](#endpoint_taxons)  | `POST` | Imports taxonomic names |
| [vouchers](#endpoint_taxons)  | `POST` | Imports voucher specimens |



<a name="endpoint_test"></a>
***
### Test

The test endpoint may be used to perform connection tests. It returns a `Success` message and the identity of the logged in user.


<a name="endpoint_bibreferences"></a>
***
### BibReferences Endpoint
The `bibreferences` endpoint interact with the [bibreference](Auxiliary-Objects#bibreferences) table. Their basic usage is getting the registered Bibliographic References. Because this is a simple table it is just easier to get them all and query latter..



#### GET optional parameters  `id`, `bibkey`, `taxon`, `limit`,`offset`
- `id=list` return only references having the id or ids provided (ex `id=1,2,3,10`)
- `bibkey=list` return only references having the bibkey or bibkeys (ex `bibkey=ducke1953,mayr1992`)
- `taxon=list of ids` return only references linked to the taxon informed.
- `limit` and `offset` -  limit query. see [Common endpoints](endpoint_common).



#### Fields obtained with 'simple' option
  - `id`- the id of the BibReference in the bibreferences table (a local database id)
  - `bibkey` - the bibkey used to search and use of the reference in the web system
  - `year` - the publication year
  - `author` - the publication authors
  - `title` - the publication title
  - `doi` - the publication DOI if present
  - `url` - an external url for the publication if present
  - `bibtex` - the reference citation record in [BibTex](http://www.bibtex.org/) format



<a name="endpoint_datasets"></a>
***
### Datasets Endpoint
The `datasets` endpoint interact with the [datasets](management_objects#datasets) table. Their basic usage is getting the registered Datasets, but not the data in the datasets (use the web interface for getting the complete data for a dataset or the [Measurements API](#endpoint_measurements)). Usefull for getting dataset_ids for importing Measurements.



#### GET optional parameters  `id` only
- `id=list` return only datasets having the id or ids provided (ex `id=1,2,3,10`)


#### Fields obtained with 'simple' option
  - `id` - the id of the Dataset in the datasets table (a local database id)
  - `name` - the name of the dataset
  - `privacyLevel` - the access level for the dataset
  - `contactEmail` - the dataset administrator email
  - `description` - a description of the dataset
  - `policy` - the data policy if specified
  - `measurements_count` - the number of measurements in the dataset
  - `taggedWidth` - the list of tags applied to the dataset


<a name="endpoint_biocollections"></a>
***
### Biocollections Endpoint
The `biocollections` endpoint interact with the [biocollections](management_objects#biocollections) table. Their basic usage is getting the list of Biological Collections (Biocollections and other individual and non-individual repositories of biological samples) registered in the database. Using for getting `biocollection_id` for importing data with the [Vouchers API](#endpoint_vouchers)).


#### GET optional parameters  `id` only
- `id=list` return only 'biocollections' having the id or ids provided (ex `id=1,2,3,10`)


#### Fields obtained with 'simple' option
  - `id` - the id of the repository or museum in the biocollections table (a local database id)
  - `name` - the name of the repository or museum
  - `acronym` - the repository or museum acronym
  - `irn` - only for Biocollections, the number of the biocollection in the [Index Herbariorum](http://sweetgum.nybg.org/science/ih/)

<a name="endpoint_measurements"></a>
***
### Measurements Endpoint
The `measurements` endpoint interact with the [measurements](Auxiliary-Objects#measurements) table. Their basic usage is getting Data linked to Individuals, Taxons, Locations or Vouchers, regardless of datasets, so it is usefull when you want particular measurements from different datasets that you have access for. If you want a full dataset, use the web interface, as it prepares a complete set of the dataset measurements and their associated data tables for download in your Job tab.


#### GET optional parameters `id`, `taxon`,`dataset`,`trait`,`individual`,`voucher`,`location`,`limit`,`offset`
- `id=list of ids` return only the measurement or measurements having the id or ids provided (ex `id=1,2,3,10`)
- `taxon=list of ids or names` return only the measurements related to the Taxons, both direct taxon measurements and indirect taxon measurements from their individuals and vouchers (ex `taxon=Aniba,Licaria`). Does not consider descendants taxons for this use `taxon_root` instead. In the example only measurements directly linked to the genus and genus level identified vouchers and individuals will be retrieved.
- `taxon_root=list of ids or names` similar to `taxon`, but get also measurements for descendants taxons of the informed query (ex `taxon=Lauraceae` will get measurements linked to Lauraceae and any taxon that belongs to it;
- `dataset=list of ids` return only the  measurements belonging to the datasets informed (ex `dataset=1,2`) - allows to get all data from a dataset.
- `trait=list of ids or export_names` return only the  measurements for the traits informed (ex `trait=DBH,DBHpom` or `dataset=2?trait=DBH`) - allows to get data for a particular trait
- `individual=list of individual ids` return only the  measurements for the individual ids informed (ex `individual=1000,1200`)
- `voucher=list of voucher ids` return only the  measurements for the voucher ids informed (ex `voucher=345,321`)
- `location=list of voucher ids` return only measurements for the locations ids informed (ex `location=4,321`)- does not retrieve measurements for individuals and vouchers in those locations, only measured locations, like plot soil surveys data.
- `limit` and `offset` are SQL statements to limit the amount of data when trying to download a large number of measurements, as the request may fail due to memory constraints. See [Common endpoints](#endpoint_common).



#### Fields obtained with 'simple' option
  - `id` - the id of the measurement in the measurements table (a local database id)
  - `measured_type` - the measured object, one of Individual, Location, Taxon or Voucher
  - `measured_id` - the id of the measured object in the respective object table (individuals.id, locations.id, taxons.id, vouchers.id)
  - `measuredFullname` - the fullname of the measured object (for a Individual will be Location+Tag, for a Voucher Collector+Number, for a Location is name and for the Taxon its fullname)
  - `traitName` - the export_name for the trait measured
  - `valueActual` - the value for the measurement (format depends on trait type)
  - `traitUnit` - the unit of measurement for quantitative traits
  - `valueDate` - the measurement measured date
  - `datasetName` - the dataset name to which the measurement belong to



#### Addition relevant fields
  - `measuredTaxonName` - the taxon name in case the measurement is from a Taxon, Voucher or Individual
  - `measuredTaxonFamily` - the taxon Family name in case the measurement is from a Taxon, Voucher or Individual
  - `measuredProject` - the project that indirectly the measurement belongs to, if any.



#### POST format
See the **Import Data Vignette** of the [OpenDataBio-R Client](https://github.com/opendatabio/opendatabio-r) for detailed information on how to import measurements for different types of [traits](Trait-Objects#traits).
The following fields are allowed in a post API:


* `dataset=number` -  the id of the dataset where the measurement should be placed **required**
* `date=YYYY-MM-DD` - the observation date for the measurement, must be passed as YYYY-MM-DD  **required**
* `object_type=string` - either 'Individual','Location','Taxon' or 'Voucher', i.e. the object from where the measurement was taken **required**
* `object_type=number` - the id of the measured object, either (individuals.id, locations.id, taxons.id, vouchers.id) **required**
* `person=number or string` either the 'id', 'abbreviation', 'full_name' or 'email' of the person that measured the object **required**
* `trait_id=number or string` - either the id or export_name for the measurement. **required**
* `value=number, string list` - this will depend on the trait type, see tutorial **required, optional for trait type LINK**
* `link_id=number` - the id of the linked object for a Trait of type Link **required if trait type is Link**
* `bibreference=number` - the id of the BibReference for the measurement. Should be use when the measurement was taken from a publication
* `notes` - any note you whish. In same cases this is a usefull place to store measurement related information. For example, when measuring 3 leaves of a voucher, you may indicate here to which leaf the measurement belongs, leaf1, leaf2, etc. allowing to link measurements from different traits by this field.
* `duplicated` - by default, the import API will prevent duplicated measurements for the same trait, object and date; specifying `duplicated=1` will allow to import such duplicated measurements. Will assume 0 if not informed and will then check for duplicated values.


<a name="endpoint_languages"></a>
***
### Languages
The `languages` endpoint interact with the [Language](Auxiliary-Objects#user_translations) table. Their basic usage is getting a list of registered Languages to import [User Translations](Auxiliary-Objects#user_translations) like [Trait](#endpoint_traits) and TraitCategories names and descriptions.

#### GET optional parameters: `none`

#### Fields obtained
  - `id` - the id of the language in the languages table (a local database id)
  - `code` - the language string code;
  - `name` - the language name;



<a name="endpoint_locations"></a>
***
### Locations
The `locations` endpoints interact with the [locations](Core-Objects#locations) table. Their basic usage is getting a list of registered countries, cities, plots, etc, or importing new locations.


#### GET optional parameters  `id`, `parent_id`,`adm_level`, `name`, `limit`, `querytype`, `lat`, `long`,`root`
- `id=list` return only locations having the id or ids provided (ex `id=1,2,3,10`)
- `admlevel=number` return only locations for the specified level:
  - **0** for country; **1** for first division within country (province, state); **2** for second division (e.g. municipality)... up to adm_level 6 as administrative areas;
  - **99** is the code for Conservation Units;
  - **100** is the code for plots and subplots;
  - **999** for 'POINT' locations like GPS waypoints;
  - **101** for transects
- `name=string` return only locations whose name matches the search string. You may use asterisk as a wildcard. Example: `name=Manaus` or `name=*Ducke*` to find name that has the word Ducke;
- `parent_id=list` return the locations for which the direct parent is in the list (ex: parent_id=2,3)
- `root=number` number is the location `id` to search, returns the location for the specified id along with all of its descendants locations; example: find the id for Brazil and use its id as root to get all the locations within Brazil;
- `querytype` one of "exact", "parent" or "closest" and must be provided with `lat` and `long`:
    - when `querytype=exact` will find a point location that has the exact match of the `lat` and `long`;
    - when `querytype=parent` will find the most inclusive parent location within which the coordinates given by `lat` and `long` fall;
    - when `querytype=closest` will find the closest location to the coordinates given by `lat` and `long`; It will only search for closest locations having `adm_level > 99`, see above.
    - `lat` and `long` must be valid coordinates in decimal degrees (negative for South and West);
- `fields=list` specify which fields you want to get with your query. The "simple" default option will return fields 'id', 'name', 'levelName', 'geom','distance','parentName','parent_id','x','y','startx','starty','centroid_raw' and 'area'.

Notice that `id`, `search`, `parent` and `root` should probably not be combined in the same query.



#### Fields obtained with 'simple' option
  - `id` - the id of the location in the locations table (a local database id)
  - `name` - the location name;
  - `fullname` - the name with all parents (e.g. Brazil > São Paulo > Sorocaba);
  - `adm_level` - the numeric value for administrative level (0 for countries, etc);
  - `levelName` - the name of the administrative level;
  - `geom` - the [WKT](https://en.wikipedia.org/wiki/Well-known_text) representation of the location;
  - `parent_id` - id of the parent location;
  - `x` and `y` - when location is a plot (100 type) its X and Y dimension in meters
  - `startx` and `starty` - when location is a subplot the X and Y start position in relation to the 0,0 coordinate of the parent plot location
  - `centroid_raw` - when location is a polygon, then its centroid WKT representation
  - `area` - when location is a polygon its area in squared meters.



#### POST format
See the **Import Data Vignette** of the [OpenDataBio-R Client](https://github.com/opendatabio/opendatabio-r) for detailed information on how to import [Locations](Core-Objects#locations)



* `name` -  the location name (parent+name must be unique in the database) - **required**
* `adm_level` - must be numeric, see [above](#locations_get) - **required**
* `altitude` - in meters
* `datum` - e.g. WSG84, the default;
* `parent` - either the id or name of the parent location. The API will detect the parent based on the informed geometry and validate the informed parent. *detected parent has priority* if informed is different. If not given, the importation process will attempt to guess it unless you pass this parameter as 0.
* `uc` - same as parent as Conservations Units are locations that transcends administrative boundaries. *detected UC have priority* if different from UC id informed in this field; If not given, the importation process will attempt to guess it unless you pass this parameter as 0.
* geometry use either: **required**
  * `geom` for a WKT representation of the geometry, POLYGON or POINT allowed;
  * `lat` and `long` for latitude and longitude in decimal degrees (use negative numbers for south/west).
* when location is plot (type 100), optional fields are:
  * dimensions: `x`, `y` for the plot (defines the Cartesian coordinates)
  * `startx` and `starty` cartesian coordinates within parent plot when subplot;
* `notes` - any note you whish to add to your location;


<a name="endpoint_persons"></a>
***
### Persons
The `persons` endpoint interact*** with the [Person](Auxiliary-Objects#persons) table. The basic usage is getting a list of registered people (plat and voucher collectors, taxonomic specialists or database users), with full name, abbreviation and e-mail.


#### GET optional parameters  `id`, `search`,`abbrev`, `email`, `limit`, `offset`
- `id=list` return only persons having the id or ids provided (ex `id=1,2,3,10`)
- `name=string` - return people whose name matches the specified string. You may use asterisk as a wildcard. Ex: `name=*ducke*`
- `abbrev = string`, return people whose abbreviation matches the specified string. You may use asterisk as a wildcard.
- `email=string`, return people whose e-mail matches the specified string. You may use asterisk as a wildcard.
- `search=string`, return people whose name, abbreviation or e-mail matches the specified string. You may use asterisk as a wildcard.
- `limit` and `offset` are SQL statements to limit the amount of data when trying to download a large number of measurements, as the request may fail due to memory constraints. See [Common endpoints](endpoint_common).



#### Fields obtained with 'simple' option
- `id` - the id of the person in the persons table (a local database id)
- `full_name` - the person name;
- `abbreviation` - the person name (this are UNIQUE values in a OpenDataBio database)
- `email` - the email, if registered or person is user
- `institution` - the persons institution, if registered
- `notes` - any registered notes;
- `biocollection` - the name of the Biological Collection (Biocollections, etc) that the person is associated with; not included in simple)



#### POST format


See the **Import Data Vignette** of the [OpenDataBio-R Client](https://github.com/opendatabio/opendatabio-r) for detailed information on how to import [Persons](Auxiliary-Objects#persons).
The following fields are allowed in a post API:


- `full_name` - person full name, **required**
- `abbreviation` - abbreviated name, as used by the person in publications, as collector, etc. (if left blank, a standard abbreviation will be generated using the full_name);
- `email` - an email address,
- `institution` - to which institution this person is associated;
- `biocollection` - name or acronym of the Biocollection to which this person is associated.


<a name="endpoint_individuals"></a>
***
### Individuals
The `individuals` endpoints interact with the [Individual](Core-Objects#individuals) table. Their basic usage is getting a list of individuals or import new ones.



#### GET optional parameters `id`, `location`, `taxon`, `tag`,`project`, `dataset`, `limit`, `offset`
- `id=number or list` - return individuals that have id or ids ex: `id=2345,345`
- `location=mixed` - return by location id or name or ids or names ex: `location=24,25,26` `location=Parcela 25ha` of the locations where the individuals
- `location_root` - same as location but return  also from the descendants of the locations informed
- `taxon=mixed` - the id or ids, or canonicalName taxon names (fullnames) ex: `taxon=Aniba,Ocotea guianensis,Licaria cannela tenuicarpa` or `taxon=456,789,3,4`
- `taxon_root` - same as taxon but return also all the individuals identified as any of the descendants of the taxons informed
- `project=mixed` - the id or ids or names of the project, ex: `project=3` or `project=OpenDataBio`
- `tag=list` - one or more individual tag number\/code, ex: `tag=individuala1,2345,2345A`
- `dataset=mixed` - the id or ids or names of the datasets, return individuals having measurements in the datasets informed
- `limit` and `offset` are SQL statements to limit the amount of data when trying to download a large number of individuals, as the request may fail due to memory constraints. See [Common endpoints](endpoint_common).



Notice that all search fields (taxon, location and project) may be specified as names (eg, "taxon=Euterpe edulis") or as database ids. If a list is specified for one of these fields, all items of the list must be of the same type, i.e. you cannot search for 'taxon=Euterpe,24'. Also, location and taxon have priority over location_root and taxon_root if both informed.



#### Fields obtained with 'simple' option
- `id` - the id of the individual in the individuals table (a local database id)
- `fullName` - the individual fullName is a unique identifier composed of `LocationName+Tag`
- `taxonName`- the current taxonomic identification of the individual in a canonicalName format (no authors) or "unidentified"
- `taxonFamily` - the taxon Family level parent
- `location_id` - the where the individual is located for link with location data
- `locationName` - the location name
- `locationParentName` - the parent location, to simplify when location is subplot
- `tag` - the individual number code on an physical tag on a living individual in the field
- `date` - the date the individual was marked and tagged
- `relativePosition` - if individual has X and Y cartesian coordinates within location, their [WKT](https://en.wikipedia.org/wiki/Well-known_text) representation, ex: POINT(X,Y)
- `xInParentLocation` - x cartesian position in relation to parent location when individual location is subplot
- `yInParentLocation` - y cartesian position in relation to parent location when individual location is subplot
- `notes` - any note
- `projectName` - the projectName the individual belongs to

#### Additional relevant fields
- `angle` and `distance` - azimuth in degrees and distance in meteres of the individual position in relation to its location (which in this case would be a geographical POINT). This may be used in some specific individual inventory surveys

<br>

#### POST format

See the **Import Data Vignette** of the [OpenDataBio-R Client](https://github.com/opendatabio/opendatabio-r) for detailed information on how to import [Individuals](Core-Objects#individuals).
The following fields are allowed in a post API:

* `collector=mixed` - **required**  - persons  'id', 'abbreviation', 'full_name', 'email'; if multiple persons, separate values in your list with  pipe `|` or `;` because commas may be present within names. Main collector is the first on the list;
* `tag=string` -  **required** -  the individual number or code (if the individual identifier is as MainCollector+Number, this is the field for Number);
* `project=mixed` - **required** - name or id or the Project;
* `date=YYYY-MM-DD or array` - the date the individual was recorded/tagged, for historical records you may inform an incomplete string in the form "1888-05-NA" or "1888-NA-NA" when day and/or month are unknown. You may also inform as an array in the form "date={ 'year' : 1888, 'month': 5}". OpenDataBio deals with incomplete dates, see the [IncompleteDate Model](Auxiliary-Objects#incompletedate). At least `year` is **required**.
* `notes` - any annotation for the Individual;

Location fields (one or multiple locations may be informed for the individual). Possible fields are:
  * `location` - the Individual's location name or id **required if longitude and latitude are not informed**
  * `latitude` and `longitude`- geographical coordinates in decimal degrees; **required if location is not informed**
  * `altitude` - the Individual location elevation (altitude) in meters above see level;
  * `location_notes` - any note for the individual location;
  * `location_date_time` - if different than the individual's date, a complete date or a date+time value for the individual first location. Mandatory for multiple locations;
  * `x` - if location is of Plot type, the x coordinate of the individual in the location;
  * `y` - if location is of Plot type, the y coordinate of the individual in the location;
  * `distance` - if location is of POINT type, the individual distance in meters from the location;
  * `angle` - if location is of POINT type, the individual azimuth (angle) from the location;

Identification fields. Identification is not mandatory, and may be informed in two different ways: (1) self identification - the individual may have its own identification; or (2), other identification - the identification is the same as that of another individual (for example, from an individual having a voucher in some biocollection).
  1. The following fields may be used for (self) individual's identification (taxon and identifier are mandatory):
    * `taxon=mixed` - name or id of the identified taxon, e.g. 'Ocotea delicata' or its id
    * `identifier=mixed` - 'id', 'abbreviation', 'full_name' or  'email' of the person responsible for the taxonomic identification;
    * `identification_date` or `identification_date_year`, `identification_date_month`, and/or `identification_date_day` - complete or incomplete. If empty, the individual's date is used;
    * `modifier` - name or number for the identification modifier. Possible values 's.s.'=1, 's.l.'=2, 'cf.'=3, 'aff.'=4, 'vel aff.'=5, defaults to 0 (none).
    * `identification_notes` - any identification notes
    * `identification_based_on_biocollection` - the biocollection name or id if the identification is based on a reference specimen deposited in an biocollection
    * `identification_based_on_biocollection_id` - only fill if `identification_based_on_biocollection` is present;
  2. If the identification is other:
    * `identification_individual` - id or fullname of the Individual having the identification.

If the Individual has Vouchers **with the same** Collectors, Date and CollectorNumber (Tag) as those of the Individual, the following fields and options allow to store the vouchers while importing the Individual record (alternatively, you may import voucher after importing individuals using the [Voucher EndPoint](#endpoint_vouchers). Vouchers for the individual may be informed in two ways:
1. As separate string fields:
  * `biocollection` - A string with a single value or a comma separated list of values. Values may be the `id` or `acronym` values of the [Biocollection Model](management_objects#biocollections). Ex:  "{ 'biocollection' : 'INPA;MO;NY'}" or "{ 'biocollection' : '1,10,20'}";
  * `biocollection_number` - A string with a single value or a comma separated list of values with the BiocollectionNumber for the Individual Voucher. If a list, then must have the same number of values as `biocollection`;
  * `biocollection_type` - A string with a single numeric code value or a comma separated list of values for Nomenclatural Type for the Individual Vouchers. The default value is 0 (Not a Type). See [nomenclatural types list](API#nomenclaturaltypes).
2. AS a single field `biocollection` containing an array with each element having the fields above for a single Biocollection:
  "{
    { 'biocollection_code' : 'INPA', 'biocollection_number' : 59786, 'biocollection_type' : 0},
    { 'biocollection_code' : 'MG', 'biocollection_number' : 34567, 'biocollection_type' : 0}
  }"



<a name="endpoint_projects"></a>
***
### Projects EndPoint
The `projects` endpoint interact with the [projects](management_objects#projects) table. The basic usage is getting the registered Projects, but not the individuals nor vouchers in the projects, only counts and some basic info. Usefull for getting project_ids for importing Individuals and Vouchers.



#### GET optional parameters  `id` only
- `id=list` return only projects having the id or ids provided (ex `id=1,2,3,10`)



#### Fields obtained with 'simple' option
  - `id` - the id of the Project in the projects table (a local database id)
  - `fullname` - project name
  - `privacyLevel` - the access level for individuals and vouchers in Project
  - `contactEmail` - the project administrator email
  - `individuals_count` - the number of individuals in the project
  - `vouchers_count` - the number of vouchers in the project


<a name="endpoint_taxons"></a>
***
### Taxons
The `taxons` endpoint interact with the [taxons](Core-Objects#taxons)  table. The basic usage is getting a list of registered taxonomic names or importing new taxonomic names.



#### GET optional parameters  `root`, `id`, `name`,`level`, `valid`, `external`, `limit`, `offset`
- `id=list` return only taxons having the id or ids provided (ex `id=1,2,3,10`)
- `name=search` returns only taxons with fullname (no authors) matching the search string. You may use asterisk as a wildcard.
- `root=number` returns the taxon for the specified id along with all of its descendants
- `level=number` return only taxons for the specified level.
- `valid=1` return only valid names
- `external=1` return the Tropicos, IPNI, MycoBank, ZOOBANK or GBIF reference numbers. You need to specify `externalrefs` in the field list to return them!
- `limit` and `offset` are SQL statements to limit the amount. See [Common endpoints](endpoint_common).

Notice that `id`, `name` and `root` should not be combined.

Taxon level options are:

|Level |Level |Level  |Level|
|:----------------------------------------|:--------------------------------|:----------------------------------|:----------------------------------------|
|<span style='color:red'>-100</span> for ` clade `                       |60 for ` cl., class `            |120 for ` fam., family `           |210 for ` section, sp., spec., species ` |
|0 for ` kingdom `                        |70 for ` subcl., subclass `      |130 for ` subfam., subfamily `     |220 for ` subsp., subspecies `           |
|10 for ` subkingd. `                     |80 for ` superord., superorder ` |150 for ` tr., tribe `             |240 for ` var., variety `                |
|30 for ` div., phyl., phylum, division ` |90 for ` ord., order `           |180 for ` gen., genus `            |270 for ` f., fo., form `                |
|40 for ` subdiv. `                       |100 for ` subord. `              |190 for ` subg., subgenus, sect. ` |                                         |



#### Fields obtained with 'simple' option
- `id` - the id of the Taxon in the taxons table (a local database id)
- `fullname` - the 'canonicalName' or full taxonomic name without authors (i.e. including genus name and epithet for species name)
- `level` - the numeric value of the taxon rank
- `levelName` - the string value of the taxon rank
- `authorSimple` - the taxon authorship, will be a Person name if the taxon is unpublished.
- `bibreferenceSimple` - unified bibliographic reference (i.e. either the short format or an extract of the bibtext reference assinged). Only reference for taxon description, aditional taxon linked references can be extracted with the [BibReference API](API#endpoint_bibreferences).
- `valid` - if valid or invalid
- `senior_id` - if invalid the id of the valid synonym that the taxon belongs to
- `parent_id` - the id of the parent taxon
- `author_id` - the id of the person that defined the taxon for unpublished names (having an author_id means the taxon is unpublished)
- `notes` - any note the taxon may have
- `family` - the family name if taxon is family or lower rank.
- `externalrefs` - the Tropicos, IPNI, MycoBank, ZOOBANK or GBIF reference numbers



#### POST format
See the **Import Data Vignette** of the [OpenDataBio-R Client](https://github.com/opendatabio/opendatabio-r) for detailed information on how to import [Taxons](Core-Objects#taxons).
The following fields are allowed in a post API:

* `name` - taxon full name **required**
* `level` - may be the numeric id or a string describing the level (see above)
* `parent_name` - the taxon's parent full name
* `bibreference` - the bibliographic reference in which the taxon was published;
* `author` - the taxon author's name;
* `author_id` - the person id **required for unpublished names**
* `valid` - boolean, true if this taxon name is valid;
* `mobot` - Tropicos.org id for this taxon
* `ipni` - IPNI id for this taxon
* `mycobank` - MycoBank id for this taxon
* `zoobank` - ZOOBANK id for this taxon

Note. Only name is required because the API will search the external nomenclatural databases and retrieve level, parent, author and the found reference ids. If the check fails, the record will not be imported


<a name="endpoint_traits"></a>
***
### Traits
The `traits` endpoint interact with the [Trait](Trait-Objects#traits) table. The basic usage is getting a list of variables and variables categories for importing [Measurements](Trait-Objects#measurements). The POST method allows you to import, but it is recommended that the Web Interface is used to store traits in the database, because the interface facilitates avoiding creating a redundant trait.

#### GET optional parameters  `id`, `name`,`categories` `limit`,`offset`
- `id=list` return only traits having the id or ids provided (ex `id=1,2,3,10`);
- `name=string` return only traits having the `export_name` as indicated (ex `name=DBH`)
- `language=mixed` return name and descriptions of both trait and categories in the specified language. Values may be 'language_id', 'language_code' or 'language_name';
- `bibreference=boolean` - if true, include the [BibReference](Auxiliary-Objects#bibreferences) associated with the trait in the results;
- `limit` and `offset` are SQL statements to limit the amount. See [Common endpoints](endpoint_common).


#### Fields obtained with 'simple' option
- `id` - the id of the Trait in the odbtraits table (a local database id)
- `type` - the numeric code defining the Trait type
- `typename` - the name of the Trait type
- `export_name` - the export name value
- `unit` - the unit of measurement for Quantitative traits
- `range_min` - the minimum allowed value for Quantitative traits
- `range_max` - the maximum allowed value for Quantitative traits
- `link_type` - if Link type trait, the class of the object the trait links to (currently only Taxon)
- `name` - the trait name in the language requested or in the default language
- `description` - the trait description in the language requested or in the default language
- `value_length` - the length of values allowed for Spectral trait types
- `objects` - the types of object the trait may be used for, separated by pipe '|'
- `categories` - each category is given for Categorical and Ordinal traits, with the following fields (the category `id`, `name`, `description` and `rank`). Ranks are meaningfull only for ORDINAL traits, but reported for all categorical traits.


#### POST format
The POST method allows you to batch import traits into the database and is designed for transfering data to OpenDataBio from other systems, including Trait Ontologies databases.  When entering few traits, it is **strongly recommended** that you enter traits one by one using the Web Interface form and then use the GET option for ids to import measurements.
  1. As noted under the [Trait Model](Trait-Objects#traits) description, it is important that one really checks whether a needed Trait is not already in the DataBase to avoid multiplication of redundant traits. The Web Interface facilitates this process and in a batch process that involves multiple languages, trait name comparsions become too complicated. OpenDataBio only checks for identifical `export_name`, which must be unique within the database. Note, however, that Traits should also be as specific as possible for detailed metadata annotations.  
  1. Traits use [User Translations](Auxiliary-Objects#user_translations) for names and descriptions, allowing a multilanguage, so names and descriptions of Trait and Trait Categories together with the respective language key (or id)

See also the **Import Data Vignette** of the [OpenDataBio-R Client](https://github.com/opendatabio/opendatabio-r) for detailed information on how to import [Traits](Trait-Objects#traits).
The following fields are allowed in a post API:


**Fields for the POST method**:
* `export_name=string` - a short name for the Trait, which will be used during data exports, are more easily used in trait selection inputs in the web-interface and also during data analyses outside OpenDataBio. **Export names must be unique** and have no translation. Short and [CamelCase](https://en.wikipedia.org/wiki/Camel_case) export names are recommended. Avoid diacritics (accents), special characters, dots and even white-spaces. **required**
* `type=number` - a numeric code specifying the trait type. See the [Trait Model](Trait-Objects#traits) for a full list. **required**
* `objects=list` - a list of the [Core objects](Core-Objects) the trait is allowed to be measured for. Possible values are 'Individual', 'Voucher', 'Location' and/or 'Taxon', singular and case sensitive. Ex:  "{'object': 'Individual,Voucher'}"; **required**
* `name=json` - see translations below; **required**
* `description=json` - see translations below; **required**
* **Trait specific fields**:
  * `units=string` - required for quantitative traits only (the unit o measurement). recommened to use English standards and full words, e.g. ('meters' instead of just 'm')
  * `range_min=number` - optional for quantitative traits. specify the minimum value allowed for a [Measurement](Trait-Objects#measurements).
  * `range_max=number` - optional for quantitative. maximum allowed value for the trait.
  * `categories=json` - **required for categorical and ordinal traits**; see translations below
  * `wavenumber_min` and `wavenumber_max` - **required for spectral traits** = minimum and maximum WaveNumber within which the 'value_length' absorbance or reflectance values are equally distributed. May be informed in `range_min` and `range_max`, priority for prefix wavenumber over range if both informed.
  * `value_length` - **required for spectral traits** = number of values in spectrum
  * `link_type`- **required for Link traits** - the class of link type, fullname or basename:  eg. 'Taxon' or 'App\Models\Taxon'.
* `bibreference=number` - the id of a [BibReference](Auxiliary-Objects#bibreferences) object from which the trait definition and or trait categories are based upon.

#### translations
* Fields `name`, `description` must have the following structure to account for  [User Translations](Auxiliary-Objects#user_translations). They should be a list with the language as 'keys'. For example a `name` field may be informed as:
  * using the Language code as keys: `{"en":"Diameter at Breast Height","pt":"Di\u00e2metro a Altura do Peito"}`
  * or using the Language ids as keys:  `{"1":"Diameter at Breast Height","2":"Di\u00e2metro a Altura do Peito"}`.
  * or using the Language names as keys:  `{"English":"Diameter at Breast Height","Portuguese":"Di\u00e2metro a Altura do Peito"}`.
* Field `categories` must include for each category+rank+lang the following fields:
  * `lang=mixed` - the id, code or name of the language of the translation, **required**
  * `name=string` - the translated category name  **required** (name+rank+lang must be unique)
  * `rank=number` - the rank for ordinal traits; for non-ordinal, rank is important to indicate the same category across languages, so may just use 1 to number of categories in the order you want. **required**
  * `description=string` - optional for categories, a definition of the category.
  * Example:
    ```
      [
        {"lang":"en","rank":1,"name":"small","description":"smaller than 1 cm"},
        {"lang":"pt","rank":1,"name":"pequeno","description":"menor que 1 cm"}
        {"lang":"en","rank":1,"name":"big","description":"bigger than 10 cm"},
        {"lang":"pt","rank":1,"name":"grande","description":"maior que 10 cm"},
      ]
    ```
* Valid languages may be retrieved with the [Language API](#endpoint_languages).



<a name="endpoint_vouchers"></a>
***
### Vouchers
The `vouchers` endpoints interact with the [Voucher](Core-Objects#vouchers) table. Their basic usage is getting and importing voucher specimens into the database.


#### GET parameters `id`, `number`, `individual`, `location`, `collector`, `taxon`, `project`, `dataset`, `limit`, `offset`
* `id=list` return only vouchers having the id or ids provided (ex `id=1,2,3,10`)
* `number=string` returns only vouchers for the informed collector number (but is a string and may contain non-numeric codes)
* `collector=mixed` one of id or ids or abbreviations, returns only vouchers for the informed **main collector**
* `project=mixed` one of ids or names, returns only the vouchers for informed project.
* `location=mixed` one of ids or names; (1) if `individual_tag` is also requested returns only vouchers for those individuals (or use *"individual=\*"* to get all vouchers for any individual collected at the location); (2) if `individual` and `individual_tag` are not informed, then returns vouchers linked to locations and to the individuals at the locations.
* `location_root=mixed` - same as location, but include also the vouchers for the descendants of the locations informed. e.g. *"location_root=Manaus"* to get any voucher collected within the Manaus administrative area;
* `individual=mixed` either a individual_id or a list of ids, or `*` - returns only vouchers for the informed individuals; when *"individual=\*"* then location must be informed, see above;
* `individual_tag` - to inform `individual_tag` you must inform `location`, if location is not informed, this will be ignored;
* `taxon=mixed` one of ids or names, returns only vouchers for the informed taxons. This could be either vouchers referred as parent of the requested taxon or vouchers of individuals of the requested taxons.
* `taxon_root=mixed` - same as taxon, but will include in the return also the vouchers for the descendants of the taxons informed. e.g. *"taxon_root=Lauraceae"* to get any Lauraceae voucher;
* `dataset=list` - id or ids list, return all vouchers with measurements in the informed datasets; this will not include vouchers of measured individuals in the dataset, only vouchers measured directly.
* `with_identification=boolean` - if set to 1, include the identification object in the result.
* `with_biocollections=boolean` - must be used with `fields=all`, if set to true (1), returns the related biocollections table
* `with_collectorsa=boolean` - must be used with `fields=all`, if set to true (1), returns the related collectors table


Notice that some search fields (taxon, location, project and collector) may be specified as names - abbreviation, fullnames and emails in the case of collector - (eg, "taxon=Euterpe edulis") or as database ids. If a list is specified for one of these fields, **all items of the list must be of the same type**.


#### Fields obtained with 'simple' option
* `id` - the Voucher id in the vouchers table (local database id)
* `fullname` - the unique database combination collectorMain+Number;
* `collectorMain` - the person associated with number that gives the voucher fullname.
* `number` -  the **collector number**, that tracks the history of individual collectors activity
* `collectorsAll` - a pipe '|' delimited list of all collectors
* `date` - the date the voucher was collected
* `taxonName` - the identification taxon name
* `taxonNameWithAuthor` - same with authorship (in case of unpublished names, authors are persons)
* `taxonFamily` -the taxon family level parent
* `identificationDate` - the date the voucher was given the taxonName
* `identifiedBy` - person giving the taxonName
* `identificationNotes` - person note regarding the application of the taxonName
* `depositedAt` - a point-comma ';' delimited list of collections where the record has a physical specimen deposited (collection # and type kind are given when available)
* `isType` - if the voucher is a nomenclatural type, the kind of type or just 'Type', or else, 'Not a Type'
* `locationName` - the location name where the voucher is assigned
* `locationFullname` - the full location hierarchy, separated by ">"
* `longitudeDecimalDegrees` - the longitude in decimal degrees
* `latitudeDecimalDegrees` - the latitude in decimal degrees
* `coordinatesPrecision` - whether the coordinates are from "Point", when location is of POINT type; "Plot" when location is of PLOT type, or the "Centroid" of the `locationName` when location is a larger area;
* `coordinatesWKT` - coordinates in [WKT](https://en.wikipedia.org/wiki/Well-known_text) representation; e.g. 'POINT(long lat)'
* `individualTag` - if parent is individual, then the Individual fullname, which is the combination of Location+FieldTag
* `projectName` - the project the voucher belongs too
* `notes` - a note that has been associated with the voucher (does not include notes assigned as measurements values, these should be gathered through the [Measurements API Endpoints](API#measurements).



#### POST format
See the **Import Data Vignette** of the [OpenDataBio-R Client](https://github.com/opendatabio/opendatabio-r) for detailed information on how to import [Vouchers](Core-Objects#vouchers). The main validation restriction is that `main_collector+number` must be unique in the database and duplicated values are not allowed.
The following fields are allowed in a post API:

* `individual=mixed` - the numeric id or fullname of the Individual the Voucher belongs to **required**;
* `biocollection=mixed` - the id, name or acronym of a registered  [Biocollection](management_objects#biocollections) the Voucher belongs to **required**;
* `biocollection_type=mixed` - the name or numeric code of numeric representing the kind of nomenclatural type the voucher represents in the Biocollection. If not informed, defaults to 0 = 'Not a Type'. See [nomenclatural types list](API#nomenclaturaltypes) for a full list of options;
* `biocollection_number=mixed` - the alpha numeric code of the voucher in the biocollection;
* `number=string` - the main *collector number*  -only if different from the *tag* value of the Individual the voucher belongs to;
* `collector=mixed` - either ids or abbreviations of persons. When multiple values are informed the first is the *main collector*. Only if different from the Individual collectors list;
* `date=YYYY-MM-DD or array` - needed only if, with collector and number, different from Individual values.  Date may be an [IncompleteDate Model](Auxiliary-Objects#incompletedate).
* `project=number` - inherits the project the Individual belongs too, but you may provide a different project if needed
* `notes=string` - any text note to add to the voucher.

<a name='nomenclaturaltypes'></a>

|**Nomenclatural types numeric does**               |
|:-----------------|:------------------|
|NotType :  0      |Isosyntype :  8    |
|Type :  1         |Neotype :  9       |
|Holotype :  2     |Epitype :  10      |
|Isotype :  3      |Isoepitype :  11   |
|Paratype :  4     |Cultivartype :  12 |
|Lectotype :  5    |Clonotype :  13    |
|Isolectotype :  6 |Topotype :  14     |
|Syntype :  7      |Phototype :  15    |



<a name="endpoint_jobs"></a>


### User Jobs
| [userjobs](#endpoint_jobs) | `GET` | Lists user Jobs | `id`, `status`|

The `jobs` endpoints interact with the [[UserJob|Interface-and-data-access]] table. Their basic usage is getting a list of submitted data import jobs, along with a status message and logs.

- Possible fields: id - database id; status - the status of the job: "Submitted", "Processing", "Success", "Failed" or "Cancelled"; `dispatcher` - the type of the job, eg, ImportTaxons; `log` - the job log messages, usually indicating whether the resources were successfuly imported, or whether errors occurred; others.

####  GET parameters:

- `status=string` return only jobs for the specified status


<a name="possible_errors"></a>


***
### Possible errors

This should be an extensive list of error codes that you can receive while using the API. If you receive any other error code, please file a [bug report](https://github.com/opendatabio/opendatabio/issues)!


- Error 401 - Unauthenticated. Currently not implemented. You may receive this error if you attempt to access some protected resources but didn't provide an API token.
- Error 403 - Unauthorized. You may receive this error if you attempt to import or edit some protected resources, and either didn't provide an API token or your user does not have enough privileges.
- Error 404 - The resource you attempted to see is not available. Note that you *can* receive this code if your user is not allowed to see a given resource.
- Error 413 - Request Entity Too Large. You may be attempting to send a very large import, in which case you might want to break it down in smaller pieces.
- Error 429 - Too many attempts. Wait one minute and try again.
- Error 500 - Internal server error. This indicates a problem with the server code. Please file a [bug report](https://github.com/opendatabio/opendatabio/issues) with details of your request.
