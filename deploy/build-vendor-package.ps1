param(
    [string]$Version = '1.0.18'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent

$zipPath = Join-Path $root "deploy\hm-v$Version-vendor-cpanel.zip"
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

$vendorPaths = @(
    'vendor\autoload.php',
    'vendor\composer',
    'vendor\barryvdh',
    'vendor\dompdf',
    'vendor\khaled.alshamaa',
    'vendor\mpdf',
    'vendor\masterminds',
    'vendor\sabberworm',
    'vendor\setasign',
    'vendor\myclabs',
    'vendor\paragonie',
    'vendor\thecodingmachine'
)

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)

foreach ($rel in $vendorPaths) {
    $abs = Join-Path $root $rel
    if (-not (Test-Path $abs)) { continue }

    if (Test-Path $abs -PathType Container) {
        Get-ChildItem $abs -Recurse -File | ForEach-Object {
            $entry = ($_.FullName.Substring($root.Length + 1) -replace '\\', '/')
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $entry) | Out-Null
        }
    }
    else {
        $entry = ($abs.Substring($root.Length + 1) -replace '\\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $abs, $entry) | Out-Null
    }
}

$zip.Dispose()

$notesPath = Join-Path $root "deploy\hm-v$Version-vendor-cpanel-README.txt"
$notes = @"
HM cPanel vendor supplement (v$Version)
======================================
Use this ZIP when the server has no Composer command (jailshell).

Upload steps
------------
1. Upload hm-v$Version-vendor-cpanel.zip to your Laravel app root (same folder as artisan).
2. Extract the ZIP and overwrite existing files under vendor/.
3. Ensure storage/app/mpdf-tmp exists and is writable (755 or 775).
4. Clear caches:
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
5. Test inquiry PDF download from the timeline page.

Alternative (if Composer is available via PHP):
  php /path/to/composer.phar install --no-dev --optimize-autoloader
"@

Set-Content -Path $notesPath -Value $notes.TrimEnd() -Encoding UTF8

$item = Get-Item $zipPath
Write-Output "Package: $zipPath"
Write-Output "Notes: $notesPath"
Write-Output ("Size MB: {0:N2}" -f ($item.Length / 1MB))
