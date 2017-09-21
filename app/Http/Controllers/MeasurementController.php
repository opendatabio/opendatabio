<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Measurement;
use App\Plant;
use App\Voucher;
use App\Taxon;
use App\Location;

class MeasurementController extends Controller
{
    // The usual CRUD methods are hidden to provide a common interface to all requests
    // coming from different nested routes
    protected function index($object) {
        $measurements = $object->measurements()->orderBy('date','asc')->paginate(10);
        $measurements->load('odbtrait');
        return view('measurements.index', compact('object', 'measurements'));
    }

    public function indexPlants($id) {
        $plant = Plant::findOrFail($id);
        return $this->index($plant);
    }
    public function indexLocations($id) {
        $location = Location::findOrFail($id);
        return $this->index($location);
    }
    public function indexVouchers($id) {
        $voucher = Voucher::findOrFail($id);
        return $this->index($voucher);
    }
    public function indexTaxons($id) {
        $taxon = Taxon::findOrFail($id);
        return $this->index($taxon);
    }
}
