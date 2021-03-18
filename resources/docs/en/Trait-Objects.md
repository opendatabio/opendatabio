* [**Trait Objects**](#)
  * [Measurement](#measurements)
  * [Trait](#traits)
  * [Forms](#forms)
* [**Core Objects**](Core-Objects)
* [**Auxiliary Objects**](Auxiliary-Objects)    
* [**Data Access Objects**](Data-Access-Objects)
* [**API**](API)

<a name="measurements"></a>
## Measurement Model
The **Measurements** table stores the values for [traits](#traits) measured for [core objects](core_objects). Its relationship with the core objects is defined by a [polymorphic relationship](#polymorphicrelationships) using columns `measured_id` and `measured_type`. These MorphTo relations are illustrated and explained in the [core objects](core_objects) page.

![](https://github.com/opendatabio/datamodel/blob/master/measurement_model.png)
<img src="{{ asset('images/docs/measurement_model.png') }}" alt="Voucher model" with=350>


* **Measurements** must belong to a [Dataset](Data-Access-Objects#datasets) - column `dataset_id`, which controls measurement access policy
* A [Person](Auxiliary-Objects#persons) must be indicated as a **measurer** (`person_id`);
* The `bibreference_id` column may be used to link measurements extracted from publications to its [Bibreference](Auxiliary-Objects#bibreferences) source;
* The **value** for the measured trait (`trait_id`) will be stored in different columns, depending on **trait type**:
  * `value` - this float column will store values for Quantitative Real traits;
  * `value_i` - this integer column will store values for Quantitative Integer traits; and is an optional field for Link type traits, allowing for example to store counts for a species (a Taxon Link trait) in a location.
  * `value_a` - this text column will store values for Text, Color and Spectral trait types.
* Values for Categorical and Ordinal traits are stored in the `measurement_category` table, which links measurements to trait categories.
* `date` - measurement date is mandatory in all cases

**Data access** Measurements belong to [Datasets](Data-Access-Objects#datasets), so Dataset access policy apply to the measurements in it. Only dataset collaborators and administrators may insert or edit measurements in a dataset, even if the dataset is of public access.


<a name="traits"></a>
***
## Trait Model
The **ODBTrait** table represents user defined traits for which [Measurements](#measurements) by associated with one of the [core object](core_objects), either [Individual](core_objects#individuals), [Voucher](core_objects#vouchers), [Location](core_objects#locations) or [Taxon](core_objects#taxons).

These custom traits give enormous flexibility to users to register their variables of interest. Clearly, such flexibility has a cost in data standardization, as the same variable may be registered as different Traits in any OpenDataBio installation. To minimize redundancy in trait ontology, users creating traits are warned about this issue and a list of similar traits is presented in case found by trait name comparison.

Traits have editing restrictions to avoid data loss or unintended data meaning change. So, although the Trait list is available to all users, trait definitions may not be changed if somebody else also used the trait for storing measurements.

Traits are translatable entities, so their `name` and `description` values can be stored in multiple languages, i.e. those configured in an OpenDataBio installation (see [User Translations](Auxiliary-Objects#user_translations)). This is placed in the `user_translations` table through a polymorphic relationship.

The Trait definition should be as specific as needed. The measurement of tree heights using direct measurement or a clinometer, for example, may not be easily converted from each other, and should be stored in different Traits. Thus, it is strongly recommended that the Trait definition field include information such as measurement instrument and other metadata that allows other users to understand whether they can use your trait or create a new one.


![](https://github.com/opendatabio/datamodel/blob/master/trait_model.png)
<img src="{{ asset('images/docs/trait_model.png') }}" alt="Voucher model" with=350>

* The Trait definition must include an `export_name` for the trait, which will be used during data exports and are more easily used in trait selection inputs in the web-interface. **Export names must be unique** and have no translation. Short and [CamelCase](https://en.wikipedia.org/wiki/Camel_case) export names are recommended.
* The following trait types are available:
  * **Quantitative real** - for real numbers;
  * **Quantitative integer** - for counts;
  * **Categorical** - for one selectable categories;
  * **Categorical multiple** - for many selectable categories;
  * **Categorical ordinal** - for one selectable ordered categories (semi-quantitative data);
  * **Text** - for any text value;
  * **Color** - for any color value, specified by the hexadecimal color code, allowing renderizations of the actual color.
  * **Link** - this is a special trait type in OpenDataBio to link to database object. Currently, only link to [Taxons](core_objects#taxons) and [Voucher](core_objects#vouchers) are  allowed as a link type traits. Use ex:  if you want to store species counts conducted in a [location](core_objects#location), you may create a Taxon link type or a Voucher link type if the taxon has vouchers. A measurement for such trait will have an optional `value` field to store the counts. This trait type  may also be used to specify the host of a parasite, or the number of predator insects.
  * **Spectral** - this is designed to accomodate Spectral data, composed of multiple absorbance or reflectance values for different wavenumbers.
  * **GenBank** - this stores [GenBank](https://www.ncbi.nlm.nih.gov/genbank/) accessions numbers allowing to retrieve molecular data linked to individuals or vouchers stored in the database through the [GenBank API Service](https://www.ncbi.nlm.nih.gov/home/develop/api/). **NOT YET IMPLEMENTED**
* The Traits table contains fields that allow measurement value validation, depending on trait type:
  * `range_max` and `range_min` - if defined for Quantitative traits, measurements will have to fit the specified range;
  * `value_length` - mandatory for Spectral Traits only, validate the length (number of values) of a spectral measurement;
  * `link_type` - if trait is Link type, the measurement `value_i` must be an id of the link type object;
  * Color traits are validated in the measurement creation process and must conform to a color hexadecimal code. A color picker is presented in the web interface for measurement insertion and edition;
  * Categorical and ordinal traits will be validated for the registered categories when importing measurements through the [API](API);
* Column `unit` defines the measurement unit for the Trait. There is no way to prevent measurements values imported with a distinct unit. Quantitative traits required `unit` definition.
* Column `bibreference_id` is the key of a single [BibReference](Auxiliary-Objects#BibReferences) that may be linked to trait definition.
* The `Trait-Objects` table stores the type of [core object](core_objects) (Taxon, Location, Voucher) that the trait can have a measurement for;



**Data access** A Trait name, definition, unit and categories may not be updated or removed if there is any measurement of this trait registered in the database. The only exceptions are: (a) it is permissible to add new categories to categorical (not ordinal) traits; (b) the user upddating the trait is the only Person that has measurements for the trait; (c) the user updating the trait is an Admin of all the datasets having measurements using trait; .



**Link trait with Trait Ontologies See [issue #31](https://github.com/opendatabio/opendatabio/issues/31)**


<a name="forms"></a>
***
## Forms
A **Form** is an organized group of [Traits](#traits), defined by a User in order to create a custom form that can be filled in for entering measurements through the web interface. A Form consists of a group of ordered Traits, which can be marked as "mandatory". Related entities are the **Report** and the **Filter**.

**This is still experimental and needs deeper testing**
