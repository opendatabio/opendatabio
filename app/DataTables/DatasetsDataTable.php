<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Models\Dataset;
use App\Models\Project;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Gate;
use Lang;
use DB;
use Auth;

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
        ->editColumn('name',function($dataset) {
          return $dataset->rawLink();
        })
        ->editColumn('title', function ($dataset) {
            $ret =  "<span style='cursor:pointer;'>".$dataset->generateCitation(true,true)."</span>";
            $id = "description_".$dataset->id;
            //$ret .= '<a class="showdataset"  data-target="'.$id.'" style="font-size: 1em;"><span class="glyphicon glyphicon-plus unstyle"></span></a>';

            $btn= $dataset->description.'<br><a href="'.url('datasets/'.$dataset->id).'" class="btn btn-primary btn-xs" data-toggle="tooltip" rel="tooltip" data-placement="right" title="'.Lang::get('messages.details').'">
              <i class="fas fa-info"></i>&nbsp;info
            </a>&nbsp;&nbsp;';
            if (Gate::allows('export',$dataset)) {
              $btn .=  '<a href="'.url('datasets/'.$dataset->id."/download").'" class="btn btn-success btn-xs datasetexport" id='.$dataset->id.' data-toggle="tooltip" rel="tooltip" data-placement="right" title="'.Lang::get('messages.data_download').'"><span class="glyphicon glyphicon-download-alt unstyle"></span></a>';
            } elseif (Auth::user()) {
              $btn .= '<a href="'.url('datasets/'.$dataset->id."/request").'" class="btn btn-warning btn-xs datasetexport" id='.$dataset->id.'  data-toggle="tooltip" rel="tooltip" data-placement="right" title="'.Lang::get('messages.data_request').'"><span class="glyphicon glyphicon-download-alt unstyle"></span></a>';
            }
            $ret .= '<div id="'.$id.'" hidden >'.$btn.'</div>';
            return $ret;
            //return $dataset->rawLink();
        })
        ->editColumn('privacy', function ($dataset) {
            $txt = "";
            if (in_array($dataset->privacy,[Dataset::PRIVACY_PROJECT, Dataset::PRIVACY_AUTH])) {
              $txt .='<i class="fas fa-lock"></i> ';
            }
            if ($dataset->privacy == Dataset::PRIVACY_PUBLIC) {
              $txt .='<i class="fas fa-lock-open"></i> ';
            }
            if ($dataset->privacy == Dataset::PRIVACY_REGISTERED) {
              $txt .='<i class="fas fa-sign-in-alt"></i> ';
            }
            $txt .= Lang::get('levels.privacy.'.$dataset->privacy);
            return $txt;
        })
        ->editColumn('license', function ($dataset) {
            $license_logo = 'images/cc_srr_primary.png';
            if (null != $dataset->license and null == $dataset->policy) {
              $license = explode(" ",$dataset->license);
              $license_logo = 'images/'.mb_strtolower($license[0]).".png";
            }
            $txt = '<img src="'.asset($license_logo).'" width="80px">';
            if (null != $dataset->license) {
              $txt = $dataset->license."<br>".$txt;
            }
            return $txt;
        })
        //->addColumn('full_name', function ($dataset) {return $dataset->full_name; })
        ->addColumn('contents', function ($dataset) {
            return $dataset->getDataTypeRawLink();
        })
        ->addColumn('downloads', function ($dataset) {
            return $dataset->downloads;
        })
        ->addColumn('tags', function ($dataset) {
            return $dataset->tagLinks;
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
        ->addColumn('admins', function ($dataset) {
            if (empty($dataset->users)) {
                return '';
            }
            $ret = '';
            foreach ($dataset->admins as $user) {
                if (!empty($ret)) {
                  $ret .= "<br>";
                }
                if (isset($user->person->full_name)) {
                  $url = url('persons/'.$user->person->id);
                  $ret .= '<a href="'.$url.'" >'.htmlspecialchars($user->person->full_name).'</a>';
                } else {
                  $ret .= htmlspecialchars($user->email);
                }
            }
            return $ret;
        })
        */
        /*
        ->addColumn('individuals', function ($dataset) {
          $individuals_count = $dataset->getCount('all',null,"individuals");
          return '<a href="'.url('individuals/'. $dataset->id. '/datasets').'" >'.$individuals_count.'</a>';
        })
        ->addColumn('vouchers', function ($dataset) {
            $vouchers_count = $dataset->getCount('all',null,"vouchers");
            return '<a href="'.url('vouchers/'. $dataset->id. '/dataset').'" >'.$vouchers_count.'</a>';
        })
        ->addColumn('taxons', function ($dataset) {
            $taxons_count = $dataset->taxonsCount();
            return '<a href="'.url('taxons/'. $dataset->id. '/dataset').'" >'.$taxons_count.'</a>';
        })
        ->addColumn('locations', function ($dataset) {
            $locations_count = $dataset->getCount('all',null,"locations");
            return '<a href="'.url('locations/'. $dataset->id. '/dataset').'" >'.$locations_count.'</a>';
        })
        ->addColumn('projects', function ($dataset) {
            $projects_count = $dataset->getCount('all',null,"projects");
            return '<a href="'.url('projects/'. $dataset->id. '/dataset').'" >'.$projects_count.'</a>';
        })
        */
        //->rawColumns(['name', 'members', 'tags','measurements','individuals','vouchers','taxons','locations','projects','action']);
        ->rawColumns(['name', 'title','tags','contents','license','privacy']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Dataset::query();
        //->withCount(
          //['measurements'])->with(['users', 'tags.translations']);

        if ($this->project) {
            $query = $query->where('project_id',$this->project);
        }
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
                //'action' => ['title' => Lang::get('messages.actions'), 'searchable' => false, 'orderable' => false],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'name' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'title' => ['title' => Lang::get('messages.title'), 'searchable' => false, 'orderable' => true],
                'privacy' => ['title' => Lang::get('messages.privacy'), 'searchable' => false, 'orderable' => true],
                'license' => ['title' => Lang::get('messages.license'), 'searchable' => false, 'orderable' => true],
                //'admins' => ['title' => Lang::get('messages.administrators'), 'searchable' => false, 'orderable' => false],
                'contents' => ['title' => Lang::get('messages.contains'), 'searchable' => false, 'orderable' => false],
                'tags' => ['title' => Lang::get('messages.tags'), 'searchable' => false, 'orderable' => false],
                'downloads' => ['title' => Lang::get('messages.downloads'), 'searchable' => false, 'orderable' => false],

                /*'individuals' => ['title' => Lang::get('messages.individuals'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
                'taxons' => ['title' => Lang::get('messages.taxons'), 'searchable' => false, 'orderable' => false],
                'locations' => ['title' => Lang::get('messages.locations'), 'searchable' => false, 'orderable' => false],
                'projects' => ['title' => Lang::get('messages.projects'), 'searchable' => false, 'orderable' => false],
                */

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
                    'targets' => [0,1,4,6],
                    'visible' => false,
                ],
              ],
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
