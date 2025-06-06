@echo off
echo Changing to application directory...
cd /d "C:\Users\Bob\Documents\GitHub\legal-ai-app\frontend-php"
IF %ERRORLEVEL% NEQ 0 (
    echo Failed to change directory!
    pause
    exit /b 1
)
echo Current directory: %CD%
echo.

echo Checking authentication files...
echo ==============================
echo.

REM Config files
echo Checking config files:
echo --------------------
if exist "config\database.php" (
    echo [✓] config\database.php exists
) else (
    echo [X] config\database.php is missing
)
if exist "config\mail.php" (
    echo [✓] config\mail.php exists
) else (
    echo [X] config\mail.php is missing
)
if exist "config\schema.sql" (
    echo [✓] config\schema.sql exists
) else (
    echo [X] config\schema.sql is missing
)
echo.

REM Core handlers
echo Checking core handlers:
echo --------------------
if exist "includes\auth_handler.php" (
    echo [✓] includes\auth_handler.php exists
) else (
    echo [X] includes\auth_handler.php is missing
)
if exist "includes\session_handler.php" (
    echo [✓] includes\session_handler.php exists
) else (
    echo [X] includes\session_handler.php is missing
)
if exist "includes\password_reset_handler.php" (
    echo [✓] includes\password_reset_handler.php exists
) else (
    echo [X] includes\password_reset_handler.php is missing
)
if exist "includes\profile_handler.php" (
    echo [✓] includes\profile_handler.php exists
) else (
    echo [X] includes\profile_handler.php is missing
)
echo.

REM API endpoints
echo Checking API endpoints:
echo --------------------
if exist "api\auth.php" (
    echo [✓] api\auth.php exists
) else (
    echo [X] api\auth.php is missing
)
if exist "api\profile.php" (
    echo [✓] api\profile.php exists
) else (
    echo [X] api\profile.php is missing
)
if exist "api\password-reset.php" (
    echo [✓] api\password-reset.php exists
) else (
    echo [X] api\password-reset.php is missing
)
echo.

REM Frontend files
echo Checking frontend files:
echo --------------------
if exist "reset-password.php" (
    echo [✓] reset-password.php exists
) else (
    echo [X] reset-password.php is missing
)
if exist "assets\js\auth.js" (
    echo [✓] assets\js\auth.js exists
) else (
    echo [X] assets\js\auth.js is missing
)
echo.

REM Check file sizes to ensure they're not empty
echo Checking file contents:
echo --------------------
for %%F in (
    "config\database.php"
    "config\mail.php"
    "config\schema.sql"
    "includes\auth_handler.php"
    "includes\session_handler.php"
    "includes\password_reset_handler.php"
    "includes\profile_handler.php"
    "api\auth.php"
    "api\profile.php"
    "api\password-reset.php"
    "reset-password.php"
    "assets\js\auth.js"
) do (
    if exist "%%~F" (
        for %%A in ("%%~F") do (
            if %%~zA==0 (
                echo [!] %%~F exists but is empty
            ) else (
                echo [✓] %%~F has content ^(%%~zA bytes^)
            )
        )
    )
)
echo.

echo Check complete!
echo ==============================
pause