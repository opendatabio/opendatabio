<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Person;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;

class PersonsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('abbreviation', function ($person) {
            return $person->rawLink();
        })
        ->addColumn('biocollection', function ($person) {
            return empty($person->biocollection) ? '' : $person->biocollection->name;
        })
        ->rawColumns(['abbreviation']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Person::query()->select(['id', 'full_name', 'abbreviation', 'email', 'institution', 'biocollection_id','notes'])->with('biocollection');

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
                'abbreviation' => ['title' => Lang::get('messages.abbreviation'), 'searchable' => true, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'full_name' => ['title' => Lang::get('messages.full_name'), 'searchable' => true, 'orderable' => true],
                'email' => ['title' => Lang::get('messages.email'), 'searchable' => true, 'orderable' => true],
                'institution' => ['title' => Lang::get('messages.institution'), 'searchable' => true, 'orderable' => true],
                'biocollection' => ['title' => Lang::get('messages.biocollection'), 'searchable' => false, 'orderable' => false],
                'notes' => ['title' => Lang::get('messages.notes'), 'searchable' => false, 'orderable' => false],
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
                    'targets' => [1, 4, 5],
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
        return 'odb_persons_'.time();
    }
}
