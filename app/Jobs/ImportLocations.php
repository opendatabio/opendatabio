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
        $data = $this->extractEntrys();
        if (!$this->setProgressMax($data)) {
            return;
        }
        foreach ($data as $location) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if (!$this->hasRequiredKeys(['name', 'adm_level'], $location))
                continue;
            }
            // Arrived here: let's import it!!
            try {
                $this->import($location);
            } catch (\Exception $e) {
                $this->setError();
                $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on location '.$location['name']);
            }
        }
    }

    public function import($location)
    {
        // First, the the independent field.
        $name = $location['name'];
        $adm_level = $location['adm_level'];
        $altitude = array_key_exists('altitude', $location) ? $location['altitude'] : null;
        $datum = array_key_exists('datum', $location) ? $location['datum'] : null;
        $notes = array_key_exists('notes', $location) ? $location['notes'] : null;
        $startx = array_key_exists('startx', $location) ? $location['startx'] : null;
        $starty = array_key_exists('starty', $location) ? $location['starty'] : null;
        $x = array_key_exists('x', $location) ? $location['x'] : null;
        $y = array_key_exists('y', $location) ? $location['y'] : null;

        // Check geom
        $geom = array_key_exists('geom', $location) ? $location['geom'] : null;
        if (is_null($geom)) {
            $lat = array_key_exists('lat', $location) ? $location['lat'] : null;
            $long = array_key_exists('long', $location) ? $location['long'] : null;
            if (is_null($lat) or is_null($long)) {
                $this->skipEntry($location, "Position for location $name not available");
                return;
            }
            $geom = "POINT ($long $lat)";
        }

        // Check parent
        $parent = array_key_exists('parent', $location) ? $location['parent'] : null;
        if (is_null($parent)) {
            if (0 == $adm_level)
                $parent = Location::world()->id;
            else { // Autoguess parent
                $parent = Location::detectParent($geom, $adm_level, false);
                if ($parent) {
                    $parent = $parent->id;
                }
            }
        } else { //If parent is given, we need validate it
            if (0 == $parent) // forces null if parent was explicitly passed as zero
                $parent = null;
            else {
                $valid = $this->validIdOrName(Location::select('id'), $parent);
                if ($valid === null) {
                    $this->skipEntry($location, "Parent for location $name is listed as $parent, but this was not found in the database.");
                    return;
                } else
                    $parent = $valid;
            }
        }

        // Similar check UC
        $uc = array_key_exists('uc', $location) ? $location['uc'] : null;
        if (is_null($uc)) { // Autoguess UC
            $uc = Location::detectParent($geom, $adm_level, true);
            if ($uc) {
                $uc = $uc->id;
            }
        } else { //If UC is given, we need validate it
            if (0 == $uc) // forces null if uc was explicitly passed as zero
                $uc = null;
            else {
                $valid = $this->validIdOrName(Location::select('id'), $uc);
                if ($valid === null) {
                    $this->skipEntry($location, "Conservation unit for location $name is listed as $uc, but this was not found in the database.");
                    return;
                } else
                    $uc = $valid;
            }
        }

        // TODO: several other validation checks
        // Is this location already imported?
        if ($parent) {
            if (Location::where('name', '=', $name)->where('parent_id', '=', $parent)->count() > 0) {
                $this->skipEntry($location, 'location '.$name.' already imported to database');

                return;
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
            'parent_id' => $parent,
            'uc_id' => $uc,
        ]);
        $location->geom = $geom;

        $location->save();
        $this->affectedId($location->id);

        return;
    }
}
