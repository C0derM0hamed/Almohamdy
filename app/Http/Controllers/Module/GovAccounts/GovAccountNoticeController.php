<?php

namespace App\Http\Controllers\Module\GovAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\GovAccounts\SaveGovAccountNoticeRequest;
use App\Http\Requests\GovAccounts\StoreGovAccountNoticeAttachmentRequest;
use App\Models\GovAccountNotice;
use App\Services\GovAccounts\GovAccountNoticeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GovAccountNoticeController extends Controller
{
    public function __construct(private readonly GovAccountNoticeService $notices) {}

    public function index(): View
    {
        return view('gov-accounts.notices.index', ['notices' => $this->notices->notices()]);
    }

    public function create(): View
    {
        return view('gov-accounts.notices.create', ['notice' => null] + $this->notices->options());
    }

    public function store(SaveGovAccountNoticeRequest $request): RedirectResponse
    {
        $notice = $this->notices->create($request->payload());
        foreach ($request->attachments() as $attachment) {
            $this->notices->storeAttachment($notice, $attachment);
        }

        return redirect()->route('modules.gov-accounts.notices.show', $notice)->with('success', __('gov_accounts.flash.notice_saved'));
    }

    public function show(int $notice): View
    {
        return view('gov-accounts.notices.show', ['notice' => $this->notices->noticeOrFail($notice)]);
    }

    public function edit(int $notice): View
    {
        return view('gov-accounts.notices.create', ['notice' => $this->notices->noticeOrFail($notice)] + $this->notices->options());
    }

    public function update(SaveGovAccountNoticeRequest $request, int $notice): RedirectResponse
    {
        $record = $this->notices->update(GovAccountNotice::query()->findOrFail($notice), $request->payload());
        foreach ($request->attachments() as $attachment) {
            $this->notices->storeAttachment($record, $attachment);
        }

        return redirect()->route('modules.gov-accounts.notices.show', $record)->with('success', __('gov_accounts.flash.notice_saved'));
    }

    public function attachment(StoreGovAccountNoticeAttachmentRequest $request, int $notice): RedirectResponse
    {
        $this->notices->storeAttachment(GovAccountNotice::query()->findOrFail($notice), $request->file('attachment'), $request->input('description'));

        return back()->with('success', __('gov_accounts.flash.action_done'));
    }

    public function download(int $notice, int $attachment)
    {
        return $this->notices->downloadAttachment(GovAccountNotice::query()->findOrFail($notice), $attachment);
    }

    public function send(int $notice): RedirectResponse
    {
        $count = $this->notices->send(GovAccountNotice::query()->findOrFail($notice));

        return redirect()->route('modules.gov-accounts.notices.show', $notice)->with('success', __('gov_accounts.flash.notice_sent', ['count' => $count]));
    }
}
