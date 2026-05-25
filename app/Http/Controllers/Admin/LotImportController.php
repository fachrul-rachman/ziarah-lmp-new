<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLotImportJob;
use App\Models\ImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LotImportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.mimes' => 'Format file harus xlsx/xls/csv.',
        ]);

        $file = $validated['file'];

        $storedPath = $file->store('imports');

        $importJob = ImportJob::query()->create([
            'filename' => $file->getClientOriginalName(),
            'status' => 'queued',
            'total_rows' => 0,
            'processed_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
            'started_at' => null,
            'finished_at' => null,
        ]);

        ProcessLotImportJob::dispatch($importJob->id, $storedPath);

        return redirect()->back()
            ->with('success', 'Import lot dijadwalkan. Silakan tunggu proses selesai.')
            ->with('import_job_id', $importJob->id);
    }

    public function show(ImportJob $importJob): JsonResponse
    {
        $importJob->load(['errors' => fn ($q) => $q->orderByDesc('id')->limit(10)]);

        return response()->json([
            'id' => $importJob->id,
            'filename' => $importJob->filename,
            'status' => $importJob->status,
            'total_rows' => $importJob->total_rows,
            'processed_rows' => $importJob->processed_rows,
            'success_rows' => $importJob->success_rows,
            'failed_rows' => $importJob->failed_rows,
            'started_at' => optional($importJob->started_at)?->toIso8601String(),
            'finished_at' => optional($importJob->finished_at)?->toIso8601String(),
            'errors' => $importJob->errors->map(fn ($e) => [
                'row_number' => $e->row_number,
                'error_message' => $e->error_message,
            ])->values(),
        ]);
    }
}
