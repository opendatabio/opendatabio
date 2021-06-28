<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Models\Taxon;
use App\Models\Location;
use App\Models\Measurement;
use Baum\Node;
use App\Models\Project;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;
use DB;
use Auth;
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
        ->addColumn('fullname', function ($taxon) {
            return $taxon->rawLink();
        })
        ->filterColumn('fullname', function ($query, $keyword) {
            $query->whereRaw('odb_txname(name, level, parent_id) like ?', ["%".$keyword."%"]);
        })
        ->editColumn('level', function ($taxon) { return $taxon->levelName; })
        ->addColumn('authorSimple', function ($taxon) { return $taxon->authorSimple; })
        //->filterColumn('authorSimple', function ($query, $keyword) {
            //$query->where('persons.full_name', 'like', ["%{$keyword}%"])->orWhere('author', 'like', ["%{$keyword}%"]);
        //})
        ->addColumn('individuals', function ($taxon) {
          if ($this->project) {
            $individual_count = $taxon->getCount('App\Models\Project',$this->project,'individuals');
            return '<a href="'.url('individuals/'.$taxon->id.'|'.$this->project.'/taxon_project').'">'.$individual_count.'</a>';
          }
          if ($this->dataset) {
              $individual_count = $taxon->getCount('App\Models\Dataset',$this->dataset,'individuals');
              return '<a href="'.url('individuals/'.$taxon->id.'|'.$this->dataset.'/taxon_dataset').'">'.$individual_count.'</a>';
          }
          if ($this->location) {
              $individual_count = $taxon->getCount('App\Models\Location',$this->location,'individuals');
              return '<a href="'.url('individuals/'.$taxon->id.'|'.$this->location.'/taxon_location').'">'.$individual_count.'</a>';
          }
          $individual_count = $taxon->getCount('all',null,"individuals");
          return '<a href="'.url('individuals/'.$taxon->id.'/taxon').'">'.$individual_count.'</a>';
         })
        ->addColumn('vouchers', function ($taxon) {
          //$voucher_count =0;
          if ($this->project) {
            $voucher_count = $taxon->getCount('App\Models\Project',$this->project,'vouchers');
            return '<a href="'.url('vouchers/'.$taxon->id.'|'.$this->project.'/taxon_project').'">'.$voucher_count.'</a>';
          }
          if ($this->dataset) {
              $voucher_count = $taxon->getCount('App\Models\Dataset',$this->dataset,'vouchers');
              return '<a href="'.url('vouchers/'.$taxon->id.'|'.$this->dataset.'/taxon_dataset').'">'.$voucher_count.'</a>';
          }
          if ($this->location) {
              $voucher_count = $taxon->getCount('App\Models\Location',$this->location,'vouchers');
              return '<a href="'.url('vouchers/'.$taxon->id.'|'.$this->location.'/taxon_location').'">'.$voucher_count.'</a>';
          }
          $voucher_count = $taxon->getCount('all',null,"vouchers");
              return '<a href="'.url('vouchers/'.$taxon->id.'/taxon').'">'.$voucher_count.'</a>';
        })
        ->addColumn('measurements', function ($taxon) {
          if ($this->project) {
            $measurements_count = $taxon->getCount('App\Models\Project',$this->project,"measurements");
            return '<a href="'.url('measurements/'.$taxon->id.'|'.$this->project.'/taxon_project').'">'.$measurements_count.'</a>';
          }
          if ($this->dataset) {
              $measurements_count = $taxon->getCount('App\Models\Dataset',$this->dataset,'measurements');
              return '<a href="'.url('measurements/'.$taxon->id.'|'.$this->dataset.'/taxon_dataset').'">'.$measurements_count.'</a>';
          }
          if ($this->location) {
                $measurements_count = $taxon->getCount('App\Models\Location',$this->location,'measurements');
                return '<a href="'.url('measurements/'.$taxon->id.'|'.$this->location.'/taxon_location').'">'.$measurements_count.'</a>';
          }
          $measurements_count = $taxon->getCount('all',null,"measurements");
          return '<a href="'.url('measurements/'.$taxon->id.'/taxon').'">'.$measurements_count.'</a>';
        })
        ->addColumn('media', function ($taxon) {
          $mediaCount = $taxon->getCount('all',null,"media");
          $urlShowAllMedia = "media/".$taxon->id."/taxons";
          return '<a href="'.url($urlShowAllMedia).'">'.$mediaCount.'</a>';
        })
        ->addColumn('parent', function ($taxon) {
          if (isset($taxon->parent)) {
            if ($this->project) {
              $url = url('taxons/'.$taxon->parent->id.'|'.$this->project.'/taxon_project');
            } else {
              if ($this->dataset) {
                $url = url('taxons/'.$taxon->parent->id.'|'.$this->dataset.'/taxon_dataset');
              } else {
                if ($this->location) {
                  $url = url('taxons/'.$taxon->parent->id.'|'.$this->location.'/taxon_location');
                }
                $url = url('taxons/'.$taxon->parent->id.'/taxon');
              }
            }
            return '<a href="'.$url.'">'.$taxon->parent->fullname.'</a>';
          }
        })
        ->addColumn('family', function ($taxon) {
            return $taxon->family; })
        ->addColumn('external', function ($taxon) {
            $ret = '';
            if ($taxon->mobot) {
                $ret .= '<a href="http://tropicos.org/Name/'.$taxon->mobot.'"  data-toggle="tooltip" rel="tooltip" data-placement="right" title="MOBOT-Tropicos.org" ><img src="'.asset('images/TropicosLogo.gif').'"  height="24px"></a>"';
            }
            if ($taxon->ipni) {
                $ret .= '<a href="http://www.ipni.org/ipni/idPlantNameSearch.do?id='.$taxon->ipni.'" data-toggle="tooltip" rel="tooltip" data-placement="right" title="International Plant Names Index - IPNI" ><img src="'.asset('images/IpniLogo.png').'" height="24px"></a>';
            }
            if ($taxon->mycobank) {
                $ret .= '<a href="http://www.mycobank.org/Biolomics.aspx?Table=Mycobank&Rec='.$taxon->mycobank.'&Fields=All" data-toggle="tooltip" rel="tooltip" data-placement="right" title="MycoBank.org" ><img src="'.asset('images/MBLogo.png').'" height="24px"></a>';
            }
            if ($taxon->zoobank) {
                $ret .= '<a href="http://zoobank.org/NomenclaturalActs/'.$taxon->zoobank.' data-toggle="tooltip" rel="tooltip" data-placement="right" title="ZooBank.org" ><img src="'.asset('images/zoobank.png').'" height="24px"></a>';
            }
            return $ret;
        })
        ->addColumn('select_taxons',  function ($taxon) {
            return $taxon->id;
        })
        ->rawColumns(['fullname', 'individuals', 'vouchers', 'measurements', 'media', 'external','family','parent']);
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
                'author_id',
                'author',
                'rgt',
                'lft',
                'level',
                'valid',
            ])
            ->addSelect(DB::raw('odb_txname(name, level, parent_id) as fullname'))->noRoot();

        if ($this->project) {
            $query->whereHas('identifications',function($object) { $object->whereHasMorph('object',["App\Models\Individual"],function($query) { $query->withoutGlobalScopes()->where('project_id',$this->project);});});
        }
        if ($this->dataset) {
              $query->whereHas('vouchers',function($voucher) {
                  $voucher->withoutGlobalScopes()->whereHas('measurements',
                      function($measurement) {
                        $measurement->withoutGlobalScopes()->where('dataset_id',$this->dataset);
                      });
                    })->orWhereHas('individuals', function($individual) {
                        $individual->withoutGlobalScopes()->whereHas('measurements',
                            function($measurement) {
                              $measurement->withoutGlobalScopes()->where('dataset_id',$this->dataset);
                            });
                          });

        }
        if ($this->taxon) {
            $taxon = Taxon::where('id',$this->taxon)->cursor();
            $query = $query->where('lft','>',$taxon->first()->lft)->where('rgt','<',$taxon->first()->rgt);
        }

        if ($this->request()->has('level')) {
          $level =  $this->request()->get('level');
          if ($level != "" and null !== $level) {
            $query = $query->where('level',$level);
          }
        }


        if ($this->location) {
            $locations_ids = Location::noWorld()->where('id',$this->location)->first()->getDescendantsAndSelf()->pluck('id')->toArray();
            if (count($locations_ids)>1) {
              $query->whereHas('summary_counts',function($count) use($locations_ids) {
                $count->whereIn('scope_id',$locations_ids)->where('scope_type',"App\Models\Location");
              });
            } else {
              //this will be used for leave locations
              $taxon_ids = Location::where('id',$this->location)->first()->taxonsIDS();
              $query = $query->whereIn('id',$taxon_ids);
            }

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

        $taxons_level =  DB::table('taxons')->selectRaw('DISTINCT level')->where('level','<>',-1)->cursor()->pluck('level')->toArray();
        $title_level = Lang::get('messages.level');
        if (count($taxons_level)) {
          $title_level  = Lang::get('messages.level').'<select name="level" id="taxon_level" ><option value="">'.Lang::get('messages.all').'</option>';
          foreach ($taxons_level as $level) {
                 $title_level  .= '<option value="'.$level.'" >'.Lang::get('levels.tax.' . $level).'</option>';
          }
          $title_level  .= '</select>';
        }

        if (Auth::user()) {
          $hidcol = [1,5,6];
          $buttons = [
              'pageLength',
              'reload',
              ['extend' => 'colvis',  'columns' => ':gt(0)'],
              [
                'text' => Lang::get('datatables.export'),
                'action' => "function () {
                  var isvisible = document.getElementById('export_pannel').style.display;
                  if (isvisible == 'none') {
                    document.getElementById('export_pannel').style.display = 'block';
                  } else {
                      document.getElementById('export_pannel').style.display = 'none';
                  }
                }",
              ],
            ];

        } else {
          $hidcol = [0,1,5,6];
          $buttons = [
              'pageLength',
              'reload',
              ['extend' => 'colvis',  'columns' => ':gt(0)'],
            ];
        }
        if ($this->related_taxa) {
          $hidcol = [0,1,4,5,6,11];
        }

        return $this->builder()
            ->columns([
                'select_taxons' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => false],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'fullname' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'level' => ['title' => $title_level,'searchable' => false, 'orderable' => false],
                'parent' => ['title' => Lang::get('messages.parent'), 'searchable' => false, 'orderable' => false],
                'family' => ['title' => Lang::get('messages.family'), 'searchable' => false, 'orderable' => false],
                'authorSimple' => ['title' => Lang::get('messages.author'), 'searchable' => false, 'orderable' => false],
                'individuals' => ['title' => Lang::get('messages.individuals'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => false],
                'media' => ['title' => Lang::get('messages.media_files'), 'searchable' => false, 'orderable' => false],
                'external' => ['title' => Lang::get('messages.external'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'asc']],
                'buttons' => $buttons,
                'columnDefs' => [[
                    'targets' => $hidcol,
                    'visible' => false,
                ],
                [
                  'targets' => 0,
                  'checkboxes' => [
                  'selectRow' => true
                  ]
                ],
                [
                  'targets' => [5],
                  'searchPane' => [
                    'options' => $taxons_level
                  ]
                ]

                ],
                'select' => [
                      'style' => 'multi',
                ]

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
