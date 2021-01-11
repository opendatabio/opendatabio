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
          $plants_count = $project->getCount('all',null,"plants");
          return '<a href="'.url('projects/'. $project->id. '/plants').'" >'.$plants_count.'</a>';
        })
        ->addColumn('vouchers', function ($project) {
          $vouchers_count = $project->getCount('all',null,"vouchers");
          return '<a href="'.url('vouchers/'. $project->id. '/project').'" >'.$vouchers_count.'</a>';
        })
        ->addColumn('taxons', function ($project) {
          $taxons_count = $project->taxonsCount();
          return '<a href="'.url('taxons/'. $project->id. '/project').'" >'.$taxons_count.'</a>';
        })
        ->addColumn('locations', function ($project) {
          $locations_count = $project->getCount('all',null,"locations");
          return '<a href="'.url('locations/'. $project->id. '/project').'" >'.$locations_count.'</a>';
        })
        ->addColumn('datasets', function ($project) {
          $dataset_counts = $project->getCount('all',null,"datasets");
          return '<a href="'.url('datasets/'. $project->id. '/project').'" >'.$dataset_counts.'</a>';
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
        ->rawColumns(['name', 'members','plants','vouchers','taxons','locations','datasets']);
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

        if ($this->dataset) {
            $dataset = $this->dataset;
            $query = $query->whereHas('plant_measurements',function($measurement) use($dataset) { $measurement->withoutGlobalScopes()->where('dataset_id','=',$dataset);})->orWhereHas('voucher_measurements',function($measurement) use($dataset) {  $measurement->withoutGlobalScopes()->where('dataset_id','=',$dataset);});
        }
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
                'name' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'privacy' => ['title' => Lang::get('messages.privacy'), 'searchable' => false, 'orderable' => true],
                'description' => ['title' => Lang::get('messages.description'), 'searchable' => false, 'orderable' => false],
                'members' => ['title' => Lang::get('messages.members'), 'searchable' => false, 'orderable' => false],
                'plants' => ['title' => Lang::get('messages.plants'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
                'taxons' => ['title' => Lang::get('messages.taxons'), 'searchable' => false, 'orderable' => false],
                'locations' => ['title' => Lang::get('messages.locations'), 'searchable' => false, 'orderable' => false],
                'datasets' => ['title' => Lang::get('messages.datasets'), 'searchable' => false, 'orderable' => false],
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
