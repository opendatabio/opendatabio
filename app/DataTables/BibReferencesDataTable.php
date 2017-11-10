<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\BibReference;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;
use DB;

class BibReferenceDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('bibkey', function ($reference) {
            return '<a href="'.url('references/'.$reference->id).'">'.
                // Needs to escape special chars, as this will be passed RAW
                htmlspecialchars($reference->bibkey).'</a>';
        })
        ->addColumn('author', function ($reference) { return $reference->author; })
        ->addColumn('year', function ($reference) { return $reference->year; })
        ->addColumn('title', function ($reference) { return $reference->title; })
        ->filterColumn('title', function ($query, $keyword) {
            $query->where('bibtex', 'like', ["%{$keyword}%"]);
        })
        ->rawColumns(['bibkey']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = BibReference::query()
            ->select([
                'id',
                'bibtex',
            ])->addSelect(DB::raw('odb_bibkey(bibtex) as bibkey'));

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
                'bibkey' => ['title' => Lang::get('messages.bibtex_key'), 'searchable' => false, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'author' => ['title' => Lang::get('messages.authors'), 'searchable' => false, 'orderable' => false],
                'year' => ['title' => Lang::get('messages.year'), 'searchable' => false, 'orderable' => false],
                'title' => ['title' => Lang::get('messages.title'), 'searchable' => true, 'orderable' => false],
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
        return 'odb_references_'.time();
    }
}
