<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Location;

class ImportLocations extends AppJob
{
    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        $data = $this->userjob->data['data'];
        if (!count($data)) {
            $this->setError();
            $this->appendLog('ERROR: data received is empty!');

            return;
        }
        $this->userjob->setProgressMax(count($data));
        foreach ($data as $location) {
            // has this job been cancelled?
            // calls "fresh" to make sure we're not receiving a cached object
            if ('Cancelled' == $this->userjob->fresh()->status) {
                $this->appendLog('WARNING: received CANCEL signal');
                break;
            }
            $this->userjob->tickProgress();

            if (!is_array($location)) {
                $this->setError();
                $this->appendLog('ERROR: location entry is not formatted as array!'.serialize($location));
                continue;
            }
            if (!array_key_exists('name', $location)) {
                $this->setError();
                $this->appendLog('ERROR: entry needs a name: '.implode(';', $location));
                continue;
            }
            // Arrived here: let's import it!!
            try {
                $this->import($location);
            } catch (\Exception $e) {
                $this->setError();
                $this->appendLog('Exception '.$e->getMessage(). ' at '. $e->getFile() . '+' . $e->getLine() . ' on location '.$location['name']);
            }
        }
    }

    public function import($location)
    {
        // First, the easy case. We receive name, level, parent, etc.
        $name = $location['name'];
        $adm_level = array_key_exists('adm_level', $location) ? $location['adm_level'] : null;
        if (is_null($adm_level)) {
            $this->appendLog("WARNING: Level for location $name not available. Skipping import...");

            return;
        }
        $altitude = array_key_exists('altitude', $location) ? $location['altitude'] : null;
        $datum = array_key_exists('datum', $location) ? $location['datum'] : null;
        $notes = array_key_exists('notes', $location) ? $location['notes'] : null;
        $lat = array_key_exists('lat', $location) ? $location['lat'] : null;
        $long = array_key_exists('long', $location) ? $location['long'] : null;
        $startx = array_key_exists('startx', $location) ? $location['startx'] : null;
        $starty = array_key_exists('starty', $location) ? $location['starty'] : null;
        $x = array_key_exists('x', $location) ? $location['x'] : null;
        $y = array_key_exists('y', $location) ? $location['y'] : null;
        $geom = array_key_exists('geom', $location) ? $location['geom'] : null;
        if ((is_null($lat) or is_null($long)) and is_null($geom)) {
            $this->appendLog("WARNING: Position for location $name not available. Skipping import...");

            return;
        }

        $parent = array_key_exists('parent', $location) ? $location['parent'] : null;
        $uc = array_key_exists('uc', $location) ? $location['uc'] : null;
        // parent might be numeric (ie, already the ID) or a name. if it's a name, let's get the id
        if (!is_numeric($parent) and !is_null($parent)) {
            $parent_obj = Location::where('name', '=', $parent)->get();
            if ($parent_obj->count()) {
                $parent = $parent_obj->first()->id;
            } else {
                $this->appendLog("WARNING: Parent for location $name is listed as $parent, but this was not found in the database.");

                return;
            }
        }
        if (!is_numeric($uc) and !is_null($uc)) {
            $uc_obj = Location::uc()->where('name', '=', $uc)->get();
            if ($uc_obj->count()) {
                $uc = $uc_obj->first()->id;
            } else {
                $this->appendLog("WARNING: Conservation unit for location $name is listed as $uc, but this was not found in the database.");

                return;
            }
        }
        // Create geom from lat/long
        if (is_null($geom)) {
            $geom = "POINT ($long $lat)";
        }

        if ($adm_level === 0) {
            $world = Location::world();
            $parent = $world->id;
        }

        // TODO: several other validation checks
        // Is this location already imported?
        if ($parent) {
            if (Location::where('name', '=', $name)->where('parent_id', '=', $parent)->count() > 0) {
                $this->appendLog('WARNING: location '.$name.' already imported to database');

                return;
            }
        }

        // Autoguess parent/UC
        if (is_null($parent)) {
            $parent = Location::detectParent($geom, $adm_level, false);
            if ($parent) {
                $parent = $parent->id;
            }
        }
        if (is_null($uc)) {
            $uc = Location::detectParent($geom, $adm_level, true);
            if ($uc) {
                $uc = $uc->id;
            }
        }

        $location = new Location([
            'name' => $name,
            'adm_level' => $adm_level,
            'altitude' => $altitude,
            'datum' => $datum,
            'notes' => $notes,
            'startx' => $startx,
            'starty' => $starty,
            'x' => $x,
            'y' => $y,
            // forces null if parent / uc was explicitly passed as zero
            'parent_id' => $parent === 0 ? null : $parent,
            'uc_id' => $uc === 0 ? null : $uc,
        ]);
        $location->geom = $geom;

        $location->save();

        return;
    }
}
