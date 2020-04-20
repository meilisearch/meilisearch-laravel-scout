<?php

namespace Meilisearch\Scout\Tests\Fixtures;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;

class SearchableModel extends Model
{
    use Searchable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['id'];

    public function searchableAs()
    {
        return 'table';
    }

    public function scoutMetadata()
    {
        return [];
    }
}
