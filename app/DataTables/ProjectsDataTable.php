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
        ->editColumn('title', function ($project) {
            $title  = $project->generateCitation(true,true);
            $ret =  "<h4 style='cursor:pointer;'>".$title."</h4>";
            $id = "description_".$project->id;
            //$ret .= '<a class="showdataset"project  data-target="'.$id.'" style="font-size: 1em;"><span class="glyphicon glyphicon-plus unstyle"></span></a>';

            $btn= $project->description.'<br><a href="'.url('projects/'.$project->id).'" class="btn btn-primary btn-xs" data-toggle="tooltip" rel="tooltip" data-placement="right" title="'.Lang::get('messages.details').'">
              <i class="fas fa-info"></i>&nbsp;info
            </a>&nbsp;&nbsp;';
            if (Gate::allows('export', $project)) {
              $btn .=  '<a href="'.url('projects/'.$project->id."/download").'" class="btn btn-success btn-xs datasetexport" id='.$project->id.' data-toggle="tooltip" rel="tooltip" data-placement="right" title="'.Lang::get('messages.data_download').'"><span class="glyphicon glyphicon-download-alt unstyle"></span></a>';
            } elseif (Auth::user()) {
              $btn .= '<a href="'.url('projects/'.$project->id."/request").'" class="btn btn-warning btn-xs datasetexport" id='.$project->id.'  data-toggle="tooltip" rel="tooltip" data-placement="right" title="'.Lang::get('messages.data_request').'"><span class="glyphicon glyphicon-download-alt unstyle"></span></a>';
            }
            $ret .= '<div id="'.$id.'" hidden >'.$btn.'</div>';
            return $ret;
        })
        ->editColumn('privacy', function ($project) {
            $txt = "";
            if ($project->privacy == Project::PRIVACY_AUTH) {
              $txt .='<i class="fas fa-lock"></i> ';
            }
            if ($project->privacy == Project::PRIVACY_PUBLIC) {
              $txt .='<i class="fas fa-lock-open"></i> ';
            }
            if ($project->privacy == Project::PRIVACY_REGISTERED) {
              $txt .='<i class="fas fa-sign-in-alt"></i> ';
            }
            $txt .= Lang::get('levels.privacy.'.$project->privacy);
            return $txt;
        })
        ->editColumn('license', function ($project) {
            $license_logo = 'images/cc_srr_primary.png';
            // and null == $project->policy
            if (null != $project->license and $project->privacy>0) {
              $license = explode(" ",$project->license);
              $license_logo = 'images/'.mb_strtolower($license[0]).".png";
            }
            $txt = '<img src="'.asset($license_logo).'" width="80px">';
            if (null != $project->license and $project->privacy>0) {
              $txt = $project->license."<br>".$txt;
            }
            return $txt;
        })
        //->addColumn('full_name', function ($dataset) {return $dataset->full_name; })
        //->addColumn('full_name', function ($project) {return $project->full_name; })
        ->addColumn('individuals', function ($project) {
          //$individuals_count = $project->getCount('all',null,"individuals");
          $individuals_count = $project->individualsCount();
          return '<a href="'.url('individuals/'. $project->id. '/project').'" >'.$individuals_count.'</a>';
        })
        ->addColumn('vouchers', function ($project) {
          //$vouchers_count = $project->getCount('all',null,"vouchers");
          $vouchers_count = $project->vouchersCount();
          return '<a href="'.url('vouchers/'. $project->id. '/project').'" >'.$vouchers_count.'</a>';
        })
        ->filterColumn('name', function ($query, $keyword) {
            $query->whereHas('authors',function($author) use($keyword) { $author->whereHas('person',function($person) use($keyword) { $person->where('full_name','like','%'.$keyword.'%');});
            })
            ->orWhereHas('tags',function($tag) use($keyword) {
                $tag->whereHas('translations',function($trn) use ($keyword) { $trn->where('translation','like','%'.$keyword.'%');
                });
            })
            ->orWhere('title','like','%'.$keyword.'%')
            ->orWhere('name','like','%'.$keyword.'%');
        })
        /*
        ->addColumn('species', function ($project) {
          //$taxons_count = $project->taxonsCount();
          $taxons_count = $project->speciesCount();
          return '<a href="'.url('taxons/'. $project->id. '/project').'" >'.$taxons_count.'</a>';
        })
        ->addColumn('locations', function ($project) {
          //$locations_count = $project->getCount('all',null,"locations");
          $locations_count = $project->locationsCount();
          return '<a href="'.url('locations/'. $project->id. '/project').'" >'.$locations_count.'</a>';
        })
        ->addColumn('datasets', function ($project) {
          //$dataset_counts = $project->getCount('all',null,"datasets");
          $dataset_counts = $project->datasetsCount();
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
        */
        //'species','locations','datasets',
        ->rawColumns(['name', 'title','individuals','vouchers','license','privacy']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Project::query()->with('users');
        //->withCount(['individuals', 'vouchers'])->

        if ($this->dataset) {
            $dataset = $this->dataset;
            $query = $query->whereHas('individualsMeasurements',function($measurement) use($dataset) { $measurement->withoutGlobalScopes()->where('dataset_id','=',$dataset);})->orWhereHas('voucher_measurements',function($measurement) use($dataset) {  $measurement->withoutGlobalScopes()->where('dataset_id','=',$dataset);});
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
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'name' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'title' => ['title' => Lang::get('messages.title'), 'searchable' => false, 'orderable' => true],
                'privacy' => ['title' => Lang::get('messages.privacy'), 'searchable' => false, 'orderable' => true],
                'license' => ['title' => Lang::get('messages.license'), 'searchable' => false, 'orderable' => true],
                /*'description' => ['title' => Lang::get('messages.description'), 'searchable' => false, 'orderable' => false],
                'members' => ['title' => Lang::get('messages.members'), 'searchable' => false, 'orderable' => false],*/
                'individuals' => ['title' => Lang::get('messages.individuals'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
                //'species' => ['title' => Lang::get('messages.species'), 'searchable' => false, 'orderable' => false],
                //'locations' => ['title' => Lang::get('messages.locations'), 'searchable' => false, 'orderable' => false],
                //'datasets' => ['title' => Lang::get('messages.datasets'), 'searchable' => false, 'orderable' => false],
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
                    'targets' => [0,1],
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
