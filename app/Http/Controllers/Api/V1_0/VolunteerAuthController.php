<?php

namespace App\Http\Controllers\Api\V1_0;

use App\Http\Requests\Api\V1_0\VolunteerRegistrationRequest;
use App\Http\Requests\Api\V1_0\CredentialRequest;
use App\Http\Requests\Request;
use App\Http\Controllers\Controller;
use App\Services\AvatarStorageService;
use Dingo\Api\Routing\Helpers;
use App\Volunteer;
use App\City;
use App\VerificationCode;
use App\Jobs\SendVerificationEmail;
use App\Utils\StringUtil;
use App\Http\Responses\Error;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Repositories\VolunteerRepository;
use App\Repositories\CityRepository;
use App\Repositories\VerificationCodeRepository;
use App\Services\JwtService;
use App\Services\VerifyEmailService;
use App\Commands\VerifyEmailCommand;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\AuthenticatedUserNotFoundException;
use App\Exceptions\NotFoundException;
use App\Transformers\VolunteerProfileTransformer;

/**
 * The controller provides user authentications
 *
 * @Author: Yi-Ming, Huang <ymhuang>
 * @Date:   2015-11-19T14:59:59+08:00
 * @Email:  ym.huang0808@gmail.com
 * @Project: VMS
 * @Last modified by:   aming
 * @Last modified time: 2016-07-04T13:40:27+08:00
 * @License: GPL-3
 */
class VolunteerAuthController extends Controller
{
    use Helpers;

    /**
     * Register a new volunteer. The request will be validated by
     * App\Http\Middleware\CheckHeaderFieldsMiddleware and
     * App\Http\Requests\Api\V1_0\VolunteerRegistrationRequest classes
     *
     * @param  VolunteerRegistrationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(VolunteerRegistrationRequest $request,
        VolunteerRepository $volunteerRepository,
        VerificationCodeRepository $verificationCodeRepository,
        CityRepository $cityRepository,
        JwtService $jwtSerivce,
        AvatarStorageService $avatarStorageService)
    {
        // Get volunteer data, except city object
        $volunteerInput = $request->except(['city', 'avatar']);
        // Get city ID
        $cityId = $request->input('city.id');

        // Save avatar name
        if ($request->has('avatar')) {
            $avatarBase64File = $request->input('avatar');

            $avatarStorageService->save($avatarBase64File);
            $volunteerInput['avatar_path'] = $avatarStorageService->getFileName();
        }

        // Find city entity
        $volunteerInput['city'] = $cityRepository->findById($cityId);

        // Create a volunteer entity
        $volunteer = $volunteerRepository->create($volunteerInput);

        // Save verification code
        $verificationCodeString = StringUtil::generateHashToken();
        $verificationCodeRepository->create(['code' => $verificationCodeString], $volunteer);

        // Send verification email to an queue
        $this->dispatch(new SendVerificationEmail($volunteer, $verificationCodeString, 'VMS 電子郵件驗證'));

        $credentials = $request->only('username', 'password');

        // Generate JWT (JSON Web Token)
        $token = $jwtSerivce->getToken($credentials);

        return $this->response
                    ->item($volunteer, new VolunteerProfileTransformer)
                    ->withHeader('Authorization', 'Bearer ' . $token)
                    ->setStatusCode(201);
    }

    /**
     * Volunteer logs in the system.
     * It will response the JSON Web token.
     *
     * @param  CredentialRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(CredentialRequest $request, JwtService $jwtSerivce)
    {
        $credentials = $request->only('username', 'password');

        // Generate JWT (JSON Web Token)
        $token = $jwtSerivce->getToken($credentials);

        // Check if the volunteer was locked
        $volunteer = Volunteer::where('username', '=', $credentials['username'])->first();

        if ($volunteer->is_locked == 1 || $volunteer->is_locked == true) {
            $token = null;
            $message = 'Authentication failed';
            $error = new Error('account_was_locked');

            throw new UnauthorizedException($message, $error);
        }

        // $rootUrl = request()->root();
        //
        // $responseJson = [
        //     'href' => env('APP_URL', $rootUrl) . '/api/users/me',
        //     'auth_access_token' => $token
        // ];
        //
        // return response()->json($responseJson, 200);

        return $this->response
                    ->item($volunteer, new VolunteerProfileTransformer)
                    ->withHeader('Authorization', 'Bearer ' . $token)
                    ->setStatusCode(200);
    }

    /**
     * Volunteer logs out the system.
     * The JWT token will be in blacklist.
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        if (! $token = JWTAuth::getToken()) {
            $message = 'Failed to logout';
            $error = new Error('no_existing_auth_access_token');
            $statusCode = 400;

            return response()->apiJsonError($message, $error, $statusCode);
        }

        JWTAuth::invalidate($token);

        return response(null, 204);
    }

    /**
     * Verify volunteer's email address with verification code.
     * It will check the volunteer's verification code and the expired time
     * @param  EmailVerificationRequest $reuqest
     * @return \Illuminate\Http\JsonResponse
     */
    public function emailVerification($emailAddress, $verificationCode, JwtService $jwtSerivce)
    {
        $volunteer = $jwtSerivce->getVolunteer();

        $service = new VerifyEmailService($volunteer, $emailAddress, $verificationCode);
        $command = new VerifyEmailCommand($service);
        $command->execute();

        $responseJson = [
            'message' => 'Successful email verification'
        ];

        return response()->json($responseJson, 200);
    }

    /**
     * Resend a new email verification
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendEmailVerification(VerificationCodeRepository $verificationCodeRepository, JwtService $jwtSerivce)
    {
        $volunteer = $jwtSerivce->getVolunteer();

        $volunteer->verificationCode()->delete();

        $verificationCodeString = StringUtil::generateHashToken();
        // Save verification code into the volunteer
        $verificationCodeRepository->create(['code' => $verificationCodeString], $volunteer);

        // Send verification email to an queue
        $this->dispatch(new SendVerificationEmail($volunteer, $verificationCodeString, 'VMS 電子郵件驗證'));

        return response(null, 204);
    }
}
