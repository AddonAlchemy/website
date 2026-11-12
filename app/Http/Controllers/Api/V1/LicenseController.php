<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Licensing\ActivateLicense;
use App\Actions\Licensing\CheckForUpdate;
use App\Actions\Licensing\DeactivateInstance;
use App\Actions\Licensing\VerifyLicense;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateCheckRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LicenseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'service' => config('app.name').' License API',
            'version' => 'v1',
            'endpoints' => [
                'POST /api/v1/activate' => 'Felder: key, product_id, [instance_id], [name]',
                'POST /api/v1/deactivate' => 'Felder: key, instance_id',
                'GET|POST /api/v1/verify' => 'Felder: key, product_id (nur Prüfung, read-only)',
                'GET /api/v1/updates/check' => 'Felder: product_id, [version], [key], [platform]',
                'GET /api/v1/plugins' => 'Öffentlicher Katalog (Filter: q, category, platform, sort)',
                'GET /api/v1/licenses' => 'Eigene Lizenzen (Bearer-Token erforderlich)',
            ],
            'hint' => 'Sende einen POST-Request mit JSON- oder Form-Body.',
        ], options: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function activate(Request $request, ActivateLicense $activate): JsonResponse
    {
        return $activate->handle($request->all(), (string) $request->ip())->toResponse();
    }

    public function deactivate(Request $request, DeactivateInstance $deactivate): JsonResponse
    {
        return $deactivate->handle($request->all())->toResponse();
    }

    public function verify(Request $request, VerifyLicense $verify): JsonResponse
    {
        return $verify->handle($request->all())->toResponse();
    }

    public function update(UpdateCheckRequest $request, CheckForUpdate $check): JsonResponse
    {
        return $check->handle($request->validated())->toResponse();
    }
}
