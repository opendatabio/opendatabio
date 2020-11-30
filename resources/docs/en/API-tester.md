### Test the API

@if(Auth::user())

Use the form below to test the GET API and see de results. You need to add the desired `endpoint`, specify endpoint parameters and inform your `token`.

> {warning} Always use `limit=5` or other small value to see the response.

<larecipe-swagger base-url="{{ env('APP_URL') }}" endpoint="api/v0/"  default-method='get' name='voucher_endpoint' id='vouchers_apitest' has-auth-header=1 ></larecipe-swagger>

@else

> {warning} Must be logged to use the API tester!

@endif
