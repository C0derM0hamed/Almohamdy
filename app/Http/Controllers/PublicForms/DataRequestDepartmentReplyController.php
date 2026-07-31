<?php

namespace App\Http\Controllers\PublicForms;

use App\Http\Controllers\Controller;
use App\Services\GovernmentDataRequests\GovernmentDataRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class DataRequestDepartmentReplyController extends Controller
{
    public function __construct(
        private readonly GovernmentDataRequestService $requests,
    ) {}

    public function show(string $token): View
    {
        try {
            [$request, $parsed] = $this->requests->openDepartmentReply($token);
        } catch (InvalidArgumentException $exception) {
            return view('public.data-requests.reply-result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        return view('public.data-requests.reply', [
            'item' => $request,
            'token' => $token,
            'administratorId' => $parsed->administratorId,
        ]);
    }

    public function store(Request $httpRequest, string $token): View|RedirectResponse
    {
        $validated = $httpRequest->validate([
            'answer_text' => ['required', 'string', 'min:3', 'max:4000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'max:8192', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'confirm' => ['accepted'],
        ]);

        try {
            [$request, $parsed] = $this->requests->resolveByReplyToken($token);
            $this->requests->submitDepartmentReply(
                $request,
                $parsed->administratorId,
                (string) $validated['answer_text'],
                array_values(array_filter((array) $httpRequest->file('files', []))),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('public.data-requests.reply.show', ['token' => $token])
                ->withInput()
                ->withErrors(['answer_text' => $exception->getMessage()]);
        }

        return view('public.data-requests.reply-result', [
            'success' => true,
            'message' => __('data_requests.department_reply.success'),
        ]);
    }
}
