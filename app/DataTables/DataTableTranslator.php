<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use Illuminate\Support\Facades\Lang;

class DataTableTranslator
{
    public static function language()
    {
        return
        [
            'search' => Lang::get('datatables.search'),
            'processing' => Lang::get('datatables.processing'),
            'info' => Lang::get('datatables.info'),
            'infoEmpty' => Lang::get('datatables.info_empty'),
            'infoFiltered' => Lang::get('datatables.info_filtered'),
            'zeroRecords' => Lang::get('datatables.zero_records'),
            'emptyTable' => Lang::get('datatables.empty_table'),
            'paginate' => [
                'previous' => Lang::get('datatables.previous'),
                'next' => Lang::get('datatables.next'),
            ],
            'buttons' => [
                'reload' => Lang::get('datatables.reload'),
                'csv' => Lang::get('datatables.csv'),
                'excel' => Lang::get('datatables.excel'),
                'print' => Lang::get('datatables.print'),
            ],
        ];
    }
}
