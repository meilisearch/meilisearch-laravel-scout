<?php

namespace Meilisearch\Scout\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MeiliSearch\Endpoints\Indexes;

class IndexCreated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @var Indexes
     */
    public $index;

    public function __construct(Indexes $index)
    {
        $this->index = $index;
    }
}
