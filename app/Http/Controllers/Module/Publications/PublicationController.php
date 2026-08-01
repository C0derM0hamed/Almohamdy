<?php

namespace App\Http\Controllers\Module\Publications;

use App\Http\Controllers\Controller;
use App\Services\Publications\PublicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicationController extends Controller
{
    public function __construct(private readonly PublicationService $service) {}

    public function index(Request $request): View
    {
        $filters = ['search' => trim((string) $request->input('search', '')), 'type_id' => $request->integer('type_id')];
        return view('publications.index', ['posts' => $this->service->list($filters), 'filters' => $filters, 'types' => $this->service->types(), 'homeRoute' => 'branch.dashboard']);
    }

    public function create(): View { return view('publications.create', ['types' => $this->service->types(), 'branches' => $this->service->branches(), 'homeRoute' => 'branch.dashboard']); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['branch_ids' => ['required', 'array', 'min:1'], 'branch_ids.*' => ['integer'], 'post_type_id' => ['required', 'integer'], 'subject_ar' => ['required', 'string', 'max:255'], 'subject_en' => ['nullable', 'string', 'max:255'], 'post_ar' => ['required', 'string'], 'post_en' => ['nullable', 'string'], 'uploaded_file' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif']]);
        $id = $this->service->create($data, $request->file('uploaded_file'));
        return redirect()->route('modules.publications.show', $id)->with('success', __('publications.created'));
    }

    public function show(int $publication): View { $post = $this->service->find($publication); abort_if($post === null, 404); return view('publications.show', ['post' => $post, 'homeRoute' => 'branch.dashboard']); }
    public function download(int $publication): mixed { return $this->service->download($publication); }
}
