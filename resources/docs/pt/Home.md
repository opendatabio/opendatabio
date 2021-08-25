# OpenDataBio Documentação

<a href="https://github.com/opendatabio/opendatabio/wiki/home-pt" target="__blank" >
  <i class="fab fa-github"></i> Documentação disponível no GitHub
</a>


<a name="api_tester"></a>

### Testar a API

@if(Auth::user())

Use o formulário abaixo para testar e entender como funciona o acesso aos dados deste repositório usando a GET API, que pode ser usada diretamente no R através do pacote do [OpenDataBio-R](https://github.com/opendatabio/opendatabio-r).


Aqui você digita o `endpoint` desejado, o seu `token` e algum parametro de busca para o endpoint. Veja <a href="https://github.com/opendatabio/opendatabio/wiki/home-pt" target="__blank" >a documentação </a> para entender o que é isso.

> {warning} Use sempre `limit=5` ou menor para ver a resposta. O objetivo aqui é apenas entender, não baixar dados. Para isso, use os caminhos implementados.

<larecipe-swagger base-url="{{ env('APP_URL') }}" endpoint="/api/v0/"  default-method='get' name='voucher_endpoint' id='vouchers_apitest' has-auth-header=1 ></larecipe-swagger>

@else

> {warning} Você precisa estar logado para poder testar a API aqui!

@endif
