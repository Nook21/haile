@echo off
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS haile;"
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\haile\database.sql
echo DONE
pause
