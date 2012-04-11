@echo off

rem path to php.exe and grabber.php
set php_path="php.exe"
set grabber_path="grabber.php"

rem ####################################################
echo PHP Grabber v1.0.0, Copyright 2012 Dmitriy V. Ibragimov
rem ####################################################

set next=1

if not exist %php_path% (
    echo Please enter path to php.exe in php_path
    set next=0
)
if not exist %grabber_path% (
    echo Please enter path to grabber.php in grabber_path
    set next=0
)

rem ####################################################

if %next% == 1 (
    rem run grabber
    call %php_path% -f  %grabber_path%
    echo Success
) else (
    echo Process failed!
)
pause