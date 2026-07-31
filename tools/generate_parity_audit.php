<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$input = $root.'/LEGACY_INVENTORY.tsv';
$output = $root.'/OLD_TO_NEW_COMPLETE_GAP_AUDIT.md';

if (! is_file($input)) {
    fwrite(STDERR, "Missing LEGACY_INVENTORY.tsv\n");
    exit(1);
}

$handle = fopen($input, 'rb');
$headers = fgetcsv($handle, separator: "\t", escape: "\\");
$rows = [];

while (($values = fgetcsv($handle, separator: "\t", escape: "\\")) !== false) {
    if (count($values) !== count($headers)) {
        continue;
    }

    $rows[] = array_combine($headers, $values);
}

fclose($handle);

$families = [
    'Authentication' => ['/(^|\/)(login|login_opt|logout)\.php$/i', 'Auth controllers and routes'],
    'Account recovery/profile' => ['/(forgot|change.*pass|signature)/i', 'Password recovery request only'],
    'Users and permissions' => ['/(user|permission|group|access_role)/i', 'System user/permission administration'],
    'Doctors directory' => ['/(clinician|clinic_details|doctor|specialized_clinic)/i', 'Doctors directory/admin'],
    'Service locations' => ['/(outpatient_clinic|floor|central_section|service_location)/i', 'Service locations'],
    'Hospital services' => ['/(service_package|hospital_service|admission_room)/i', 'Hospital services'],
    'Complaints' => ['/(complaint|comlaint|patient_complaint)/i', 'Complaints list/show/timeline'],
    'Inquiries' => ['/(inquir|inqure)/i', 'Inquiries list/timeline/PDF/status'],
    'Employee leave' => ['/(vacation|leave_request)/i', 'Employee leave'],
    'Absence notifications' => ['/(absence_notification)/i', 'Work absence notification'],
    'Training' => ['/(training|course_trainee|course_registration)/i', 'None'],
    'Appointments' => ['/(appointment|booking_period)/i', 'None'],
    'Government circulars' => ['/(government_circular|briefing)/i', 'Government circulars'],
    'Inspection visits' => ['/(inspection_visit)/i', 'Inspection visits'],
    'Data requests' => ['/(government.*report|g_data|g_answer)/i', 'Government data requests'],
    'Correspondence' => ['/(corporate_communication|correspondence)/i', 'Correspondence/outgoing correspondence'],
    'Legal cases' => ['/(lawsuit|legal|labor_case|medical_case|commercial_case|administrative_case|executive_title)/i', 'None'],
    'Reports and print' => ['/(^|\/)(rep|report)|(_pdf|pdf\.php|print)/i', 'Selected module PDF/print routes only'],
];

$fullyMigrated = [
    'login.php' => 'GET/POST login',
    'login_opt.php' => 'GET/POST OTP',
    'logout.php' => 'POST logout',
];

$counts = [];
$matrix = [];

foreach ($rows as $row) {
    $path = $row['legacy_path'];
    $basename = basename($path);
    $module = 'Unclassified legacy surface';
    $equivalent = 'None identified';

    foreach ($families as $name => [$pattern, $target]) {
        if (preg_match($pattern, $path) === 1) {
            $module = $name;
            $equivalent = $target;
            break;
        }
    }

    $isMenu = $row['reachability'] === 'menu';
    $isMapped = ! in_array($equivalent, ['None', 'None identified'], true);

    if (isset($fullyMigrated[$basename])) {
        $status = 'Fully migrated';
        $equivalent = $fullyMigrated[$basename];
    } elseif ($isMapped) {
        $status = 'Partially migrated';
    } elseif ($isMenu) {
        $status = 'Missing';
    } else {
        $status = 'Client decision required';
    }

    $counts[$status] = ($counts[$status] ?? 0) + 1;
    $missingBackend = $status === 'Fully migrated' ? 'No static gap identified' : ($isMapped ? 'Function-level parity unproven' : 'No mapped Laravel backend');
    $missingFrontend = $status === 'Fully migrated' ? 'No static gap identified' : ($isMapped ? 'Legacy screens/actions not fully mapped' : 'No mapped Blade/route');
    $missingContent = $status === 'Fully migrated' ? 'No static gap identified' : 'Labels, fields, statuses, and output require page review';
    $missingPermission = $status === 'Fully migrated' ? 'No static gap identified' : 'Production page/grant mapping unavailable';
    $priority = $isMenu ? 'P0' : (($row['reachability'] === 'outbound-linked' || $row['forms'] !== '') ? 'P1' : 'P2');
    $scope = $row['scope_evidence'] !== '' ? $row['scope_evidence'] : 'Not statically proven';
    $actions = trim($row['forms'].'; '.$row['actions'], '; ');
    $workflow = $row['statuses'] !== '' ? 'Numeric evidence: '.$row['statuses'] : 'Not statically resolved';
    $evidence = trim($row['evidence'].'; reachability='.$row['reachability'].'; activity='.$row['activity'], '; ');

    $cells = [
        $row['page_name'], $path, $module, $row['purpose_hint'], $row['tables'], $row['permission_keys'],
        $scope, $actions, $workflow, $row['attachments'], $row['print_pdf'], $equivalent, $status,
        $missingBackend, $missingFrontend, $missingContent, $missingPermission, $priority, $evidence,
    ];

    $matrix[] = '| '.implode(' | ', array_map(
        static fn (string $value): string => str_replace(["\r", "\n", '|'], [' ', ' ', '\\|'], $value === '' ? 'Not statically resolved' : $value),
        $cells,
    )).' |';
}

ksort($counts);
$summary = implode("\n", array_map(
    static fn (string $status, int $count): string => "| {$status} | {$count} |",
    array_keys($counts),
    array_values($counts),
));

$document = <<<MD
# Old-to-New Complete Gap Audit

Generated: 2026-07-31

## Decision and method

This matrix starts from every first-party PHP file discovered in `OldProject`, not from Laravel routes. It is generated from `LEGACY_INVENTORY.tsv` by `tools/generate_parity_audit.php`. Bundled libraries and UI demos are excluded; their application call sites remain represented. Static matching is deliberately conservative: a Laravel module match is **Partially migrated** until each legacy form, action, status, permission, scope, attachment, and output is proven equivalent. No file is marked obsolete or duplicate without runtime/client evidence.

| Status | Count |
|---|---:|
{$summary}
| **Total** | **%d** |

Only three narrow authentication entry points are classified fully migrated by direct route evidence. This is not a claim that the whole authentication/account family is complete. Rows marked **Client decision required** exist in source but lack enough production reachability evidence to classify active, duplicate, obsolete, or inaccessible. Active menu targets without a Laravel family are **Missing**.

## Required production evidence

- Sanitized `page`, `user_permission`, `user_groups_permission`, and branch-permission rows.
- Production menu/rewrite configuration and representative role/branch/company accounts.
- Production access evidence sufficient to resolve unknown/backup-looking files.
- Attachment inventory and production-like database/files for write, PDF, and download tests.

## Complete matrix

| Legacy Page | Legacy File/URL | Module | Purpose | Tables | Permission | Branch/Company Scope | Forms/Actions | Workflow | Attachments | Print/PDF | NewProject Equivalent | Status | Missing Backend | Missing Frontend | Missing Content | Missing Permission | Priority | Evidence |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
%s
MD;

file_put_contents($output, sprintf($document, count($rows), implode("\n", $matrix)));

fwrite(STDOUT, sprintf("Generated %s with %d rows.\n", basename($output), count($rows)));
