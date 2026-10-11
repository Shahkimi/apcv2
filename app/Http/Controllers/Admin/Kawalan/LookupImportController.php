<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Kawalan\DatabaseImportUploadRequest;
use App\Http\Requests\Admin\Kawalan\LookupImportMapRequest;
use App\Services\Import\LookupDataset;
use App\Services\Import\LookupImportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LookupImportController extends Controller
{
    public function upload(DatabaseImportUploadRequest $request, string $dataset, LookupImportService $service): JsonResponse
    {
        $ds = LookupDataset::find($dataset);
        $this->forget($service, $ds);

        $meta = $service->storeUpload($request->file('file'), $ds);
        session([
            $ds->sessionKey() => [
                'relative_path' => $meta['relative_path'],
                'headers' => $meta['headers'],
                'row_count' => $meta['row_count'],
                'stored_at' => time(),
            ],
        ]);

        return response()->json([
            'headers' => $meta['headers'],
            'row_count' => $meta['row_count'],
            'impact' => $service->impact($ds),
        ]);
    }

    public function preview(LookupImportMapRequest $request, string $dataset, LookupImportService $service): JsonResponse
    {
        $ds = $request->dataset();
        if (($gone = $this->sessionOrGone($service, $ds)) !== null) {
            return $gone;
        }
        /** @var array{relative_path: string, headers: list<string>} $s */
        $s = session($ds->sessionKey());
        $result = $service->preview($s['relative_path'], $s['headers'], (string) $request->validated('mapping.'.$ds->column), $ds);
        unset($result['rows']);

        return response()->json($result);
    }

    public function import(LookupImportMapRequest $request, string $dataset, LookupImportService $service): JsonResponse
    {
        $ds = $request->dataset();
        if (($gone = $this->sessionOrGone($service, $ds)) !== null) {
            return $gone;
        }
        /** @var array{relative_path: string, headers: list<string>} $s */
        $s = session($ds->sessionKey());
        $out = $service->commitImport($s['relative_path'], $s['headers'], (string) $request->validated('mapping.'.$ds->column), $ds);

        if (! $out['ok']) {
            return response()->json($out, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->forget($service, $ds);

        return response()->json([
            'imported' => $out['imported'],
            'replaced' => $out['replaced'],
            'pegawai' => $out['pegawai'],
            'deletes_pegawai' => $out['deletes_pegawai'],
            'errors' => [],
        ]);
    }

    public function template(string $dataset): StreamedResponse
    {
        $ds = LookupDataset::find($dataset);

        return response()->streamDownload(function () use ($ds): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [$ds->column]);
            fputcsv($out, [$ds->sample]);
            fclose($out);
        }, 'templat-'.$ds->key.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function forget(LookupImportService $service, LookupDataset $ds): void
    {
        /** @var array<string, mixed>|null $session */
        $session = session($ds->sessionKey());
        if (is_array($session)) {
            $service->deleteStoredFile($session['relative_path'] ?? null);
        }
        session()->forget($ds->sessionKey());
    }

    private function sessionOrGone(LookupImportService $service, LookupDataset $ds): ?JsonResponse
    {
        /** @var array<string, mixed>|null $session */
        $session = session($ds->sessionKey());
        if (! is_array($session) || ! isset($session['relative_path'], $session['headers'])) {
            return response()->json(
                ['message' => __('Sesi import tamat. Muat naik semula fail.')],
                Response::HTTP_GONE
            );
        }
        if (time() - (int) ($session['stored_at'] ?? 0) > 1800) {
            $this->forget($service, $ds);

            return response()->json(
                ['message' => __('Sesi import tamat (30 min). Muat naik semula fail.')],
                Response::HTTP_GONE
            );
        }

        return null;
    }
}
