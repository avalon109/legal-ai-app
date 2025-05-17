@echo off
echo Restructuring tenant rights application...

:: Create temporary directory
mkdir temp

:: Move all files to temp except the ones we're working with
move *.* temp\
move temp\restructure.bat .

:: Create necessary directories
mkdir includes
mkdir config
mkdir uploads
mkdir logs
mkdir assets

:: Move files from private back to main directories
move private\includes\*.* includes\
move private\config\*.* config\

:: Move assets and other files from public
move assets\*.* assets\

:: Clean up empty directories
rmdir /s /q private
rmdir /s /q public
rmdir /s /q temp

:: Move important files back to root
move temp\index.php .
move temp\test_db.php .

echo Done! Check the structure and test the application. 