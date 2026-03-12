@extends('pdf.layout')

@section('content')

    @if($selectedAsset !== 'all')
    @php
        $invMap = $p3kInventory->keyBy('id');
    @endphp
    @endif

    <div style="margin-bottom: 10px; font-size: 12px; font-weight: bold; line-height: 1.8;">
        <table style="border: none; width: auto; margin-top: 0;">
            @if($selectedAsset !== 'all')
            <tr>
                <td style="border: none; padding: 2px 0;">KODE ASET</td>
                <td style="border: none; padding: 2px 0;">: {{ $selectedAsset }}</td>
            </tr>
            @endif

            <tr>
                <td style="border: none; padding: 2px 0; width: 140px;">LOKASI KOTAK P3K</td>
                <td style="border: none; padding: 2px 0;">: {{ $selectedAsset !== 'all' ? $roomName : 'Semua Lokasi' }}</td>
            </tr>

            <tr>
                <td style="border: none; padding: 2px 0;">BULAN</td>
                <td style="border: none; padding: 2px 0;">: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('F Y') }}</td>
            </tr>
        </table>
    </div>

    @php $rowNum = 0; @endphp

    <table>
        <thead>
            <tr>
                <th width="4%" style="text-align: center;">No</th>
                <th width="18%" style="text-align: center;">Isi</th>
                <th width="8%" style="text-align: center;">Jml. Awal</th>
                <th width="8%" style="text-align: center;">Mutasi</th>
                <th width="13%" style="text-align: center;">Tipe Aktivitas</th>
                <th width="10%" style="text-align: center;">Tanggal</th>
                <th width="25%">Keterangan</th>
                <th width="14%">Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $entry)
                @php
                    $isInspection = $entry['entry_type'] === 'inspeksi';
                    $isPemakaian = $entry['entry_type'] === 'pemakaian';
                    $isPenambahan = $entry['entry_type'] === 'penambahan';
                    $qtyColor = $isPemakaian ? '#dc2626' : ($isPenambahan ? '#2563eb' : '#b45309');
                    $itemCount = count($entry['items']);
                @endphp

                @foreach($entry['items'] as $idx => $item)
                    @php $rowNum++; @endphp
                    <tr>
                        {{-- No --}}
                        <td style="text-align: center; vertical-align: middle; font-size: 10px;">{{ $rowNum }}</td>

                        {{-- Isi --}}
                        <td style="vertical-align: middle; font-size: 10px;">{{ $item['item_name'] }}</td>

                        {{-- Jml. Awal --}}
                        <td style="text-align: center; vertical-align: middle; font-size: 10px;">
                            @if($selectedAsset !== 'all' && isset($invMap[$item['item_id']]))
                                {{ $invMap[$item['item_id']]->current_qty }}
                            @else
                                -
                            @endif
                        </td>

                        {{-- Mutasi --}}
                        <td style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold; color: {{ $qtyColor }};">
                            {{ $item['qty'] !== null ? $item['qty'] : '-' }}
                        </td>

                        {{-- Shared columns: rowspan for inspections, normal for others --}}
                        @if($isInspection)
                            @if($idx === 0)
                                <td rowspan="{{ $itemCount }}" style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">
                                    {{ $entry['action_type'] }}
                                </td>
                                <td rowspan="{{ $itemCount }}" style="text-align: center; vertical-align: middle; font-size: 10px;">
                                    {{ \Carbon\Carbon::parse($entry['record_date'])->format('d/m/Y H:i') }}
                                </td>
                                <td rowspan="{{ $itemCount }}" style="vertical-align: middle; font-size: 10px; {{ ($entry['has_issue'] ?? false) ? 'color: #dc2626; font-weight: bold;' : '' }}">
                                    {{ $entry['notes'] }}
                                </td>
                                <td rowspan="{{ $itemCount }}" style="vertical-align: middle; font-size: 10px;">
                                    {{ $entry['actor'] }}
                                </td>
                            @endif
                        @else
                            <td style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">
                                {{ $entry['action_type'] }}
                            </td>
                            <td style="text-align: center; vertical-align: middle; font-size: 10px;">
                                {{ \Carbon\Carbon::parse($entry['record_date'])->format('d/m/Y H:i') }}
                            </td>
                            <td style="vertical-align: middle; font-size: 10px;">
                                {{ $entry['notes'] }}
                            </td>
                            <td style="vertical-align: middle; font-size: 10px;">
                                {{ $entry['actor'] }}
                            </td>
                        @endif
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px; color: #999; font-style: italic;">
                        Tidak ada data laporan P3K untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

@endsection