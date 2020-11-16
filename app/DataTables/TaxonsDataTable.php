<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Taxon;
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
        ->editColumn('fullname', function ($taxon) {
            return $taxon->rawLink();
        })
        ->editColumn('level', function ($taxon) { return $taxon->levelName; })
        ->addColumn('authorSimple', function ($taxon) { return $taxon->authorSimple; })

        ->filterColumn('fullname', function ($query, $keyword) {
            $query->whereRaw('odb_txname(name, level, parent_id) like ?', ["%".$keyword."%"]);            
        })
        //->filterColumn('authorSimple', function ($query, $keyword) {
            //$query->where('persons.full_name', 'like', ["%{$keyword}%"])->orWhere('author', 'like', ["%{$keyword}%"]);
        //})
        ->addColumn('plants', function ($taxon) {
          if ($this->project_id) {
            $plant_count = $taxon->plantsCount($this->project_id);
          } else {
            $plant_count = $taxon->plantsCount();
          }
          return '<a href="'.url('taxons/'.$taxon->id.'/plants').'">'.$plant_count.'</a>'; })
        ->addColumn('vouchers', function ($taxon) {
          $voucher_count =0;
          if ($this->project_id) {
            $voucher_count = $taxon->vouchersCount($this->project_id);
          } else {
            $voucher_count = $taxon->vouchersCount();
          }
          return '<a href="'.url('taxons/'.$taxon->id.'/vouchers').'">'.$voucher_count.'</a>';
        })
        ->addColumn('measurements', function ($taxon) {
          if ($this->project_id) {
            $measurements_count = $taxon->measurementsCount($this->project_id);
          } else {
            $measurements_count = $taxon->measurementsCount();
          }
          return '<a href="'.url('taxons/'.$taxon->id.'/measurements').'">'.$measurements_count.'</a>';
        })
        ->addColumn('pictures', function ($taxon) {
          return '<a href="'.url('taxons/'.$taxon->id).'">'.$taxon->picturesCount().'</a>';
        })
        ->addColumn('family', function ($taxon) {
            return $taxon->familyRawLink(); })
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

            return $ret;
        })
        ->rawColumns(['fullname', 'plants', 'vouchers', 'measurements', 'pictures', 'external','family']);
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
            /*
            ->leftJoin('persons', 'taxons.author_id', '=', 'persons.id')
            ->withCount(['identified_plants', 'identified_vouchers', 'measurements', 'pictures'])
            ->with('externalrefs');
            */
        if ($this->project_id) {
            // this solution  would allow all users to see taxon list for any project
            //$ids = array_unique(Project::find($this->project_id)->taxons()->pluck('id')->toArray());
            //$query->whereIn('id',$ids);

            // solution for only users with permissions to see project associated data, but fail the search engine
            $query->whereHas('identifications',function($object) { $object->whereHasMorph('object',["App\Plant","App\Voucher"],function($query) { $query->where('project_id',$this->project_id);});});


            //$query = $query->whereHas('vouchers_direct',function($voucher) { $voucher->where('project_id',$this->project_id);});
            //$query = $query->orWhereHas('plants',function($plant) { $plant->where('project_id',$this->project_id);});
        }
        if ($this->taxon_id) {
            $taxons = Taxon::find($this->taxon_id)->getDescendants()->pluck('id')->toArray();
            $query = $query->whereIn('id',$taxons);
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
                'fullname' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'family' => ['title' => Lang::get('messages.family'), 'searchable' => false, 'orderable' => false],
                'level' => ['title' => Lang::get('messages.level'), 'searchable' => false, 'orderable' => true],
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
                    'csv',
                    'excel',
                    'print',
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'columnDefs' => [[
                    'targets' => [1,4, 9],
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
        return 'odb_taxons_'.time();
    }
}
