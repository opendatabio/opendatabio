<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Models\Biocollection;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;

class BiocollectionsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->addColumn('details', function ($biocollection) {
            if($biocollection->irn >0) {
              return '<a href="http://sweetgum.nybg.org/science/ih/herbarium_details?irn='.($biocollection->irn).'>'.Lang::get('messages.details').'</a>';
            } else {
              return Lang::get('messages.noih');
            }
        })
        ->editColumn('acronym', function($biocollection) {
          return $biocollection->rawLink();
        })
        ->addColumn('vouchers', function ($biocollection) {
            if($biocollection->vouchers_count >0) {
              return '<a href="'.url('vouchers/'.$biocollection->id.'/biocollection').'">'.$biocollection->vouchers_count .'</a>';
            } else {
              return 0;
            }
        })
        ->rawColumns(['details','acronym','vouchers']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Biocollection::query()->withCount('vouchers');
        return $this->applyScopes($query);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\Datatables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->columns([
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'acronym' => ['title' => Lang::get('messages.acronym'), 'searchable' => true, 'orderable' => true],
                'name' => ['title' => Lang::get('messages.institution'), 'searchable' => true, 'orderable' => true],
                'details' => ['title' => Lang::get('messages.details'), 'searchable' => true, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => true, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                //'order' => [[0, 'asc']],
                'buttons' => [
                    'csv',
                    'excel',
                    'print',
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'columnDefs' => [[
                    'targets' => [0],
                    'visible' => false,
                ]],
            ]);
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'odb_biocollections_'.time();
    }
}
