<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Core\PreviewInitialDataImportRequest;
use App\Modules\Core\Exports\InitialDataImportWorkbook;
use App\Modules\Core\Services\InitialDataImportReader;
use App\Modules\Core\Services\InitialDataImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InitialDataImportController extends Controller
{
    private const SESSION_KEY = 'core.initial_data_import.preview';

    public function index(Request $request, InitialDataImportService $service): View
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        $disk = Storage::disk('local');

        if (is_array($preview)
            && is_string($preview['path'] ?? null)
            && str_starts_with($preview['path'], 'initial-data-imports/')
            && $disk->exists($preview['path'])) {
            $plan = $service->preview($disk->path($preview['path']), $preview['csv_type'] ?? null);

            if ($plan['valid']) {
                return view('core.initial-data-imports.index', [
                    'plan' => $plan,
                    'previewName' => $preview['preview_name'] ?? null,
                ]);
            }

            $this->discardPreview($request);

            return view('core.initial-data-imports.index', [
                'plan' => $plan,
                'previewName' => null,
            ]);
        }

        if ($preview !== null) {
            $this->discardPreview($request);
        }

        return view('core.initial-data-imports.index', [
            'plan' => null,
            'previewName' => null,
        ]);
    }

    public function preview(PreviewInitialDataImportRequest $request, InitialDataImportService $service): View
    {
        $this->discardPreview($request);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $path = $file->store('initial-data-imports', 'local');
        $extension = strtolower($file->getClientOriginalExtension());
        $csvType = $extension === 'csv' ? $request->validated('csv_type') : null;
        $plan = $service->preview(Storage::disk('local')->path($path), $csvType);

        if ($plan['valid']) {
            $request->session()->put(self::SESSION_KEY, [
                'path' => $path,
                'csv_type' => $csvType,
                'preview_name' => $file->getClientOriginalName(),
            ]);
        } else {
            Storage::disk('local')->delete($path);
        }

        return view('core.initial-data-imports.index', [
            'plan' => $plan,
            'previewName' => $plan['valid'] ? $file->getClientOriginalName() : null,
        ]);
    }

    public function confirm(Request $request, InitialDataImportService $service): RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);

        if (! is_array($preview)
            || ! is_string($preview['path'] ?? null)
            || ! str_starts_with($preview['path'], 'initial-data-imports/')) {
            return redirect()->route('core.data-imports.index')->withErrors([
                'file' => 'Pratinjau impor sudah tidak tersedia. Unggah kembali berkas untuk memulai.',
            ]);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($preview['path'])) {
            $this->discardPreview($request);

            return redirect()->route('core.data-imports.index')->withErrors([
                'file' => 'Berkas pratinjau tidak ditemukan. Unggah kembali berkas untuk memulai.',
            ]);
        }

        try {
            $result = $service->import($disk->path($preview['path']), $preview['csv_type'] ?? null);
        } catch (ValidationException $exception) {
            $this->discardPreview($request);

            return redirect()->route('core.data-imports.index')->withErrors($exception->errors());
        }

        $this->discardPreview($request);

        return redirect()->route('core.data-imports.index')->with(
            'success',
            $result['duplicate']
                ? 'Berkas ini sudah pernah diimpor. Tidak ada data atau stok yang ditambahkan ulang.'
                : 'Impor data awal berhasil. Stok dicatat sebagai mutasi manual_in dan dapat diperiksa melalui stock opname.',
        )->with('import_summary', $result['summary']);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->discardPreview($request);

        return redirect()->route('core.data-imports.index')->with('success', 'Pratinjau dibatalkan; belum ada data yang disimpan.');
    }

    public function templateXlsx(): BinaryFileResponse
    {
        return Excel::download(new InitialDataImportWorkbook, 'template-migrasi-data-awal.xlsx');
    }

    public function templateCsv(string $type): StreamedResponse
    {
        abort_unless(array_key_exists($type, InitialDataImportReader::HEADINGS), 404);
        $headings = InitialDataImportReader::HEADINGS[$type];

        return response()->streamDownload(function () use ($headings): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                return;
            }

            fputcsv($stream, $headings, escape: '');
            fclose($stream);
        }, 'template-'.$type.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function discardPreview(Request $request): void
    {
        $preview = $request->session()->pull(self::SESSION_KEY);

        if (is_array($preview)
            && is_string($preview['path'] ?? null)
            && str_starts_with($preview['path'], 'initial-data-imports/')) {
            Storage::disk('local')->delete($preview['path']);
        }
    }
}
