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
                <th width="20%">Tindakan & Catatan K3</th>
                <th width="12%">STATUS PENGGANTIAN</th>
                <th width="10%">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $k3Report)
                @php 
                    $globalNo = 1;
                    $picReports = $k3Report['pic_reports'];
                    $rowspan = count($picReports) > 0 ? count($picReports) : 1; 
                @endphp

                @if(count($picReports) > 0)
                    @foreach($picReports as $picIndex => $picReport)
                        <tr>
                            <td style="text-align: center; vertical-align: middle;">
                                {{ $globalNo++ }}
                            </td>

                            @if($picIndex === 0)
                                @if($selectedAsset === 'all') 
                                    <td rowspan="{{ $rowspan }}" style="vertical-align: middle;"><b>{{ $k3Report['asset_code'] }}</b></td> 
                                @endif
                            @endif

                            <td>
                                <b>{{ $picReport['actor'] }}</b><br>
                                <div class="meta-text">{{ \Carbon\Carbon::parse($picReport['record_date'])->format('d/m/Y H:i') }}</div>
                            </td>
                            
                            <td style="padding: 6px;">
                                <div class="{{ $picReport['status'] === 'KRITIS' ? 'text-danger' : ($picReport['status'] === 'SAFE' ? 'text-black' : '') }}" style="font-weight: bold;">
                                    {{ $picReport['status'] }}
                                </div>
                                
                                @if($picReport['status'] === 'KRITIS')
                                    @php
                                        $rincianRusak = str_replace("Kondisi: KRITIS\nRincian: ", "", $picReport['details']);
                                    @endphp
                                    <div class="meta-text" style="color: #b91c1c; margin-top: 2px;">
                                        {{ $rincianRusak }}
                                    </div>
                                    @if(!empty($picReport['notes']))
                                        <div class="meta-text" style="margin-top: 4px; color: #4b5563; font-style: italic;">
                                            <b>Catatan:</b> {{ $picReport['notes'] }}
                                        </div>
                                    @endif
                                @elseif($picReport['status'] === 'SAFE')
                                    <div class="meta-text" style="margin-top: 2px;">
                                        Seluruh komponen standar normal.
                                    </div>
                                @else
                                    <div class="meta-text" style="color: #999; font-style: italic; margin-top: 4px;">
                                        Belum ada data.
                                    </div>
                                @endif
                            </td>

                            @if($picIndex === 0)
                                <td rowspan="{{ $rowspan }}" style="vertical-align: middle;">
                                    <b>{{ $k3Report['actor_k3'] ?? '-' }}</b><br>
                                    @if(!empty($k3Report['record_date_k3']))
                                        <div style="font-size: 10px; color: #6b7280; margin-top: 4px;">
                                            {{ \Carbon\Carbon::parse($k3Report['record_date_k3'])->format('d M Y, H:i') }}
                                        </div>
                                    @endif
                                </td>


                                <td rowspan="{{ $rowspan }}" style="vertical-align: middle;">
                                    {{ $k3Report['tindakan'] ?? '-' }}
                                </td>

                                <td rowspan="{{ $rowspan }}" style="text-align: center; vertical-align: middle;">
                                    @if($k3Report['status_penggantian'] === 'Tidak Diganti')
                                        <span style="color: #6b7280; font-size: 12px;">{{ $k3Report['status_penggantian'] }}</span>
                                    @else
                                        <div>
                                            {!! $k3Report['status_penggantian'] !!}
                                        </div>
                                    @endif
                                </td>


                                <td rowspan="{{ $rowspan }}" style="font-weight: bold; text-align: center; vertical-align: middle;">
                                    @if($k3Report['kondisi_akhir'] == 'SAFE') 
                                        <span class="text-black">SAFE</span>
                                    @else 
                                        <span class="text-danger">KRITIS</span> 
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @else
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