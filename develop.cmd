@echo off
rem Windows shim: lets PowerShell/cmd run `.\develop <command>` by delegating to
rem the bash script of the same name via Git Bash. Both shells resolve the
rem extension-less `develop` to this .cmd (PATHEXT), so the same command line
rem works in Git Bash, PowerShell and cmd.
setlocal
set "BASH=%ProgramFiles%\Git\bin\bash.exe"
if not exist "%BASH%" set "BASH=%LocalAppData%\Programs\Git\bin\bash.exe"
if not exist "%BASH%" (
    echo Git Bash not found. Install Git for Windows or adjust develop.cmd. 1>&2
    exit /b 1
)
"%BASH%" "%~dp0develop" %*
exit /b %ERRORLEVEL%
