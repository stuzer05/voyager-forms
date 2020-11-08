<?php

namespace Pvtl\VoyagerForms;

use TCG\Voyager\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;
use Pvtl\VoyagerForms\Form;

class FormInput extends Model
{
    use Translatable;

    protected $fillable = [
        'form_id',
        'label',
        'class',
        'type',
        'options',
        'rules',
        'required',
    ];

    protected $translatable = ['label', 'options'];

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'ASC');
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = serialize($value);
    }

    public function getOptionsAttribute($value)
    {
        return unserialize($value);
    }

    public function getNameAttribute($value) {
        return str_replace('-', '_', \Str::slug($this->label));
    }
}
