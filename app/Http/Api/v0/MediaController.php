<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Models\Media;
use App\Models\Location;
use App\Models\Individual;
use App\Models\Taxon;
use App\Models\ODBFunctions;
use Illuminate\Http\Request;
use Response;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $media = Media::select('*');
        if ($request->individual) {
            $individuals = explode(",",$request->individual);
            $media->where('model_type',Individual::class)->whereIn("model_id",$individuals);
        }
        if ($request->voucher) {
            $vouchers = explode(",",$request->voucher);
            $media->where('model_type',Voucher::class)->whereIn("model_id",$vouchers);
        }
        if ($request->taxon or $request->taxon_root) {
            if ($request->taxon) {
              $taxon_query= $request->taxon;
            } else {
              $taxon_query =  $request->taxon_root;
            }
            $taxon_ids = ODBFunctions::asIdList(
                    $taxon_query,
                    Taxon::select('id'),
                    'odb_txname(name, level, parent_id)');
            if ($request->taxon_root) {
              $taxons = Taxon::whereIn('id',$taxon_ids);
              $taxon_ids = array_unique(Arr::flatten($taxons->cursor()->map(function($taxon) { return $taxon->getDescendantsAndSelf()->pluck('id')->toArray();})->toArray()));
            }
            //the individual identification is directly linked to their vouchers
            $media = $media->whereHas('individualIdentification', function ($q) use ($taxon_ids) {
              $q->whereIn('taxon_id',$taxon_ids);
            })->orWhereHas('voucherIdentification', function ($q) use ($taxon_ids) {
              $q->whereIn('taxon_id',$taxon_ids);
            })->orWhereRaw("(media.model_type='App\Models\Taxon' AND media.model_id IN (".implode(",",$taxon_ids)."))");
        }
        if ($request->location or $request->location_root) {
            if ($request->location) {
              $location_query= $request->location;
            } else {
              $location_query =  $request->location_root;
            }
            $locations_ids = ODBFunctions::asIdList($location_query, Location::select('id'), 'name');
            if ($request->location_root) {
              $query_locations = Location::whereIn('id',$locations_ids);
              $locations_ids = array_unique(Arr::flatten($query_locations->cursor()->map(function($location) { return $location->getDescendantsAndSelf()->pluck('id')->toArray();})->toArray()));
            }
            $media->where('model_type',Location::class)->whereIn("model_id",$locations_ids);
        }
        if ($request->limit && $request->offset) {
            $media = $media->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $media = $media->limit($request->limit);
          }
        }
        if ($request->dataset) {
            $datasets = ODBFunctions::asIdList($request->dataset, Dataset::select('id'), 'name');
            $media = $media->whereIn('dataset_id',$datasets);
        }

        $fields = ($request->fields ? $request->fields : 'simple');
        $possible_fields = config('api-fields.media');
        $field_sets = array_keys($possible_fields);
        if (in_array($fields,$field_sets)) {
            $fields = implode(",",$possible_fields[$fields]);
        }
        $media = $media->cursor();
        if ($fields=="id") {
          $media = $media->pluck('id')->toArray();
        } else {
          $media = $this->setFields($media, $fields, null);
        }
        return $this->wrap_response($media);
    }

}
