param([Parameter(Mandatory=$true)][int]$ImportProcessId)
$ErrorActionPreference = 'Stop'
Wait-Process -Id $ImportProcessId -ErrorAction SilentlyContinue
Set-Location -LiteralPath 'C:\Users\babyd\Herd\colorado-supply'
& 'C:\Users\babyd\.config\herd\bin\php84\php.exe' 'ai/modules/nsn-sourcing-workflow-complete/finish_full_benchmark.php' *> 'ai/modules/nsn-sourcing-workflow-complete/mysql-full-final-output.txt'
