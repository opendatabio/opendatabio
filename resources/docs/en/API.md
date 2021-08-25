- [OpenDataBio API and R client](#)
- [API Tester](API-tester)
- [API EndPoints](#endpoints)
  - [BibReferences EndPoint](#endpoint_bibreferences)
  - [Biocollections EndPoint](#endpoint_biocollections)
  - [Datasets EndPoint](#endpoint_datasets)
  - [Individuals EndPoint](#endpoint_individuals)
  - [Language EndPoint](#endpoint_languages)
  - [Locations EndPoint](#endpoint_locations)
  - [Measurements EndPoint](#endpoint_measurements)
  - [Persons EndPoint](#endpoint_persons)
  - [Projects EndPoint](#endpoint_projects)
  - [Taxons EndPoint](#endpoint_taxons)
  - [Traits EndPoint](#endpoint_traits)
  - [Vouchers EndPoint](#endpoint_vouchers)
  - [UserJobs EndPoint](#endpoint_jobs)
- [Possible errors](#possible_errors)


# OpenDataBio API and R Client

The OpenDataBio API ([Application Programming Interface -API](https://en.wikipedia.org/wiki/API)) allows to programatically interact with an OpenDataBio database for exporting and importing data. The [OpenDataBio R package](https://github.com/opendatabio/opendatabio-r) is a  **client ** for this API, allowing the interaction with the data repository directly from R and illustrating the API capabilities so that other clients can be easily built.


The API allows easy querying of the database and easy data importation through a [REST](https://en.wikipedia.org/wiki/Representational_state_transfer) inspired interface. All API requests and responses are formatted in [JSON](https://en.wikipedia.org/wiki/JSON). The import and export tools of the web interface use the implemented APIs, so data exports and imports have standard formats.

<a name="api_call"></a>
***
# API call

A simple call to the OpenDataBio API has four independent pieces:

1. **HTTP verb** -  either `GET` for exports, or  `POST` for imports, related to the action that you want to accomplish. Other verbs, `PUT`, `DELETE`, etc are not implemenented and deletes and updates can only be made through the interface and have several restrictions for data preservation.
1. **base URL** - is the URL used to access your OpenDataBio server, plus `/api/v0`. For, example, `http://opendatabio.inpa.gov.br/opendatabio/api/v0`
1. **[endpoint](#endpoints)** - represents the object or collection of objects that you want to access, for example, for querying taxonomic names, the endpoint is "taxons"
1. **request parameters** - represent filtering and processing that should be done with the objects, and are represented in the API call after a question mark. For example, to retrieve only valid taxonomic names (non synonyms) end the request with `?valid=1`.

The API call exemplified above can be entered in a browser, by using the full URL `http://opendatabio.inpa.gov.br/api/v0/taxons?valid=1`. When using the OpenDataBio-R client for API calls, this example would be called as  `odb_get_taxons(list(valid=1))`.

<a name="api_authentication"></a>
***
# API Authentication

Authentication is done using an `API token`, that can be found under your user profile on the web interface. Authentication is required, so only registered users can access the API. The token is assigned to a single database user, and should not be shared, exposed, e-mailed or stored in version controls. To authenticate against the OpenDataBio API, use the token in the "Authorization" header of the API request. When using the R client, pass the token to the `odb_config` function `cfg = odb_config(token="your-token-here")`.


Users will only have access to the data for which the user has permission and to, or any data with public access in the database, which by default includes locations, taxons, bibliographic references, persons and traits. Measurements, individuals, and Vouchers access depends on permissions understood by the users token.

<a name="api_versions"></a>
***
# API versions

The OpenDataBio API follows its own version number. This means that the client can expect to use the same code and get the same answers regardless of which OpenDataBio version that the server is running. All changes done within the same API version should be backward compatible. Our API versioning is handled by the URL, so to ask for a specific API version, use the version number between the base URL and endpoint, such as `http://opendb.inpa.gov.br/api/v0/taxons`


<a name="endpoints"></a>
***
# API EndPoints - v0 - unstable API version

The API version 0 (v0) is an unstable version. The first stable version will be the API version 1.

<a name="endpoint_common"></a>
## Common parameters

Most Endpoints accept all the common parameters described below. They may also accept specific parameters detailed below each endpoint. Note that most conditions are combined with an "AND" operator, when multiple parameters are sent.
- `id=number` return only the specified resource. This can be a list joined by commas. Example: `api/locations?id=1,50,124`
- `limit`: the number of items that should be returned (must be greater than 0). Example: `api/taxons?limit=10`
- `offset`: the initial record to extract, to be used with limit when trying to download a large amount of data. Example: `api/taxons?offset=10000&limit=10000` returns 10K records starting from the 10K position of the current query.
- `fields`: the fields that should be returned with object. This can be a list joined by commmas. The order in which fields are specified is ignored. See specific below for fields supported by each endpoint. There are two special words for the fields parameters: "simple" (default), which returns a reasonable collection of fields, and "all", which returns all fields. `fields=all` should be used with care, as it may return sub-objects for each object. Most objects accept the `created_at` and `updated_at` fields, which return dates. Example: `api/taxons?fields=id,fullname,valid`

- Notice that some parameters accept an asterisk as wildcard, so `api/taxons?name=Euterpe` will return taxons with name exactly as "Euterpe", while `api/taxons?name=Eut*` will return names starting with "Eut".

## Quick reference for GET verb

| Endpoint |  HTTP verb | Description | Possible parameters |
| --- | --- | --- | --- |
| [/](#endpoint_test) | `GET` | Tests your access | none |
| [bibreferences](#endpoint_bibreferences) | `GET` | Lists of bibliographic references | `id`, `bibkey` |
| [biocollections](#endpoint_biocollections) | `GET` | List of Biocollections and other vouchers Repositories |  `id` only |
| [datasets](#endpoint_datasets) | `GET` | Lists registered datasets | `id` only|
| [individuals](#endpoint_individuals) | `GET` | Lists registered individuals |`id`, `location`, `taxon`, `tag`,`project`, `dataset`, `limit`, `offset`|
| [languages](#endpoint_languages) | `GET` | Lists registered languages | |
| [measurements](#endpoint_measurements) | `GET` | Lists Measurements | `id`, `taxon`,`dataset`,`trait`,`individual`,`voucher`,`location`,`limit`,`offset`|
| [locations](#endpoint_locations) | `GET` | Lists locations | `root`, `id`, `parent_id`,`adm_level`, `name`, `limit`, `querytype`, `lat`, `long`,`project`,`dataset`,`limit`,`offset`|
| [persons](#endpoint_persons) | `GET` | Lists registered people |`id`, `search`, `name`, `abbrev`, `email`, `limit`,`offset`|
| [projects](#endpoint_projects) | `GET` | Lists registered projects | `id` only|
| [taxons](#endpoint_taxons) | `GET` | Lists taxonomic names |`root`, `id`, `name`,`level`, `valid`, `external`, `project`,`dataset`,`limit`,`offset`  |
| [traits](#endpoint_traits) | `GET` | Lists variables (traits) list |`id`, `name`,`limit`,`offset`|
| [vouchers](#endpoint_vouchers) | `GET` | Lists registered voucher specimens | `id`, `number`, `individual`, `location`, `collector`, `taxon`, `project`, `dataset` |
| [userjobs](#endpoint_jobs) | `GET` | Lists user Jobs | `id`, `status`|



## Imports or POST verbs

The available `POST` APIs are listed below and under each endpoint. Batch imports of  [Bibliographic References](Auxiliary-Objects#bibreferences) and [MediaFiles](Auxiliary-Objects#mediafiles) are possible only through the web interface, otherwise you may use the R Client. A *Import Data Tutorial* is available as vignette of the [OpenDataBio-R Client](https://github.com/opendatabio/opendatabio-r).

**The Import Data tools of the Web Interface use the POST API methods**

| Endpoint |  HTTP verb | Description |
| --- | --- | --- |
| [biocollections](#endpoint_biocollections) | `POST` | Import BioCollections |
| [individuals](#endpoint_individuals) | `POST` | Import individuals |
| [locations](#endpoint_locations) | `POST` | Import locations |
| [measurements](#endpoint_measurements) | `POST` | Import Measurements to Datasets |
| [persons](#endpoint_persons) | `POST` | Imports a list of people |
| [traits](#endpoint_traits) | `POST` | Import traits |
| [taxons](#endpoint_taxons)  | `POST` | Imports taxonomic names |
| [vouchers](#endpoint_vouchers)  | `POST` | Imports voucher specimens |



<a name="endpoint_test"></a>
***
## Test EndPoint

The test endpoint may be used to perform connection tests. It returns a `Success` message and the identity of the logged in user.


<a name="endpoint_bibreferences"></a>
***
## BibReferences Endpoint
The `bibreferences` endpoint interact with the [bibreference](Auxiliary-Objects#bibreferences) table. Their basic usage is getting the registered Bibliographic References. Because this is a simple table it is just easier to get them all and query latter..


### GET optional parameters
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
## Datasets Endpoint
The `datasets` endpoint interact with the [datasets](management_objects#datasets) table. Their basic usage is getting the registered Datasets, but not the data in the datasets (use the web interface for getting the complete data for a dataset or the [Measurements API](#endpoint_measurements)). Usefull for getting dataset_ids for importing Measurements.



### GET optional parameters
- `id=list` return only datasets having the id or ids provided (ex `id=1,2,3,10`)

### Fields obtained with 'simple' option

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
## Biocollections Endpoint
The `biocollections` endpoint interact with the [biocollections](management_objects#biocollections) table. Their basic usage is getting the list of Biological Collections (Biocollections and other individual and non-individual repositories of biological samples) registered in the database. Using for getting `biocollection_id` for importing data with the [Vouchers API](#endpoint_vouchers)).


### GET optional parameters
- `id=list` return only 'biocollections' having the id or ids provided (ex `id=1,2,3,10`)


### Fields obtained with 'simple' option
  - `id` - the id of the repository or museum in the biocollections table (a local database id)
  - `name` - the name of the repository or museum
  - `acronym` - the repository or museum acronym
  - `irn` - only for Biocollections, the number of the biocollection in the [Index Herbariorum](http://sweetgum.nybg.org/science/ih/)


<a name="endpoint_individuals"></a>
***
## Individuals Endpoint
The `individuals` endpoints interact with the [Individual](Core-Objects#individuals) table. Their basic usage is getting a list of individuals or import new ones.

### GET optional parameters
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

#### Fields obtained
  - `id` - the ODB id of the Individual in the individuals table (a local database id)
  - `basisOfRecord` [DWC](https://dwc.tdwg.org/terms/#dwc:basisOfRecord) - will be always 'organism' [dwc location]([DWC](https://dwc.tdwg.org/terms/#organism)
  - `organismID` [DWC](https://dwc.tdwg.org/terms/#dwc:organismID) - a local unique combination of record info, composed of recordNumber,recordedByMain,locationName
  - `recordedBy` [DWC](https://dwc.tdwg.org/terms/#dwc:recordedBy) - pipe "|" separated list of registered Persons abbreviations
  - `recordedByMain` - the first person in recordedBy, the main collectors
  - `recordNumber` [DWC](https://dwc.tdwg.org/terms/#dwc:recordNumber) - an identifier for the individual, may be the code in a tree aluminum tag, a bird band code, a collector number
  - `recordedDate` [DWC](https://dwc.tdwg.org/terms/#dwc:recordedDate) - the record date
  - `scientificName` [DWC](https://dwc.tdwg.org/terms/#dwc:scientificName)  - the current taxonomic identification of the individual (no authors) or "unidentified"
  - `scientificNameAuthorship` [DWC](https://dwc.tdwg.org/terms/#dwc:scientificNameAuthorship) - the taxon authorship. For **taxonomicStatus unpublished**: will be a ODB registered Person name
  - `family` [DWC](https://dwc.tdwg.org/terms/#dwc:family)
  - `genus` [DWC](https://dwc.tdwg.org/terms/#dwc:genus)
  - `identificationQualifier` [DWC](https://dwc.tdwg.org/terms/#dwc:identificationQualifier) - identification name modifiers cf. aff. s.l., etc.
  - `identifiedBy` [DWC](https://dwc.tdwg.org/terms/#dwc:identifiedBy) - the Person identifying the scientificName  of this record
  - `dateIdentified` [DWC](https://dwc.tdwg.org/terms/#dwc:dateIdentified) - when the identification was made (may be incomplete, with 00 in the month and or day position)
  - `identificationRemarks` [DWC](https://dwc.tdwg.org/terms/#dwc:identificationRemarks) - any notes associated with the identification
  - `locationName` - the location name (if plot the plot name, if point the point name, ...)
  - `locationParentName` - the immediate parent locationName, to facilitate use when location is subplot
  - `higherGeography` [DWC](https://dwc.tdwg.org/terms/#dwc:higherGeography) - the parent LocationName '|' separated (e.g. Brasil | Amazonas | Rio Preto da Eva | Fazenda Esteio | Reserva km37 | Manaus ForestGeo-PDBFF Plot | Quadrat 100x100 );
  - `decimalLatitude` [DWC](https://dwc.tdwg.org/terms/#dwc:decimalLatitude) - depends on the location adm_level and the individual X and Y, or Angle and Distance attributes, which are used to calculate these global coordinates for the record; if individual has multiple locations (a monitored bird), the last location is obtained with this get API
  - `decimalLongitude` [DWC](https://dwc.tdwg.org/terms/#dwc:decimalLongitude) - same as for decimalLatitude
  - `x` - the individual X position in a Plot location
  - `y` - the individual Y position in a Plot location
  - `gx` - the individual global X position in a Parent Plot location, when location is subplot (ForestGeo standards)
  - `gy` - the individual global Y position in a Parent Plot location, when location is subplot (ForestGeo standards)
  - `angle` - the individual azimuth direction in relation to a POINT reference, either when adm_level is POINT or when X and Y are also provided for a Plot location this is calculated from X and Y positions  
  - `distance` - the individual distance direction in relation to a POINT reference, either when adm_level is POINT or when X and Y are also provided for a Plot location this is calculated from X and Y positions  
  - `organismRemarks` [DWC](https://dwc.tdwg.org/terms/#dwc:organismRemarks) - any note associated with the Individual record
  - `associatedMedia` [DWC](https://dwc.tdwg.org/terms/#dwc:associatedMedia) - urls to ODB media files associated with the record
  - `datasetName` - the name of the ODB Dataset to which the record belongs to [DWC](https://dwc.tdwg.org/terms/#dwc:datasetName)
  - `accessRights` - the ODB Dataset access privacy setting - [DWC](https://dwc.tdwg.org/terms/#dwc:accessRights)
  - `bibliographicCitation` - the ODB Dataset citation - [DWC](https://dwc.tdwg.org/terms/#dwc:bibliographicCitation)
  - `license` - the ODB Dataset license - [DWC](https://dwc.tdwg.org/terms/#dwc:license)

<br>

### POST format

See the **Import Data Vignette** of the [OpenDataBio-R Client](https://github.com/opendatabio/opendatabio-r) for detailed information on how to import [Individuals](Core-Objects#individuals).
The following fields are allowed in a post API:

* `collector=mixed` - **required**  - persons  'id', 'abbreviation', 'full_name', 'email'; if multiple persons, separate values in your list with  pipe `|` or `;` because commas may be present within names. Main collector is the first on the list;
* `tag=string` -  **required** -  the individual number or code (if the individual identifier is as MainCollector+Number, this is the field for Number);
* `dataset=mixed` - **required** - name or id or the Project;
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


<a name="endpoint_individual_locations"></a>
***
## Individual-locations Endpoint
The `individual-locations` endpoint interact with the [individual_location](Core-Objects#individuals) table. Their basic usage is getting location data for individuals, i.e. occurrence data for organisms. And for importing multiple locations for registered individuals. Designed for occurrences of organisms that move and have multiple locations, else the same info is retrieved with the [Individuals endpoint](#endpoint_individuals).

### GET optional parameters
- `individual_id=number or list` - return locations for individuals that have id or ids ex: `id=2345,345`
- `location=mixed` - return by location id or name or ids or names ex: `location=24,25,26` `location=Parcela 25ha` of the locations where the individuals
- `location_root` - same as location but return  also from the descendants of the locations informed
- `taxon=mixed` - the id or ids, or canonicalName taxon names (fullnames) ex: `taxon=Aniba,Ocotea guianensis,Licaria cannela tenuicarpa` or `taxon=456,789,3,4`
- `taxon_root` - same as taxon but return also all the the locations for individuals identified as any of the descendants of the taxons informed
- `dataset=mixed` - the id or ids or names of the datasets, return individuals belonging to the datasets informed
- `limit` and `offset` are SQL statements to limit the amount of data when trying to download a large number of individuals, as the request may fail due to memory constraints. See [Common endpoints](endpoint_common).

Notice that all search fields (taxon, location and dataset) may be specified as names (eg, "taxon=Euterpe edulis") or as database ids. If a list is specified for one of these fields, all items of the list must be of the same type, i.e. you cannot search for 'taxon=Euterpe,24'. Also, location and taxon have priority over location_root and taxon_root if both informed.

#### Fields obtained
- `individual_id` - the ODB id of the Individual in the individuals table (a local database id)
- `location_id` - the ODB id of the Location in the locations table (a local database id)
- `basisOfRecord` - will be always 'occurrence' - [DWC](https://dwc.tdwg.org/terms/#dwc:basisOfRecord) and [dwc location]([DWC](https://dwc.tdwg.org/terms/#occurrence);
- `occurrenceID` - the unique identifier for this record, the individual+location+date_time  - [DWC](https://dwc.tdwg.org/terms/#dwc:occurrenceID)  
- `organismID` - the unique identifier for the Individual [DWC](https://dwc.tdwg.org/terms/#dwc:organismID)
- `recordedDate` - the occurrence date+time observation - [DWC](https://dwc.tdwg.org/terms/#dwc:recordedDate)
- `locationName` - the location name (if plot the plot name, if point the point name, ...)
- `higherGeography` - the parent LocationName '|' separated (e.g. Brasil | Amazonas | Rio Preto da Eva | Fazenda Esteio | Reserva km37 | Manaus ForestGeo-PDBFF Plot | Quadrat 100x100 ) - [DWC](https://dwc.tdwg.org/terms/#dwc:higherGeography)
- `decimalLatitude` - depends on the location adm_level and the individual X and Y, or Angle and Distance attributes, which are used to calculate these global coordinates for the record - [DWC](https://dwc.tdwg.org/terms/#dwc:decimalLatitude)
- `decimalLongitude` - same as for decimalLatitude - [DWC](https://dwc.tdwg.org/terms/#dwc:decimalLongitude)
- `georeferenceRemarks` - will contain the explanation of the type of decimalLatitude - [DWC](https://dwc.tdwg.org/terms/#dwc:georeferenceRemarks)
- `x` - the individual X position in a Plot location
- `y` - the individual Y position in a Plot location
- `angle` - the individual azimuth direction in relation to a POINT reference, either when adm_level is POINT or when X and Y are also provided for a Plot location this is calculated from X and Y positions  
- `distance` - the individual distance direction in relation to a POINT reference, either when adm_level is POINT or when X and Y are also provided for a Plot location this is calculated from X and Y positions  
- `minimumElevation` - the altitude for this occurrence record if any - [DWC](https://dwc.tdwg.org/terms/#dwc:minimumElevation)
- `occurrenceRemarks` - any note associated with this record - [DWC](https://dwc.tdwg.org/terms/#dwc:occurrenceRemarks)
- `scientificName` - the current taxonomic identification of the individual (no authors) or "unidentified" - [DWC](https://dwc.tdwg.org/terms/#dwc:scientificName)  
- `family` - the current taxonomic family name, if apply - [DWC](https://dwc.tdwg.org/terms/#dwc:family)
- `datasetName` - the name of the ODB Dataset to which the record belongs to - [DWC](https://dwc.tdwg.org/terms/#dwc:datasetName)
- `accessRights` - the ODB Dataset access privacy setting - [DWC](https://dwc.tdwg.org/terms/#dwc:accessRights)
- `bibliographicCitation` - the ODB Dataset citation - [DWC](https://dwc.tdwg.org/terms/#dwc:bibliographicCitation)
- `license` - the ODB Dataset license [DWC](https://dwc.tdwg.org/terms/#dwc:license)

<br>

### POST format
This method is for importing additional locations for Individuals that are already registered. Designed for moving organisms.
Possible fields are:
  * `individual` - the Individual's id **required**
  * `location` - the Individual's location name or id **required OR longitude+latitude**
  * `latitude` and `longitude`- geographical coordinates in decimal degrees; **required if location is not informed**
  * `altitude` - the Individual location elevation (altitude) in meters above see level;
  * `location_notes` - any note for the individual location;
  * `location_date_time` - if different than the individual's date, a complete date or a date+time (hh:mm:ss) value for the individual location. **required**
  * `x` - if location is of Plot type, the x coordinate of the individual in the location;
  * `y` - if location is of Plot type, the y coordinate of the individual in the location;
  * `distance` - if location is of POINT type (or latitude and longitude are informed), the individual distance in meters from the location;
  * `angle` - if location is of POINT type, the individual azimuth (angle) from the location


<a name="endpoint_measurements"></a>
***
## Measurements Endpoint
The `measurements` endpoint interact with the [measurements](Auxiliary-Objects#measurements) table. Their basic usage is getting Data linked to Individuals, Taxons, Locations or Vouchers, regardless of datasets, so it is usefull when you want particular measurements from different datasets that you have access for. If you want a full dataset, use the web interface, as it prepares a complete set of the dataset measurements and their associated data tables for download in your Job tab.

### GET optional parameters
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
- `id` - the Measurement ODB id in the measurements table (local database id)
- `basisOfRecord` [DWC](https://dwc.tdwg.org/terms/#dwc:basisOfRecord) - will be always 'MeasurementsOrFact' [dwc measurementorfact]([DWC](https://dwc.tdwg.org/terms/#measurementorfact)
- `measured_type` - the measured object, one of 'Individual', 'Location', 'Taxon' or 'Voucher'
- `measured_id` - the id of the measured object in the respective object table (individuals.id, locations.id, taxons.id, vouchers.id)
- `measurementID` [DWC](https://dwc.tdwg.org/terms/#measurementorfact) -  a unique identifier for the Measurement record - combine measured resourceRelationshipID, measurementType and date
- `measurementType` [DWC](https://dwc.tdwg.org/terms/#measurementorfact) - the export_name for the ODBTrait measured
- `measurementValue` [DWC](https://dwc.tdwg.org/terms/#measurementorfact) - the value for the measurement - will depend on kind of the measurementType (i.e. ODBTrait)
- `measurementUnit` [DWC](https://dwc.tdwg.org/terms/#measurementorfact) - the unit of measurement for quantitative traits
- `measurementDeterminedDate` [DWC](https://dwc.tdwg.org/terms/#measurementorfact) - the Measurement measured date
- `measurementDeterminedBy` [DWC](https://dwc.tdwg.org/terms/#dwc:measurementDeterminedBy) - Person responsible for the measurement
- `measurementRemarks` [DWC](https://dwc.tdwg.org/terms/#dwc:measurementRemarks) - text note associated with this Measurement record
- `resourceRelationship` [DWC](https://dwc.tdwg.org/terms/#dwc:resourceRelationship) - the measured object (resource) - one of 'location','taxon','organism','preservedSpecimen'
- `resourceRelationshipID` [DWC](https://dwc.tdwg.org/terms/#dwc:resourceRelationshipID) - the id of the resourceRelationship
- `relationshipOfResource` [DWC](https://dwc.tdwg.org/terms/#dwc:relationshipOfResource) - will always be 'measurement of'
- `scientificName` [DWC](https://dwc.tdwg.org/terms/#dwc:scientificName)  - the current taxonomic identification (no authors) or 'unidentified' if the resourceRelationship object is not 'location'
- `family` [DWC](https://dwc.tdwg.org/terms/#dwc:family) - taxonomic family name if applies
- `datasetName` - the name of the ODB Dataset to which the record belongs to - [DWC](https://dwc.tdwg.org/terms/#dwc:datasetName)
- `accessRights` - the ODB Dataset access privacy setting - [DWC](https://dwc.tdwg.org/terms/#dwc:accessRights)
- `bibliographicCitation` - the ODB Dataset citation - [DWC](https://dwc.tdwg.org/terms/#dwc:bibliographicCitation)
- `license` - the ODB Dataset license - [DWC](https://dwc.tdwg.org/terms/#dwc:license)

### POST format
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




<a name="endpoint_media"></a>
***
## Media Endpoint
The `media` endpoint interact with the [media](Auxiliary-Objects#mediafiles) table. Their basic usage is getting the metadata associated with MediaFiles and the files URL. POST method is only available through the web interface.

### GET optional parameters
- `individual=number or list` - return media associated with the individuals having id or ids ex: `id=2345,345`
- `voucher=number or list` - return media associated with the vouchers having id or ids ex: `id=2345,345`
- `location=mixed` - return media associated with the locations having id or name or ids or names ex: `location=24,25,26` `location=Parcela 25ha`
- `location_root` - same as location but return also media associated with the descendants of the locations informed
- `taxon=mixed` - the id or ids, or canonicalName taxon names (fullnames) ex: `taxon=Aniba,Ocotea guianensis,Licaria cannela tenuicarpa` or `taxon=456,789,3,4`
- `taxon_root` - same as taxon but return also all the locations for individuals identified as any of the descendants of the taxons informed
- `dataset=mixed` - the id or ids or names of the datasets, return individuals belonging to the datasets informed
- `limit` and `offset` are SQL statements to limit the amount of data when trying to download a large number of individuals, as the request may fail due to memory constraints. See [Common endpoints](endpoint_common).

Notice that all search fields (taxon, location and dataset) may be specified as names (eg, "taxon=Euterpe edulis") or as database ids. If a list is specified for one of these fields, all items of the list must be of the same type, i.e. you cannot search for 'taxon=Euterpe,24'. Also, location and taxon have priority over location_root and taxon_root if both informed.

#### Fields obtained
- `id` - the Measurement ODB id in the measurements table (local database id)
- `basisOfRecord` [DWC](https://dwc.tdwg.org/terms/#dwc:basisOfRecord) - will be always 'MachineObservation' [DWC](https://dwc.tdwg.org/terms/#machineobservation)
- `model_type` - the related object, one of 'Individual', 'Location', 'Taxon' or 'Voucher'
- `model_id` - the id of the related object in the respective object table (individuals.id, locations.id, taxons.id, vouchers.id)
- `resourceRelationship` [DWC](https://dwc.tdwg.org/terms/#dwc:resourceRelationship) - the related object (resource) - one of 'location','taxon','organism','preservedSpecimen'
- `resourceRelationshipID` [DWC](https://dwc.tdwg.org/terms/#dwc:resourceRelationshipID) - the id of the resourceRelationship
- `relationshipOfResource` [DWC](https://dwc.tdwg.org/terms/#dwc:relationshipOfResource) - will be the dwcType
- `recordedBy` [DWC](https://dwc.tdwg.org/terms/#dwc:recordedBy) - pipe "|" separated list of registered Persons abbreviations
- `recordedDate` [DWC](https://dwc.tdwg.org/terms/#dwc:recordedDate) - the media file date
- `scientificName` [DWC](https://dwc.tdwg.org/terms/#dwc:scientificName)  - the current taxonomic identification of the individual (no authors) or "unidentified"
- `family` [DWC](https://dwc.tdwg.org/terms/#dwc:family)
- `dwcType` [DWC](https://dwc.tdwg.org/terms/#type) - one of StillImage, MovingImage, Sound
- `datasetName` - the name of the ODB Dataset to which the record belongs to [DWC](https://dwc.tdwg.org/terms/#dwc:datasetName)
- `accessRights` - the ODB Dataset access privacy setting - [DWC](https://dwc.tdwg.org/terms/#dwc:accessRights)
- `bibliographicCitation` - the ODB Dataset citation - [DWC](https://dwc.tdwg.org/terms/#dwc:bibliographicCitation)
- `license` - the ODB Dataset license - [DWC](https://dwc.tdwg.org/terms/#dwc:license)
- `file_name` - the file name
- `file_url` - the url to the file


<a name="endpoint_languages"></a>
***
## Languages EndPoint
The `languages` endpoint interact with the [Language](Auxiliary-Objects#user_translations) table. Their basic usage is getting a list of registered Languages to import [User Translations](Auxiliary-Objects#user_translations) like [Trait](#endpoint_traits) and TraitCategories names and descriptions.

### GET optional parameters: `none`

### Fields obtained
  - `id` - the id of the language in the languages table (a local database id)
  - `code` - the language string code;
  - `name` - the language name;



<a name="endpoint_locations"></a>
***
## Locations Endpoint
The `locations` endpoints interact with the [locations](Core-Objects#locations) table. Their basic usage is getting a list of registered countries, cities, plots, etc, or importing new locations.


### GET optional parameters
- `id=list` return only locations having the id or ids provided (ex `id=1,2,3,10`)
- `adm_level=number` return only locations for the specified level or type:
  - **2** for country; **3** for first division within country (province, state); **4** for second division (e.g. municipality)... up to adm_level 10 as administrative areas (Geometry: polygon, MultiPolygon);
  - **97** is the code for Environmental Layers (Geometry: polygon, multipolygon);
  - **98** is the code for Indigenous Areas (Geometry: polygon, multipolygon);
  - **99** is the code for Conservation Units (Geometry: polygon, multipolygon);
  - **100** is the code for plots and subplots (Geometry: polygon or point);
  - **101** for transects (Geometry: point or linestring)
  - **999** for any 'POINT' locations like GPS waypoints (Geometry: point);
- `name=string` return only locations whose name matches the search string. You may use asterisk as a wildcard. Example: `name=Manaus` or `name=*Ducke*` to find name that has the word Ducke;
- `parent_id=list` return the locations for which the direct parent is in the list (ex: parent_id=2,3)
- `root=number` number is the location `id` to search, returns the location for the specified id along with all of its descendants locations; example: find the id for Brazil and use its id as root to get all the locations belonging to Brazil;
- `querytype` one of "exact", "parent" or "closest" and must be provided with `lat` and `long`:
    - when `querytype=exact` will find a point location that has the exact match of the `lat` and `long`;
    - when `querytype=parent` will find the most inclusive parent location within which the coordinates given by `lat` and `long` fall;
    - when `querytype=closest` will find the closest location to the coordinates given by `lat` and `long`; It will only search for closest locations having `adm_level > 99`, see above.
    - `lat` and `long` must be valid coordinates in decimal degrees (negative for South and West);
- `fields=list` specify which fields you want to get with your query (see below for field names), or use options 'all' or 'simple', to get full set and the most important columns, respectively
- `project=mixed` - id or name of project (may be a list) return the locations belonging to one or more Projects
- `dataset=mixed` - id or name of a dataset (may be a list) return the locations belonging to one or more Datasets

Notice that `id`, `search`, `parent` and `root` should probably not be combined in the same query.


#### Fields obtained

  - `id` - the ODB id of the Location in the locations table (a local database id)
  - `basisOfRecord` [DWC](https://dwc.tdwg.org/terms/#dwc:basisOfRecord) - will always contain 'location' [dwc location]([DWC](https://dwc.tdwg.org/terms/#location)
  - `locationName` - the location name (if country the country name, if state the state name, etc...)
  - `adm_level` - the numeric value for the ODB administrative level (2 for countries, etc)
  - `levelName` - the name of the ODB administrative level
  - `parent_id` - the ODB id of the parent location
  - `parentName` - the immediate parent locationName
  - `higherGeography` [DWC](https://dwc.tdwg.org/terms/#dwc:higherGeography) - the parent LocationName '|' separated (e.g. Brasil | São Paulo | Cananéia);
  - `footprintWKT` [DWC](https://dwc.tdwg.org/terms/#dwc:footprintWKT) - the [WKT](https://en.wikipedia.org/wiki/Well-known_text) representation of the location; if adm_level==100 (plots) or adm_level==101 (transects) and they have been informed as a POINT location, the respective polygon or linestring geometries, the footprintWKT will be that generated using the location's x and y dimensions.
  - `x` and `y` - (meters) when location is a plot (100 == adm_level) its X and Y dimensions, if a transect (101 == adm_level), x may be the length and y may be a buffer dimension around the linestring.
  - `startx` and `starty` - (meters) when location is a subplot (100 == adm_level with parent also adm_level==100), the X and Y start position in relation to the 0,0 coordinate of the parent plot location, which is either a Point, or the first coordinate of a Polygon geometry type;
  - `distance` - only when querytype==closest, this value will be present, and indicates the distance, in meters, the locations is from your queried coordinates;
  - `locationRemarks` [DWC](https://dwc.tdwg.org/terms/#dwc:locationRemarks) - any notes associated with this Location record
  - `decimalLatitude` [DWC](https://dwc.tdwg.org/terms/#dwc:decimalLatitude) - depends on the adm_level: if adm_level<=99, the latitude of the centroid; if adm_level == 999 (point), its latitude; if adm_level==100 (plot) or 101 (transect), but is a POINT geometry, the POINT latitude, else if POLYGON geometry, then the first point of the POLYGON or the LINESTRING geometry.
  - `decimalLongitude` [DWC](https://dwc.tdwg.org/terms/#dwc:decimalLongitude) - same as for decimalLatitude
  - `georeferenceRemarks` [DWC](https://dwc.tdwg.org/terms/#dwc:georeferenceRemarks) - will contain the explanation about decimalLatitude
  - `geodeticDatum` -[DWC](https://dwc.tdwg.org/terms/#dwc:geodeticDatum)  the geodeticDatum informed for the geometry (ODB does not treat map projections, assumes data is always is WSG84)

## POST format

ODB Locations are stored with a parent-child relationship, assuring validations and facilitating queries. Parents will be guessed using the location geometry. If parent is not informed, the imported location must be completely contained by a registered parent (using sql [ST_WITHIN](https://postgis.net/docs/ST_Within.html) function to  detect parent). However, if a parent is informed, the importation may also test if the geometry fits a buffered version of the parent geometry, thus ignoring minor geometries overlap and shared borders. Countries can be imported without parent relations. Any other location must be registered within at least a country as parent. If the record is marine, and falls outside of a registered country polygon, a 'ismarine' argument must be indicated to accept the non-spatial parent relationship.

Make sure your geometry projection is **EPSG:4326** **WGS84**. Use this standard!

Available POST variables are:
* `name` -  the location name  - **required** (parent+name must be unique in the database)
* `adm_level` - must be numeric, see [above](#locations_get) - **required**
* geometry use either: **required**
  * `geom` for a WKT representation of the geometry, POLYGON, MULTIPOLYGON, POINT OR LINESTRING allowed;
  * `lat` and `long` for latitude and longitude in decimal degrees (use negative numbers for south/west).  
* `altitude` - in meters
* `datum` - defaults to 'EPSG:4326-WGS 84' and your are strongly encourage of importing only data in this projection. You may inform a different projection here;
* `parent` - either the id or name of the parent location. The API will detect the parent based on the informed geometry and the *detected parent has priority* if informed is different. However, only when parent is informed, validation will also test whether your location falls within a buffered version of the informed parent, allowing to import locations that have a parent-child relationship but their borders overlap somehow (either shared borders or differences in georeferencing);
* when location is plot (`adm_level=100`), optional fields are:
  * `x` and `y` for the plot dimensions in meters(defines the Cartesian coordinates)
  * `startx` and `starty` for start position of a subplot in relation to its parent plot location;
* `notes` - any note you wish to add to your location;
* `ismarine` - to permit the importation of location records that not fall within any register parent location you may add ismarine=1. Note, however, that this allows you to import misplaced locations. Only use if your location is really a marine location that fall outside any Country border;

**alternatively**: you may just submit a single column named `geojson` containing a Feature record, with its geometry and having as 'properties' at least tags `name` and `adm_level` (or `admin_level`). See [geojson.org](https://geojson.org/). This is usefull, for example, to import country political boundaries (https://osm-boundaries.com/).


<a name="endpoint_persons"></a>
***
## Persons Endpoint
The `persons` endpoint interact*** with the [Person](Auxiliary-Objects#persons) table. The basic usage is getting a list of registered people (plat and voucher collectors, taxonomic specialists or database users), with full name, abbreviation and e-mail.


### GET optional parameters
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


### POST format

The following fields are allowed in a post API:
- `full_name` - person full name, **required**
- `abbreviation` - abbreviated name, as used by the person in publications, as collector, etc. (if left blank, a standard abbreviation will be generated using the full_name);
- `email` - an email address,
- `institution` - to which institution this person is associated;
- `biocollection` - name or acronym of the Biocollection to which this person is associated.


<a name="endpoint_projects"></a>
***
## Projects EndPoint
The `projects` endpoint interact with the [projects](management_objects#projects) table. The basic usage is getting the registered Projects, but not the individuals nor vouchers in the projects, only counts and some basic info. Usefull for getting project_ids for importing Individuals and Vouchers.

### GET optional parameters
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
## Taxons Endpoint
The `taxons` endpoint interact with the [taxons](Core-Objects#taxons)  table. The basic usage is getting a list of registered taxonomic names or importing new taxonomic names.

### GET optional parameters
- `id=list` return only taxons having the id or ids provided (ex `id=1,2,3,10`)
- `name=search` returns only taxons with fullname (no authors) matching the search string. You may use asterisk as a wildcard.
- `root=number` returns the taxon for the specified id along with all of its descendants
- `level=number` return only taxons for the specified level.
- `valid=1` return only valid names
- `external=1` return the Tropicos, IPNI, MycoBank, ZOOBANK or GBIF reference numbers. You need to specify `externalrefs` in the field list to return them!
- `project=mixed` - id or name of project (may be a list) return the taxons belonging to one or more Projects
- `dataset=mixed` - id or name of a dataset (may be a list) return the taxons belonging to one or more Datasets
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


#### Fields obtained

- `id` - this ODB id for this Taxon record in the taxons table
- `senior_id` - if invalid this ODB identifier of the valid synonym for this taxon (acceptedNameUsage) - only when taxonomicStatus == 'invalid'
- `parent_id` - the id of the parent taxon
- `author_id` - the id of the person that defined the taxon for unpublished names (having an author_id means the taxon is unpublished)
- `scientificName` [DWC](https://dwc.tdwg.org/terms/#dwc:scientificName) - the full taxonomic name without authors (i.e. including genus name and epithet for species name)
- `scientificNameID` [DWC](https://dwc.tdwg.org/terms/#dwc:scientificNameID) - nomenclatural databases ids, if any external reference is stored for this Taxon record
- `taxonRank` [DWC](https://dwc.tdwg.org/terms/#dwc:scientificName)  - the string value of the taxon rank
- `level` - the ODB numeric value of the taxon rank
- `scientificNameAuthorship` [DWC](https://dwc.tdwg.org/terms/#dwc:scientificNameAuthorship) - the taxon authorship. For **taxonomicStatus unpublished**: will be a ODB registered Person name
- `namePublishedIn` - unified bibliographic reference (i.e. either the short format or an extract of the bibtext reference assigned). This will be mostly retrieved from nomenclatural databases; Taxon linked references can be extracted with the [BibReference API](API#endpoint_bibreferences).
- `taxonomicStatus` [DWC](https://dwc.tdwg.org/terms/#dwc:taxonomicStatus) - one of  'accepted', 'invalid' or 'unpublished'; if invalid, fields senior_id and acceptedNameUsage* will be filled
- `parentNameUsage` [DWC](https://dwc.tdwg.org/terms/#dwc:parentNameUsage) - the name of the parent taxon, if species, the genus, if genus, family, and so on
- `family` [DWC](https://dwc.tdwg.org/terms/#dwc:family) - the family name if taxonRank family or below
- `higherClassification` [DWC](https://dwc.tdwg.org/terms/#dwc:higherClassification) - the full taxonomic hierarchical classification, pipe separated (will include only Taxons registered in this database)
- `acceptedNameUsage` [DWC](https://dwc.tdwg.org/terms/#dwc:acceptedNameUsage) - if taxonomicStatus invalid the valid scientificName for this Taxon
- `acceptedNameUsageID` [DWC](https://dwc.tdwg.org/terms/#dwc:acceptedNameUsageID) - if taxonomicStatus invalid the scientificNameID ids of the valid Taxon
- `taxonRemarks` [DWC](https://dwc.tdwg.org/terms/#dwc:taxonRemarks) - any note the taxon record may have
- `basisOfRecord` [DWC](https://dwc.tdwg.org/terms/#dwc:basisOfRecord) - will always be 'taxon'
- `externalrefs` - the Tropicos, IPNI, MycoBank, ZOOBANK or GBIF reference numbers


### POST format

The POST API requires ONLY the **full name** of the taxon to be imported, i.e. for species or below species taxons the complete name must be informed (e.g. *Ocotea guianensis*  or *Licaria cannela aremeniaca*). The script will validate the name retrieving the remaining required info from the nomenclatural databases using their API services. It will search GBIF and Tropicos if the case and retrieve taxon info, their ids in these repositories and also the full classification path and senior synonyms (if the case) up to when it finds a valid record name in this ODB database. So, unless you are trying to import unpublished names, just submit the name parameter of the list below.
* `name` - taxon full name **required**, e.g. "Ocotea floribunda"  or "Pagamea plicata glabrescens"
* `level` - may be the numeric id or a string describing the taxonRank (see above) **recommended for unpublished names**
* `parent` - the taxon's parent full name or id - **note** - if you inform a valid parent and the system detects a different parent through the API to the nomenclatural databases, preference will be given to the informed parent; **required for unpublished names**
* `bibreference` - the bibliographic reference in which the taxon was published;
* `author` - the taxon author's name;
* `author_id` or `person` - the registered Person name, abbreviation, email or id, representing the author of unpublished names -  **required for unpublished names**
* `valid` - boolean, true if this taxon name is valid; 0 or 1
* `mobot` - Tropicos.org id for this taxon
* `ipni` - IPNI id for this taxon
* `mycobank` - MycoBank id for this taxon
* `zoobank` - ZOOBANK id for this taxon
* `gbif` - GBIF nubKey for this taxon


<a name="endpoint_traits"></a>
***
## Traits Endpoint
The `traits` endpoint interact with the [Trait](Trait-Objects#traits) table. The basic usage is getting a list of variables and variables categories for importing [Measurements](Trait-Objects#measurements). The POST method allows  you to batch import variables, but it is *strongly recommended* that you use the Web Interface to define traits in the database, because the interface facilitates avoiding creating redundant traits.

### GET optional parameters
- `id=list` return only traits having the id or ids provided (ex `id=1,2,3,10`);
- `name=string` return only traits having the `export_name` as indicated (ex `name=DBH`)
- `categories` - if true return the categories for categorical traits
- `language=mixed` return name and descriptions of both trait and categories in the specified language. Values may be 'language_id', 'language_code' or 'language_name';
- `bibreference=boolean` - if true, include the [BibReference](Auxiliary-Objects#bibreferences) associated with the trait in the results;
- `limit` and `offset` are SQL statements to limit the amount. See [Common endpoints](endpoint_common).

### Fields obtained
- `id` - the id of the Trait in the odbtraits table (a local database id)
- `type` - the numeric code defining the Trait type
- `typename` - the name of the Trait type
- `export_name` - the export name value
- `measurementType` [DWC](https://dwc.tdwg.org/terms/#measurementorfact) - same as export_name for DWC compatibility
- `measurementMethod` [DWC](https://dwc.tdwg.org/terms/#measurementorfact) - combine name, description and categories if apply (included in the Measurement GET API, for DWC compatibility)
- `measurementUnit` - the unit of measurement for Quantitative traits
- `range_min` - the minimum allowed value for Quantitative traits
- `range_max` - the maximum allowed value for Quantitative traits
- `link_type` - if Link type trait, the class of the object the trait links to (currently only Taxon)
- `name` - the trait name in the language requested or in the default language
- `description` - the trait description in the language requested or in the default language
- `value_length` - the length of values allowed for Spectral trait types
- `objects` - the types of object the trait may be used for, separated by pipe '|'
- `categories` - each category is given for Categorical and Ordinal traits, with the following fields (the category `id`, `name`, `description` and `rank`). Ranks are meaningfull only for ORDINAL traits, but reported for all categorical traits.
-  `bibreference` - the BibReference record associated with the Trait definition

### POST format
The POST method allows you to batch import traits into the database and is designed for transfering data to OpenDataBio from other systems, including Trait Ontologies databases.  When entering few traits, it is **strongly recommended** that you enter traits one by one using the Web Interface form and then use the GET option for ids to import measurements.
  1. As noted under the [Trait Model](Trait-Objects#traits) description, it is important that one really checks whether a needed Trait is not already in the DataBase to avoid multiplication of redundant traits. The Web Interface facilitates this process and in a batch process that involves multiple languages, trait name comparsions become too complicated. OpenDataBio only checks for identifical `export_name`, which must be unique within the database. Note, however, that Traits should also be as specific as possible for detailed metadata annotations.  
  1. Traits use [User Translations](Auxiliary-Objects#user_translations) for names and descriptions, allowing a multilanguage, so names and descriptions of Trait and Trait Categories together with the respective language key (or id)

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

#### Translations
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
## Vouchers Endpoint
The `vouchers` endpoints interact with the [Voucher](Core-Objects#vouchers) table. Their basic usage is getting and importing voucher specimens into the database.

### GET parameters
* `id=list` return only vouchers having the id or ids provided (ex `id=1,2,3,10`)
* `number=string` returns only vouchers for the informed collector number (but is a string and may contain non-numeric codes)
* `collector=mixed` one of id or ids or abbreviations, returns only vouchers for the informed **main collector**
* `dataset=list` - id or ids list, return all vouchers with measurements in the informed datasets; this will not include vouchers of measured individuals in the dataset, only vouchers measured directly.
* `project=mixed` one of ids or names, returns only the vouchers for informed project.
* `location=mixed` one of ids or names; (1) if `individual_tag` is also requested returns only vouchers for those individuals (or use *"individual=\*"* to get all vouchers for any individual collected at the location); (2) if `individual` and `individual_tag` are not informed, then returns vouchers linked to locations and to the individuals at the locations.
* `location_root=mixed` - same as location, but include also the vouchers for the descendants of the locations informed. e.g. *"location_root=Manaus"* to get any voucher collected within the Manaus administrative area;
* `individual=mixed` either a individual_id or a list of ids, or `*` - returns only vouchers for the informed individuals; when *"individual=\*"* then location must be informed, see above;
* `taxon=mixed` one of ids or names, returns only vouchers for the informed taxons. This could be either vouchers referred as parent of the requested taxon or vouchers of individuals of the requested taxons.
* `taxon_root=mixed` - same as taxon, but will include in the return also the vouchers for the descendants of the taxons informed. e.g. *"taxon_root=Lauraceae"* to get any Lauraceae voucher;

Notice that some search fields (taxon, location, project and collector) may be specified as names - abbreviation, fullnames and emails in the case of collector - (eg, "taxon=Euterpe edulis") or as database ids. If a list is specified for one of these fields, **all items of the list must be of the same type**.


#### Fields obtained with 'simple' option

- `id` - the Voucher ODB id in the vouchers table (local database id)
- `basisOfRecord` [DWC](https://dwc.tdwg.org/terms/#dwc:basisOfRecord) - will be always 'preservedSpecimen' [dwc location]([DWC](https://dwc.tdwg.org/terms/#preservedspecimen)
- `occurrenceID` [DWC](https://dwc.tdwg.org/terms/#dwc:occurrenceID) - a unique identifier for the Voucher record - combine organismID with biocollection info
- `organismID` [DWC](https://dwc.tdwg.org/terms/#dwc:organismID) - a unique identifier for the Individual the Voucher belongs to
- `individual_id` - the ODB id for the Individual the Voucher belongs to
- `collectionCode` [DWC](https://dwc.tdwg.org/terms/#dwc:collectionCode) - the Biocollection acronym where the Voucher is deposited
- `catalogNumber` [DWC](https://dwc.tdwg.org/terms/#dwc:catalogNumber) - the Biocollection number or code for the Voucher
- `typeStatus` [DWC](https://dwc.tdwg.org/terms/#dwc:typeStatus) - if the Voucher represent a nomenclatural type
- `recordedBy` [DWC](https://dwc.tdwg.org/terms/#dwc:recordedBy) - collectors pipe "|" separated list of registered Persons abbreviations that collected the vouchers
- `recordedByMain` - the first person in recordedBy, the main collector
- `recordNumber` [DWC](https://dwc.tdwg.org/terms/#dwc:recordNumber) - an identifier for the Voucher, generaly the Collector Number value
- `recordedDate` [DWC](https://dwc.tdwg.org/terms/#dwc:recordedDate) - the record date, collection date
- `scientificName` [DWC](https://dwc.tdwg.org/terms/#dwc:scientificName)  - the current taxonomic identification of the individual (no authors) or "unidentified"
- `scientificNameAuthorship` [DWC](https://dwc.tdwg.org/terms/#dwc:scientificNameAuthorship) - the taxon authorship. For **taxonomicStatus unpublished**: will be a ODB registered Person name
- `family` [DWC](https://dwc.tdwg.org/terms/#dwc:family)
- `genus` [DWC](https://dwc.tdwg.org/terms/#dwc:genus)
- `identificationQualifier` [DWC](https://dwc.tdwg.org/terms/#dwc:identificationQualifier) - identification name modifiers cf. aff. s.l., etc.
- `identifiedBy` [DWC](https://dwc.tdwg.org/terms/#dwc:identifiedBy) - the Person identifying the scientificName  of this record
- `dateIdentified` [DWC](https://dwc.tdwg.org/terms/#dwc:dateIdentified) - when the identification was made (may be incomplete, with 00 in the month and or day position)
- `identificationRemarks` [DWC](https://dwc.tdwg.org/terms/#dwc:identificationRemarks) - any notes associated with the identification
- `locationName` - the location name for the organismID the voucher belongs to  (if plot the plot name, if point the point name, ...)
- `higherGeography` [DWC](https://dwc.tdwg.org/terms/#dwc:higherGeography) - the parent LocationName '|' separated (e.g. Brasil | Amazonas | Rio Preto da Eva | Fazenda Esteio | Reserva km37);
- `decimalLatitude` [DWC](https://dwc.tdwg.org/terms/#dwc:decimalLatitude) - depends on the location adm_level and the individual X and Y, or Angle and Distance attributes, which are used to calculate these global coordinates for the record; if individual has multiple locations (a monitored bird), the location closest to the voucher date is obtained
- `decimalLongitude` [DWC](https://dwc.tdwg.org/terms/#dwc:decimalLongitude) - same as for decimalLatitude
- `occurrenceRemarks` [DWC](https://dwc.tdwg.org/terms/#dwc:occurrenceRemarks) - text note associated with this record
- `associatedMedia` [DWC](https://dwc.tdwg.org/terms/#dwc:associatedMedia) - urls to ODB media files associated with the record
- `datasetName` - the name of the ODB Dataset to which the record belongs to [DWC](https://dwc.tdwg.org/terms/#dwc:datasetName)
- `accessRights` - the ODB Dataset access privacy setting - [DWC](https://dwc.tdwg.org/terms/#dwc:accessRights)
- `bibliographicCitation` - the ODB Dataset citation - [DWC](https://dwc.tdwg.org/terms/#dwc:bibliographicCitation)
- `license` - the ODB Dataset license - [DWC](https://dwc.tdwg.org/terms/#dwc:license)

### POST format

The following fields are allowed in a post API:

* `individual=mixed` - the numeric id or organismID of the Individual the Voucher belongs to **required**;
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
***
## User Jobs Endpoint
The `jobs` endpoints interact with the [UserJobs](Data-Access-Objects#jobs) table. The basic usage is getting a list of submitted data import jobs, along with a status message and logs.

- Possible fields: id - database id; status - the status of the job: "Submitted", "Processing", "Success", "Failed" or "Cancelled"; `dispatcher` - the type of the job, eg, ImportTaxons; `log` - the job log messages, usually indicating whether the resources were successfuly imported, or whether errors occurred; others.

### GET parameters
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
