param(
    [Parameter(Mandatory = $true)][string]$DataFile,
    [string]$PrinterName = ''
)
$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($PrinterName)) {
    $defaultPrinter = Get-CimInstance Win32_Printer | Where-Object { $_.Default -eq $true } | Select-Object -First 1
    if ($null -eq $defaultPrinter) { throw 'Tidak ada printer default Windows. Pasang printer thermal dan jadikan default satu kali di Windows.' }
    $PrinterName = [string]$defaultPrinter.Name
}
if (-not (Test-Path -LiteralPath $DataFile -PathType Leaf)) { throw 'File data struk tidak ditemukan.' }

Add-Type -TypeDefinition @'
using System;
using System.Runtime.InteropServices;

public class McRawPrinter
{
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    public class DOC_INFO_1
    {
        [MarshalAs(UnmanagedType.LPWStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPWStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPWStr)] public string pDataType;
    }

    [DllImport("winspool.drv", EntryPoint = "OpenPrinterW", SetLastError = true, CharSet = CharSet.Unicode)]
    public static extern bool OpenPrinter(string pPrinterName, out IntPtr phPrinter, IntPtr pDefault);
    [DllImport("winspool.drv", EntryPoint = "ClosePrinter", SetLastError = true)]
    public static extern bool ClosePrinter(IntPtr hPrinter);
    [DllImport("winspool.drv", EntryPoint = "StartDocPrinterW", SetLastError = true, CharSet = CharSet.Unicode)]
    public static extern int StartDocPrinter(IntPtr hPrinter, int Level, [In] DOC_INFO_1 pDocInfo);
    [DllImport("winspool.drv", EntryPoint = "EndDocPrinter", SetLastError = true)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);
    [DllImport("winspool.drv", EntryPoint = "StartPagePrinter", SetLastError = true)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);
    [DllImport("winspool.drv", EntryPoint = "EndPagePrinter", SetLastError = true)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);
    [DllImport("winspool.drv", EntryPoint = "WritePrinter", SetLastError = true)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, int dwCount, out int dwWritten);
}
'@

$printerHandle = [IntPtr]::Zero
if (-not [McRawPrinter]::OpenPrinter($PrinterName, [ref]$printerHandle, [IntPtr]::Zero)) {
    $code = [Runtime.InteropServices.Marshal]::GetLastWin32Error()
    throw "Gagal membuka printer '$PrinterName' (Windows error $code)."
}
$docStarted = $false
$pageStarted = $false
$buffer = [IntPtr]::Zero
try {
    $docInfo = [McRawPrinter+DOC_INFO_1]::new()
    $docInfo.pDocName = 'MONEY CHANGER T3 - Receipt'
    $docInfo.pOutputFile = $null
    $docInfo.pDataType = 'RAW'
    if ([McRawPrinter]::StartDocPrinter($printerHandle, 1, $docInfo) -eq 0) {
        $code = [Runtime.InteropServices.Marshal]::GetLastWin32Error()
        throw "Gagal memulai pekerjaan cetak (Windows error $code)."
    }
    $docStarted = $true
    if (-not [McRawPrinter]::StartPagePrinter($printerHandle)) { throw 'Gagal memulai halaman printer.' }
    $pageStarted = $true
    [byte[]]$bytes = [System.IO.File]::ReadAllBytes($DataFile)
    $buffer = [Runtime.InteropServices.Marshal]::AllocHGlobal($bytes.Length)
    [Runtime.InteropServices.Marshal]::Copy($bytes, 0, $buffer, $bytes.Length)
    [int]$written = 0
    if (-not [McRawPrinter]::WritePrinter($printerHandle, $buffer, $bytes.Length, [ref]$written) -or $written -ne $bytes.Length) {
        $code = [Runtime.InteropServices.Marshal]::GetLastWin32Error()
        throw "Data struk tidak terkirim seluruhnya (Windows error $code)."
    }
    [void][McRawPrinter]::EndPagePrinter($printerHandle)
    $pageStarted = $false
    [void][McRawPrinter]::EndDocPrinter($printerHandle)
    $docStarted = $false
    Write-Output 'OK'
}
finally {
    if ($buffer -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::FreeHGlobal($buffer) }
    if ($pageStarted) { [void][McRawPrinter]::EndPagePrinter($printerHandle) }
    if ($docStarted) { [void][McRawPrinter]::EndDocPrinter($printerHandle) }
    if ($printerHandle -ne [IntPtr]::Zero) { [void][McRawPrinter]::ClosePrinter($printerHandle) }
}
