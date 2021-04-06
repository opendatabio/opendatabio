<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Models\Voucher;
use App\Models\Location;
use App\Models\Individual;
use App\Models\Project;
use App\Models\ODBFunctions;
use App\Models\Biocollection;
use Illuminate\Http\Request;

use Lang;




class ImportVouchers extends ImportCollectable
{
    private $requiredKeys;

    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        $data = $this->extractEntrys();
        if (!$this->setProgressMax($data)) {
            return;
        }
        $this->requiredKeys = $this->removeHeaderSuppliedKeys(['individual', 'biocollection']);
        $this->validateHeader();
        foreach ($data as $voucher) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if ($this->validateData($voucher)) {
                // Arrived here: let's import it!!
                //am I entering here?
                //$this->appendLog('YESSESSSSS');
                try {
                    $this->import($voucher);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on voucher '.$voucher['number']);
                }
            }
        }
    }

    protected function validateData(&$voucher)
    {
        if (!$this->hasRequiredKeys($this->requiredKeys, $voucher)) {
            return false;
        }

        if (!$this->validateIndividual($voucher)) {
            return false;
        }

        if (!$this->validateProject($voucher)) {
            return false;
        }

        //collectors (at least a valid one must exist if informed) but is not mandatory
        $hascollector  = array_key_exists('collector',$voucher) ? ((null != $voucher['collector']) ? $voucher['collector'] : null) : null;
        $collectors = $this->extractCollectors('Voucher', $voucher, 'collector');
        if (null == $collectors  and $hascollector) {
          return false;
        }
        $voucher['collector'] = $collectors;

        //if collector is informed, then number is mandatory
        $hasnumber   = array_key_exists('number',$voucher) ? ((null != $voucher['number']) ? $voucher['number'] : null) : null;
        if ($hascollector and !$hasnumber) {
          $this->skipEntry($voucher, 'Because you informed the collector you must also inform number. Note that neither is mandatory for voucher');
          return false;
        }
        //if collector and number is informed, a date must be provided as well
        if ($hascollector and !$this->validateDate($voucher)) {
          $this->skipEntry($voucher,'Collector was informed, then date must also be informed. Note that neither is mandatory for voucher, which inherits these from the individual if empty');
          return false;
        }

        if (!$this->validateBiocollection($voucher)) {
            return false;
        }
        return true;
    }

    public function validateBiocollection(&$voucher)
    {
      $query = Biocollection::select(['id', 'acronym', 'name', 'irn']);
      $fields = ['id', 'acronym', 'name', 'irn'];
      $valid = ODBFunctions::validRegistry($query, $voucher['biocollection'], $fields);
      if (!$valid) {
        $this->skipEntry($voucher,'You informed an invalid Biocollection reference: '.$voucher['biocollection']);
        return false;
      }
      $voucher['biocollection_id'] = $valid->id;

      $biocollection_type =  array_key_exists('biocollection_type',$voucher) ? ((null != $voucher['biocollection_type']) ? $voucher['biocollection_type'] : 0) : 0;
      if (!in_array($biocollection_type,Biocollection::NOMENCLATURE_TYPE)) {
        $validtype = null;
        $btype = mb_strtolower($biocollection_type);
        foreach (Biocollection::NOMENCLATURE_TYPE as $type) {
          $en = mb_strtolower(Lang::get('levels.vouchertype.'.$type));
          $pt = mb_strtolower(Lang::choice('levels.vouchertype.'.$type,1,[],'pt'));
          if ($en==$btype or $pt==$btype) {
            $validtype = $type;
          }
        }
        //not found in translations nor as numeric return
        if (null == $validtype) {
          $this->skipEntry($voucher,'You informed an invalid NOMENCLATURE_TYPE reference: '.$biocollection_type);
          return false;
        }
        $voucher['biocollection_type'] = $validtype;
      } else {
        /* this is required as 0 may have been defined here */
        $voucher['biocollection_type'] = $biocollection_type;
      }
      return true;
     }

    public function validateDate(&$voucher)
    {
      //validate date
      $date = isset($voucher['date']) ? $voucher['date'] : (isset($this->header['date']) ? $this->header['date'] : null);
      if (null == $date) {
        $year = isset($voucher['date_year']) ? $voucher['date_year'] : (isset( $voucher['year']) ? $voucher['year'] : null);
        $month = isset($voucher['date_month']) ? $voucher['date_month'] : (isset( $voucher['month']) ? $voucher['month'] : null);
        $day = isset($voucher['date_day']) ? $voucher['date_day'] : (isset( $voucher['day']) ? $voucher['day'] : null);
        $date = [$month,$day,$year];
      }
      if (is_string($date)) {
        if (preg_match("/\//",$date)) {
            $date = explode("/",$date);
            $date = [$date[1],$date[2],$date[0]];
        } elseif (preg_match("/-/",$date)) {
            $date = explode("-",$date);
            $date = [$date[1],$date[2],$date[0]];
        }
      } elseif (!is_array($date)) {
        if (get_class($date)==="DateTime") {
           $year = $date->format('Y');
           $day = $date->format('d');
           $month = $date->format('m');
           $date = [$month,$day,$year];
        }
      }
      $hasdate = array_filter($date);
      if (count($hasdate)>0) {
        if (!(Individual::checkDate($date))) {
          $this->skipEntry($voucher,'Informed date is invalid! Date'.json_encode($date));
          return false;
        }
        $voucher['date'] = $date;
        return true;
      }
      return false;
    }

    public function validateIndividual(&$voucher)
    {
      if (array_key_exists('individual', $voucher)) {
          $individual = $voucher['individual'];
          if (((int)($individual))>0) {
              $ref = Individual::where('id',$individual);
          } else {
              $ref = Individual::whereRaw('odb_ind_fullname(id,tag) like "'.$individual.'"');
          }
          if ($ref->count()==1) {
              $voucher['individual_id'] = $ref->get()->first()->id;
              //add project if does not exists
              if (null == $voucher['project']) {
                $voucher['project'] = $ref->get()->first()->project_id;
              }
              return true;
          }
      }
      $this->skipEntry($voucher, ' Individual '.$voucher['individual'].' not found in the database');
      return false;
    }


    public function import($voucher)
    {

        $keys_mandatory = ['individual','biocollection'];
        $keys_other = ['biocollection_type','biocollection_number','project','collector','number','notes','date'];
        $store_request = [
          'from_the_api' => 1,
          'individual_id' => (int) $voucher['individual_id'],
          'biocollection_id' => (int) $voucher['biocollection_id'],
          'biocollection_type' => (int) $voucher['biocollection_type'],
          'project_id' => (int) $voucher['project'],
          'biocollection_number' => array_key_exists('biocollection_number',$voucher) ? ((null != $voucher['biocollection_number']) ? $voucher['biocollection_number'] : null) : null,
          'number' =>  array_key_exists('number',$voucher) ? ((null != $voucher['number']) ? (string) $voucher['number'] : null) : null,
          'collector' => array_key_exists('collector',$voucher) ? ((null != $voucher['collector']) ? $voucher['collector'] : null) : null,
          'notes' => array_key_exists('notes',$voucher) ? ((null != $voucher['notes']) ?  (string) $voucher['notes'] : null) : null,
          'date' => array_key_exists('date',$voucher) ? ((null != $voucher['date']) ? $voucher['date'] : null) : null,
        ];

        //transform info in a request
        $saverequest = new Request;
        $saverequest->merge($store_request);

        //store the record, which will result in the individual
        $savedvoucher = app('App\Http\Controllers\VoucherController')->store($saverequest);
        if (is_string($savedvoucher)) {
          $this->skipEntry($voucher,'This voucher could not be imported. Possible errors may be: '.$savedvoucher);
          return ;
        }



        $this->affectedId($savedvoucher->id);


        return;
    }
}
