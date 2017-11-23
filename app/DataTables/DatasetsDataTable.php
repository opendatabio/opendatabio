<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Dataset;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;

class DatasetsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('name', function ($dataset) {
            return '<a href="'.url('datasets/'.$dataset->id).'">'.
                // Needs to escape special chars, as this will be passed RAW
                htmlspecialchars($dataset->name).'</a>';
        })
        ->editColumn('privacy', function ($dataset) { return Lang::get('levels.privacy.'.$dataset->privacy); })
        ->addColumn('full_name', function ($dataset) {return $dataset->full_name; })
        ->addColumn('plants', function ($dataset) {return $dataset->plants_count; })
        ->addColumn('vouchers', function ($dataset) {return $dataset->vouchers_count; })
        ->addColumn('measurements', function ($dataset) {return $dataset->measurements_count; })
        ->addColumn('members', function ($dataset) {
            if (empty($dataset->users)) {
                return '';
            }
            $ret = '';
            foreach ($dataset->users as $user) {
                $ret .= htmlspecialchars($user->email).'<br>';
            }

            return $ret;
        })
        ->addColumn('tags', function ($dataset) {
            if (empty($dataset->tags)) {
                return '';
            }
            $ret = '';
            foreach ($dataset->tags as $tag) {
                $ret .= "<a href='".url('tags/'.$tag->id)."'>".htmlspecialchars($tag->name).'</a><br>';
            }

            return $ret;
        })
        ->rawColumns(['name', 'members', 'tags']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Dataset::withCount(['measurements'])->with(['users', 'tags.translations']);
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
                'name' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'privacy' => ['title' => Lang::get('messages.privacy'), 'searchable' => false, 'orderable' => true],
                'members' => ['title' => Lang::get('messages.members'), 'searchable' => false, 'orderable' => false],
                'tags' => ['title' => Lang::get('messages.tags'), 'searchable' => false, 'orderable' => false],
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
                    'targets' => [1, 3],
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
        return 'odb_datasets_'.time();
    }
}
