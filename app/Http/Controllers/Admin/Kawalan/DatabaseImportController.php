<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Kawalan\DatabaseImportMapRequest;
use App\Http\Requests\Admin\Kawalan\DatabaseImportUploadRequest;
use App\Services\DatabaseImportService;
use App\Services\Import\LookupDataset;
use App\Services\Import\LookupImportService;
use App\Services\Kehadiran\KehadiranCallingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DatabaseImportController extends Controller
{
    public function index(
        Request $request,
        DatabaseImportService $service,
        LookupImportService $lookupService,
        KehadiranCallingService $calling,
    ): View {
        $key = (string) $request->query('dataset', 'pegawai');
        $lookup = in_array($key, LookupDataset::keys(), true) ? LookupDataset::find($key) : null;

        if ($lookup === null) {
            return view('admin::kawalan.database.index', [
                'dataset' => 'pegawai',
                'lookup' => null,
                'urls' => [
                    'upload' => route('admin.kawalan.database.upload'),
                    'preview' => route('admin.kawalan.database.preview'),
                    'import' => route('admin.kawalan.database.import'),
                ],
                'fillableFields' => $service->fillableFields(),
                'requiredMapped' => $service->requiredMappedFields(),
                'optionalPolicyFields' => $service->optionalPolicyFields(),
                'jasamuFields' => DatabaseImportService::JASAMU_FIELDS,
            ]);
        }

        return view('admin::kawalan.database.index', [
            'dataset' => $lookup->key,
            'lookup' => [
                'key' => $lookup->key,
                'label' => $lookup->label,
                'column' => $lookup->column,
                'columnLabel' => $lookup->columnLabel,
                'aliases' => $lookup->aliases,
                'impact' => $lookupService->impact($lookup),
                'activeSesi' => $calling->activeOnAirSesi()?->sesi,
                'template' => route('admin.kawalan.database.lookup.template', $lookup->key),
            ],
            'urls' => [
                'upload' => route('admin.kawalan.database.lookup.upload', $lookup->key),
                'preview' => route('admin.kawalan.database.lookup.preview', $lookup->key),
                'import' => route('admin.kawalan.database.lookup.import', $lookup->key),
            ],
            'fillableFields' => [$lookup->column],
            'requiredMapped' => [$lookup->column],
            'optionalPolicyFields' => [],
            'jasamuFields' => [],
        ]);
    }

    public function upload(DatabaseImportUploadRequest $request, DatabaseImportService $service): JsonResponse
    {
        $meta = $service->storeUpload($request->file('file'));
        session([
            DatabaseImportService::SESSION_KEY => [
                'relative_path' => $meta['relative_path'],
                'headers' => $meta['headers'],
                'row_count' => $meta['row_count'],
                'stored_at' => time(),
            ],
        ]);

        return response()->json(['headers' => $meta['headers'], 'row_count' => $meta['row_count']]);
    }

    public function preview(DatabaseImportMapRequest $request, DatabaseImportService $service): JsonResponse
    {
        $gone = $this->pegawaiImportSessionOrGone($service);
        if ($gone !== null) {
            return $gone;
        }
        /** @var array{relative_path: string, headers: list<string>} $s */
        $s = session(DatabaseImportService::SESSION_KEY);
        $v = $request->validated();

        return response()->json($service->preview($s['relative_path'], $s['headers'], $v['mapping'], $v['empty_policy']));
    }

    public function import(DatabaseImportMapRequest $request, DatabaseImportService $service): JsonResponse
    {
        if (($gone = $this->pegawaiImportSessionOrGone($service)) !== null) {
            return $gone;
        }
        /** @var array{relative_path: string, headers: list<string>} $s */
        $s = session(DatabaseImportService::SESSION_KEY);
        $v = $request->validated();
        $out = $service->commitImport($s['relative_path'], $s['headers'], $v['mapping'], $v['empty_policy']);
        if (! $out['ok']) {
            return response()->json($out, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $this->forgetImportSession($service, $s['relative_path']);

        return response()->json(['imported' => $out['imported'], 'errors' => []]);
    }

    private function forgetImportSession(DatabaseImportService $service, string $relativePath): void
    {
        $service->deleteStoredFile($relativePath);
        session()->forget(DatabaseImportService::SESSION_KEY);
    }

    private function pegawaiImportSessionOrGone(DatabaseImportService $service): ?JsonResponse
    {
        /** @var array<string, mixed>|null $session */
        $session = session(DatabaseImportService::SESSION_KEY);
        if (! is_array($session) || ! isset($session['relative_path'], $session['headers'])) {
            return response()->json(
                ['message' => __('Sesi import tamat. Muat naik semula CSV.')],
                Response::HTTP_GONE
            );
        }
        if (time() - (int) ($session['stored_at'] ?? 0) > 1800) {
            $this->forgetImportSession($service, (string) $session['relative_path']);

            return response()->json(
                ['message' => __('Sesi import tamat (30 min). Muat naik semula CSV.')],
                Response::HTTP_GONE
            );
        }

        return null;
    }
}
