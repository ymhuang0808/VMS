<?php

namespace App\Http\Controllers\Api\V1_0;

use App\Http\Controllers\Api\BaseAuthController;
use App\Http\Requests\Api\V1_0\FillBulkCustomFieldsRequest;
use App\Http\Requests\Api\V1_0\CreateProjectCustomFieldRequest;
use App\Http\Requests\Api\V1_0\ShowAllMembersCustomFieldDataRequest;
use App\Http\Requests\Api\V1_0\ShowProjectRequest;
use App\Http\Requests\Api\V1_0\FillCustomFieldRequest;
use App\Repositories\ProjectCustomFieldRepository;
use App\Repositories\MemberCustomFieldDataRepository;
use App\Repositories\MemberCustomFieldDataDbRepository;
use App\Project;
use App\CustomField\TypeMapping;
use App\Services\TransformerService;
use App\Transformers\ProjectCustomFieldTransformer;
use App\Transformers\JsonApiMemberCustomFieldsDbTransformer;
use App\Transformers\ProjectMemberDataCustomFieldTransformer;
use App\Transformers\ProjectMemberWithCustomFieldDataTransformer;
use App\Transformers\CustomField\UserCustomFieldWithDataTransformer;
use App\ProjectMember;
use Illuminate\Support\Arr;

class ProjectCustomFieldController extends BaseAuthController
{
    public function store(
        CreateProjectCustomFieldRequest $request,
        ProjectCustomFieldRepository $repository,
        $id
    ) {
        $project = Project::findOrFail($id);
        $data = $request->only([
            'name',
            'type',
            'description',
            'required',
            'order',
            'metadata'
        ]);

        $transformer = new ProjectCustomFieldTransformer();

        if ($request->has('id')) {
            $customField = $repository->update($project, $request->input('id'), $data);
            $response = $this->response->item($customField, $transformer);
        } else {
            $metadata = (isset($data['metadata'])) ? $data['metadata'] : null;

            $customField = $repository->newInstance(
                $data['name'],
                $data['description'],
                $data['required'],
                $data['type'],
                $data['order'],
                $metadata
            );

            $project->customFields()->save($customField);

            $response = $this->response
                            ->item($customField, $transformer)
                            ->setStatusCode(201);
        }

        return $response;
    }

    public function showAll($projectId)
    {
        $project = Project::find($projectId);
        $customFields = $project->customFields()->get();

        return $this->response->collection($customFields,
            new ProjectCustomFieldTransformer);
    }

    public function fillCustomField(FillCustomFieldRequest $request,
        MemberCustomFieldDataRepository $repository, $projectId)
    {
        $user = $this->jwtService->getUser();

        /**
         * TODO: Should be validated
         */
        $data = $request->input('data.attributes.content');
        $customFieldId = $request->input('data.relationships.custom_field.data.id');
        $project = Project::find($projectId);

        $repository->updateOrCreate($project, $user, $customFieldId, $data);

        $result = [];
        $status = 201;

        return response()->json($result, $status);
    }

    public function fillBulkCustomFields(FillBulkCustomFieldsRequest $request,
        MemberCustomFieldDataRepository $repository, $projectId)
    {
        $user = $this->jwtService->getUser();

        // TODO Should validate the project_custom_field.id exists
        $collection = collect($request->all());
        $project = Project::find($projectId);
        $mapping = [];

        /**
         * @TODO MUST validate the custom field id is able to be updated
         */
        $collection->each(function ($customFieldData) use (&$mapping) {
            $customFieldId = $customFieldData['custom_field_id'];
            $mapping[$customFieldId] = $customFieldData['data'];
        });

        $repository->updateOrCreateMany($project, $user, $mapping);

        $result = [];
        $status = 201;

        return response()->json($result, $status);
    }

    public function showAllCustomFieldsData(
        ShowProjectRequest $request,
        MemberCustomFieldDataRepository $repository,
        $projectId)
    {
        $user = $this->jwtService->getUser();
        $project = Project::find($projectId);

        $memberCustomFieldData = $repository->getVolunteerAllCustomFieldData($project, $user);

        if ($memberCustomFieldData === null) {
            $result = [];
            $status = 204;

            $response = $this->response->noContent();
        } else {
            $transformer = new UserCustomFieldWithDataTransformer();
            $response = $this->response->collection($memberCustomFieldData,
                $transformer);
        }

        return $response;
    }

    public function showAllMembersCustomFieldData(
        ShowAllMembersCustomFieldDataRequest $request,
        MemberCustomFieldDataDbRepository $repository,
        $projectId
    ) {
        $project = Project::find($projectId);

        $transformer = new ProjectMemberWithCustomFieldDataTransformer();

        return $this->response->item($project, $transformer);
    }
}
