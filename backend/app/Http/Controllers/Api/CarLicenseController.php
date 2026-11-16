<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarLicenseDocumentRequest;
use App\Http\Requests\CarLicenseRequest;
use App\Http\Resources\CarLicenseDocumentResource;
use App\Http\Resources\CarLicenseResource;
use App\Models\Car;
use App\Models\CarLicense;
use App\Models\CarLicenseDocument;
use App\Models\MonthlyCar;
use App\Services\CarLicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CarLicenseController extends Controller
{
    private function query(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:500', 'status' => ['nullable', Rule::in(CarLicense::STATUSES)],
            'office_region' => ['nullable', Rule::in(array_keys(config('car_licenses.offices')))],
            'fleet' => ['nullable', Rule::in(['daily', 'monthly'])], 'current' => 'nullable|boolean',
        ]);
        return CarLicense::query()->with('car', 'monthlyCar', 'renewal')
            ->when($request->filled('status'), fn ($q) => $q->status($request->input('status')))
            ->when($request->filled('office_region'), fn ($q) => $q->where('office_region', $request->input('office_region')))
            ->when($request->input('fleet') === 'daily', fn ($q) => $q->whereNotNull('car_id'))
            ->when($request->input('fleet') === 'monthly', fn ($q) => $q->whereNotNull('monthly_car_id'))
            ->when($request->boolean('current'), fn ($q) => $q->whereDoesntHave('renewal'))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->string('search')->toString().'%';
                $q->where(fn ($sub) => $sub->where('license_number', 'like', $term)
                    ->orWhere('office_branch', 'like', $term)->orWhere('remarks', 'like', $term)
                    ->orWhereHas('car', fn ($v) => $v->where('plate_no', 'like', $term)->orWhere('model_name', 'like', $term))
                    ->orWhereHas('monthlyCar', fn ($v) => $v->where('plate_no', 'like', $term)->orWhere('model_name', 'like', $term)));
            });
    }

    public function index(Request $request)
    {
        return CarLicenseResource::collection($this->query($request)->orderBy('expiry_date')->orderByDesc('id')->paginate(min(100, max(1, $request->integer('per_page', 10)))));
    }

    public function summary()
    {
        $current = CarLicense::query()->whereDoesntHave('renewal');
        $data = ['total' => (clone $current)->count()];
        foreach (CarLicense::STATUSES as $status) $data[strtolower(str_replace(' ', '_', $status))] = (clone $current)->status($status)->count();
        $data['alerts'] = CarLicenseResource::collection((clone $current)->with('car', 'monthlyCar')->status('Expiring Soon')->orderBy('expiry_date')->limit(5)->get());
        $data['expired_alerts'] = CarLicenseResource::collection((clone $current)->with('car', 'monthlyCar')->status('Expired')->orderByDesc('expiry_date')->limit(5)->get());
        return response()->json(['data' => $data]);
    }

    public function options()
    {
        $vehicles = collect();
        foreach (['daily' => Car::class, 'monthly' => MonthlyCar::class] as $type => $model) {
            $vehicles = $vehicles->concat($model::query()->orderBy('plate_no')->get(['id', 'plate_no', 'model_name'])->map(fn ($v) => [...$v->toArray(), 'fleet' => $type, 'value' => $type.':'.$v->id]));
        }
        return response()->json(['data' => ['vehicles' => $vehicles->values(), 'offices' => config('car_licenses.offices'), 'periods' => CarLicense::PERIODS, 'statuses' => CarLicense::STATUSES]]);
    }

    public function store(CarLicenseRequest $request, CarLicenseService $service)
    {
        return (new CarLicenseResource($service->save($request->validated(), $request->user())))->response()->setStatusCode(201);
    }

    public function show(CarLicense $car_license)
    {
        $license = $car_license->load('car', 'monthlyCar', 'documents');
        $root = $license;
        while ($root->parent()->exists()) $root = $root->parent()->firstOrFail();
        $history = collect();
        do {
            $history->push($root);
            $root = $root->renewal()->first();
        } while ($root);
        $license->setRelation('historyRecords', $history->sortBy('issue_date')->values());
        return new CarLicenseResource($license);
    }

    public function update(CarLicenseRequest $request, CarLicense $car_license, CarLicenseService $service)
    {
        return new CarLicenseResource($service->save($request->validated(), $request->user(), $car_license));
    }

    public function destroy(CarLicense $car_license, CarLicenseService $service)
    {
        $service->delete($car_license);
        return response()->noContent();
    }

    public function renew(CarLicenseRequest $request, CarLicense $car_license, CarLicenseService $service)
    {
        $data = $request->validated();
        unset($data['car_id'], $data['monthly_car_id']);
        return (new CarLicenseResource($service->save($data, $request->user(), $car_license, true)))->response()->setStatusCode(201);
    }

    public function upload(CarLicenseDocumentRequest $request, CarLicense $car_license)
    {
        $file = $request->file('document');
        $disk = Storage::disk('car-licenses');
        $root = strtolower(str_replace('\\', '/', rtrim($disk->path(''), '\\/')));
        $webroot = strtolower(str_replace('\\', '/', rtrim(public_path(), '\\/')));
        if ($root === $webroot || str_starts_with($root, $webroot.'/')) abort(503, 'Car license documents must be stored outside the web root.');
        $key = $car_license->id.'/'.Str::uuid().'.'.strtolower($file->extension());
        $stored = $disk->putFileAs(dirname($key), $file, basename($key));
        try {
            if (! $stored) throw new \RuntimeException('The document could not be stored.');
            $doc = DB::transaction(function () use ($car_license, $file, $stored): CarLicenseDocument {
                CarLicense::query()->lockForUpdate()->findOrFail($car_license->id);
                $name = preg_replace('/[^\\pL\\pN._ -]/u', '_', basename(str_replace('\\', '/', $file->getClientOriginalName())));
                return CarLicenseDocument::create(['car_license_id' => $car_license->id, 'storage_key' => $stored, 'original_name' => mb_substr($name ?: 'document', 0, 200), 'mime_type' => $file->getMimeType() ?: 'application/octet-stream', 'size' => $file->getSize()]);
            });
        } catch (\Throwable $error) {
            if ($stored) $disk->delete($stored);
            throw $error;
        }
        return (new CarLicenseDocumentResource($doc))->response()->setStatusCode(201);
    }

    public function download(CarLicenseDocument $document)
    {
        $disk = Storage::disk('car-licenses');
        if (! $disk->exists($document->storage_key)) abort(404);
        return response()->streamDownload(function () use ($disk, $document): void {
            $stream = $disk->readStream($document->storage_key);
            if ($stream === false) abort(404);
            fpassthru($stream);
            if (is_resource($stream)) fclose($stream);
        }, $document->original_name, ['Content-Type' => $document->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store', 'Content-Security-Policy' => "default-src 'none'; sandbox"]);
    }
}
