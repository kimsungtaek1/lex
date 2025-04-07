@echo off
chcp 65001 >nul
cd /d "C:\Users\khs\Desktop\lex"

:: 원격 저장소 정보만 갱신 (pull 아님)
git fetch origin

:: 내 변경된 파일들만 add
for /f "delims=" %%F in ('git status --porcelain') do (
    set "line=%%F"
    call :processLine
)

:: 커밋 메시지 입력받기
set /p msg=Commit message: 
git commit -m "%msg%"

:: 변경 내용만 푸시 (충돌 없다면 성공)
git push origin HEAD:main
goto :eof

:processLine
setlocal enabledelayedexpansion
set "status=!line:~0,2!"
set "filepath=!line:~3!"

:: 무시할 파일 필터
echo !filepath! | findstr /i "\.tmp \.log \.bak \.swp \.swo \.DS_Store \.vscode" >nul
if errorlevel 1 (
    if "!status!"==" D" (
        rem 삭제된 파일은 무시
    ) else (
        git add "!filepath!" >nul 2>&1
    )
)
endlocal
goto :eof
