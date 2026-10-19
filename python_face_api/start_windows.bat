@echo off
cd /d %~dp0
if not exist venv (
  py -3.11 -m venv venv
)
venv\Scripts\python.exe -m pip install --upgrade pip
if errorlevel 1 (
  pause
  exit /b 1
)
venv\Scripts\python.exe -m pip install -r requirements.txt
if errorlevel 1 (
  pause
  exit /b 1
)
venv\Scripts\python.exe app.py
pause
