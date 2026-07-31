<?php

namespace App\Http\Controllers\PublicForms;

use App\Http\Controllers\Controller;
use App\Services\CorporateCommunications\CorporateCommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CorrespondenceDepartmentReplyController extends Controller
{
    public function __construct(
        private readonly CorporateCommunicationService $communications,
    ) {}

    public function show(string $token): View
    {
        try {
            [$item, $parsed] = $this->communications->openDepartmentReply($token);
        } catch (InvalidArgumentException $exception) {
            return view('public.correspondence.reply-result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        return view('public.correspondence.reply', [
            'item' => $item,
            'token' => $token,
            'administratorId' => $parsed->administratorId,
        ]);
    }

    public function store(Request $request, string $token): View|RedirectResponse
    {
        $validated = $request->validate([
            'details' => ['required', 'string', 'min:3', 'max:4000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'max:8192', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'confirm' => ['accepted'],
        ]);

        try {
            [$item, $parsed] = $this->communications->resolveByReplyToken($token);
            $this->communications->submitDepartmentReply(
                $item,
                $parsed->administratorId,
                (string) $validated['details'],
                array_values(array_filter((array) $request->file('files', []))),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('public.correspondence.reply.show', ['token' => $token])
                ->withInput()
                ->withErrors(['details' => $exception->getMessage()]);
        }

        return view('public.correspondence.reply-result', [
            'success' => true,
            'message' => __('correspondence.department_reply.success'),
        ]);
    }
}
