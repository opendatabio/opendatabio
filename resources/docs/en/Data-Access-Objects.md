* [Data Access Objects](#)
  * [Datasets](#datasets)
  * [Projects](#projects)
  * [Herbaria](#herbaria)
  * [User](#users)      
  * [UserJobs](#jobs)      
* [**Core Objects**](Core-Objects)
* [**Auxiliary Objects**](Auxiliary-Objects)
* [**Trait Objects**](Trait-Objects)
* [**API**](API)

# Data Access Objects (Management)

[Datasets](#datasets) and [Projects](#projects) are a ways to group data in OpenDataBio and define data policy access to them. Datasets group [Measurements](Trait-Objects#measurements) and Projects group [Plants](Core-Objects#plants) and [Vouchers](Core-Objects#vouchers).

Both must have at least one [User](#users) defined as `administrator`, who has total control over the project or dataset, including granting the following roles to other users: `administrator`, `collaborator` or `viewer`:
    * **Collaborators** are able to insert and edit objects (Plants and Vouchers) into the Project, or Measurements into the Dataset, but are not able to delete records nor change the project's configuration.
    * **Viewers** have read-only access to the Project's or Dataset data, including downloads;
    * Only **Full Users** and **SuperAdmins** may be assigned as **administrators** or **collaborators**. Thus, if a user who was administrator or collaborator of a project is demoted to "Registered User", she or he will become a viewer in the project or dataset.

[Herbaria](#herbaria) are Museum Collections, including Herbaria but not limited to plant Collections, to which [Vouchers](Core-Objects#vouchers) may belong to. It is included in this page because this model will be improved to permit such collections to use OpenDataBio for their data management and curational purposes. With this improvement the Herbaria Model will be renamed and gain additional management tools for Curators and museum staff. This will required the solving of possible conflicts of permissions with Projects privacy level.


<a name="datasets"></a>
***
## Datasets
**DataSets** are groups of [Measurements](Trait-Objects#measurements) which have the same authorization policy. Each DataSet may have one or more [Users](#users)  `administrators`, `collaborators` or `viewers`. Administrators may set the `privacy level` to *public access*, *restricted to registered users* or *restricted to authorized users*. This control access to the measurements within a dataset as exemplified in diagram below:

![](https://github.com/opendatabio/datamodel/blob/master/dataset_model.png)
<img src="{{ asset('images/docs/dataset_model.png') }}" alt="Datasets model" with=350>

Datasets may also have many [Bibliographic References](Auxiliary-Objects#bibreferences), which together with fields `policy` and `metadata` permits to annotate the dataset with relevant information for appending to downloads or presenting to unauthorized users in a request form. This allows to:
    * Link any publication that have used the dataset and optionally indicate that they are of mandatory citation when using the data;
    * Define a specific data `policy` when using the data
    * Detail any relevant `metadata` in addition to those that are automatically retrieved from the database like the [Traits](Trait-Objects#traits) measured.


![](https://github.com/opendatabio/datamodel/blob/master/dataset_bibreference.png)
<img src="{{ asset('images/docs/dataset_bibreference.png') }}" alt="Datasets model" with=350>


<a name="herbaria"></a>
***
## Herbaria
The **Herbarium** object currenlty only stores basic information about Biological Collections that may be used to link to [Vouchers](Core-Objects#voucher), to indicate in which Biological Collections the voucher is deposited. The Herbarium object may be an Herbarium registered in the Index Herbariorum (http://sweetgum.nybg.org/science/ih/) or any other Museum Collection, formal or informal.  *Data access:* only [SuperAdmins](#users) can register or remove this entities.

The Herbarium object also interacts with the [Person](Auxiliary-Objects#persons) model. When a Person is linked to an herbarium it will be listed as a taxonomic specialist.

**Herbaria will renamed to a generic name to be more clear that it also allow the registration of non-plant collections. It will also be improved to permit such collections to use the OpenDataBio system to manage their vouchers (loans, donations, etc).**


![](https://github.com/opendatabio/datamodel/blob/master/herbaria_model.png)
<img src="{{ asset('images/docs/herbaria_model.png') }}" alt="Datasets model" with=350>


**Data Access** - only administrators can register new Herbaria and delete unused herbarium. Updates are not yet implemented.

<a name="projects"></a>
***
## Projects
The **Project** model groups [Plants](Core-Objects#plants) and [Vouchers](Core-Objects#vouchers) and interacts with [Users](#users) in the same way as Datasets, having  `administrators`, `collaborators` or `viewers` users. Administrators may set the `privacy level` to *public access*, *restricted to registered users* or *restricted to authorized users*, which controls the data for the Plants and Vouchers objects belonging to the Project.


![](https://github.com/opendatabio/datamodel/blob/master/project_model.png)
<img src="{{ asset('images/docs/project_model.png') }}" alt="Projects model" with=350>


**Data access**: Measurements related to Plants or Vouchers in a Project should be accessible to users having access to datasets with such measurements. The most restricted policy will be applied when they conflict. Datasets are independent from Projects and may aggregate Measurements from plants and vouchers belonging to different projects. To avoid such conflict between Project and Dataset policies, consider defining a **public access**  policy to Projects as soon as possible as they do not control measurements.


<a name="users"></a>
***
## Users
The **Users** table stores information about the database users and administrators. Each **User** may be associated with a default [Person](Auxiliary-Objects#persons). When this user enters new data, this person is used as the default person in forms. The person can only be associated to a single user.

There are three possible **access levels** for a user:
    * `Registered User` (the lowest level) - have very few permissions
    * `Full User` - may be assigned as collaborators to Projects and Datasets;
    * `SuperAdmin` (the highest level). - superadmins have have access to all objects, regardless of project or dataset configuration.


![](https://github.com/opendatabio/datamodel/blob/master/user_model.png)
<img src="{{ asset('images/docs/user_model.png') }}" alt="Users model" with=350>

Each user is assigned to the **registered user** level when she or he registers in an OpenDataBio system. After that, a **SuperAdmin** may promote her/him to Full User or SuperAdmin. SuperAdmins also have the ability to edit other users and remove them from the database.


Every registered user is created along with a restricted Project and Dataset,  which are referred to as her **user Workspace**. This allows users to import plant and voucher data before incorporating them into a larger project. [TO IMPLEMENT: export batches of objects from one project to another].


**Data Access**:users are created upon registration. Only administrators can update and delete user records.


<a name="jobs"></a>
***
## User Jobs
The **UserJob** table is used to store temporarily background tasks, such as importing and exporting data. Any user is allowed to create a job; cancel their own jobs; list jobs that have not been deleted. The **Job** table contains the data used by the Laravel framework to interact with the Queue. The data from this table is deleted when the job runs successfully. The UserJob entity is used to keep this information, along with allowing for job logs, retrying failed jobs and cancelling jobs that have not yet finished.


![](https://github.com/opendatabio/datamodel/blob/master/user_userjob.png)
<img src="{{ asset('images/docs/user_userjob.png') }}" alt="User Jobs model" with=350>


**Data Access**: Each registered user can see, edit and remove their own UserJobs.
