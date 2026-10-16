<?php

namespace App\Http\Controllers;

use App\Models\PageSection;
use App\Models\PageSectionVote;
use App\Models\PageSectionSubmission;
use App\Support\PageFormBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PageBlockController extends Controller
{
    /** Vote on a polling block (POST = submit vote, GET=?results=1 = fetch counts) */
    public function vote(Request $request, PageSection $section)
    {
        abort_unless($section->tipe === 'polling', 404);

        $cfg       = $section->konfigurasi ?? [];
        $opsiCount = count($cfg['opsi'] ?? []);

        // GET request: return vote results only
        if ($request->isMethod('GET')) {
            return response()->json($this->voteSummaryPayload($section));
        }

        $pilihan = (int) $request->input('pilihan');

        if ($pilihan < 0 || $pilihan >= $opsiCount) {
            return response()->json(['error' => 'Pilihan tidak valid.'], 422);
        }

        $sessionKey = hash('sha256', session()->getId() . '|' . $request->ip() . '|' . $section->id);

        // Prevent double-vote
        if (PageSectionVote::where('section_id', $section->id)
                           ->where('session_key', $sessionKey)->exists()) {
            return response()->json(['error' => 'Anda sudah memilih.'], 409);
        }

        PageSectionVote::create([
            'section_id'  => $section->id,
            'session_key' => $sessionKey,
            'pilihan'     => $pilihan,
        ]);

        return response()->json(['ok' => true] + $this->voteSummaryPayload($section));
    }

    /** Submit a formulir block */
    public function submit(Request $request, PageSection $section)
    {
        abort_unless($section->tipe === 'formulir', 404);

        $cfg = PageFormBlock::normalizeConfig($section->konfigurasi ?? []);
        $fields = $cfg['fields'] ?? [];
        $availability = PageFormBlock::availabilityState($cfg);
        $rawInput = (array) $request->input('fields', []);

        if (! ($availability['is_open'] ?? false)) {
            throw ValidationException::withMessages([
                'form' => (string) ($availability['message'] ?? 'Akses formulir saat ini ditutup.'),
            ]);
        }

        $accessState = PageFormBlock::authorizeAccess($section, $cfg, $request);
        if (! ($accessState['authorized'] ?? false)) {
            throw ValidationException::withMessages([
                'form' => (string) ($accessState['message'] ?? 'Anda tidak memiliki akses untuk mengisi formulir ini.'),
            ]);
        }

        $request->validate(PageFormBlock::validationRules($fields, $rawInput));

        $inputData = PageFormBlock::normalizeAnswers($fields, $rawInput, true);

        $responseLimit = (string) ($cfg['response_limit'] ?? 'none');
        $limitMessage = PageFormBlock::duplicateMessage($cfg);
        $emailFieldKey = PageFormBlock::firstEmailFieldKey($fields);

        if ($responseLimit === 'session' && $request->session()->has('page_form_submitted.' . $section->id)) {
            throw ValidationException::withMessages([
                'form' => $limitMessage,
            ]);
        }

        if ($responseLimit === 'email' && PageFormBlock::hasDuplicateEmailSubmission($section, $fields, $inputData)) {
            throw ValidationException::withMessages([
                $emailFieldKey ? 'fields.' . $emailFieldKey : 'form' => $limitMessage,
            ]);
        }

        $inputData = PageFormBlock::normalizeAnswersFromRequest($section, $fields, $request, true);

        $quizResult = null;
        if (PageFormBlock::isQuiz($cfg)) {
            $attemptSessionKey = 'page_form_quiz_started.' . $section->id;
            $startedAt = $request->session()->get($attemptSessionKey);

            if (! is_string($startedAt) || trim($startedAt) === '') {
                $startedAt = (string) $request->input('_quiz_started_at', '');
            }

            $quizResult = PageFormBlock::buildQuizAttemptPayload(
                $cfg,
                $fields,
                $inputData,
                is_string($startedAt) && trim($startedAt) !== '' ? $startedAt : null
            );

            if (! empty($quizResult['timed_out'])) {
                throw ValidationException::withMessages([
                    'form' => (string) ($cfg['time_limit_message'] ?? 'Waktu pengerjaan untuk formulir ini sudah habis.'),
                ]);
            }

            $inputData['__quiz'] = $quizResult;
        }

        $inputData['__access'] = array_filter([
            'scope' => $accessState['scope'] ?? 'public',
            'event_id' => data_get($accessState, 'event.id'),
            'event_title' => data_get($accessState, 'event.judul'),
            'participant_id' => data_get($accessState, 'participant.id'),
            'participant_name' => data_get($accessState, 'participant.nama'),
            'user_type' => data_get($accessState, 'participant.id')
                ? 'event_participant'
                : (Auth::guard('mahasiswa')->check() ? 'mahasiswa' : (Auth::check() ? 'pegawai' : 'public')),
            'user_id' => Auth::guard('mahasiswa')->id() ?: Auth::id(),
        ], static fn ($value): bool => $value !== null && $value !== '');

        $proctoringPayload = PageFormBlock::extractProctoringPayload($section, $cfg, $request);
        if ($proctoringPayload !== null) {
            $inputData['__proctoring'] = $proctoringPayload;
        }

        PageSectionSubmission::create([
            'section_id' => $section->id,
            'data'       => $inputData,
            'ip'         => $request->ip(),
        ]);

        if ($responseLimit === 'session') {
            $request->session()->put('page_form_submitted.' . $section->id, true);
        }

        if (PageFormBlock::isQuiz($cfg)) {
            $request->session()->forget('page_form_quiz_started.' . $section->id);
        }

        // Optionally send email if configured
        $to = $cfg['email_tujuan'] ?? null;
        if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            try {
                $text = implode("\n", PageFormBlock::mailLines($fields, $inputData));
                Mail::raw($text, function ($m) use ($to) {
                    $m->to($to)->subject('Pesan baru dari website Poltekkes Bandung');
                });
            } catch (\Exception) {
                // Suppress mail errors
            }
        }

        $redirect = back()->with('blok_sukses_' . $section->id, true);

        if ($quizResult !== null) {
            $redirect->with('blok_quiz_result_' . $section->id, $quizResult);
        }

        return $redirect;
    }

    public function authorizeFormAccess(Request $request, PageSection $section)
    {
        abort_unless($section->tipe === 'formulir', 404);

        $cfg = PageFormBlock::normalizeConfig($section->konfigurasi ?? []);
        $accessState = PageFormBlock::authorizeAccess($section, $cfg, $request);

        if (! ($accessState['authorized'] ?? false)) {
            throw ValidationException::withMessages([
                'form' => (string) ($accessState['message'] ?? 'Anda tidak memiliki akses untuk membuka formulir ini.'),
            ]);
        }

        return back()->with('blok_akses_' . $section->id, true);
    }

    private function voteSummaryPayload(PageSection $section): array
    {
        $rows  = PageSectionVote::where('section_id', $section->id)
            ->selectRaw('pilihan, COUNT(*) as cnt')
            ->groupBy('pilihan')
            ->pluck('cnt', 'pilihan')
            ->toArray();
        $total = array_sum($rows);
        return ['votes' => $rows, 'total' => $total];
    }
}
