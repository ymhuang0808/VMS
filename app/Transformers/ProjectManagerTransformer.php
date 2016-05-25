<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Volunteer;

class ProjectManagerTransformer extends TransformerAbstract
{
    public function transform(Volunteer $user)
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email
        ];
    }
}
