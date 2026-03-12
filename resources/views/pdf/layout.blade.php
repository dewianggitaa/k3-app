<!DOCTYPE html>
<html>
<head>
    <title>Laporan K3 - {{ $tab }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        .header { margin-bottom: 20px; padding-bottom: 15px; position: relative; }
        .header-top { display: table; width: 100%; margin-bottom: 15px; }
        .doc-info-box { display: table-cell; vertical-align: top; width: 60%; }
        .doc-info-inner { border: 1.5px solid #333; padding: 8px 12px; display: inline-block; font-size: 10px; line-height: 1.8; }
        .logo-box { display: table-cell; vertical-align: top; text-align: right; width: 40%; }
        .logo { height: 50px; }
        .title { font-size: 16px; font-weight: bold; margin: 0; text-align: center; padding: 10px 0; }        
        .subtitle { font-size: 12px; color: #555; margin-top: 8px; text-align: center; }
        .asset-info { font-size: 13px; margin-top: 10px; background: #f0f8ff; padding: 5px 10px; border-left: 4px solid #007bff; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #999; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #e2e8f0; font-weight: bold; font-size: 10px; text-transform: uppercase; color: #1e293b; }
        td { font-size: 11px; line-height: 1.4; }
        .text-danger { color: #dc2626; font-weight: bold; }
        .text-success { color: #16a34a; font-weight: bold; }
        .meta-text { font-size: 9px; color: #555; margin-top: 3px; }
        .footer { position: fixed; bottom: -20px; left: 0px; right: 0px; font-size: 9px; color: #777; width: 100%; text-align: center; padding-top: 10px; }
        .signature { width: 250px; float: right; text-align: center; margin-top: 30px; }
        .clear { clear: both; }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($documentVersion) && $documentVersion)
            <div class="header-top">
                <div class="doc-info-box">
                    <div class="doc-info-inner">
                        @if($documentVersion->attachment_number)
                            {{ $documentVersion->attachment_number }}<br>
                        @endif
                        Kode Dokumen &nbsp;: {{ $documentVersion->document_code }}<br>
                        Tanggal Efektif : {{ $documentVersion->effective_date ? $documentVersion->effective_date->translatedFormat('d F Y') : '-' }}
                    </div>
                </div>
                <div class="logo-box">
                    <img src="{{ public_path('logo.png') }}" class="logo" alt="Logo">
                </div>
            </div>
            <h1 class="title">{{ $documentVersion->title }}</h1>
        @else
            {{-- Fallback: format header lama --}}
            <img src="{{ public_path('logo.png') }}" class="logo" alt="Logo" style="position: absolute; top: 0; right: 0;">
            <h1 style="font-size: 18px; font-weight: bold; margin: 0; padding-top: 10px; border-bottom: 2px solid #000; padding-bottom: 15px;">CATATAN PEMERIKSAAN {{ $tab }}</h1>
        @endif

    </div>

    @yield('content')

    @if(!isset($isPreview) || !$isPreview)
    <div class="signature">
        <p>Diketahui Oleh,</p>
        <br><br><br><br>
        <p style="border-bottom: 1px solid #000; margin-bottom: 5px;"><b>{{ $supervisor }}</b></p>
        <p style="margin:0;"><i>Supervisor K3</i></p>
    </div>
    <div class="clear"></div>
    @endif

    <div class="footer">
        <p>Dokumen dicetak oleh {{ $printedBy }} - {{ $printedByDepartment }} pada {{ now()->format('d/m/Y H:i:s') }} WIB.</p>
    </div>
</body>
</html>