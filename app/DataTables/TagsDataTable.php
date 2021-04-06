<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Models\Tag;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;
use DB;

class TagsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->addColumn('name', function ($tag) { return $tag->rawLink(); })
        ->addColumn('description', function ($tag) { return $tag->description; })
        ->addColumn('datasets', function ($tag) {
              return '<a href="'.url('datasets/'. $tag->id. '/tags').'" >'.$tag->datasets()->count().'</a>';
        })
        ->addColumn('projects', function ($tag) {
                return '<a href="'.url('projects/'. $tag->id. '/tags').'" >'.$tag->projects()->count().'</a>';
        })
        ->addColumn('media', function ($tag) { return $tag->media()->count(); })
        ->filterColumn('name', function ($query, $keyword) {
            $query->whereHas('translations',function($translation) use($keyword) { $translation->where('translation','like','%'.$keyword.'%');});
        })
        ->rawColumns(['name','projects','datasets']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Tag::query()->with('translations');

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
                'name' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'description' => ['title' => Lang::get('messages.description'), 'searchable' => false, 'orderable' => false],
                'datasets' => ['title' => Lang::get('messages.datasets'), 'searchable' => false, 'orderable' => false],
                'projects' => ['title' => Lang::get('messages.projects'), 'searchable' => false, 'orderable' => false],
                'media' => ['title' => Lang::get('messages.media'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                //'order' => [[0, 'asc']],
                'buttons' => [
                    'csv',
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'columnDefs' => [[
                    'targets' => [0,2],
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
