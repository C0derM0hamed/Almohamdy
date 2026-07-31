param(
    [string]$BaseVersion = '1.0.1',
    [string]$NewVersion = '1.0.2'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
Set-Location $root

$includeRoots = @('app', 'bootstrap', 'config', 'lang', 'public', 'resources', 'routes')

$excludeDirs = @(
    'vendor', 'node_modules', '.git', '.cursor', 'deploy', 'tests', 'database',
    'storage', 'bootstrap\cache'
)

$excludeFiles = @(
    '.env', 'hm.zip', '.phpunit.result.cache', 'artisan'
)

function Test-ExcludedPath {
    param([string]$RelativePath)

    $normalized = $RelativePath -replace '\\', '/'

    foreach ($dir in $excludeDirs) {
        $dirNorm = ($dir -replace '\\', '/').TrimEnd('/')
        if ($normalized -eq $dirNorm -or $normalized.StartsWith("$dirNorm/")) {
            return $true
        }
    }

    foreach ($file in $excludeFiles) {
        if ($normalized -eq ($file -replace '\\', '/')) {
            return $true
        }
    }

    if ($normalized -match '^bootstrap/cache/.+\.php$') {
        return $true
    }

    return $false
}

function Get-FileSha256 {
    param([string]$Path)
    return (Get-FileHash -Path $Path -Algorithm SHA256).Hash.ToUpper()
}

function Get-DeployRootFiles {
    $files = @()

    foreach ($includeRoot in $includeRoots) {
        $absolute = Join-Path $root $includeRoot
        if (-not (Test-Path $absolute)) { continue }

        $items = Get-ChildItem -Path $absolute -Recurse -File | Where-Object {
            $relative = $_.FullName.Substring($root.Length + 1)
            -not (Test-ExcludedPath $relative)
        }

        $files += $items
    }

    foreach ($topFile in @('artisan', 'composer.json', 'composer.lock')) {
        $absolute = Join-Path $root $topFile
        if (Test-Path $absolute) {
            $files += Get-Item $absolute
        }
    }

    return $files | Sort-Object FullName -Unique
}

$baseManifestPath = Join-Path $root "deploy\version-$BaseVersion.manifest.txt"
if (-not (Test-Path $baseManifestPath)) {
    throw "Base manifest not found: $baseManifestPath"
}

$baseHashes = @{}
Get-Content $baseManifestPath | ForEach-Object {
    if ($_ -match '^([A-F0-9]{64})\s+(.+)$') {
        $baseHashes[$matches[2].Trim()] = $matches[1].ToUpper()
    }
}

$deployFiles = Get-DeployRootFiles
$currentHashes = @{}
foreach ($file in $deployFiles) {
    $relative = ($file.FullName.Substring($root.Length + 1) -replace '\\', '/')
    $currentHashes[$relative] = Get-FileSha256 $file.FullName
}

$changed = @()
$added = @()
$removed = @()

foreach ($path in $currentHashes.Keys | Sort-Object) {
    if (-not $baseHashes.ContainsKey($path)) {
        $added += $path
        $changed += $path
    }
    elseif ($baseHashes[$path] -ne $currentHashes[$path]) {
        $changed += $path
    }
}

foreach ($path in $baseHashes.Keys | Sort-Object) {
    if (Test-ExcludedPath $path) { continue }
    if (-not $currentHashes.ContainsKey($path)) {
        $removed += $path
    }
}

$deployDir = Join-Path $root 'deploy'
New-Item -ItemType Directory -Force -Path $deployDir | Out-Null

$zipPath = Join-Path $deployDir "hm-v$NewVersion-cpanel.zip"
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)

foreach ($path in ($changed | Sort-Object)) {
    $source = Join-Path $root ($path -replace '/', '\')
    if (-not (Test-Path $source)) { continue }
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $source, $path) | Out-Null
}

$zip.Dispose()

$newManifestPath = Join-Path $deployDir "version-$NewVersion.manifest.txt"
$manifestLines = foreach ($path in ($currentHashes.Keys | Sort-Object)) {
    "{0}  {1}" -f $currentHashes[$path], $path
}
Set-Content -Path $newManifestPath -Value $manifestLines -Encoding UTF8

$notesPath = Join-Path $deployDir "hm-v$NewVersion-cpanel-README.txt"
$notes = @"
HM cPanel incremental deployment package
======================================
Base version on server: v$BaseVersion
Package version:        v$NewVersion
Generated:              $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')

Files in ZIP (changed since v$BaseVersion): $($changed.Count)
New files:    $($added.Count)
Modified:     $($changed.Count - $added.Count)
Removed:      $($removed.Count)

Upload steps (cPanel File Manager)
----------------------------------
1. Upload hm-v$NewVersion-cpanel.zip to your Laravel app root (same folder as artisan).
2. Extract the ZIP and overwrite existing files.
3. In server .env set: HM_APP_VERSION=$NewVersion
4. Clear caches (Terminal or SSH):
   php artisan view:clear
   php artisan route:clear
   php artisan config:clear
5. Hard-refresh the browser (Ctrl+F5).
6. Confirm login page shows v$NewVersion.

Changed files:
$(($changed | Sort-Object | ForEach-Object { " - $_" }) -join "`n")

$(if ($removed.Count -gt 0) { "Removed since v$BaseVersion (optional cleanup):`n$(($removed | Sort-Object | ForEach-Object { " - $_" }) -join "`n")" } else { '' })
"@

Set-Content -Path $notesPath -Value $notes.TrimEnd() -Encoding UTF8

Write-Output "Package: $zipPath"
Write-Output "Manifest: $newManifestPath"
Write-Output "Notes: $notesPath"
Write-Output "Changed files: $($changed.Count)"
