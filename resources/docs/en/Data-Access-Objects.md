* [Data Access Objects](#)
  * [Datasets](#datasets)
  * [Projects](#projects)
  * [User](#users)      
  * [UserJobs](#jobs)      
* [**Core Objects**](Core-Objects)
* [**Auxiliary Objects**](Auxiliary-Objects)
* [**Trait Objects**](Trait-Objects)
* [**API**](API)

# Data Access Objects
[Datasets](#datasets) and [Projects](#projects) allows to group data, control data access and publish datasets. Datasets group [Measurements](Trait-Objects#measurements) and Projects group [Individuals](Core-Objects#individuals), [Vouchers](Core-Objects#vouchers) or [Media Files](Auxiliary-Objects#mediafiles).
<br><br>
Both must have at least one [User](#users) defined as `administrator`, who has total control over the project or dataset, including granting the following roles to other users: `administrator`, `collaborator` or `viewer`:
* **Collaborators** are able to insert and edit objects, but are not able to delete records nor change the project's configuration.
* **Viewers** have read-only access to the data, including downloads;
* Only **Full Users** and **SuperAdmins** may be assigned as **administrators** or **collaborators**. Thus, if a user who was administrator or collaborator of a project is demoted to "Registered User", she or he will become a viewer in the project or dataset.
<br><br>
A <a href="https://creativecommons.org/licenses/">CreativeCommons.org</a> public license must be assigned to Projects or Datasets released as *public access* or *restricted to registered users*.
<br><br>
A **citation** will be generated for any Project or Dataset, indicating how to cite publicly accessible data.

<a name="datasets"></a>
***
## Datasets
**DataSets** are groups of [Measurements](Trait-Objects#measurements) and may have one or more [Users](#users)  `administrators`, `collaborators` or `viewers`. Administrators may set the `privacy level` to *public access*, *restricted to registered users* or *restricted to authorized users*. This control access to the measurements within a dataset as exemplified in diagram below:

![](https://github.com/opendatabio/datamodel/blob/master/dataset_model.png)
<img src="{{ asset('images/docs/dataset_model.png') }}" alt="Datasets model" with=350>

Datasets may also have many [Bibliographic References](Auxiliary-Objects#bibreferences), which together with fields `policy`, `metadata` permits to annotate the dataset with relevant information for appending to downloads. This allows to:
    * Link any publication that have used the dataset and optionally indicate that they are of mandatory citation when using the data;
    * Define a specific data `policy` when using the data in addition to the A <a href="https://creativecommons.org/licenses/">CreativeCommons.org</a> public `license`;
    * Detail any relevant `metadata` in addition to those that are automatically retrieved from the database like the [Traits](Trait-Objects#traits) measured.


![](https://github.com/opendatabio/datamodel/blob/master/dataset_bibreference.png)
<img src="{{ asset('images/docs/dataset_bibreference.png') }}" alt="Datasets model" with=350>


<a name="projects"></a>
***
## Projects
The **Project** model groups [Individuals](Core-Objects#individuals) and [Vouchers](Core-Objects#vouchers) and interacts with [Users](#users) in the same way as Datasets, having  `administrators`, `collaborators` or `viewers` users. Administrators may set the `privacy level` to *public access*, *restricted to registered users* or *restricted to authorized users*, which controls the data for the Individuals and Vouchers objects belonging to the Project.

![](https://github.com/opendatabio/datamodel/blob/master/project_model.png)
<img src="{{ asset('images/docs/project_model.png') }}" alt="Projects model" with=350>

**Data access**: Measurements related to Individuals or Vouchers in a Project should be accessible to users having access to datasets with such measurements. The most restricted policy will be applied when they conflict. Datasets are independent from Projects and may aggregate Measurements from individuals and vouchers belonging to different projects.


<a name="users"></a>
***
## Users
The **Users** table stores information about the database users and administrators. Each **User** may be associated with a default [Person](Auxiliary-Objects#persons). When this user enters new data, this person is used as the default person in forms. The person can only be associated to a single user.

There are three possible **access levels** for a user:
    * `Registered User` (the lowest level) - have very few permissions
    * `Full User` - may be assigned as collaborators to Projects and Datasets;
    * `SuperAdmin` (the highest level). - superadmins have have access to all objects, regardless of project or dataset configuration and is the system administrator.


![](https://github.com/opendatabio/datamodel/blob/master/user_model.png)
<img src="{{ asset('images/docs/user_model.png') }}" alt="Users model" with=350>

Each user is assigned to the **registered user** level when she or he registers in an OpenDataBio system. After that, a **SuperAdmin** may promote her/him to Full User or SuperAdmin. SuperAdmins also have the ability to edit other users and remove them from the database.


Every registered user is created along with a restricted Project and Dataset,  which are referred to as her **user Workspace**. This allows users to import individual and voucher data before incorporating them into a larger project. [TO IMPLEMENT: export batches of objects from one project to another].


**Data Access**:users are created upon registration. Only administrators can update and delete user records.


<a name="jobs"></a>
***
## User Jobs
The **UserJob** table is used to store temporarily background tasks, such as importing and exporting data. Any user is allowed to create a job; cancel their own jobs; list jobs that have not been deleted. The **Job** table contains the data used by the Laravel framework to interact with the Queue. The data from this table is deleted when the job runs successfully. The UserJob entity is used to keep this information, along with allowing for job logs, retrying failed jobs and cancelling jobs that have not yet finished.


![](https://github.com/opendatabio/datamodel/blob/master/user_userjob.png)
<img src="{{ asset('images/docs/user_userjob.png') }}" alt="User Jobs model" with=350>


**Data Access**: Each registered user can see, edit and remove their own UserJobs.
