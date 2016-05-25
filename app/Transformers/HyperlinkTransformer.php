<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Hyperlink;

class HyperlinkTransformer extends TransformerAbstract
{
    public function transform(Hyperlink $hyperlink)
    {
        return [
            "id" => $hyperlink->id,
            "name" => $hyperlink->name,
            "link" => $hyperlink->link
        ];
    }
}
