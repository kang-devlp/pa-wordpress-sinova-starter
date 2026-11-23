<?php

namespace App\Http\Controllers;

use App\Models\SinovaWebsiteAuditLog;
use App\Models\SinovaWebsiteDocument;
use App\Services\Sinova\WordPressSinovaSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class WebsiteSinovaController extends Controller
{
    public function index(Request $request, WordPressSinovaSyncService $sync)
    {
        $baseUrl = $sync->baseUrl();
        $modules = collect(WordPressSinovaSyncService::MODULES)
            ->map(function (array $m) use ($baseUrl): array {
                $m['periods'] = WordPressSinovaSyncService::periodsForModule($m['slug']);
                $m['public_url'] = WordPressSinovaSyncService::resolveEvidenceUrl((string) ($m['public_url'] ?? '/'), $baseUrl);
                return $m;
            })
            ->values();

        $years = [2027, 2026, 2025, 2024, 2023, 2022, 2021, 2020, 2019, 2018, 2017];

        $documents = Schema::hasTable('sinova_website_documents')
            ? SinovaWebsiteDocument::query()
                ->orderBy('page_slug')
                ->orderByDesc('year')
                ->orderBy('sub_group')
                ->orderBy('period_order')
                ->get()
                ->map(fn (SinovaWebsiteDocument $d) => [
                    'id' => $d->id,
                    'page_slug' => $d->page_slug,
                    'year' => $d->year,
                    'sub_group' => $d->sub_group,
                    'period_key' => $d->period_key,
                    'period_order' => $d->period_order,
                    'title' => $d->title,
                    'source_type' => $d->source_type,
                    'doc_url' => $d->doc_url,
                    'file_size_kb' => $d->file_size_kb,
                    'sync_status' => $d->sync_status,
                    'last_synced_at' => $d->last_synced_at?->format('d/m/Y H:i'),
                    'link_http_status' => $d->link_http_status,
                    'link_health_note' => $d->link_health_note,
                    'updated_by_name' => $d->updated_by_name,
                ])->values()
            : collect();

        $recentLogs = Schema::hasTable('sinova_website_audit_logs')
            ? SinovaWebsiteAuditLog::query()
                ->orderByDesc('id')
                ->limit(40)
                ->get()
                ->map(fn (SinovaWebsiteAuditLog $log) => [
                    'id' => $log->id,
                    'user_name' => $log->user_name,
                    'action' => $log->action,
                    'page_slug' => $log->page_slug,
                    'year' => $log->year,
                    'sub_group' => $log->sub_group,
                    'period_key' => $log->period_key,
                    'source_type' => $log->source_type,
                    'sync_ok' => $log->sync_ok,
                    'message' => $log->message,
                    'created_at' => $log->created_at?->format('d/m/Y H:i'),
                ])->values()
            : collect();

        $compliance = WordPressSinovaSyncService::complianceReport($documents);

        return Inertia::render('WebsiteSinova/Index', [
            'appMeta' => ['name' => config('app.name', 'Portal SINOVA')],
            'siteBaseUrl' => $baseUrl,
            'modules' => $modules,
            'categories' => WordPressSinovaSyncService::CATEGORIES,
            'roadmapGroups' => WordPressSinovaSyncService::roadmapGroups(),
            'compliance' => $compliance,
            'months' => collect(WordPressSinovaSyncService::MONTHS)->map(fn ($v, $k) => [
                'key' => $k,
                'label' => $v['label'],
                'order' => $v['order'],
            ])->values(),
            'years' => $years,
            'documents' => $documents,
            'recentLogs' => $recentLogs,
            'gatewayStatus' => $sync->ping(),
        ]);
    }

    public function save(Request $request, WordPressSinovaSyncService $sync)
    {
        $validSlugs = array_keys(WordPressSinovaSyncService::MODULES);
        $validPeriods = array_keys(WordPressSinovaSyncService::ALL_PERIODS);

        $validated = $request->validate([
            'page_slug' => ['required', 'string', Rule::in($validSlugs)],
            'year' => ['required', 'integer', 'min:2017', 'max:2027'],
            'sub_group' => ['required', 'string', 'max:64'],
            'period_key' => ['required', 'string', Rule::in($validPeriods)],
            'source_type' => ['required', 'string', Rule::in(['wp_pdf', 'gdrive', 'external'])],
            'doc_url' => ['nullable', 'url', 'max:1000'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $slug = $validated['page_slug'];
        $module = WordPressSinovaSyncService::MODULES[$slug];
        if (! in_array($validated['sub_group'], $module['sub_groups'], true)) {
            return back()->withErrors(['sub_group' => 'Kelompok dokumen tidak valid untuk modul ini.']);
        }

        $allowedPeriodKeys = array_column(WordPressSinovaSyncService::periodsForModule($slug), 'key');
        if (! in_array($validated['period_key'], $allowedPeriodKeys, true)) {
            return back()->withErrors(['period_key' => 'Periode slot tidak valid untuk modul ini.']);
        }

        $user = $request->user();
        $userName = (string) ($user?->name ?: $user?->email ?: 'Operator');

        $payload = [
            'page_slug' => $slug,
            'year' => (int) $validated['year'],
            'sub_group' => $validated['sub_group'],
            'period_key' => $validated['period_key'],
            'source_type' => $validated['source_type'],
            'title' => $validated['title'] ?? '',
            'updated_by' => $userName,
        ];

        if ($validated['source_type'] === 'wp_pdf') {
            $file = $request->file('pdf_file');
            if (! $file) {
                return back()->withErrors(['pdf_file' => 'Silakan pilih file PDF maksimal 10 MB.']);
            }
            $raw = file_get_contents($file->getRealPath());
            if ($raw === false || substr($raw, 0, 5) !== '%PDF-') {
                return back()->withErrors(['pdf_file' => 'File yang diunggah bukan PDF asli yang valid.']);
            }
            $payload['pdf_base64'] = base64_encode($raw);
            $payload['filename'] = $file->getClientOriginalName();
        } else {
            $url = trim((string) ($validated['doc_url'] ?? ''));
            if ($url === '' || stripos($url, 'https://') !== 0) {
                return back()->withErrors(['doc_url' => 'Tautan wajib diisi dan diawali https://.']);
            }
            $payload['doc_url'] = $url;
        }

        try {
            $saved = $sync->upsertDocument($payload);
            $health = $sync->checkLinkHealth((string) $saved['doc_url']);
            $periodOrder = WordPressSinovaSyncService::ALL_PERIODS[$validated['period_key']]['order'] ?? 99;

            SinovaWebsiteDocument::updateOrCreate(
                [
                    'page_slug' => $slug,
                    'year' => (int) $validated['year'],
                    'sub_group' => $validated['sub_group'],
                    'period_key' => $validated['period_key'],
                ],
                [
                    'period_order' => (int) ($saved['period_order'] ?? $periodOrder),
                    'title' => (string) ($saved['title'] ?? ''),
                    'source_type' => (string) ($saved['source_type'] ?? $validated['source_type']),
                    'doc_url' => (string) $saved['doc_url'],
                    'file_size_kb' => isset($saved['file_size_kb']) ? (int) $saved['file_size_kb'] : null,
                    'sync_status' => 'synced',
                    'last_synced_at' => now(),
                    'last_sync_error' => null,
                    'link_http_status' => $health['status'],
                    'link_health_note' => $health['note'],
                    'link_checked_at' => now(),
                    'updated_by_user_id' => $user?->id,
                    'updated_by_name' => $userName,
                ]
            );

            SinovaWebsiteAuditLog::create([
                'user_id' => $user?->id,
                'user_name' => $userName,
                'action' => 'UPSERT',
                'page_slug' => $slug,
                'year' => (int) $validated['year'],
                'sub_group' => $validated['sub_group'],
                'period_key' => $validated['period_key'],
                'source_type' => (string) ($saved['source_type'] ?? $validated['source_type']),
                'doc_url' => (string) $saved['doc_url'],
                'sync_ok' => true,
                'message' => 'Sinkron ke website satker berhasil (' . $health['note'] . ')',
                'created_at' => now(),
            ]);

            return back()->with('success', "Berhasil diperbarui & disinkronkan ke website ({$health['note']}).");
        } catch (\Throwable $e) {
            SinovaWebsiteAuditLog::create([
                'user_id' => $user?->id,
                'user_name' => $userName,
                'action' => 'UPSERT_FAIL',
                'page_slug' => $slug,
                'year' => (int) $validated['year'],
                'sub_group' => $validated['sub_group'],
                'period_key' => $validated['period_key'],
                'source_type' => $validated['source_type'],
                'sync_ok' => false,
                'message' => mb_substr($e->getMessage(), 0, 240),
                'created_at' => now(),
            ]);

            return back()->with('error', 'Sinkronisasi gagal: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, WordPressSinovaSyncService $sync)
    {
        $validSlugs = array_keys(WordPressSinovaSyncService::MODULES);
        $validPeriods = array_keys(WordPressSinovaSyncService::ALL_PERIODS);

        $validated = $request->validate([
            'page_slug' => ['required', 'string', Rule::in($validSlugs)],
            'year' => ['required', 'integer', 'min:2017', 'max:2027'],
            'sub_group' => ['required', 'string', 'max:64'],
            'period_key' => ['required', 'string', Rule::in($validPeriods)],
        ]);

        $user = $request->user();
        $userName = (string) ($user?->name ?: $user?->email ?: 'Operator');

        try {
            $sync->deleteDocument(
                $validated['page_slug'],
                (int) $validated['year'],
                $validated['sub_group'],
                $validated['period_key']
            );

            SinovaWebsiteDocument::query()
                ->where('page_slug', $validated['page_slug'])
                ->where('year', (int) $validated['year'])
                ->where('sub_group', $validated['sub_group'])
                ->where('period_key', $validated['period_key'])
                ->delete();

            SinovaWebsiteAuditLog::create([
                'user_id' => $user?->id,
                'user_name' => $userName,
                'action' => 'DELETE',
                'page_slug' => $validated['page_slug'],
                'year' => (int) $validated['year'],
                'sub_group' => $validated['sub_group'],
                'period_key' => $validated['period_key'],
                'sync_ok' => true,
                'message' => 'Slot dokumen dihapus dari website & portal internal.',
                'created_at' => now(),
            ]);

            return back()->with('success', 'Dokumen pada slot tersebut telah dihapus dari website.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus dokumen: ' . $e->getMessage());
        }
    }

    public function pull(Request $request, WordPressSinovaSyncService $sync)
    {
        $slug = $request->input('page_slug');
        if ($slug !== null && ! isset(WordPressSinovaSyncService::MODULES[$slug])) {
            $slug = null;
        }

        $user = $request->user();
        $userName = (string) ($user?->name ?: $user?->email ?: 'Operator');

        try {
            $res = $sync->pullDocuments($slug);

            SinovaWebsiteAuditLog::create([
                'user_id' => $user?->id,
                'user_name' => $userName,
                'action' => 'PULL_SYNC',
                'page_slug' => $slug ?: 'all',
                'sync_ok' => true,
                'message' => "Menarik {$res['count']} data dari website satker.",
                'created_at' => now(),
            ]);

            return back()->with('success', "Berhasil menarik {$res['count']} data terbaru dari website satker.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menarik data: ' . $e->getMessage());
        }
    }

    public function healthCheck(Request $request, WordPressSinovaSyncService $sync)
    {
        $validated = $request->validate([
            'page_slug' => ['required', 'string', Rule::in(array_keys(WordPressSinovaSyncService::MODULES))],
            'year' => ['required', 'integer', 'min:2017', 'max:2027'],
        ]);

        $docs = SinovaWebsiteDocument::query()
            ->where('page_slug', $validated['page_slug'])
            ->where('year', (int) $validated['year'])
            ->limit(36)
            ->get();

        $okCount = 0;
        $badCount = 0;
        foreach ($docs as $doc) {
            $res = $sync->checkLinkHealth($doc->doc_url);
            $doc->update([
                'link_http_status' => $res['status'],
                'link_health_note' => $res['note'],
                'link_checked_at' => now(),
            ]);
            if ($res['status'] >= 200 && $res['status'] < 400) {
                $okCount++;
            } else {
                $badCount++;
            }
        }

        return back()->with('success', "Pemeriksaan selesai ({$validated['page_slug']} {$validated['year']}): {$okCount} tautan sehat, {$badCount} perlu perhatian.");
    }
}
