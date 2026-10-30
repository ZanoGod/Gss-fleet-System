param([string]$ProjectRoot = 'C:\xampp\htdocs\Gss-fleet-System')
$ErrorActionPreference = 'Stop'
$sourceRoot = Join-Path $PSScriptRoot 'frontend\src'
$targetRoot = Join-Path $ProjectRoot 'frontend\src'
if (!(Test-Path (Join-Path $ProjectRoot 'frontend\package.json'))) {
    throw "Frontend package.json not found under $ProjectRoot. Pass the correct -ProjectRoot."
}
if (!(Test-Path (Join-Path $sourceRoot 'modules\customers\api.ts'))) {
    throw 'Patch files are missing. Extract the complete ZIP before running this installer.'
}
$backupRoot = Join-Path $ProjectRoot ('customer-frontend-backup-' + (Get-Date -Format 'yyyyMMdd-HHmmss-fff'))
New-Item -ItemType Directory -Path $backupRoot | Out-Null
Get-ChildItem $sourceRoot -Recurse -File | ForEach-Object {
    $relative = $_.FullName.Substring($sourceRoot.Length).TrimStart([char]'\', [char]'/')
    $destination = Join-Path $targetRoot $relative
    if (Test-Path $destination) {
        $backup = Join-Path $backupRoot $relative
        New-Item -ItemType Directory -Force -Path (Split-Path $backup -Parent) | Out-Null
        Copy-Item $destination $backup
    }
    New-Item -ItemType Directory -Force -Path (Split-Path $destination -Parent) | Out-Null
    Copy-Item $_.FullName $destination -Force
    Write-Host "Installed: $destination"
}
Write-Host "Backup: $backupRoot"
Write-Host 'Frontend patch installed. No backend files, dependencies, configuration, or database records changed.'
