<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Taxon;
use App\Location;
use App\Measurement;
use Baum\Node;
use App\Project;
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
        ->addColumn('plants', function ($taxon) {
          if ($this->project) {
            $plant_count = $taxon->getCount('App\Project',$this->project,'plants');
            return '<a href="'.url('plants/'.$taxon->id.'|'.$this->project.'/taxon_project').'">'.$plant_count.'</a>';
          }
          if ($this->dataset) {
              $plant_count = $taxon->getCount('App\Dataset',$this->dataset,'plants');
              return '<a href="'.url('plants/'.$taxon->id.'|'.$this->dataset.'/taxon_dataset').'">'.$plant_count.'</a>';
          }
          if ($this->location) {
              $plant_count = $taxon->getCount('App\Location',$this->location,'plants');
              return '<a href="'.url('plants/'.$taxon->id.'|'.$this->location.'/taxon_location').'">'.$plant_count.'</a>';
          }
          $plant_count = $taxon->getCount('all',null,"plants");
          return '<a href="'.url('plants/'.$taxon->id.'/taxon').'">'.$plant_count.'</a>';
         })
        ->addColumn('vouchers', function ($taxon) {
          //$voucher_count =0;
          if ($this->project) {
            $voucher_count = $taxon->getCount('App\Project',$this->project,'vouchers');
            return '<a href="'.url('vouchers/'.$taxon->id.'|'.$this->project.'/taxon_project').'">'.$voucher_count.'</a>';
          }
          if ($this->dataset) {
              $voucher_count = $taxon->getCount('App\Dataset',$this->dataset,'vouchers');
              return '<a href="'.url('vouchers/'.$taxon->id.'|'.$this->dataset.'/taxon_dataset').'">'.$voucher_count.'</a>';
          }
          if ($this->location) {
              $voucher_count = $taxon->getCount('App\Location',$this->location,'vouchers');
              return '<a href="'.url('vouchers/'.$taxon->id.'|'.$this->location.'/taxon_location').'">'.$voucher_count.'</a>';
          }
          $voucher_count = $taxon->getCount('all',null,"vouchers");
              return '<a href="'.url('vouchers/'.$taxon->id.'/taxon').'">'.$voucher_count.'</a>';
        })
        ->addColumn('measurements', function ($taxon) {
          if ($this->project) {
            $measurements_count = $taxon->getCount('App\Project',$this->project,"measurements");
            return '<a href="'.url('measurements/'.$taxon->id.'|'.$this->project.'/taxon_project').'">'.$measurements_count.'</a>';
          }
          if ($this->dataset) {
              $measurements_count = $taxon->getCount('App\Dataset',$this->dataset,'measurements');
              return '<a href="'.url('measurements/'.$taxon->id.'|'.$this->dataset.'/taxon_dataset').'">'.$measurements_count.'</a>';
          }
          if ($this->location) {
                $measurements_count = $taxon->getCount('App\Location',$this->location,'measurements');
                return '<a href="'.url('measurements/'.$taxon->id.'|'.$this->location.'/taxon_location').'">'.$measurements_count.'</a>';
          }
          $measurements_count = $taxon->getCount('all',null,"measurements");
          return '<a href="'.url('measurements/'.$taxon->id.'/taxon').'">'.$measurements_count.'</a>';
        })
        ->addColumn('pictures', function ($taxon) {
          $pictures_count = $taxon->getCount('all',null,"pictures");
          return '<a href="'.url('taxons/'.$taxon->id).'">'.$pictures_count.'</a>';
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
                $ret .= '<a href="http://tropicos.org/Name/'.$taxon->mobot.'"><img src="'.asset('images/TropicosLogo.gif').'" alt="Tropicos"></a>"';
            }
            if ($taxon->ipni) {
                $ret .= '<a href="http://www.ipni.org/ipni/idPlantNameSearch.do?id='.$taxon->ipni.'"><img src="'.asset('images/IpniLogo.png').'" alt="IPNI" width="33px"></a>';
            }
            if ($taxon->mycobank) {
                $ret .= '<a href="http://www.mycobank.org/Biolomics.aspx?Table=Mycobank&Rec='.$taxon->mycobank.'&Fields=All"><img src="'.asset('images/MBLogo.png').'" alt="Mycobank" width="33px"></a>';
            }
            if ($taxon->zoobank) {
                $ret .= '<a href="http://zoobank.org/NomenclaturalActs/'.$taxon->zoobank.' ><img src="'.asset('images/zoobank.png').'" alt="ZOOBANK" width="33px"></a>';
            }
            return $ret;
        })
        ->addColumn('select_taxons',  function ($taxon) {
            return $taxon->id;
        })
        ->rawColumns(['fullname', 'plants', 'vouchers', 'measurements', 'pictures', 'external','family','parent']);
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
            ->addSelect(DB::raw('odb_txname(name, level, parent_id) as fullname'));
            /* THIS ONLY COUNT CURRENT TAXON, NOT DESCENDANTS
            ->withCount([
              'measurements', => function ($query) {
                $query->withoutGlobalScope();
              },
              'identified_plants' => function ($query) {
                $query->withoutGlobalScope();
              },
              'identified_vouchers' => function ($query) {
                $query->withoutGlobalScope();
              },
              'plant_vouchers' => function ($query) {
                $query->withoutGlobalScope();
              },
            ]);
            */

            /*
            ->leftJoin('persons', 'taxons.author_id', '=', 'persons.id')
            ->withCount(['identified_plants', 'identified_vouchers', 'measurements', 'pictures'])
            ->with('externalrefs');
            */
        if ($this->project) {
            $query->whereHas('identifications',function($object) { $object->whereHasMorph('object',["App\Plant","App\Voucher"],function($query) { $query->withoutGlobalScopes()->where('project_id',$this->project);});});
        }
        if ($this->dataset) {
              $query->whereHas('identifications',function($object) {
                  $object->whereHasMorph('object',["App\Plant","App\Voucher"],
                    function($query) {
                      $query->withoutGlobalScopes()->whereHas('measurements',
                      function($measurement) {
                        $measurement->withoutGlobalScopes()->where('dataset_id',$this->dataset);
                      });
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
                $count->whereIn('scope_id',$locations_ids)->where('scope_type',"App\Location");
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

        $taxons_level =  DB::table('taxons')->selectRaw('DISTINCT level')->cursor()->pluck('level')->toArray();
        $title_level = Lang::get('messages.level');
        if (count($taxons_level)) {
          $title_level  = Lang::get('messages.level').'<select name="level" id="taxon_level" ><option value="">'.Lang::get('messages.all').'</option>';
          foreach ($taxons_level as $level) {
                 $title_level  .= '<option value="'.$level.'" >'.Lang::get('levels.tax.' . $level).'</option>';
          }
          $title_level  .= '</select>';
        }

        return $this->builder()
            ->columns([
                'select_taxons' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => false],
                'fullname' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'parent' => ['title' => Lang::get('messages.parent'), 'searchable' => false, 'orderable' => false],
                'family' => ['title' => Lang::get('messages.family'), 'searchable' => false, 'orderable' => false],
                'level' => ['title' => $title_level,'searchable' => false, 'orderable' => false],
                'authorSimple' => ['title' => Lang::get('messages.author'), 'searchable' => false, 'orderable' => false],
                'plants' => ['title' => Lang::get('messages.plants'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => false],
                'pictures' => ['title' => Lang::get('messages.pictures'), 'searchable' => false, 'orderable' => false],
                'external' => ['title' => Lang::get('messages.external'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'asc']],
                'buttons' => [
                    /*'csv',
                    'excel',
                    'print',*/
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'columnDefs' => [[
                    'targets' => [2,4,6,11],
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
                    'options' => Taxon::select(DB::raw('DISTINCT level'))->cursor()->pluck('level')->toArray()
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
