# Documentação OpenDataBio

> {primary}  Este espaço é reservado para administradores definirem documentação e diretivas personalizadas para usuários de uma instalação específica do OpenDataBio. Por exemplo, este é um espaço para adicionar um **código de conduta** para os usuários, quem contatar para se tornar um usuário pleno, etc.

O <a href="https://opendatabio.github.io" target="__blank" >OpenDataBio site</a>  contém a documentação do software onde você pode encontrar ajuda no uso e instalação do OpenDataBio.

Para personalizar os documentos de instalação:

1. Estes documentos são renderizados usando o pacote laravel [LaRecipe](https://github.com/saleem-hadad/larecipe)
1. Os arquivos são salvos em `resources/docs/{lang}` e podem ser multi-idiomas
1. Você pode criar quantos arquivos markdown `*.md` e criar um índice para eles.
1. Verifique o [LaRecipe](https://github.com/saleem-hadad/larecipe) para obter mais informações sobre como personalizar seus arquivos markdown


<a name="api_tester"> </a>
### Teste a API

Se você for um usuário logado, verá abaixo um testador para a [GET API](https://opendatabio.github.io/docs/api) desta instalação.

@if(Auth::user ())

Use o formulário abaixo para testar a API GET e ver os resultados.
Você precisa adicionar o `endpoint` desejado, especificar os parâmetros do endpoint e informar seu` token`. Ver [GET API docs](https://opendatabio.github.io/docs/api/quick-reference)

> {warning} Sempre use `limit = 5` ou outro valor pequeno para ver a resposta. Use para entender apenas.

<larecipe-swagger base-url="{{env('APP_URL')}}" endpoint="/api/v0/" default-method='get' has-auth- header = 1> </larecipe-swagger>

@else

> {warning} Deve ser registrado para usar o testador de API!

@endif
