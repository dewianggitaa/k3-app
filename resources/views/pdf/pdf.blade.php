@extends('pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                @if($selectedAsset === 'all') <th width="12%">Tabung</th> @endif
                <th width="15%">Data Pelapor (PIC)</th>
                <th width="23%">Hasil Inspeksi APAR</th>
                <th width="15%">Validasi K3</th>
                <th width="20%">Tindakan & Catatan</th>
                <th width="10%">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $k3Report)
                @php 
                    $picReports = $k3Report['pic_reports'];
                    $rowspan = count($picReports) > 0 ? count($picReports) : 1; 
                @endphp

                @if(count($picReports) > 0)
                    @foreach($picReports as $picIndex => $picReport)
                        <tr>
                            @if($picIndex === 0)
                                <td rowspan="{{ $rowspan }}" style="text-align: center; vertical-align: middle;">{{ $index + 1 }}</td>
                                @if($selectedAsset === 'all') 
                                    <td rowspan="{{ $rowspan }}" style="vertical-align: middle;"><b>{{ $k3Report['asset_code'] }}</b></td> 
                                @endif
                            @endif

                            <td>
                                <b>{{ $picReport['actor'] }}</b><br>
                                <div class="meta-text">{{ \Carbon\Carbon::parse($picReport['record_date'])->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <div class="{{ $picReport['status'] === 'KRITIS' ? 'text-danger' : 'text-success' }}" style="font-weight: bold; margin-bottom: 4px;">
                                    {{ $picReport['status'] }}
                                </div>
                            </td>

                            @if($picIndex === 0)
                                <td rowspan="{{ $rowspan }}" style="background-color: #f8fafc; vertical-align: middle;">
                                    <b>{{ $k3Report['actor_k3'] ?? '-' }}</b><br>
                                    <div class="meta-text">{{ \Carbon\Carbon::parse($k3Report['record_date_k3'])->format('d/m/Y H:i') }}</div>
                                </td>
                                <td rowspan="{{ $rowspan }}" style="background-color: #f8fafc; vertical-align: middle;">
                                    {{ $k3Report['tindakan'] ?? '-' }}
                                </td>
                                <td rowspan="{{ $rowspan }}" style="text-align: center; vertical-align: middle; background-color: #f8fafc;">
                                    @if($k3Report['kondisi_akhir'] == 'Safe') 
                                        <span class="text-success">AMAN AZA</span>
                                    @else 
                                        <span class="text-danger">KRITIS</span> 
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @else
                    {{-- Fallback jika ada laporan K3 tanpa PIC --}}
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        @if($selectedAsset === 'all') <td><b>{{ $k3Report['asset_code'] }}</b></td> @endif
                        <td colspan="2" style="text-align: center; color: #999; font-style: italic;">Tidak ada data laporan PIC pada periode ini</td>
                        <td style="background-color: #f8fafc;"><b>{{ $k3Report['actor_k3'] }}</b></td>
                        <td style="background-color: #f8fafc;">{{ $k3Report['tindakan'] }}</td>
                        <td style="background-color: #f8fafc; text-align: center;">SAFE</td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="{{ $selectedAsset === 'all' ? 7 : 6 }}" style="text-align: center; padding: 40px; color: #666;">Belum ada validasi K3 yang tercatat untuk periode dwibulanan ini.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection