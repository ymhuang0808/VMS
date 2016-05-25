<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Project;
use App\Transformers\HyperlinkTransformer;
use App\Transformers\ProjectManagerTransformer;

class ProjectTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [
        'hyperlinks',
        'managers'
    ];

    public function transform(Project $project)
    {
        $projectItem = [
            "data" => [
                "id" => $project->id,
                "name" => $project->name,
                "is_published" => $project->is_published,
                "permission" => $project->permission,
                "organization" => $project->organization,
                "description" => $project->description
            ]
        ];

        return $projectItem;
    }

    public function includeHyperlinks(Project $project)
    {
        $hyperlinks = $project->hyperlinks()->get();

        if ($hyperlinks === null) {
            return $this->null();
        }

        return $this->collection($hyperlinks,
            new HyperlinkTransformer()
        );
    }

    public function includeManagers(Project $project)
    {
        $managers = $project->managers()->get();

        if ($managers === null) {
            return $this->null();
        }

        return $this->collection($managers,
            new ProjectManagerTransformer()
        );
    }
}
