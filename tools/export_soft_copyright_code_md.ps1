param(
	[string]$OutFile = "soft_copyright_source_60pages_pure.md",
	[int]$LinesPerPage = 60,
	[int]$TotalPages = 60,
	[int]$TakeFirstPages = 30,
	[int]$TakeLastPages = 30,
	[string[]]$RootDirs = @("public", "src", "config"),
	[string[]]$Extensions = @(".php")
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Get-RelPath([string]$fullPath, [string]$basePath) {
	$base = [System.IO.Path]::GetFullPath($basePath)
	$full = [System.IO.Path]::GetFullPath($fullPath)
	if ($full.StartsWith($base, [System.StringComparison]::OrdinalIgnoreCase)) {
		$rel = $full.Substring($base.Length).TrimStart('\','/')
		return $rel -replace '\\','/'
	}
	return $fullPath -replace '\\','/'
}

function Get-Priority([string]$rel) {
	$r = $rel.ToLowerInvariant()
	if ($r -eq "public/index.php") { return 0 }
	if ($r -eq "index.php") { return 1 }
	if ($r -eq "src/bootstrap.php") { return 2 }
	if ($r -like "src/service/*") { return 3 }
	if ($r -like "src/controller/*") { return 4 }
	if ($r -like "src/model/*") { return 5 }
	if ($r -like "src/*") { return 6 }
	if ($r -like "config/*") { return 7 }
	if ($r -like "public/*") { return 8 }
	return 9
}

function Read-FileLinesSmart([string]$path) {
	$bytes = [System.IO.File]::ReadAllBytes($path)
	if (-not $bytes -or $bytes.Length -le 0) { return @() }

	$text = $null

	# BOM detection
	if ($bytes.Length -ge 2 -and $bytes[0] -eq 0xFF -and $bytes[1] -eq 0xFE) {
		# UTF-16 LE
		$text = [System.Text.Encoding]::Unicode.GetString($bytes)
	} elseif ($bytes.Length -ge 2 -and $bytes[0] -eq 0xFE -and $bytes[1] -eq 0xFF) {
		# UTF-16 BE
		$text = [System.Text.Encoding]::BigEndianUnicode.GetString($bytes)
	} elseif ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
		# UTF-8 BOM
		$utf8 = New-Object System.Text.UTF8Encoding($true, $true)
		$text = $utf8.GetString($bytes)
	} else {
		# Heuristic: detect UTF-16 without BOM by zero-byte ratio
		$sampleLen = [Math]::Min($bytes.Length, 4096)
		$zerosEven = 0
		$zerosOdd = 0
		for ($i = 0; $i -lt $sampleLen; $i++) {
			if ($bytes[$i] -eq 0x00) {
				if (($i % 2) -eq 0) { $zerosEven++ } else { $zerosOdd++ }
			}
		}
		$pairs = [Math]::Max([Math]::Floor($sampleLen / 2), 1)
		$ratioEven = $zerosEven / $pairs
		$ratioOdd = $zerosOdd / $pairs

		if ($ratioOdd -ge 0.30 -and $ratioOdd -gt $ratioEven) {
			$text = [System.Text.Encoding]::Unicode.GetString($bytes)
		} elseif ($ratioEven -ge 0.30 -and $ratioEven -gt $ratioOdd) {
			$text = [System.Text.Encoding]::BigEndianUnicode.GetString($bytes)
		} else {
			# Try UTF-8 strict first, then fall back to GB18030
			$utf8Strict = New-Object System.Text.UTF8Encoding($false, $true)
			try {
				$text = $utf8Strict.GetString($bytes)
			} catch {
				$gb = [System.Text.Encoding]::GetEncoding(54936)
				$text = $gb.GetString($bytes)
			}
		}
	}

	if ($null -eq $text) { return @() }

	# Remove NULs that may break display/copy
	$text = $text -replace "\u0000", ""

	# Normalize newlines, then split by the actual LF character.
	# NOTE: Do NOT use .Split("\n") here: in PowerShell it can bind to the char[] overload,
	# which would split on the characters '\\' and 'n' and corrupt output.
	$text = $text -replace "`r`n", "`n" -replace "`r", "`n"
	return ($text -split "`n")
}

$repoRoot = (Get-Location).ProviderPath

# Collect files
$files = @()
foreach ($dir in $RootDirs) {
	if (-not (Test-Path -LiteralPath $dir)) { continue }
	$files += Get-ChildItem -LiteralPath $dir -Recurse -File -ErrorAction SilentlyContinue |
		Where-Object { $Extensions -contains $_.Extension.ToLowerInvariant() }
}

$files = $files | Where-Object {
	$_.FullName -notmatch "\\\\vendor\\\\" -and
	$_.FullName -notmatch "\\\\uploads\\\\" -and
	$_.FullName -notmatch "\\\\assets\\\\vendor\\\\"
}

if (-not $files -or $files.Count -le 0) {
	throw "No source files found to export."
}

$items = foreach ($f in $files) {
	$rel = Get-RelPath $f.FullName $repoRoot
	[PSCustomObject]@{
		FullName = $f.FullName
		Rel = $rel
		Priority = (Get-Priority $rel)
	}
}

$items = $items | Sort-Object Priority, Rel

# Build a long list of code lines (pure code, no separators)
$codeLines = New-Object System.Collections.Generic.List[string]
foreach ($it in $items) {
	$content = Read-FileLinesSmart $it.FullName
	foreach ($line in $content) { $codeLines.Add([string]$line) }
	$codeLines.Add("")
}

if ($codeLines.Count -lt 1) {
	throw "Source content is empty."
}

# Split into pages
$pages = New-Object System.Collections.Generic.List[object]
for ($i = 0; $i -lt $codeLines.Count; $i += $LinesPerPage) {
	$slice = $codeLines.GetRange($i, [Math]::Min($LinesPerPage, $codeLines.Count - $i))
	$pages.Add($slice)
}

if ($TakeFirstPages + $TakeLastPages -ne $TotalPages) {
	throw "Invalid params: TakeFirstPages + TakeLastPages must equal TotalPages."
}

if ($pages.Count -lt $TotalPages) {
	throw "Not enough pages: with $LinesPerPage lines/page there are only $($pages.Count) pages; cannot reach $TotalPages pages. Reduce LinesPerPage or expand RootDirs/Extensions."
}

$selected = New-Object System.Collections.Generic.List[object]
for ($i = 0; $i -lt $TakeFirstPages; $i++) { $selected.Add($pages[$i]) }
for ($i = $pages.Count - $TakeLastPages; $i -lt $pages.Count; $i++) { $selected.Add($pages[$i]) }

# Flatten: EXACT TotalPages * LinesPerPage lines
$outLines = New-Object System.Collections.Generic.List[string]
foreach ($page in $selected) {
	$lines = [System.Collections.Generic.List[string]]$page
	for ($i = 0; $i -lt $LinesPerPage; $i++) {
		$val = ""
		if ($i -lt $lines.Count) { $val = [string]$lines[$i] }
		$outLines.Add($val)
	}
}

$OutFile = [System.IO.Path]::GetFullPath((Join-Path $repoRoot $OutFile))
$outDir = [System.IO.Path]::GetDirectoryName($OutFile)
if (-not (Test-Path -LiteralPath $outDir)) {
	New-Item -ItemType Directory -Path $outDir | Out-Null
}

$outLines | Out-File -LiteralPath $OutFile -Encoding UTF8

Write-Host "Generated: $OutFile"
Write-Host "Note: code-only markdown, total lines: $($outLines.Count)."
