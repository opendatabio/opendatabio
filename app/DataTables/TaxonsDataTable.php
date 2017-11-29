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
            return $taxon->rawLink();
        })
        ->editColumn('level', function ($taxon) { return Lang::get('levels.tax.'.$taxon->level); })
        ->addColumn('authorSimple', function ($taxon) { return $taxon->authorSimple; })

        ->filterColumn('fullname', function ($query, $keyword) {
            $query->whereRaw('odb_txname(name, level, parent_id) like ?', ["%{$keyword}%"]);
        })
        ->filterColumn('authorSimple', function ($query, $keyword) {
            $query->where('persons.full_name', 'like', ["%{$keyword}%"])->orWhere('author', 'like', ["%{$keyword}%"]);
        })
        ->addColumn('plants', function ($taxon) {return $taxon->identified_plants_count; })
        ->addColumn('vouchers', function ($taxon) {return $taxon->identified_vouchers_count; })
        ->addColumn('measurements', function ($taxon) {return $taxon->measurements_count; })
        ->rawColumns(['fullname']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Taxon::query()
            ->select([
                'taxons.id',
                'name',
                'parent_id',
                'author',
                'rgt',
                'lft',
                'level',
                'valid',
                'full_name',
            ])->addSelect(DB::raw('odb_txname(name, level, parent_id) as fullname'))
            ->leftJoin('persons', 'taxons.author_id', '=', 'persons.id')
            ->withCount(['identified_plants', 'identified_vouchers', 'measurements']);

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
                'fullname' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'level' => ['title' => Lang::get('messages.level'), 'searchable' => false, 'orderable' => true],
                'authorSimple' => ['title' => Lang::get('messages.author'), 'searchable' => true, 'orderable' => true],
                'plants' => ['title' => Lang::get('messages.plants'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
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
        return 'odb_taxons_'.time();
    }
}
