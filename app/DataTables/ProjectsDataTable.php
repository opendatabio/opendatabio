<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Models\Project;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;
use Gate;
use Auth;

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
        ->addColumn('title', function ($project) {
          $title = isset($project->title) ? $project->title  : $project->name;
          return $title;
        })
        ->addColumn('datasets', function ($project) {
          $datasets_count = $project->datasets()->count();
          return '<a href="'.url('project/'. $project->id. '/datasets').'" >'.$datasets_count.'</a>';
        })
        ->filterColumn('name', function ($query, $keyword) {
            $query->orWhereHas('tags',function($tag) use($keyword) {
                $tag->whereHas('translations',function($trn) use ($keyword) { $trn->where('translation','like','%'.$keyword.'%');
                });
            })
            ->orWhere('title','like','%'.$keyword.'%')
            ->orWhere('name','like','%'.$keyword.'%')
            ->orWhere('description','like','%'.$keyword.'%');
        })
        ->addColumn('admins', function ($project) {
            if (empty($project->admins)) {
                return '';
            }
            $ret = '';
            foreach ($project->admins as $user) {
                if (isset($user->person->full_name)) {
                  $ret .= $user->person->full_name.'<br>';
                } else {
                  $ret .= htmlspecialchars($user->email).'<br>';
                }
            }
            return $ret;
        })
        ->rawColumns(['name','admins','datasets']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Project::query()->with('users')->withCount(['datasets']);
        if ($this->tag) {
            $tagid = $this->tag;
            $query = $query->whereHas('tags',function($tag) use($tagid) { $tag->where('tags.id',$tagid);});
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
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'name' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'title' => ['title' => Lang::get('messages.title'), 'searchable' => false, 'orderable' => false],
                'description' => ['title' => Lang::get('messages.description'), 'searchable' => false, 'orderable' => false],
                'admins' => ['title' => Lang::get('messages.admins'), 'searchable' => false, 'orderable' => false],
                'datasets' => ['title' => Lang::get('messages.datasets'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'asc']],
                'buttons' => [
                    'pageLength',
                    'csv',
                    'excel',
                    'print',
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'columnDefs' => [[
                    'targets' => [0,3,4],
                    'visible' => false,
                ]],
                'pagelength' => 10,
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
