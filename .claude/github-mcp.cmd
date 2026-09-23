@echo off
for /f "delims=" %%i in ('gh auth token') do set GITHUB_PERSONAL_ACCESS_TOKEN=%%i
npx -y @modelcontextprotocol/server-github
