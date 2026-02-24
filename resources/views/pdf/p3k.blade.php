@extends('pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th width="15%">Waktu Kejadian</th>
                @if($selectedAsset === 'all') <th width="15%">Kotak P3K</th> @endif
                <th width="15%">Tipe Aktivitas</th>
                <th width="20%">Aktor / Pelapor</th>
                <th width="{{ $selectedAsset === 'all' ? '35%' : '50%' }}">Detail Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['record_date'])->format('d/m/Y H:i') }}</td>
                    @if($selectedAsset === 'all') <td><b>{{ $row['asset_code'] }}</b></td> @endif
                    <td><b>{{ $row['action_type'] }}</b></td>
                    <td>{{ $row['actor'] }}</td>
                    <td>
                        @php $parts = explode("\n", $row['details']); $isKritis = str_contains($parts[0], 'KRITIS'); @endphp
                        <div style="font-weight: bold; " class="{{ $isKritis ? 'text-danger' : '' }}">{{ $parts[0] }}</div>
                        @if(isset($parts[1])) <div class="meta-text" style="font-style: italic;">{{ $parts[1] }}</div> @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $selectedAsset === 'all' ? 5 : 4 }}" style="text-align: center; padding: 20px;">Tidak ada data laporan.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection