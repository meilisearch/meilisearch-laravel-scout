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

    /**
     * The model name if created via a Serachable Model.
     *
     * @var string
     */
    public $model;

    public function __construct(Indexes $index, string $model = null)
    {
        $this->index = $index;
        $this->model = $model;
    }
}
