<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LicenseResource;
use App\Http\Resources\UserResource;
use App\Models\License;
use App\Services\DownloadLinkFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AccountController extends Controller
{
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function licenses(Request $request): AnonymousResourceCollection
    {
        return LicenseResource::collection(
            $request->user()->licenses()
                ->with(['plugin:id,public_id,name,slug,latest_version', 'activations'])
                ->latest()
                ->paginate(50),
        );
    }

    public function download(Request $request, License $license, DownloadLinkFactory $links): JsonResponse
    {
        abort_unless($license->user_id === $request->user()->id, 404);
        abort_unless($license->isUsable(), 403, 'Diese Lizenz ist nicht mehr gültig.');

        $release = $license->plugin->latestRelease;

        abort_if($release === null, 404, 'Für dieses Produkt ist keine Version verfügbar.');

        return response()->json([
            'version' => $release->version,
            'file' => ['name' => $release->file_name, 'size' => $release->file_size, 'sha256' => $release->file_hash],
            'download_url' => $links->make($release, $request->user(), $license),
            'expires_in' => config('marketplace.downloads.link_ttl_seconds'),
        ]);
    }
}
