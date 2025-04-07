@echo off
chcp 65001 >nul
cd /d "C:\Users\khs\Desktop\lex"

:: 원격 저장소 정보만 갱신 (파일 안받음)
git fetch origin

:: 변경된 파일만 추적
for /f "delims=" %%F in ('git status --porcelain') do (
    set "line=%%F"
    call :processLine
)

:: 커밋 메시지 입력받기
set /p msg=Commit message: 
git commit -m "%msg%"

:: 안전하게 강제 푸시 (내가 원할 때만 변경됨)
git push origin HEAD:main --force-with-lease
goto :eof

:processLine
setlocal enabledelayedexpansion
set "status=!line:~0,2!"
set "filepath=!line:~3!"

:: 무시할 확장자들 필터링
echo !filepath! | findstr /i "\.tmp \.log \.bak \.swp \.swo \.DS_Store \.vscode" >nul
if errorlevel 1 (
    if "!status!"==" D" (
        rem 삭제된 파일은 스킵
    ) else (
        git add "!filepath!" >nul 2>&1
    )
)
endlocal
goto :eof
