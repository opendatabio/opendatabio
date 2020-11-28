* [Data Access Objects](#)
  * [Datasets](#datasets)
  * [Projects](#projects)
  * [Herbaria](#herbaria)
  * [User](#users)      
  * [UserJobs](#jobs)      
* [**Core Objects**](core_objects)
* [**Trait Objects**](trait_objects)
* [**Auxiliary Objects**](auxiliary_objects)

# Data Access Objects (Management)

[Datasets](#datasets) and [Projects](#projects) are a ways to group data in OpenDataBio and define data policy access to them. Datasets group [Measurements](trait_objects#measurements) and Projects group [Plants](core_objects#plants) and [Vouchers](core_objects#vouchers).

Both must have at least one [User](#users) defined as `administrator`, who has total control over the project or dataset, including granting the following roles to other users: `administrator`, `collaborator` or `viewer`:
    * **Collaborators** are able to insert and edit objects (Plants and Vouchers) into the Project, or Measurements into the Dataset, but are not able to delete records nor change the project's configuration.
    * **Viewers** have read-only access to the Project's or Dataset data, including downloads;
    * Only **Full Users** and **SuperAdmins** may be assigned as **administrators** or **collaborators**. Thus, if a user who was administrator or collaborator of a project is demoted to "Registered User", she or he will become a viewer in the project or dataset.

<br>
[Herbaria](#herbaria) are Museum Collections, including Herbaria but not limited to plant Collections, to which [Vouchers](core_objects#vouchers) may belong to. It is included in this page because this model will be improved to permit such collections to use OpenDataBio for their data management and curational purposes. With this improvement the Herbaria Model will be renamed and gain additional management tools for Curators and museum staff. This will required the solving of possible conflicts of permissions with Projects privacy level.


<a name="datasets"></a>
<br>
<larecipe-progress type="primary" :value="100"></larecipe-progress>
## Datasets
**DataSets** are groups of [Measurements](trait_objects#measurements) which have the same authorization policy. Each DataSet may have one or more [Users](#users)  `administrators`, `collaborators` or `viewers`. Administrators may set the `privacy level` to *public access*, *restricted to registered users* or *restricted to authorized users*. This control access to the measurements within a dataset as exemplified in diagram below:
<br>
<img src="{{asset('images/dataset_model.png')}}" alt="Core objects" with=350>


<br><br>
Datasets may also have many [Bibliographic References](auxiliary_objects#bibreferences), which together with fields `policy` and `metadata` permits to annotate the dataset with relevant information for appending to downloads or presenting to unauthorized users in a request form. This allows to:
    * Link any publication that have used the dataset and optionally indicate that they are of mandatory citation when using the data;
    * Define a specific data `policy` when using the data
    * Detail any relevant `metadata` in addition to those that are automatically retrieved from the database like the [Traits](trait_objects#traits) measured.

<br><br>
<img src="{{asset('images/dataset_bibreference.png')}}" alt="Core objects" with=300>




<a name="herbaria"></a>
<br>
<larecipe-progress type="primary" :value="100"></larecipe-progress>
## Herbaria
The **Herbarium** object currenlty only stores basic information about Biological Collections that may be used to link to [Vouchers](core_objects#voucher), to indicate in which Biological Collections the voucher is deposited. The Herbarium object may be an Herbarium registered in the Index Herbariorum (http://sweetgum.nybg.org/science/ih/) or any other Museum Collection, formal or informal.  *Data access:* only [SuperAdmins](#users) can register or remove this entities.

The Herbarium object also interacts with the [Person](auxiliary_objects#persons) model. When a Person is linked to an herbarium it will be listed as a taxonomic specialist.

> {warning} Herbaria will renamed to a generic name to be more clear that it also allow the registration of non-plant collections. It will also be improved to permit such collections to use the OpenDataBio system to manage their vouchers (loans, donations, etc).


.<img src="{{asset('images/herbaria_model.png')}}" alt="Core objects" with=300>



> {success} **Data Access** - only administrators can register new Herbaria and delete unused herbarium. Updates are not yet implemented.

<a name="projects"></a>
<br>
<larecipe-progress type="primary" :value="100"></larecipe-progress>
## Projects
The **Project** model groups [Plants](core_objects#plants) and [Vouchers](core_objects#vouchers) and interacts with [Users](#users) in the same way as Datasets, having  `administrators`, `collaborators` or `viewers` users. Administrators may set the `privacy level` to *public access*, *restricted to registered users* or *restricted to authorized users*, which controls the data for the Plants and Vouchers objects belonging to the Project.
<br>
<img src="{{asset('images/project_model.png')}}" alt="Project Model" with=350>
<br>
<br>

> {success}  
  Measurements related to Plants or Vouchers in a Project should be accessible to users having access to datasets with such measurements. The most restricted policy will be applied when they conflict.
    <br><br>
  Datasets are independent from Projects and may aggregate Measurements from plants and vouchers belonging to different projects. To avoid such conflict between Project and Dataset policies, consider defining a **public access**  policy to Projects as soon as possible as they do not control measurements.

<br>
<br>


<a name="users"></a>
<br>
<larecipe-progress type="primary" :value="100"></larecipe-progress>
## Users
The **Users** table stores information about the database users and administrators. Each **User** may be associated with a default [Person](auxiliary_objects#persons). When this user enters new data, this person is used as the default person in forms. The person can only be associated to a single user.

There are three possible **access levels** for a user:
    * `Registered User` (the lowest level) - have very few permissions
    * `Full User` - may be assigned as collaborators to Projects and Datasets;
    * `SuperAdmin` (the highest level). - superadmins have have access to all objects, regardless of project or dataset configuration.

<br>
<img src="{{asset('images/user_model.png')}}" alt="Project Model" with=350>


> {primary} Each user is assigned to the **registered user** level when she or he registers in an OpenDataBio system. After that, a **SuperAdmin** may promote her/him to Full User or SuperAdmin. SuperAdmins also have the ability to edit other users and remove them from the database.
  <br>
  Every registered user is created along with a restricted Project and Dataset,  which are referred to as her **user Workspace**. This allows users to import plant and voucher data before incorporating them into a larger project. [TO IMPLEMENT: export batches of objects from one project to another].


> {success} **Data Access** - users are created upon registration. Only administrators can update and delete user records.


<a name="jobs"></a>
<br>
<larecipe-progress type="primary" :value="100"></larecipe-progress>
## Jobs
The **UserJob** table is used to store temporarily background tasks, such as importing and exporting data. Any user is allowed to create a job; cancel their own jobs; list jobs that have not been deleted. The **Job** table contains the data used by the Laravel framework to interact with the Queue. The data from this table is deleted when the job runs successfully. The UserJob entity is used to keep this information, along with allowing for job logs, retrying failed jobs and cancelling jobs that have not yet finished.


<br>
<img src="{{asset('images/user_userjob.png')}}" alt="User Job Model" with=350>


> {success} **Data Access**: Each registered user can see, edit and remove their own UserJobs.
