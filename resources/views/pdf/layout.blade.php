<!DOCTYPE html>
<html>
<head>
    <title>Laporan K3 - {{ $tab }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px; position: relative; }
        .logo { position: absolute; top: 0; right: 0; height: 50px; }
        .title { font-size: 18px; font-weight: bold; margin: 0; padding-top: 10px; }
        .subtitle { font-size: 12px; color: #555; margin-top: 5px; }
        .asset-info { font-size: 13px; margin-top: 10px; background: #f0f8ff; padding: 5px 10px; border-left: 4px solid #007bff; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #999; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #e2e8f0; font-weight: bold; font-size: 10px; text-transform: uppercase; color: #1e293b; }
        td { font-size: 11px; line-height: 1.4; }
        .text-danger { color: #dc2626; font-weight: bold; }
        .text-success { color: #16a34a; font-weight: bold; }
        .meta-text { font-size: 9px; color: #555; margin-top: 3px; }
        .footer { position: fixed; bottom: -20px; left: 0px; right: 0px; font-size: 9px; color: #777; width: 100%; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
        .signature { width: 250px; float: right; text-align: center; margin-top: 30px; }
        .clear { clear: both; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('logo.png') }}" class="logo" alt="Logo">
        <h1 class="title">CATATAN PEMERIKSAAN {{ $tab }}</h1>
        <div class="subtitle">
            Periode: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}
        </div>
        @if($selectedAsset !== 'all')
        <div class="asset-info">
            <strong>KODE ASET / LOKASI:</strong> {{ $selectedAsset }}
        </div>
        @endif
    </div>

    @yield('content')

    <div class="signature">
        <p>Diketahui Oleh,</p>
        <br><br><br><br>
        <p style="border-bottom: 1px solid #000; margin-bottom: 5px;"><b>{{ $supervisor }}</b></p>
        <p style="margin:0;"><i>Supervisor K3</i></p>
    </div>
    <div class="clear"></div>
    <div class="footer">
        <p>Dokumen dicetak oleh {{ $printedBy }} - {{ $printedByDepartment }} pada {{ now()->format('d/m/Y H:i:s') }} WIB.</p>
    </div>
</body>
</html>