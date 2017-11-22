<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

/* This file contains code adapted from https://github.com/VentureCraft/revisionable
 * by Chris Duell and others, licensed under MIT license.

The MIT License (MIT)

Copyright (c) 2014 Davis Peixoto

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace App;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Log;
use Lang;

/**
 * Revision.
 *
 * Base model to allow for revision history on
 * any model that extends this model
 *
 * Original code (c) Venture Craft <http://www.venturecraft.com.au>
 */
class Revision extends Eloquent
{
    public $table = 'revisions';
    protected $fillable = ['revisionable_type', 'revisionable_id', 'key', 'old_value', 'new_value', 'user_id'];

    /**
     * Revisionable.
     *
     * Grab the revision history for the model that is calling
     *
     * @return array revision history
     */
    public function revisionable()
    {
        return $this->morphTo();
    }

    /**
     * Field Name.
     *
     * Returns the field that was updated, in the case that it's a foreign key
     * denoted by a suffix of "_id", then "_id" is simply stripped
     *
     * TODO: integrate with Lang::get()
     *
     * @return string field
     */
    public function fieldName()
    {
        if (strpos($this->key, '_id')) {
            return str_replace('_id', '', $this->key);
        } else {
            return $this->key;
        }
    }

    /**
     * Old Value.
     *
     * Grab the old value of the field, if it was a foreign key
     * attempt to get an identifying name for the model.
     *
     * @return string old value
     */
    public function oldValue()
    {
        return $this->getValue('old');
    }

    /**
     * New Value.
     *
     * Grab the new value of the field, if it was a foreign key
     * attempt to get an identifying name for the model.
     *
     * @return string old value
     */
    public function newValue()
    {
        return $this->getValue('new');
    }

    /**
     * Revision Unknown String
     * When displaying revision history, when a foreign key is updated
     * instead of displaying the ID, you can choose to display a string
     * of your choice, just override this method in your model
     * By default, it will fall back to the models ID.
     *
     * @return string an identifying name for the model
     */
    public function getRevisionNullString()
    {
        return Lang::get('messages.revisionable_nothing');
    }

    /**
     * No revision string
     * When displaying revision history, if the revisions value
     * cant be figured out, this is used instead.
     * It can be overridden.
     *
     * @return string an identifying name for the model
     */
    public function getRevisionUnknownString()
    {
        return Lang::get('messages.revisionable_unknown');
    }

    /**
     * Responsible for actually doing the grunt work for getting the
     * old or new value for the revision.
     *
     * @param string $which old or new
     *
     * @return string value
     */
    private function getValue($which = 'new')
    {
        $which_value = $which.'_value';
        // if blank, return appropriate message
        if (is_null($this->$which_value) || $this->$which_value == '') {
            return $this->getRevisionNullString();
        }
        // First find the main model that was updated
        $main_model = $this->revisionable_type;
        // Load it, WITH the related model
        if (class_exists($main_model)) {
            $main_model = new $main_model();
            try {
                // for pivot related values, the key is the actual method name
                if (method_exists($main_model, $this->key)) {
                    $related_class = $main_model->{$this->key}()->getRelated();
                    $item = $related_class::find($this->$which_value);
                    if (is_null($item)) {
                        return $this->getRevisionUnknownString();
                    }
                    if (method_exists($item, 'identifiableName')) {
                        return $item->identifiableName();
                    }
                    // for simple foreign keys:
                } elseif ($this->isRelated()) {
                    $related_model = $this->getRelatedModel();
                    // Now we can find out the namespace of of related model
                    if (!method_exists($main_model, $related_model)) {
                        $related_model = camel_case($related_model); // for cases like published_status_id
                        if (!method_exists($main_model, $related_model)) {
                            throw new \Exception('Relation '.$related_model.' does not exist for '.get_class($main_model));
                        }
                    }
                    $related_class = $main_model->$related_model()->getRelated();
                    $item = $related_class::find($this->$which_value);
                    if (is_null($item)) {
                        return $this->getRevisionUnknownString();
                    }
                    if (method_exists($item, 'identifiableName')) {
                        return $item->identifiableName();
                    }
                }
            } catch (\Exception $e) {
                // Just a fail-safe, in the case the data setup isn't as expected
                // Nothing to do here.
                Log::info('Revisionable: '.$e);
            }
            // if there was an issue
            // or, if it's a normal value
            $mutator = 'get'.studly_case($this->key).'Attribute';
            if (method_exists($main_model, $mutator)) {
                return $main_model->$mutator($this->$which_value);
            }
        }

        return $this->$which_value;
    }

    /**
     * Return true if the key is for a related model.
     *
     * @return bool
     */
    private function isRelated()
    {
        $idSuffix = '_id';
        $pos = strrpos($this->key, $idSuffix);
        if (false !== $pos
            && strlen($this->key) - strlen($idSuffix) === $pos
        ) {
            return true;
        }

        return false;
    }

    /**
     * Return the name of the related model.
     *
     * @return string
     */
    private function getRelatedModel()
    {
        $main_model = $this->revisionable_type;
        $main_model = new $main_model();
        // searches the declared relatedModels
        if (is_array($main_model->relatedModels)) {
            // this should work with array_keys, but somehow it's not working
            foreach ($main_model->relatedModels as $k => $v) {
                if ($k == $this->key) {
                    return $v;
                }
            }
        }
        // if none found, try to guess it
        $idSuffix = '_id';

        return substr($this->key, 0, strlen($this->key) - strlen($idSuffix));
    }

    /**
     * User Responsible. Simplified from source.
     *
     * @return User user responsible for the change
     */
    public function userResponsible()
    {
        return User::find($this->user_id);
    }

    /**
     * Returns the object we have the history of.
     *
     * @return object|false
     */
    public function historyOf()
    {
        if (class_exists($class = $this->revisionable_type)) {
            return $class::find($this->revisionable_id);
        }

        return false;
    }
}
