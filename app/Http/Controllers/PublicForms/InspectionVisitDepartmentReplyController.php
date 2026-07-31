<?php

namespace App\Http\Controllers\PublicForms;

use App\Http\Controllers\Controller;
use App\Http\Requests\GovernmentInspectionVisits\SubmitInspectionVisitDepartmentReplyRequest;
use App\Services\GovernmentInspectionVisits\GovernmentInspectionVisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class InspectionVisitDepartmentReplyController extends Controller
{
    public function __construct(
        private readonly GovernmentInspectionVisitService $visits,
    ) {}

    public function show(string $token): View|RedirectResponse
    {
        try {
            [$visit, $parsed, $pending] = $this->visits->openDepartmentReply($token);
        } catch (InvalidArgumentException $exception) {
            return view('public.inspection-visits.reply-result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        return view('public.inspection-visits.reply', [
            'visit' => $visit,
            'pending' => $pending,
            'token' => $token,
            'administratorId' => $parsed->administratorId,
            'mode' => 'reply',
        ]);
    }

    public function store(SubmitInspectionVisitDepartmentReplyRequest $request, string $token): RedirectResponse|View
    {
        try {
            [$visit, $parsed] = $this->visits->resolveByReplyToken($token);
            $this->visits->submitDepartmentReply($visit, $parsed->administratorId, $request->payload());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('public.inspection-visits.reply.show', ['token' => $token])
                ->withInput()
                ->withErrors(['items' => $exception->getMessage()]);
        }

        return view('public.inspection-visits.reply-result', [
            'success' => true,
            'message' => __('inspection_visits.department_reply.success'),
        ]);
    }

    public function showReturned(string $token): View|RedirectResponse
    {
        try {
            [$visit, $parsed, $pending] = $this->visits->openReturnedReply($token);
        } catch (InvalidArgumentException $exception) {
            return view('public.inspection-visits.reply-result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        return view('public.inspection-visits.reply', [
            'visit' => $visit,
            'pending' => $pending,
            'token' => $token,
            'administratorId' => $parsed->administratorId,
            'mode' => 'returned',
        ]);
    }

    public function storeReturned(SubmitInspectionVisitDepartmentReplyRequest $request, string $token): RedirectResponse|View
    {
        try {
            [$visit, $parsed] = $this->visits->resolveByReplyToken($token);
            $this->visits->submitReturnedReply($visit, $parsed->administratorId, $request->payload());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('public.inspection-visits.reply-returned.show', ['token' => $token])
                ->withInput()
                ->withErrors(['items' => $exception->getMessage()]);
        }

        return view('public.inspection-visits.reply-result', [
            'success' => true,
            'message' => __('inspection_visits.department_reply.success'),
        ]);
    }
}
