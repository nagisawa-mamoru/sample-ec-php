// Notification hook: Claude Codeが許可待ち・入力待ちになったらWindowsのトースト通知を出す。
// 標準入力で受け取るJSON（例: { "message": "Claude needs your permission to use Bash", ... }）の
// messageを通知本文に使う。
const { spawnSync } = require('child_process')

let input = ''
process.stdin.on('data', (chunk) => (input += chunk))
process.stdin.on('end', () => {
  let message = 'Claude Codeが待機しています'
  try {
    message = JSON.parse(input).message || message
  } catch {
    // JSONが読めなくても既定のメッセージで通知する
  }

  // トースト通知はWindows PowerShell 5.1（powershell.exe）のWinRT APIで表示する。
  // 文字列はコマンドに埋め込まず環境変数で渡し、クォートの問題を避ける。
  const script = `
[Windows.UI.Notifications.ToastNotificationManager, Windows.UI.Notifications, ContentType = WindowsRuntime] | Out-Null
$xml = [Windows.UI.Notifications.ToastNotificationManager]::GetTemplateContent([Windows.UI.Notifications.ToastTemplateType]::ToastText02)
$texts = $xml.GetElementsByTagName('text')
$texts.Item(0).AppendChild($xml.CreateTextNode($env:TOAST_TITLE)) | Out-Null
$texts.Item(1).AppendChild($xml.CreateTextNode($env:TOAST_MESSAGE)) | Out-Null
$toast = [Windows.UI.Notifications.ToastNotification]::new($xml)
$appId = '{1AC14E77-02E7-4E5D-B744-2EB1AE5198B7}\\WindowsPowerShell\\v1.0\\powershell.exe'
[Windows.UI.Notifications.ToastNotificationManager]::CreateToastNotifier($appId).Show($toast)
`

  spawnSync('powershell.exe', ['-NoProfile', '-NonInteractive', '-Command', script], {
    env: { ...process.env, TOAST_TITLE: 'Claude Code', TOAST_MESSAGE: message },
    timeout: 10000,
    windowsHide: true,
  })
})
