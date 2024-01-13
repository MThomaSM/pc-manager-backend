Add-Type -AssemblyName System.Windows.Forms

[System.Windows.Forms.Application]::EnableVisualStyles()

$form = New-Object System.Windows.Forms.Form
$form.Text = 'Port Forwarder'
$form.Size = New-Object System.Drawing.Size(282,112)
$form.StartPosition = 'CenterScreen'
$form.MaximizeBox = false;
$form.FormBorderStyle = 'FixedDialog'

$label = New-Object System.Windows.Forms.Label
$label.Location = New-Object System.Drawing.Point(10,20)
$label.Size = New-Object System.Drawing.Size(280,20)
$form.Controls.Add($label)

function Update-Status {
    try {
        $task = Get-ScheduledTask -TaskName "PCManagerPortForwarding" -ErrorAction Stop
        if ($task.State -eq 'Running') {
            $label.Text = 'Status: Running'
            $label.ForeColor = [System.Drawing.Color]::Green
            $startButton.Enabled = $false
            $stopButton.Enabled = $true
            $restartButton.Enabled = $true
        } else {
            $label.Text = 'Status: Stopped'
            $label.ForeColor = [System.Drawing.Color]::Red
            $startButton.Enabled = $true
            $stopButton.Enabled = $false
            $restartButton.Enabled = $false
        }
    }
    catch {
        $label.Text = 'Task doesnt exist yet, create it with START button'
        $label.ForeColor = [System.Drawing.Color]::Black
        $startButton.Enabled = $true
        $stopButton.Enabled = $false
        $restartButton.Enabled = $false
    }
}

$startButton = New-Object System.Windows.Forms.Button
$startButton.Location = New-Object System.Drawing.Point(10,50)
$startButton.Size = New-Object System.Drawing.Size(75,23)
$startButton.Text = 'START'
$startButton.Add_Click({
    try {
        $task = Get-ScheduledTask -TaskName "PCManagerPortForwarding" -ErrorAction Stop
    } catch {
        $exePath = [System.AppDomain]::CurrentDomain.BaseDirectory
        $configPath = Join-Path $exePath "/config.toml"
        $clientPath = Join-Path $exePath "/data/client.exe"
        $Action = New-ScheduledTaskAction -Execute $clientPath -Argument "-c `"$configPath`""
        $Trigger = New-ScheduledTaskTrigger -AtStartup
        $Principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount
        $Settings = New-ScheduledTaskSettingsSet -Hidden
        $Task = New-ScheduledTask -Action $Action -Principal $Principal -Trigger $Trigger -Settings $Settings
        Register-ScheduledTask -TaskName "PCManagerPortForwarding" -InputObject $Task
    }
    Start-ScheduledTask -TaskName "PCManagerPortForwarding"
    Update-Status
})
$form.Controls.Add($startButton)

$stopButton = New-Object System.Windows.Forms.Button
$stopButton.Location = New-Object System.Drawing.Point(100,50)
$stopButton.Size = New-Object System.Drawing.Size(75,23)
$stopButton.Text = 'STOP'
$stopButton.Add_Click({ Stop-ScheduledTask -TaskName "PCManagerPortForwarding"; Update-Status })
$form.Controls.Add($stopButton)

$restartButton = New-Object System.Windows.Forms.Button
$restartButton.Location = New-Object System.Drawing.Point(190,50)
$restartButton.Size = New-Object System.Drawing.Size(75,23)
$restartButton.Text = 'RESTART'
$restartButton.Add_Click({
    Stop-ScheduledTask -TaskName "PCManagerPortForwarding"
    Start-ScheduledTask -TaskName "PCManagerPortForwarding"
    Update-Status
})
$form.Controls.Add($restartButton)

Update-Status

$timer = New-Object System.Windows.Forms.Timer
$timer.Interval = 60000 # 1 min
$timer.Add_Tick({ Update-Status })
$timer.Start()

$form.ShowDialog()

# Compile to exe:
#Invoke-ps2exe .\PortForwarder.ps1 .\PortForwarder.exe -requireAdmin -noConsole -noOutput -iconFile icon-small.ico