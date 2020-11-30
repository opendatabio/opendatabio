* [**Auxiliary Objects**](#)
  * [BibReference](#bibreferences)
  * [Identification](#identifications)
  * [Incomplete Dates](#incompletedate)
  * [Person](#persons)
  * [Picture](#pictures)
  * [Tag](#tags)
  * [User Translation](#user_translations)
* [**Core Objects**](Core-Objects)
* [**Trait Objects**](Trait-Objects)
* [**Data Access Objects**](Data-Access-Objects)
* [**API**](API)

<a name="bibreferences"></a>
## BibReference Model

The **BibReference** table contains basically a [BibTex](http://www.bibtex.org/) formatted reference stored in the `bibtex` column. The BibtexKey, authors and other relevant fields are extracted from it from the BibTex record. These bibliographic references may be used to:
  * Store references for [Datasets](Data-Access-Objects#datasets) - with the option of defining references for which citation is mandatory when using the dataset in publications; but all references that have used the dataset may be linked to the dataset; links are done with a Pivot table named `dataset_bibreference`;
  * Store the references for [Taxons](Core-Objects#taxons) - possible to specify specifically the reference in which the Taxon name is described, currently mandatory in some Taxonomic journals like [PhytoTaxa](https://www.mapress.com/j/pt/). This description reference is stored in the `bibreference_id` of the Taxons table. Any other number of references may be linked to a Taxon, which are then linked through a pivot table named `taxons_bibreference`. This was mostly designed for taxonomic related work;
  * Store [Measurements](Trait-Objects#measurements) from the literature and link them to the reference source;


![](https://github.com/opendatabio/datamodel/blob/master/bibreference_model.png)
<img src="{{ asset('images/docs/bibreference_model.png') }}" alt="BibReference model" with=350>


* The **Bibtexkey  must be unique** in the database, and a helper function is be provided to standardize it with format `<von? last name> <year> <first word of title>`. The "von part" of the name is the "von", "di", "de la",  which are part of the last name for some authors. The first word of the title ignores common stop-words such as "a", "the", or "in". [DOIs](https://www.doi.org/) for a BibReference may be specified either in the relevant BibTex field or in a separate text input, and are stored in the `doi` field.

 **Data access** [full users](Data-Access-Objects#users) may register new references, edit references details and remove reference records that have no associated data. BibReferences have public access!

<a name="identifications"></a>
***
## Identification Model

The **Identification** table represents the taxonomic identification data for a [Plant](Core-Objects#plants) or a [Voucher](Core-Objects#vouchers). The relationship with these objects is established through a [polymorphic relationship](#polymorphicrelationships) using fields `object_type` and `object_id`.

![](https://github.com/opendatabio/datamodel/blob/master/identification_model.png)
<img src="{{ asset('images/docs/identification_model.png') }}" alt="Identification model" with=350>

The tables structures and their direct links:

![](https://github.com/opendatabio/datamodel/blob/master/identification_model_phpadm.png)
<img src="{{ asset('images/docs/identification_model_phpadm.png') }}" alt="Identification model" with=350>

* The Identification object includes several optional fields, but `person_id`, the [Person](Auxiliary-Objects#persons) responsible for the identification and the identification `date` are mandatory.
* The following fields are optional:
  * `modifier` - is a numeric code appending a taxonomic modifier to the name. Possible values 's.s.'=1, 's.l.'=2, 'cf.'=3, 'aff.'=4, 'vel aff.'=5, defaults to 0 (none).
  * `herbarium_id` and `herbarium_reference` - these fields are to be used to indicate that the identification is based upon comparison to a specimen deposited in a Biological Collection and creates a link between the object the identification is for and the Bio Collection specimen. `herbarium_id` stores the [Biological Collection or Herbarium](Data-Access-Objects#herbaria) id, and `herbarium_reference` the unique identifier of the specimen compared.
  * `notes` - a text of choice, usefull for adding comments to the identification.
* Changes in identifications are [audited](audits) for tracking change history;

<a name="incompletedate"></a>
***
## Incomplete Dates
[Vouchers](Core-Objects#vouchers) and [Plants](Core-Objects#plants) collecting and identification dates may be Incomplete, but at least **year** is mandatory. The `date` column of the identification table is of 'date' type, and incomplete dates are stored having 00 in the missing part: '2005-00-00' when only year is known; '1988-08-00' when only month is known. The webinterface permits such entry. Read the [API](API) respective endpoint to see options for incomplete date during POST apis.

<a name="persons"></a>
***
## Person Model
The **Person** object stores persons names, which may or may not be a [User](Data-Access-Objects#users) directly involved with the database. It is used to store information about people that are:
    *  **collectors** of [vouchers](Core-Objects#vouchers) and [plants](Core-Objects#plants);
    * **taxonomic determinators** or identifiers of [vouchers](Core-Objects#vouchers) and [plants](Core-Objects#plants);
    * **measurer** of [measurements](Trait-Objects#measurements);
    * **authors** for *unpublished* [Taxon](Core-Objects#taxons) names;
    * **taxonomic specialists** - linked with Taxon model by a pivot table named `person_taxon`;

![](https://github.com/opendatabio/datamodel/blob/master/person_model.png)
<img src="{{ asset('images/docs/person_model.png') }}" alt="Person model" with=350>


The tables structures and their direct links:
![](https://github.com/opendatabio/datamodel/blob/master/persons_model_phpadm.png)
<img src="{{ asset('images/docs/persons_model_phpadm.png') }}" alt="Person model" with=350>

* When registering a new person, the system suggests the name `abbreviation`, but the user is free to change it to better adapt it to the usual abbreviation used by each person. The **abbreviation must be unique** for one Person, duplicates are not allowed in the Persons table. Therefore, two persons with the exact same name must be diferentiated in the `abbreviation` column.
* The `herbarium_id`  column of the Persons table is used to list to which herbarium a person is associated, which may be used when the Person is also a taxonomic specialist.


**Data access** [full users](Data-Access-Objects#users) may register new persons and edit the persons they have inserted and remove persons that have no associated data. Admins may edit any Person. Persons have public access!

<a name="pictures"></a>
***
## Picture Model
**Pictures** are similar to [measurements](Trait-Objects@measurements) in that they might be associated with all [core objects](Core-Objects). Pictures may be **tagged**, i.e. you may define keywords to pictures, allowing to query them by [Tags](#tags). For example, a plant image may be tagged with 'flowers' or 'fruits' to indicate what is in the image, or a tag that informs about image quality.


![](https://github.com/opendatabio/datamodel/blob/master/picture_model.png)
<img src="{{ asset('images/docs/picture_model.png') }}" alt="Picture model" with=350>

The Picture table and its direct links:

![](https://github.com/opendatabio/datamodel/blob/master/picture_model_phpadm.png)
<img src="{{ asset('images/docs/picture_model_phpadm.png') }}" alt="Picture model" with=350>

* Pictures are linked to the [Core-Objects](Core-Objects) through a [polymorphic relationship](#polymorphicrelationships) defined by columns `object_id` and `object_type` just like in the [Measurements](Trait-Objects#measurements) model.
* Pictures are not stored in the database, but in the `public/upload_pictures` server storage folder. The naming convention for images have the following logic: `object_type+object_id`. An image from the voucher with id=1 will be named by the system as `voucher_1.jpg` and a thumbnail is generated for each image with `t_` prefix. All images are stored as `jpg`, regardless of the type that has been uploaded.
* Multiple [Persons](#persons) may be associated with the Picture for credits, these are linked with the **Collectors** table and its [polymorphic relationship](#polymorphicrelationships) structure.
* A Picture may have a `description` in each language configured in the Language table, which will be stored in the `user_translations` table, which relates to the Tag model through a [polymorphic relationship](#polymorphicrelationships). Inputs for each language are shown in the web-interface Picture create/edit forms.
* It is possible to **batch upload images** through the web interface, requiring also a file informing the objects to link the image with, the image tags ids, description translations and collectors ids.

**Data access** [full users](Data-Access-Objects#users) may register images and delete the images they have inserted. Admins may delete any image.
   Images have public access, except when linked to Plant or Vouchers, in which case depends on Project policies.

<a name="tags"></a>
***
## Tag Model
The **Tag** model allows users to define **translatable** keywords that may be used to flag [Datasets](Data-Access-Objects#datasets), [Projects](Data-Access-Objects#projects) or [Pictures](#pictures). The Tag model is linked with these objects through a pivot table for each, named `dataset_tag`, `project_tag` and `picture_tag`, respectively.

A Tag may have `name` and `description` in each language configured in the Language table, which will be stored in the `user_translations` table, which relates to the Tag model through a [polymorphic relationship](#polymorphicrelationships). Inputs for each language are shown in the web-interface Tag creation form.

![](https://github.com/opendatabio/datamodel/blob/master/tag_model.png)
<img src="{{ asset('images/docs/tag_model.png') }}" alt="Tag model" with=350>

The tables structures and direct links:

![](https://github.com/opendatabio/datamodel/blob/master/tag_model_phpadm.png)
<img src="{{ asset('images/docs/tag_model_phpadm.png') }}" alt="Tag model" with=350>

**Data access** [full users](Data-Access-Objects#users) may register tags, edit those they have inserted and delete those that have not been used. Tags have public access as they are just keywords to facilitate navegation.

<a name="user_translations"></a>
***
## User Translation Model
The **UserTranslation** model translates user data: [Trait](Trait-Objects#traits) and Trait Categories names and descriptions, [Picture](#pictures) descriptions and [Tags](#tags). The relations between these models are established by  [polymorphic relations](Core-Objects#polymorphicrelationships) using fields `translatable_type` and `translatable_id`. This model permits translations to any language listed in the **Language** table, which is currently only accessible for insertion and edition directly in the SQL database. Input forms in the web interface will be listed for registered Languages.

![](https://github.com/opendatabio/datamodel/blob/master/usertranslation_model.png)
<img src="{{ asset('images/docs/usertranslation_model.png') }}" alt="Tag model" with=350>
