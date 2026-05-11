<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CarerLoginRequest;
use App\Models\User;
use App\Services\CarerAuthenticationService;
use Illuminate\Http\JsonResponse;

class CarerLoginController extends Controller
{
    public function __invoke(CarerLoginRequest $request, CarerAuthenticationService $authenticator): JsonResponse
    {
        $user = $authenticator->authenticate($request->validated(), $request);

        return response()->json([
            'message' => 'Carer login verified.',
            'carer' => $this->carerPayload($user),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function carerPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'job_title' => $user->job_title,
            'home' => $user->home ? [
                'id' => $user->home->id,
                'name' => $user->home->name,
            ] : null,
            'profile' => $user->carerProfile ? [
                'status' => $user->carerProfile->status,
                'account_status' => $user->carerProfile->account_status,
                'dbs_check_status' => $user->carerProfile->dbs_check_status,
                'availability_pattern' => $user->carerProfile->availability_pattern,
            ] : null,
        ];
    }
}
   