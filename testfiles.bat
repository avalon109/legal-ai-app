@echo off
echo Checking for required files...
echo.

set BASE_DIR=c:\Users\Bob\Documents\GitHub\legal-ai-app\frontend-php

echo Checking config files...
if exist "%BASE_DIR%\config\schema.sql" (
    echo [FOUND] config\schema.sql
) else (
    echo [MISSING] config\schema.sql
)

echo.
echo Checking include files...
if exist "%BASE_DIR%\includes\auth_handler.php" (
    echo [FOUND] includes\auth_handler.php
) else (
    echo [MISSING] includes\auth_handler.php
)

if exist "%BASE_DIR%\includes\profile_handler.php" (
    echo [FOUND] includes\profile_handler.php
) else (
    echo [MISSING] includes\profile_handler.php
)

if exist "%BASE_DIR%\includes\password_reset_handler.php" (
    echo [FOUND] includes\password_reset_handler.php
) else (
    echo [MISSING] includes\password_reset_handler.php
)

echo.
echo Checking API files...
if exist "%BASE_DIR%\api\auth.php" (
    echo [FOUND] api\auth.php
) else (
    echo [MISSING] api\auth.php
)

if exist "%BASE_DIR%\api\profile.php" (
    echo [FOUND] api\profile.php
) else (
    echo [MISSING] api\profile.php
)

if exist "%BASE_DIR%\api\password_reset.php" (
    echo [FOUND] api\password_reset.php
) else (
    echo [MISSING] api\password_reset.php
)

echo.
echo Checking frontend files...
if exist "%BASE_DIR%\index.php" (
    echo [FOUND] index.php
) else (
    echo [MISSING] index.php
)

if exist "%BASE_DIR%\reset-password.php" (
    echo [FOUND] reset-password.php
) else (
    echo [MISSING] reset-password.php
)

if exist "%BASE_DIR%\assets\js\auth.js" (
    echo [FOUND] assets\js\auth.js
) else (
    echo [MISSING] assets\js\auth.js
)

echo.
echo Check complete. Any files marked as [MISSING] need to be created.
pause