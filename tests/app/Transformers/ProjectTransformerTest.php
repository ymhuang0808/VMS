<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Transformers\ProjectTransformer;
use App\Hyperlink;
use App\Volunteer;
use App\Project;
use App\Country;
use App\Services\TransformerService;

class ProjectTransformerTest extends TestCase
{
    use DatabaseMigrations;

    private $project;

    private function setUpData()
    {
        $this->project = factory(App\Project::class)->create([
            'name' => 'FooLoL',
            'description' => 'LoLolO0l11',
            'organization' => 'AbC1000',
            'is_published' => true,
            'permission' => 0
        ]);

        $hyperlinks = [];

        $hyperlinks[] = factory(App\Hyperlink::class)->make([
            'name' => 'orZ',
            'link' => 'http://abc.ccc'
        ]);
        $hyperlinks[] = factory(App\Hyperlink::class)->make([
            'name' => 'oOo0olll',
            'link' => 'https://qoo.qqq'
        ]);

        $city = factory(App\City::class)->make();
        $city->country()->associate(factory(App\Country::class)->make());

        $managers = [];

        $managers[] = factory(App\Volunteer::class)->create([
            'username' => 'OoWoO',
            'first_name' => 'Markus',
            'last_name' => 'Wu',
            'email' => 'wow@ccc.qoo'
        ]);

        $this->project->managers()->saveMany($managers);
        $this->project->hyperlinks()->saveMany($hyperlinks);
    }

    public function testTransform()
    {
        $this->setUpData();

        $manager = TransformerService::getManager();
        $resource = TransformerService::getResourceItem($this->project,
            'App\Transformers\ProjectTransformer',
            'project');

        $expected = [
            'data' => [
                'id' => 1,
                'name' => 'FooLoL',
                'is_published' => true,
                'permission' => 0,
                'organization' => 'AbC1000',
                'description' => 'LoLolO0l11',
            ],
            'hyperlinks' => [
                'data' => [
                    [
                        'id' => 1,
                        'name' => 'orZ',
                        'link' => 'http://abc.ccc'
                    ],
                    [
                        'id' => 2,
                        'name' => 'oOo0olll',
                        'link' => 'https://qoo.qqq'
                    ]
                ]
            ],
            'managers' => [
                'data' => [
                    [
                        'id' => 1,
                        'username' => 'OoWoO',
                        'first_name' => 'Markus',
                        'last_name' => 'Wu',
                        'email' => 'wow@ccc.qoo'
                    ]
                ]
            ]
        ];

        $this->assertSame($expected, $manager->createData($resource)->toArray());
    }
}
