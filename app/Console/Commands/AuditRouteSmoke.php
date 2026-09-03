<?php

namespace App\Console\Commands;

use App\Data\NavigationItem;
use App\Services\Dashboard\NavigationService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class AuditRouteSmoke extends Command
{
    protected $signature = 'hm:audit-route-smoke
                            {--user=CLIENT_TEST_SUPER_ADMIN : hr_username to impersonate}
                            {--level=3 : hr_user_level for the session}
                            {--branch=1 : branch_id for the session}
                            {--company=1 : companies_groups_id for the session}
                            {--only-sidebar : Only audit sidebar URLs}
                            {--limit=0 : Max routes to test (0 = all)}';

    protected $description = 'Smoke-test GET routes and report 403/404/500 responses';

    /** @var list<string> */
    private array $failures = [];

    /** @var list<string> */
    private array $warnings = [];

    public function handle(NavigationService $navigation): int
    {
        $user = $this->resolveUser();
        if ($user === null) {
            return self::FAILURE;
        }

        $session = [
            'authenticated' => true,
            'hr_user_id' => (int) $user->hr_id,
            'hr_user_level' => (int) ($user->hr_user_level ?: $this->option('level')),
            'hr_branch_id' => (int) ($user->branch_id ?: $this->option('branch')),
            'companies_groups_id' => (int) ($user->companies_groups_id ?: $this->option('company')),
            'groupid' => (int) ($user->groupid ?? 0),
        ];

        $urls = $this->option('only-sidebar')
            ? $this->sidebarUrls($navigation, $session)
            : $this->allGetUrls($navigation, $session);

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $urls = array_slice($urls, 0, $limit);
        }

        $this->info('Auditing '.count($urls).' URLs as '.$user->hr_username.' (level '.$session['hr_user_level'].')');

        $bar = $this->output->createProgressBar(count($urls));
        $bar->start();

        foreach ($urls as $label => $url) {
            $this->probe($label, $url, $session);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($this->warnings !== []) {
            $this->warn('Warnings ('.count($this->warnings).'):');
            foreach ($this->warnings as $line) {
                $this->line('  '.$line);
            }
            $this->newLine();
        }

        if ($this->failures === []) {
            $this->info('No blocking failures detected.');

            return self::SUCCESS;
        }

        $this->error('Failures ('.count($this->failures).'):');
        foreach ($this->failures as $line) {
            $this->line('  '.$line);
        }

        return self::FAILURE;
    }

    /** @param array<string, int|bool> $session */
    private function probe(string $label, string $url, array $session): void
    {
        try {
            $response = $this->dispatch($url, $session);
        } catch (\Throwable $e) {
            $this->failures[] = "500 {$label} :: {$url} :: ".$e->getMessage();

            return;
        }

        $status = $response->getStatusCode();

        if ($status >= 500) {
            $this->failures[] = "{$status} {$label} :: {$url}";

            return;
        }

        if ($status === 403) {
            $this->failures[] = "403 {$label} :: {$url}";

            return;
        }

        if (in_array($status, [404, 405, 419], true)) {
            $this->failures[] = "{$status} {$label} :: {$url}";

            return;
        }

        if ($status >= 400) {
            $this->warnings[] = "{$status} {$label} :: {$url}";
        }
    }

    /** @param array<string, int|bool> $session */
    private function dispatch(string $url, array $session): Response
    {
        $request = Request::create($url, 'GET');
        $request->setLaravelSession(app('session.store'));
        $request->session()->start();
        foreach ($session as $key => $value) {
            $request->session()->put($key, $value);
        }

        return app()->handle($request);
    }

    /** @param array<string, int|bool> $session
     * @return array<string, string>
     */
    private function sidebarUrls(NavigationService $navigation, array $session): array
    {
        app('session.store')->start();
        foreach ($session as $key => $value) {
            session([$key => $value]);
        }

        $urls = [];
        foreach ($this->flatten($navigation->sidebar()) as $item) {
            if ($item->url === '' || str_contains($item->url, '{')) {
                continue;
            }
            $label = $item->route ?: $item->title;
            $urls[$label] = $item->url;
        }

        return $urls;
    }

    /** @param array<string, int|bool> $session
     * @return array<string, string>
     */
    private function allGetUrls(NavigationService $navigation, array $session): array
    {
        $urls = $this->sidebarUrls($navigation, $session);

        foreach (Route::getRoutes() as $route) {
            if (! $this->isGetRoute($route)) {
                continue;
            }

            $name = $route->getName() ?? $route->uri();
            if (isset($urls[$name])) {
                continue;
            }

            $generated = $this->generateUrl($route);
            if ($generated === null) {
                continue;
            }

            $urls[$name] = $generated;
        }

        return $urls;
    }

    private function isGetRoute(LaravelRoute $route): bool
    {
        return in_array('GET', $route->methods(), true)
            && ! in_array('POST', $route->methods(), true);
    }

    private function generateUrl(LaravelRoute $route): ?string
    {
        $name = $route->getName();
        if ($name === null) {
            return null;
        }

        $parameters = [];
        foreach ($route->parameterNames() as $parameter) {
            $parameters[$parameter] = $this->sampleParameter($parameter, $route);
        }

        try {
            return route($name, $parameters, false);
        } catch (\Throwable) {
            return null;
        }
    }

    private function sampleParameter(string $parameter, LaravelRoute $route): mixed
    {
        $uri = $route->uri();

        if (preg_match('/\{'.preg_quote($parameter, '/').':([^}]+)\}/', $uri, $matches)) {
            $constraint = $matches[1];
            if (str_contains($constraint, 'standard|manual')) {
                return 'standard';
            }
            if (str_contains($constraint, 'incoming|outgoing')) {
                return 'outgoing';
            }
            if (str_contains($constraint, 'cc|collections')) {
                return 'cc';
            }
            if (preg_match('/\|/', $constraint)) {
                return explode('|', $constraint)[0];
            }
        }

        return match (true) {
            $parameter === 'type' => 'standard',
            $parameter === 'kind' => 'cc',
            $parameter === 'page' => 'complaints',
            $parameter === 'locale' => 'ar',
            str_ends_with($parameter, '_id'), str_ends_with($parameter, 'Id') => 1,
            in_array($parameter, ['id', 'complaint', 'inquiry', 'section', 'package', 'consent', 'doctor', 'notification', 'contact', 'template', 'reference', 'reply', 'record', 'visit', 'claim', 'plan', 'appointment', 'referral', 'transferal', 'agreement', 'publication', 'circular', 'outpatientClinic', 'floor', 'clinician', 'user', 'group', 'branch', 'company', 'mode'], true) => 1,
            default => '1',
        };
    }

    /** @param list<NavigationItem> $items
     * @return list<NavigationItem>
     */
    private function flatten(array $items): array
    {
        $flat = [];
        foreach ($items as $item) {
            $flat[] = $item;
            if ($item->children !== []) {
                $flat = [...$flat, ...$this->flatten($item->children)];
            }
        }

        return $flat;
    }

    private function resolveUser(): ?object
    {
        $username = (string) $this->option('user');
        $user = \Illuminate\Support\Facades\DB::table('ra_users')
            ->where('hr_username', $username)
            ->where('activated', '1')
            ->first();

        if ($user === null) {
            $this->error("User {$username} not found or inactive.");

            return null;
        }

        return $user;
    }
}
