<?php

namespace Pvtl\VoyagerForms;

use TCG\Voyager\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use Translatable;

    protected $fillable = [
        'title',
        'view',
        'mailto',
        'hook',
        'layout',
        'email_template',
        'message_success',
    ];

    protected $translatable = ['title', 'message_success'];

    protected static function boot() {
        parent::boot();

        // before delete() method call this
        static::deleting(function($form) {
            // do the rest of the cleanup...
            $form->inputs()->delete();
        });
    }

    public function inputs()
    {
        return $this->hasMany(FormInput::class)->ordered();
    }

    public function setMailToAttribute($value)
    {
        $this->attributes['mailto'] = serialize($value);
    }

    public function getMailToAttribute($value)
    {
        return unserialize($value);
    }

    public function getNameAttribute($value) {
        $layout = str_replace('_', '-', $this->layout);
        return \Str::slug("form-{$this->title}-{$layout}");
    }

    public function fieldNameToLabel($field_name) {
        $fields = $this->inputs;

        $result = $fields->filter(function($field) use ($field_name) {
            return $field->name == $field_name;
        })->first();

        return $result ? $result->label : $field_name;
    }
}
