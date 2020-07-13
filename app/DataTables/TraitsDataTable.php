<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\ODBTrait;
use App\UserTranslation;
use Lang;

use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
#use Lang;

class TraitsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('name', function ($odbtrait) {
            return $odbtrait->rawLink();
        })
        ->editColumn('type', function ($odbtrait) { return Lang::get('levels.traittype.'.$odbtrait->type); })
        ->addColumn('details', function ($odbtrait) {return $odbtrait->details(); })
        ->addColumn('measurements', function ($odbtrait) {return $odbtrait->measurements_count; })
        ->filterColumn('name', function ($query, $keyword) {
            $translations = UserTranslation::where('translation', 'like', '%'.$keyword.'%')->where('translatable_type','like','%ODBTrait%')->get()->pluck('translatable_id')->toArray();
            $query->whereIn('id', $translations);
        })
        ->rawColumns(['name']);

    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
      $query = ODBTrait::with(['translations','categories.translations'])->withCount("measurements")->orderBy('export_name', 'asc');
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
                'name' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => false],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'type' => ['title' => Lang::get('messages.type'), 'searchable' => false, 'orderable' => true],
                'details' => ['title' => Lang::get('messages.details'), 'searchable' => false, 'orderable' => false],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'asc']],
                'buttons' => [
                    'csv',
                    'excel',
                    'print',
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'columnDefs' => [[
                    'targets' => [1],
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
        return 'odb_odbtraits_'.time();
    }
}
