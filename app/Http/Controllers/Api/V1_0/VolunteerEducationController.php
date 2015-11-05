<?php

namespace App\Http\Controllers\Api\V1_0;

use Illuminate\Http\Request;
use App\Http\Requests\Api\V1_0\EducationRequest;
use App\Http\Requests\Api\V1_0\UpdateEducationRequest;
use Gate;
use App\Http\Controllers\Api\BaseVolunteerController;
use App\Exceptions\AccessDeniedException;
use App\Transformers\VolunteerEducationTransformer;
use App\Education;
use App\Services\JwtService;

class VolunteerEducationController extends BaseVolunteerController
{
    protected $jwtService;

    public function __construct(JwtService $jwtService)
    {
        parent::__construct();

        $this->jwtService = $jwtService;
    }

    /**
     * Show volunteer's own existing educations
     * @return Illuminate\Http\JsonResponse
     */
    public function show()
    {
        $volunteer = $this->jwtService->getVolunteer();

        $educations = $volunteer->educations()->get();
        
        // Set serialzer for a transformer
        $manager = new \League\Fractal\Manager();
        $manager->setSerializer(new \League\Fractal\Serializer\ArraySerializer());

        // transform Experience model into array
        $resource = new \League\Fractal\Resource\Collection(
            $educations,
            new VolunteerEducationTransformer,
            'educations');

        return response()->json($manager->createData($resource)->toArray(), 200);
    }

    /**
     * Store a new education
     * @param  App\Http\Requests\Api\V1_0\EducationRequest $request
     * @return Illuminate\Http\JsonResponse
     */
    public function store(EducationRequest $request)
    {
        $volunteer = $this->jwtService->getVolunteer();
        
        $education = new Education($request->all());
        $education = $volunteer->educations()->save($education);
        $responseJson = [
            'education' => [
                'id' => (int) $education->id
            ]
        ];

        return response()->json($responseJson, 201);
    }

    /**
     * Update volunteer's own education
     * @param  App\Http\Requests\Api\V1_0\UpdateEducationRequest $request
     * @return Illuminate\Http\JsonResponse
     */
    public function update(UpdateEducationRequest $request)
    {
        $education = Education::findOrFail($request->input('id'));

        // Check the App\Policies\VolunteerEducationPolicy::update()
        if (Gate::denies('update', $education)) {
            // Forbidden to update
            throw new AccessDeniedException();
        }

        $education->update($request->except('id'));

        return response()->json(null, 204);
    }

    /**
     * Delete volunteer's own education
     * @param  Integer $educationId
     * @return Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $education = Education::findOrFail($id);

        // // Check the App\Policies\VolunteerEducationPolicy::update()
        if (Gate::denies('delete', $education)) {
            // Forbidden to delete the education record
            throw new AccessDeniedException();
        }

        $education->delete();

        return response()->json(null, 204);
    }
}
