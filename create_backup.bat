@echo off
echo.
echo ========================================
echo   PPMS Complete Backup Creator
echo ========================================
echo.

set BACKUP_NAME=PPMS_Complete_Backup_%date:~-4,4%-%date:~-10,2%-%date:~-7,2%_%time:~0,2%-%time:~3,2%-%time:~6,2%
set BACKUP_NAME=%BACKUP_NAME: =0%

echo Creating backup: %BACKUP_NAME%
echo.

REM Create backup directory
mkdir "%BACKUP_NAME%" 2>nul

echo [1/6] Copying main PPMS system files...
xcopy "html" "%BACKUP_NAME%\ppms\html\" /E /I /Y >nul
xcopy "php" "%BACKUP_NAME%\ppms\php\" /E /I /Y >nul
xcopy "css" "%BACKUP_NAME%\ppms\css\" /E /I /Y >nul
xcopy "js" "%BACKUP_NAME%\ppms\js\" /E /I /Y >nul
xcopy "assets" "%BACKUP_NAME%\ppms\assets\" /E /I /Y >nul

echo [2/6] Copying database backup...
copy "ppms_database_backup.sql" "%BACKUP_NAME%\" >nul

echo [3/6] Copying documentation...
copy "PPMS_BACKUP_README.md" "%BACKUP_NAME%\README.md" >nul

echo [4/6] Creating system info file...
echo # PPMS System Information > "%BACKUP_NAME%\SYSTEM_INFO.txt"
echo. >> "%BACKUP_NAME%\SYSTEM_INFO.txt"
echo Backup Date: %date% %time% >> "%BACKUP_NAME%\SYSTEM_INFO.txt"
echo System: Perwira Parcel Management System >> "%BACKUP_NAME%\SYSTEM_INFO.txt"
echo Database: MariaDB/MySQL >> "%BACKUP_NAME%\SYSTEM_INFO.txt"
echo. >> "%BACKUP_NAME%\SYSTEM_INFO.txt"
echo Default Admin: admin / admin123 >> "%BACKUP_NAME%\SYSTEM_INFO.txt"
echo Test Receiver: 010401150099 / MikailTest123! >> "%BACKUP_NAME%\SYSTEM_INFO.txt"
echo Test Staff: 0105 / StaffTest123! >> "%BACKUP_NAME%\SYSTEM_INFO.txt"
echo. >> "%BACKUP_NAME%\SYSTEM_INFO.txt"
echo Access: http://localhost/ppms/ >> "%BACKUP_NAME%\SYSTEM_INFO.txt"

echo [5/6] Creating installation instructions...
echo @echo off > "%BACKUP_NAME%\INSTALL.bat"
echo echo Installing PPMS System... >> "%BACKUP_NAME%\INSTALL.bat"
echo xcopy "ppms" "C:\xampp\htdocs\ppms\" /E /I /Y >> "%BACKUP_NAME%\INSTALL.bat"
echo echo. >> "%BACKUP_NAME%\INSTALL.bat"
echo echo Installation complete! >> "%BACKUP_NAME%\INSTALL.bat"
echo echo Next: Import ppms_database_backup.sql to phpMyAdmin >> "%BACKUP_NAME%\INSTALL.bat"
echo echo Access: http://localhost/ppms/ >> "%BACKUP_NAME%\INSTALL.bat"
echo pause >> "%BACKUP_NAME%\INSTALL.bat"

echo [6/6] Creating zip archive...
powershell -Command "& {Add-Type -AssemblyName System.IO.Compression.FileSystem; [System.IO.Compression.ZipFile]::CreateFromDirectory('%CD%\%BACKUP_NAME%', '%CD%\%BACKUP_NAME%.zip')}" 2>nul

if exist "%BACKUP_NAME%.zip" (
    echo.
    echo ========================================
    echo   BACKUP CREATED SUCCESSFULLY!
    echo ========================================
    echo.
    echo Backup File: %BACKUP_NAME%.zip
    echo Location: %CD%\%BACKUP_NAME%.zip
    echo.
    echo Contents:
    echo   - Complete PPMS system files
    echo   - Database backup (SQL)
    echo   - Installation instructions
    echo   - System documentation
    echo.
    echo Ready for deployment or archival!
    echo.
    
    REM Clean up temporary directory
    rmdir /s /q "%BACKUP_NAME%" 2>nul
    
) else (
    echo.
    echo ERROR: Failed to create zip file.
    echo Manual backup directory created: %BACKUP_NAME%
    echo.
)

echo Press any key to exit...
pause >nul
