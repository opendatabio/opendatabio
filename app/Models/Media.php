<?php

namespace App\Models;

//use Illuminate\Database\Eloquent\Model;
use App\Scopes\MediaProjectScope;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use CodeInc\StripAccents\StripAccents;
use Spatie\Activitylog\Traits\LogsActivity;
use Lang;
use Carbon\Carbon;

class Media extends BaseMedia
{

    use LogsActivity, Translatable;

    //only the `deleted` event will get logged automatically
    protected static $recordEvents = ['deleted'];

    /* adds project to fillable */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->mergeFillable(['project_id']);
    }


    public function rawLink()
    {
        return "<em><a href='".url('media/'.$this->id)."'>".htmlspecialchars($this->fullname).'</a></em>';
    }

    public function getFullnameAttribute()
    {
      return $this->media_type." ".Lang::get('messages.for')." ".$this->model->fullname;
    }

     /**
    * The "booted" method of the model.
    *
    * @return void
    */
    protected static function booted()
    {
       static::addGlobalScope(new MediaProjectScope);
     }

    /* adjust GET query key names
      * column 'name' in media table conficts with Translatable name
    */
    public function newQuery($excludeDeleted = true)
     {
         // This uses the explicit list to avoid conflict due to global scope
         // maybe check http://lyften.com/journal/user-settings-using-laravel-5-eloquent-global-scopes.html ???
         return parent::newQuery($excludeDeleted)->addSelect(
             'media.id',
             'media.model_type',
             'media.model_id',
             'media.uuid',
             'media.collection_name',
             'media.name as media_name',
             'media.file_name',
             'media.mime_type',
             'media.disk',
             'media.conversions_disk',
             'media.size',
             'media.manipulations',
             'media.custom_properties',
             'media.generated_conversions',
             'media.responsive_images',
             'media.order_column',
             'media.created_at',
             'media.updated_at',
             'media.project_id'
         );
     }


    public function collectors()
    {
      return $this->morphMany(Collector::class, 'object')->with('person');
    }

    public function getTaxonNameAttribute()
    {
      if ($this->model_type == 'App\Models\Individual' or $this->model_type == 'App\Models\Voucher' ) {
        return $this->model()->withoutGlobalScopes()->first()->taxon_name;
      }
      if ($this->model_type == 'App\Models\Taxon' ) {
        return $this->model()->first()->fullname;
      }
      return null;
    }






    /* PROJECT */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function getProjectNameAttribute()
    {
        if ($this->project) {
            return $this->project->name;
        }
        return 'Unknown project';
    }

    /* MEDIA AUTHORS */
    public function getAllCollectorsAttribute()
    {
        if ($this->collectors->count() == 0) {
          return null;
        }
        $persons = $this->collectors->map(function($person) { return $person->person->abbreviation;})->toArray();
        $persons = implode(' & ',$persons);
        return $persons;
    }

    public function getAbbreviatedCollectorsAttribute()
    {
        if ($this->collectors->count() == 0) {
          return null;
        }
        $persons = $this->collectors->map(function($person) { return $person->person->abbreviation;})->toArray();
        if (count($persons)>2) {
            $persons = $persons[0]." et al.";
        } else {
            $persons = implode(" & ",$persons);
        }
        return $persons;
    }

    /* MEDIA TAGS */
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function getTagLinksAttribute()
    {
        if (empty($this->tags)) {
            return '';
        }
        $ret = '';
        $i=0;
        foreach ($this->tags as $tag) {
            if ($i>0) {
              $ret .= " | ";
            }
            $ret .= "<a href='".url('tags/'.$tag->id)."'>".htmlspecialchars($tag->name).'</a>';
            $i++;
        }

        return $ret;
    }

    /* HELPERS */
    public function getMediaTypeAttribute()
    {
      if (preg_match("/image/i",$this->mime_type)) {
        return 'image';
      }
      if (preg_match("/audio/i",$this->mime_type)) {
        return 'audio';
      }
      if (preg_match("/video/i",$this->mime_type)) {
        return 'video';
      }
      if (preg_match("/pdf/i",$this->mime_type)) {
        return 'pdf';
      }
      return null;
    }

    public function license_icons($iconsSizeClass="")
    {
      $license = explode(" ",$this->license);
      $license = explode("-",$license[0]);
      $icons = [];
      foreach ($license as $cc_license) {
          if ($cc_license ==  "CC0") {
            $icons[] = '<i class="fab fa-creative-commons-zero '.$iconsSizeClass.'"></i>';
          } elseif ($cc_license ==  "CC") {
            $icons[] = '<i class="fab fa-creative-commons '.$iconsSizeClass.'"></i>';
          } else {
            $icons[] = '<i class="fab fa-creative-commons-'.mb_strtolower($cc_license).' '.$iconsSizeClass.'"></i>';
          }
      }
      return implode(" ",$icons);
    }

    public function getLicenseAttribute()
    {
      return $this->getCustomProperty('license');
    }

    public function getYearAttribute()
    {
      $date =  $this->getCustomProperty('date');
      if (null != $date) {
        return Date("Y",strtotime($date));
      }
      return null;
    }
    public function getDateAttribute()
    {
      return  (string) $this->getCustomProperty('date');
    }

    public function getNotesAttribute()
    {
      return $this->getCustomProperty('notes');
    }

    public function getTitleAttribute()
    {
      return (null != $this->description and preg_match("/missing/i",$this->description)==0) ? $this->description : null;
    }


    /* HOW TO CITE THE MEDIA */

    /* this function generates the caption to show with  media thumbnails */
    public function gallery_citation($iconsSizeClass='fa-2x')
    {
      //$icons= $this->license_icons($iconsSizeClass);
      $taxon = (null != $this->taxon_name) ? '<em>'.$this->taxon_name."</em>. " : "";
      $title = (null != $this->description and preg_match("/missing/i",$this->description)==0) ? "<strong>".$this->description."</strong>. " : "";
      $authors = (null != $this->abbreviated_collectors) ? $this->abbreviated_collectors." " : "";
      $size = "<small>".$this->human_readable_size."</small>";
      $year = (null != $this->year) ? "(".$this->year.") " : "";
      return $taxon.$title.$year.$authors." ".$size;
      //." <br>".$this->model->rawLink();
    }

    /* generates citation for display and bibtex record */
    public function generate_citation($returnBibtex=false,$short=false)
    {
      if ($short) {
        $authors = $this->abbreviated_collectors;
      } else {
        $authors = $this->all_collectors;
      }
      if (null == $authors) { $authors = ""; }
      $title= "";
      $bibtitle = "";
      if (null != $this->title) {
        $title .= $this->title.". ";
        $bibtitle .= $title;
      }
      if (null != $this->taxon_name) {
        $title .= '<em>'.$this->taxon_name."</em>. ";
        $bibtitle .= $this->taxon_name;
      }
      $title = "<strong>".$title."</strong>";
      $year = (null != $this->year) ? "(".$this->year.") " : "";
      $license = $this->license;
      if (preg_match("/CC0/i",$license)) {
        $license = "public domain - CC0";
      }
      $url =  url('media/'.$this->id);
      $when = today()->format('Y-m-d');
      $media_type = ucfirst($this->media_type);
      $citation = $title.$year.$authors." ".$media_type.", license ".$license;
      $citation .= '. From '.$url.', accessed '.$when.".";

      $bibkey = (null != $this->abbreviated_collectors) ? $this->abbreviated_collectors : $bibtitle;
      $bibkey = preg_replace('[,| |\\.|-|_]','',StripAccents::strip((string) $bibkey ));
      $bibkey .= "_".$this->year;
      $bibtex =  [
         'title' => $bibtitle,
         'year' => $year,
         'author' => $authors,
         'howpublished' => "url\{".url('media/'.$this->id)."}",
         'license' => $license,
         'note' => $media_type.", license ".$license.". Accessed: ".today()->format("Y-m-d"),
         'url' => "{".url('media/'.$this->id)."}",
      ];

      if ($returnBibtex) {
        return "@misc{".$bibkey.",\n".json_encode($bibtex,JSON_PRETTY_PRINT);
      }

      return $citation;
    }


    public function getCitationAttribute()
    {
      return $this->generate_citation(false);
    }
    public function getBibtexAttribute()
    {
      return $this->generate_citation(true);
    }


    /* MEASUREMENTS RELATIONSHIP  */
    public function measurements()
    {
        return $this->morphMany(Measurement::class, 'measured');
    }

    // TODO: use eager loading but withoutGlobalScopes?
    public function getMeasurementsCountAttribute()
    {
        return $this->measurements()->withoutGlobalScopes()->count();
    }

}
