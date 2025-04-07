@echo off
chcp 65001 >nul
cd /d "C:\Users\khs\Desktop\lex"

:: Changed files만 돌면서
for /f "delims=" %%F in ('git status --porcelain') do (
    set "line=%%F"
    call :processLine
)

:: 커밋 메시지 입력받기
set /p msg=Commit message: 
git commit -m "%msg%"
git push origin main
goto :eof

:processLine
setlocal enabledelayedexpansion
set "status=!line:~0,2!"
set "filepath=!line:~3!"

:: 무시할 파일 필터
echo !filepath! | findstr /i "\.tmp \.log \.bak \.swp \.swo \.DS_Store \.vscode" >nul
if errorlevel 1 (
    if "!status!"==" D" (
        :: 삭제된 파일은 건드리지 않음
        rem skip deleted file
    ) else (
        git add "!filepath!" >nul 2>&1
    )
)
endlocal
goto :eof