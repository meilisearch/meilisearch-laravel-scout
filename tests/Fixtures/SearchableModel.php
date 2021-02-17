<?php

namespace Meilisearch\Scout\Tests\Fixtures;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class SearchableModel extends Model
{
    use Searchable;
    use HasTimestamps;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['id', 'title'];

    public function searchableAs()
    {
        return 'table';
    }

    public function scoutMetadata()
    {
        return [];
    }
}
