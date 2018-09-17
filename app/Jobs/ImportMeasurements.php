<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Measurement;
use App\Location;
use App\Taxon;
use App\Person;
use App\Plant;
use App\Voucher;
use App\ODBFunctions;
use App\ODBTrait;
use App\TraitCategory;
use Auth;

class ImportMeasurements extends AppJob
{
    protected $sourceType;

    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        $data = $this->extractEntrys();
        if (!$this->setProgressMax($data)) {
            return;
        }
        if (!$this->validateHeader()) {
            $this->setError();
            return;
        }

        foreach ($data as $measurement) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if ($this->validateData($measurement)) {
                // Arrived here: let's import it!!
                try {
                    $this->import($measurement);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' TRACE: '.$e->getTraceAsString());
                }
            } else {
                $this->appendLog('WARNING: Invalid data format: '.json_encode($measurement));
            }
        }
    }

    protected function validateHeader()
    {
        if (!$this->hasRequiredKeys(['object_type', 'measurer', 'date', 'dataset'], $this->header)) {
            return false;
        } elseif (!$this->validatePerson()) {
            return false;
        } elseif (!$this->validateDataset()) {
            return false;
        } elseif (!$this->validateObjetType()) {
            return false;
        } else {
            return true;
        }
    }

    protected function validatePerson()
    {
        $person = $this->header['measurer'];
        $valid = ODBFunctions::validRegistry(Person::select('id'), $person, ['id', 'abbreviation', 'full_name', 'email']);
        if (null === $valid) {
            $this->appendLog('Error: Header reffers to '.$person.' as who do these measurements, but this person was not found in the database.');

            return false;
        } else {
            $this->header['person'] = $valid->id;

            return true;
        }
    }

    protected function validateDataset()
    {
        $valid = Auth::user()->datasets()
                ->where('datasets.id', $this->header['dataset'])
                ->orWhere('datasets.name', $this->header['dataset'])
                ->get();
        if ((null === $valid) or (0 === count($valid))) {
            $this->appendLog('Error: Header reffers to '.$this->header['dataset'].' as dataset, but this dataset was not found in the database.');

            return false;
        } else {
            $this->header['dataset'] = $valid->first()->id;

            return true;
        }
    }

    protected function validateObjetType()
    {
        return in_array($this->header['object_type'], ['App\\Location', 'App\\Taxon', 'App\\Plant', 'App\\Sample']);
    }

    protected function validateData(&$measurement)
    {
        if (!$this->hasRequiredKeys(['object_id'], $measurement)) {
            return false;
        } elseif (!$this->validateObject($measurement)) {
            return false;
        } elseif (!$this->validateMeasurements($measurement)) {
            return false;
        } else {
            return true;
        }
    }

    protected function validateObject(&$measurement)
    {
        if ('App\\Location' === $this->header['object_type']) {
            $query = Location::select('id')->where('id', $measurement['object_id'])->get();
        } elseif ('App\\Taxon' === $this->header['object_type']) {
            $query = Taxon::select('id')->where('id', $measurement['object_id'])->get();
        } elseif ('App\\Plant' === $this->header['object_type']) {
            $query = Plant::select('plants.id')->where('id', $measurement['object_id'])->get();
        } elseif ('App\\Sample' === $this->header['object_type']) {
            $query = Voucher::select('id')->where('id', $measurement['object_id'])->get();
        }
        if (count($query)) {
            return true;
        } else {
            $this->appendLog('WARNING: Object '.$this->header['object_type'].' - '.$measurement['object_id'].' not found, all of their measurements will be ignored.');

            return false;
        }
    }

    protected function validateMeasurements(&$measurement)
    {
        $valids = array();
        foreach ($measurement as $key => $value) {
            if (null !== $value) {
                if ('object_id' === $key) {
                    $valids[$key] = $value;
                } else {
                    $trait = ODBFunctions::validRegistry(ODBTrait::select('*'), $key, ['id', 'export_name']);
                    if ($trait and $trait->object_types()->pluck('object_type')->contains($this->header['object_type'])) {
                        $value = $this->validValue($trait, $value);
                        if (null !== $value) {
                            $trait_id = (string) $trait->id;
                            $valids[$trait_id] = $value;
                        }
                    } else {
                        $this->appendLog('WARNING: Trait '.$key.' of object '.$measurement['object_id'].' not found, this measurement will be ignored.');
                    }
                }
            }
        }
        if (count($valids) > 1) {
            $measurement = $valids;

            return true;
        }

        return false;
    }

    private function validValue($trait, $value) {
        switch ($trait->type) {
            case ODBTrait::QUANT_INTEGER:
            case ODBTrait::QUANT_REAL:
                if (is_numeric($value) and
                        ((null === $trait->range_min) or ($trait->range_min <= $value)) and
                        ((null === $trait->range_max) or ($trait->range_max >= $value))) {
                    return $value;
                }
                break;
            case ODBTrait::CATEGORICAL:
            case ODBTrait::ORDINAL:
                return $this->getCategoryId($trait, $value);
        // TODO validate value
            case ODBTrait::CATEGORICAL_MULTIPLE:
                $values = explode(',', $value);
                $ret = array();
                foreach ($values as $value) {
                    $cat = $this->getCategoryId($trait, $value);
                    if (null === $cat) {
                        $this->appendLog('WARNING: Category '.$value.' unknown, will be ignored.');
                    } else {
                        $ret[] = $cat;
                    }
                }
                if (count($ret)) {
                    return $ret;
                }
                break;
            case ODBTrait::TEXT:
            case ODBTrait::COLOR:
                return $value;
            case ODBTrait::LINK:
                $valid = array();
                switch ($trait->link_type) {
                    case 'App\\Taxon':
                        $valid = Taxon::select('id')->where('id', $value)->get();
                        break;
                    case 'App\\Plant':
                        $valid = Plant::select('id')->where('id', $value)->get();
                        break;
                    case 'App\\Location':
                        $valid = Location::select('id')->where('id', $value)->get();
                        break;
                    case 'App\\Sample':
                        $valid = Voucher::select('id')->where('id', $value)->get();
                        break;
                }
                if (count($valid))
                    return $value;
        }
        
        return null;
    }

    private function getCategoryId($trait, $name) {
        foreach ($trait->categories as $cat) {
            if ($name === $cat->name) {
                return $cat->id;
            }
        }
        
        return null;
    }

    public function import($measurements)
    {
        $measured_id = $measurements['object_id'];
        unset($measurements['object_id']);
        foreach ($measurements as $key => $value) {
            $measurement = new Measurement([
                'trait_id' => $key,
                'measured_id' => $measured_id,
                'measured_type' => $this->header['object_type'],
                'dataset_id' => $this->header['dataset'],
                'person_id' => $this->header['person'],
                'bibreference_id' => array_key_exists('bibreference', $this->header) ? $this->header['bibreference'] : null,
            ]);
            $measurement->setDate($this->header['date']);
            $measurement->save();
            if (ODBTrait::LINK == $measurement->type) {
                $measurement->value_i = $value;
            } else {
                $measurement->valueActual = $value;
            }
            $measurement->save();
            $this->affectedId($measurement->id);
        }

        return;
    }
}
