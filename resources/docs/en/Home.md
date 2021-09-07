# OpenDataBio Documentation


> {primary}   This space is reserved for administrators to define custom documentation and directives for users of a specific an OpenDataBio installation. For example, this is a space to add a **code of conduct** for users, who to contact to become a full user, etc..

The <a href="https://opendatabio.github.io" target="__blank" >OpenDataBio site</a> contains the software documentation where you may find help in using and installing OpenDataBio.

To customize the installation docs:

1. This docs are rendered using the laravel [LaRecipe](https://github.com/saleem-hadad/larecipe) package
1. Files are save in `resources/docs/{lang}` and may be multilingual
1. You may create as many markdown files `*.md` and create an index for them.
1. Checkout the [LaRecipe package](https://github.com/saleem-hadad/larecipe) for more information on how to customize the markdown files.


<a name="api_tester"></a>
### Test the API
If you are a logged user you will see below a tester for this installation [GET API](https://opendatabio.github.io/docs/api).

@if(Auth::user())

Use the form below to test the GET API and see de results. You need to add the desired `endpoint`, specify endpoint parameters and inform your `token`.

> {warning} Always use `limit=5` or other small value to see the response.

<larecipe-swagger base-url="{{ env('APP_URL') }}" endpoint="/api/v0/"  default-method='get' has-auth-header=1 ></larecipe-swagger>

@else

> {warning} Must be logged to use the API tester!

@endif
