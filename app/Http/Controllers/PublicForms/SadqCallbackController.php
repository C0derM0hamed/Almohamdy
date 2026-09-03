<?php

namespace App\Http\Controllers\PublicForms;

use App\Http\Controllers\Controller;
use App\Services\LegacyWorkflows\MedicalAgreementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class SadqCallbackController extends Controller
{
    public function __construct(private readonly MedicalAgreementService $agreements) {}

    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('services.sadq.callback_secret');
        if ($secret !== '') {
            $provided = (string) ($request->header('X-Sadq-Callback-Secret') ?: $request->input('secret', ''));
            abort_unless($provided !== '' && hash_equals($secret, $provided), 401, 'Callback authentication failed.');
        }

        $payload = $request->json()->all();
        if (!is_array($payload) || $payload === []) {
            return response()->json(['ok' => false, 'error' => 'Invalid JSON body.'], 400);
        }

        try {
            return response()->json(['ok' => true] + $this->agreements->handleSadqCallback($payload));
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['ok' => false, 'error' => $exception->getMessage() ?: 'Callback processing failed.'], 422);
        }
    }
}
