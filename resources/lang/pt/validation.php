<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => 'O campo :attribute não foi aceito.',
    'active_url'           => 'O campo :attribute deve ser uma URL válida.',
    'after'                => 'O campo :attribute deve ser uma data posterior a :date.',
    'after_or_equal'       => 'O campo :attribute deve ser uma data igual ou posterior a :date.',
    'alpha'                => 'O campo :attribute deve conter apenas letras.',
    'alpha_dash'           => 'O campo :attribute deve conter apenas letras, números ou hífens.',
    'alpha_num'            => 'O campo :attribute deve conter apenas letras e números.',
    'array'                => 'O campo :attribute deve conter um vetor.',
    'before'               => 'O campo :attribute deve ser uma data anterior a  :date.',
    'before_or_equal'      => 'O campo :attribute deve ser uma data anterior ou igual a :date.',
    'between'              => [
        'numeric' => 'O campo :attribute deve ser um valor entre :min e :max.',
        'file'    => 'O campo :attribute deve ter entre :min e :max kilobytes.',
        'string'  => 'O campo :attribute deve ter entre :min e :max caracteres.',
        'array'   => 'O campo :attribute deve ter entre :min e :max itens.',
    ],
    'boolean'              => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed'            => 'O campo :attribute não está igual a sua confirmação.',
    'date'                 => 'O campo :attribute deve ser uma data.',
    'date_format'          => 'O campo :attribute deve ser uma data no formato :format.',
    'different'            => 'O campo :attribute e o campo :other devem ser diferentes.',
    'digits'               => 'O campo :attribute deve ter :digits dígitos.',
    'digits_between'       => 'O campo :attribute deve ter entre :min e :max dígitos.',
    'dimensions'           => 'O campo :attribute tem dimensões inválidas.',
    'distinct'             => 'O campo :attribute tem um valor duplicado.',
    'email'                => 'O campo :attribute deve ser um endereço de e-mail válido.',
    'exists'               => 'O valor selecionado em :attribute é inválido.',
    'file'                 => 'O campo :attribute deve ser um arquivo.',
    'filled'               => 'O campo :attribute tem preenchimento obrigatório.',
    'image'                => 'O campo :attribute deve ser uma imagem.',
    'in'                   => 'O valor selecionado em :attribute é inválido.',
    'in_array'             => 'O valor selecionado em :attribute precisa ser válido em :other.',
    'integer'              => 'O campo :attribute deve ser um número inteiro.',
    'ip'                   => 'O campo :attribute deve ser um IP válido.',
    'json'                 => 'O campo :attribute deve conter um JSON válido.',
    'max'                  => [
        'numeric' => 'O campo :attribute não pode ser maior do que :max.',
        'file'    => 'O campo :attribute não pode ser maior do que :max kilobytes.',
        'string'  => 'O campo :attribute deve ter menos do que :max caracteres.',
        'array'   => 'O campo :attribute deve ter menos de :max itens.',
    ],
    'mimes'                => 'O campo :attribute precisa ser um arquivo do tipo: :values.',
    'mimetypes'            => 'O campo :attribute precisa ser um arquivo do tipo: :values.',
    'min'                  => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'file'    => 'O campo :attribute deve ter no mínimo :min kilobytes.',
        'string'  => 'O campo :attribute deve ter no mínimo :min caracteres.',
        'array'   => 'O campo :attribute deve ter no mínimo :min itens.',
    ],
    'not_in'               => 'O valor selecionado em :attribute é inválido.',
    'numeric'              => 'O campo :attribute deve ser um número.',
    'present'              => 'O campo :attribute deve estar preenchido.',
    'regex'                => 'O campo :attribute não tem o formato válido.',
    'required'             => 'O campo :attribute tem preenchimento obrigatório.',
    'required_if'          => 'O campo :attribute tem preenchimento obrigatório se :other é :value.',
    'required_unless'      => 'O campo :attribute tem preenchimento obrigatório a não se que :other seja :values.',
    'required_with'        => 'O campo :attribute tem preenchimento obrigatório se :values estiver preenchido.',
    'required_with_all'    => 'O campo :attribute tem preenchimento obrigatório se :values estiver preenchido.',
    'required_without'     => 'O campo :attribute tem preenchimento obrigatório se :values não estiver preenchido.',
    'required_without_all' => 'O campo :attribute tem preenchimento obrigatório se :values não estiver preenchido.',
    'same'                 => 'Os campos :attribute e :other devem ser iguais.',
    'size'                 => [
        'numeric' => 'O campo :attribute deve ter tamanho :size.',
        'file'    => 'O campo :attribute deve ter :size kilobytes.',
        'string'  => 'O campo :attribute deve ter :size caracteres.',
        'array'   => 'O campo :attribute deve conter :size itens.',
    ],
    'string'               => 'O campo :attribute deve conter caracteres.',
    'timezone'             => 'O campo :attribute deve ser um fuso horário.',
    'unique'               => 'O campo :attribute já está tomado.',
    'uploaded'             => 'O upload de arquivo para o campo :attribute falhou.',
    'url'                  => 'O campo :attribute está com o formato inválido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'parent_id' => [
            'required_unless' => 'O campo parent é obrigatório, exceto para países.',
        ],
        // these are for registering plants
        'x' => [
            'max' => 'Os campos x e y devem ser menores ou iguais às dimensões da parcela indicada.',
        ],
        'y' => [
            'max' => 'Os campos x e y devem ser menores ou iguais às dimensões da parcela indicada.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
    'full_name' => 'nome completo',
    'abbreviation' => 'abreviação',
    'institution' => 'instituição',
    'biocollection_id' => 'herbário',

    ],

];
