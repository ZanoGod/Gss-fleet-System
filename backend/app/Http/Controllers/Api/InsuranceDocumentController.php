<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InsuranceDocumentRequest;
use App\Http\Resources\InsuranceDocumentResource;
use App\Models\InsuranceDocument;
use App\Models\InsurancePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InsuranceDocumentController extends Controller
{
    public function store(InsuranceDocumentRequest $request, InsurancePolicy $policy)
    {
        Gate::authorize('update', $policy);
        $file = $request->file('document');
        $disk = Storage::disk('insurance');
        $root = strtolower(str_replace('\\', '/', rtrim($disk->path(''), '\\/')));
        $webroot = strtolower(str_replace('\\', '/', rtrim(public_path(), '\\/')));
        if ($root === $webroot || str_starts_with($root, $webroot.'/')) {
            abort(503, 'Insurance document storage must be outside the web root.');
        }
        $extension = strtolower($file->extension());
        $key = $policy->id.'/'.Str::uuid().'.'.$extension;
        $replacement = $request->validated('replaces_id');
        $stored = $disk->putFileAs(dirname($key), $file, basename($key));
        try {
            if (! $stored) {
                throw new \RuntimeException('The private document could not be stored.');
            }
            $document = DB::transaction(function () use ($policy, $replacement, $stored, $file): InsuranceDocument {
                $lockedPolicy = InsurancePolicy::query()->lockForUpdate()->findOrFail($policy->id);
                if ($lockedPolicy->archived_at) {
                    throw ValidationException::withMessages(['document' => 'Archived policies cannot receive documents.']);
                }
                $old = null;
                if ($replacement) {
                    $old = $lockedPolicy->documents()->lockForUpdate()->findOrFail($replacement);
                    if ($old->archived_at || $old->replacement()->exists()) {
                        throw ValidationException::withMessages(['replaces_id' => 'This document is already archived or replaced.']);
                    }
                    $old->archived_at = now();
                    $old->save();
                }
                $name = preg_replace('/[^\\pL\\pN._ -]/u', '_', basename(str_replace('\\', '/', $file->getClientOriginalName())));
                $document = InsuranceDocument::create([
                    'policy_id' => $lockedPolicy->id,
                    'storage_key' => $stored,
                    'original_name' => mb_substr($name ?: 'document', 0, 200),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                    'replaces_id' => $old?->id,
                ]);
                return $document;
            });
        } catch (\Throwable $error) {
            if ($stored) $disk->delete($stored);
            throw $error;
        }
        return (new InsuranceDocumentResource($document))->response()->setStatusCode(201);
    }

    public function download(InsuranceDocument $document)
    {
        Gate::authorize('view', $document);
        $disk = Storage::disk('insurance');
        if (! $disk->exists($document->storage_key)) {
            abort(404);
        }
        return response()->streamDownload(function () use ($disk, $document): void {
            $stream = $disk->readStream($document->storage_key);
            if ($stream === false) {
                abort(404);
            }
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function archive(InsuranceDocument $document)
    {
        Gate::authorize('update', $document->policy);
        if (! $document->archived_at) $document->forceFill(['archived_at' => now()])->save();
        return new InsuranceDocumentResource($document->refresh());
    }
}
