<?php

namespace App\Http\Controllers\Api\V1_0;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;
use App\Http\Requests\Api\V1_0\UpdateProfileRequest;
use App\Http\Requests\Api\V1_0\UpdateSkillsRequest;
use App\Exceptions\AuthenticatedUserNotFoundException;
use App\Exceptions\JWTTokenNotFoundException;
use App\Exceptions\ExceedingIndexException;
use App\City;
use App\Utils\ArrayUtil;

class VolunteerProfileController extends Controller
{
    protected $volunteer;

    public function __construct()
    {
        if (env('APP_ENV') == 'testing' && array_key_exists("HTTP_AUTHORIZATION", request()->server())) {
            JWTAuth::setRequest(\Route::getCurrentRequest());
        }
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showMe()
    {

        return 'qqq';
    }

    public function updateMe(UpdateProfileRequest $request)
    {
        $this->getVolunteerIdentifier();

        if ($request->has('city') && $request->has('city.id')) {
            $input = $request->expect(['city', 'city.id']);
            $cityInput = $request->only(['city.id']);
            $city = City::find($cityInput);

            $this->volunteer->city()->associate($city);
            $this->volunteer->save();
        }

        $this->volunteer->update($input);

        // retrive volunteer's profile
    }

    public function updateSkillsMe(UpdateSkillsRequest $request)
    {
        $this->getVolunteerIdentifier();

        $skillsList = $request->input('skills');
        $existingSkillIndexes = $request->input('existing_skill_indexes');

        if (count($existingSkillIndexes) != 0) {
            $maxIndex = max($existingSkillIndexes);
            
            if (ArrayUtil::isIndexExceed($skillsList, $maxIndex)) {
                // Index exceeds $skillsList size
                throw new ExceedingIndexException();
            }
        }

        $unexistingSkills = ArrayUtil::getUnexisting($skillsList, $existingSkillIndexes);

        // Update volunteer's skills
        foreach ($unexistingSkills as $skill) {
            $this->volunteer->skills()
                 ->firstOrCreate(['name' => $skill]);
        }

        return response()->json(null, 204);
    }

    protected function getVolunteerIdentifier()
    {
        try {
            if (! $this->volunteer = JWTAuth::parseToken()->authenticate()) {
                throw new AuthenticatedUserNotFoundException();
            }
        } catch (JWTException $e) {
            throw new JWTTokenNotFoundException($e);
        }
    }

}
