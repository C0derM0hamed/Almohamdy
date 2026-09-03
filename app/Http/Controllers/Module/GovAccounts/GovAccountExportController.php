<?php

namespace App\Http\Controllers\Module\GovAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\GovAccounts\GovAccountExportRequest;
use App\Services\GovAccounts\GovAccountExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GovAccountExportController extends Controller
{
    public function __construct(private readonly GovAccountExportService $exports) {}

    public function __invoke(GovAccountExportRequest $request, string $format): StreamedResponse
    {
        return $this->exports->download($request->filters(), $format);
    }
}
