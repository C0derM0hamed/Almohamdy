<?php

namespace App\Http\Controllers\PublicForms;

use App\Http\Controllers\Controller;
use App\Services\CorporateCommunications\CorporateCommunicationOutgoingLetterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class OutgoingLetterReviseController extends Controller
{
    public function __construct(
        private readonly CorporateCommunicationOutgoingLetterService $letters,
    ) {}

    public function show(string $token): View
    {
        try {
            [$item, $parsed] = $this->letters->openDepartmentRevise($token);
        } catch (InvalidArgumentException $exception) {
            return view('public.outgoing-correspondence.revise-result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        return view('public.outgoing-correspondence.revise', [
            'item' => $item,
            'token' => $token,
            'administratorId' => $parsed->administratorId,
        ]);
    }

    public function store(Request $request, string $token): View|RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:1000'],
            'letter_content' => ['required', 'string', 'min:3'],
            'confirm' => ['accepted'],
        ]);

        try {
            [$item, $parsed] = $this->letters->resolveByReplyToken($token);
            $this->letters->submitDepartmentRevise(
                $item,
                $parsed->administratorId,
                (string) $validated['subject'],
                (string) $validated['letter_content'],
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('public.outgoing-correspondence.revise.show', ['token' => $token])
                ->withInput()
                ->withErrors(['letter_content' => $exception->getMessage()]);
        }

        return view('public.outgoing-correspondence.revise-result', [
            'success' => true,
            'message' => __('outgoing_correspondence.department_revise.success'),
        ]);
    }
}
