<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'page';

    protected $guarded = ['id'];

    protected $translatable = ['name', 'content'];
}
