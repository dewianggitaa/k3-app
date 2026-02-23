<!DOCTYPE html>
<html>
<head>
    <title>Laporan K3 - {{ $tab }}</title>
    <style>
        body { 
            font-family: Helvetica, Arial, sans-serif; 
            font-size: 11px; 
            color: #333; 
            margin: 0;
            padding: 0;
        }
        .header { 
            margin-bottom: 20px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 15px; 
            position: relative; 
        }
        .logo { 
            position: absolute; 
            top: 0; 
            right: 0; 
            height: 50px; 
        }
        .title { 
            font-size: 18px; 
            font-weight: bold; 
            margin: 0; 
            padding-top: 10px; 
        }
        .subtitle { 
            font-size: 12px; 
            color: #555; 
            margin-top: 5px; 
        }
        .asset-info { 
            font-size: 13px; 
            margin-top: 10px; 
            background: #f0f8ff; 
            padding: 5px 10px; 
            border-left: 4px solid #007bff; 
            display: inline-block; 
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
        }
        th, td { 
            border: 1px solid #999; 
            padding: 8px; 
            text-align: left; 
            vertical-align: top; 
        }
        th { 
            background-color: #e2e8f0; 
            font-weight: bold; 
            font-size: 10px; 
            text-transform: uppercase; 
            color: #1e293b; 
        }
        td { 
            font-size: 11px; 
            line-height: 1.4;
        }
        
        /* Pewarnaan jika ada temuan khusus di PDF */
        .text-danger {
            color: #dc2626;
            font-weight: bold;
        }

        .footer { 
            margin-top: 40px; 
            font-size: 9px; 
            color: #777; 
            width: 100%; 
            text-align: center; 
            border-top: 1px solid #ddd; 
            padding-top: 10px; 
        }
        .signature { 
            width: 250px; 
            float: right; 
            text-align: center; 
            margin-top: 30px; 
        }
        .clear { clear: both; }
    </style>
</head>
<body>

    <div class="header">
        <img src="{{ public_path('logo.png') }}" class="logo" alt="Logo Kimia Farma">
        
        <h1 class="title">REKAM JEJAK LAPORAN & INSPEKSI {{ $tab }}</h1>
        <div class="subtitle">
            Periode Laporan: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}
        </div>

        @if($selectedAsset !== 'all')
        <div class="asset-info">
            <strong>KODE ASET / LOKASI:</strong> {{ $selectedAsset }}
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="15%">Waktu Kejadian</th>
                
                @if($selectedAsset === 'all')
                <th width="15%">Kode Aset</th>
                @endif
                
                <th width="15%">Tipe Aktivitas</th>
                <th width="20%">Aktor / Pelapor</th>
                
                <th width="{{ $selectedAsset === 'all' ? '35%' : '50%' }}">Detail Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
            <tr>
                <td>{{ \Carbon\Carbon::parse($row['record_date'])->format('d/m/Y H:i') }}</td>
                
                @if($selectedAsset === 'all')
                <td><b>{{ $row['asset_code'] }}</b></td>
                @endif
                
                <td><b>{{ $row['action_type'] }}</b></td>
                <td>{{ $row['actor'] }}</td>
                
                <td class="{{ (str_contains($row['details'], 'Temuan:') || str_contains($row['details'], 'RUSAK') || str_contains($row['details'], 'KRITIS')) ? 'text-danger' : '' }}">
                    {{ $row['details'] }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $selectedAsset === 'all' ? 5 : 4 }}" style="text-align: center; padding: 20px;">
                    Tidak ada data laporan pada periode atau filter ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature">
        <p>Disetujui / Dicetak Oleh,</p>
        <br><br><br><br>
        <p style="border-bottom: 1px solid #000; margin-bottom: 5px;"><b>{{ $printedBy }}</b></p>
        <p style="margin:0;"><i>HSE / Tim K3</i></p>
    </div>

    <div class="clear"></div>

    <div class="footer">
        <p>Dokumen Audit K3 dicetak dari Sistem pada {{ now()->format('d/m/Y H:i:s') }} WIB.</p>
    </div>

</body>
</html>