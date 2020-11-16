<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Project;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;

class ProjectsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('name', function ($project) {
            return $project->rawLink();
        })
        ->editColumn('privacy', function ($project) { return Lang::get('levels.privacy.'.$project->privacy); })
        ->addColumn('full_name', function ($project) {return $project->full_name; })
        ->addColumn('plants', function ($project) {
          return '<a href="'.url('projects/'. $project->id. '/plants').'" >'.$project->plants_public_count().'</a>';
        })
        ->addColumn('vouchers', function ($project) {
          return '<a href="'.url('projects/'. $project->id. '/vouchers').'" >'.$project->vouchers_public_count().'</a>';
        })
        ->addColumn('members', function ($project) {
            if (empty($project->users)) {
                return '';
            }
            $ret = '';
            foreach ($project->users as $user) {
                if (isset($user->person->full_name)) {
                  $ret .= $user->person->full_name.'<br>';
                } else {
                  $ret .= htmlspecialchars($user->email).'<br>';
                }
            }

            return $ret;
        })
        ->rawColumns(['name', 'members','plants','vouchers']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Project::query()->with('users');
        //->withCount(['plants', 'vouchers'])->

        if ($this->tag) {
            $query->whereHas('tags',function($tag) { $tag->where('tags.id',$this->tag);});
        }

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
                'description' => ['title' => Lang::get('messages.description'), 'searchable' => false, 'orderable' => false],
                'members' => ['title' => Lang::get('messages.members'), 'searchable' => false, 'orderable' => false],
                'plants' => ['title' => Lang::get('messages.plants'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
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
                    'targets' => [1, 3,4],
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
        return 'odb_projects_'.time();
    }
}
