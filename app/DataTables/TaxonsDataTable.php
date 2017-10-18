<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Taxon;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;
use DB;

class TaxonsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('fullname', function ($taxon) {
            return '<a href="'.url('taxons/'.$taxon->id).'">'.
                // Needs to escape special chars, as this will be passed RAW
                htmlspecialchars($taxon->qualifiedFullname).'</a>';
        })
        ->editColumn('level', function ($taxon) { return Lang::get('levels.tax.'.$taxon->level); })
        ->filterColumn('fullname', function ($query, $keyword) {
            $query->whereRaw('odb_txname(name, level, parent_id) like ?', ["%{$keyword}%"]);
        })
//	    ->addColumn('full_name', function($location) {return $location->full_name;})
        ->rawColumns(['fullname']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Taxon::query()->select($this->getColumns())->addSelect(DB::raw('odb_txname(name, level, parent_id) as fullname'));

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
                'fullname' => ['title' => 'Name'],
                'level',
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'order' => [[0, 'desc']],
                'buttons' => [
                    'csv',
                    'excel',
                    'print',
                    'reload',
                ],
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        // we need to ask for all of the columns that might be needed for other methods
        return [
            'id',
            'name',
            'parent_id',
        'rgt',
        'lft',
        'level',
        'valid',
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'odb_taxons_'.time();
    }
}
