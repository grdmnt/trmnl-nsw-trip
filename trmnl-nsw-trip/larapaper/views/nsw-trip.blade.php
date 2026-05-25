@props(['size' => 'full'])

@php
$hasTrain = false;
$hasBus = false;
$hasMultipleModes = false;

if (!empty($data['departures'])) {
    foreach ($data['departures'] as $d) {
        if (($d['mode'] ?? '') == 'train') $hasTrain = true;
        if (($d['mode'] ?? '') == 'bus') $hasBus = true;
    }
    $hasMultipleModes = $hasTrain && $hasBus;
}
@endphp

<x-trmnl::view size="{{$size}}">
    <x-trmnl::layout>
        @if(empty($data['departures']))
            <x-trmnl::richtext gapSize="large" align="center">
                <x-trmnl::title>No Departures</x-trmnl::title>
                <x-trmnl::content>No trips found.</x-trmnl::content>
            </x-trmnl::richtext>
        @elseif($size == 'quadrant')
            {{-- Compact quadrant layout --}}
            <div style="display: flex; flex-direction: column; gap: 2px;">
                @foreach($data['departures'] as $departure)
                    <div style="display: flex; align-items: center; gap: 6px; white-space: nowrap; overflow: hidden;">
                        {{-- Mode icon (only if mixed modes) --}}
                        @if($hasMultipleModes)
                            <span style="flex-shrink: 0; width: 16px;">
                                @if(($departure['mode'] ?? '') == 'train')
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="4" y="2" width="16" height="14" rx="3"/><rect x="6" y="5" width="4" height="3" rx="1" fill="white"/><rect x="14" y="5" width="4" height="3" rx="1" fill="white"/><rect x="10" y="12" width="4" height="2" rx="1" fill="white"/></svg>
                                @elseif(($departure['mode'] ?? '') == 'bus')
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="3" y="3" width="18" height="14" rx="2"/><rect x="6" y="6" width="4" height="3" rx="1" fill="white"/><rect x="14" y="6" width="4" height="3" rx="1" fill="white"/><rect x="10" y="12" width="4" height="2" rx="1" fill="white"/></svg>
                                @else
                                    <x-trmnl::label style="font-size: 10px;">{{ strtoupper(substr($departure['mode'] ?? '?', 0, 1)) }}</x-trmnl::label>
                                @endif
                            </span>
                        @endif

                        {{-- Line --}}
                        <span style="flex-shrink: 0; min-width: 32px;">
                            <x-trmnl::label>{{ $departure['line'] ?? '?' }}</x-trmnl::label>
                        </span>

                        {{-- Time --}}
                        <span style="flex-shrink: 0;">
                            <x-trmnl::label>{{ $departure['dep'] ?? '-' }}</x-trmnl::label>
                        </span>

                        {{-- Arrow --}}
                        <span style="flex-shrink: 0; opacity: 0.7;">
                            <x-trmnl::label>→</x-trmnl::label>
                        </span>

                        {{-- Arrival --}}
                        <span style="flex-shrink: 0;">
                            <x-trmnl::label>{{ $departure['arr'] ?? '?' }}</x-trmnl::label>
                        </span>

                        {{-- Duration --}}
                        <span style="flex-shrink: 0; margin-left: auto;">
                            <x-trmnl::label>({{ $departure['duration'] ?? '?' }}m)</x-trmnl::label>
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Full / half layouts --}}
            <x-trmnl::table>
                <thead>
                    <tr>
                        <th style="width: 18%;"><x-trmnl::title>Dep</x-trmnl::title></th>
                        @if($hasMultipleModes)
                            <th style="width: 10%;"><x-trmnl::title></x-trmnl::title></th>
                        @endif
                        <th style="width: 16%;"><x-trmnl::title>Line</x-trmnl::title></th>
                        <th style="width: 20%;"><x-trmnl::title>Arr</x-trmnl::title></th>
                        <th style="width: 14%;"><x-trmnl::title>Dur</x-trmnl::title></th>
                        <th style="width: 22%;"><x-trmnl::title>Plat</x-trmnl::title></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['departures'] as $departure)
                        <tr>
                            <td>
                                <x-trmnl::label>{{ $departure['dep'] ?? '-' }}</x-trmnl::label>
                            </td>
                            @if($hasMultipleModes)
                                <td style="text-align: center;">
                                    @if(($departure['mode'] ?? '') == 'train')
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="4" y="2" width="16" height="14" rx="3"/><rect x="6" y="5" width="4" height="3" rx="1" fill="white"/><rect x="14" y="5" width="4" height="3" rx="1" fill="white"/><rect x="10" y="12" width="4" height="2" rx="1" fill="white"/></svg>
                                    @elseif(($departure['mode'] ?? '') == 'bus')
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="3" y="3" width="18" height="14" rx="2"/><rect x="6" y="6" width="4" height="3" rx="1" fill="white"/><rect x="14" y="6" width="4" height="3" rx="1" fill="white"/><rect x="10" y="12" width="4" height="2" rx="1" fill="white"/></svg>
                                    @else
                                        <x-trmnl::label style="font-size: 10px;">{{ strtoupper(substr($departure['mode'] ?? '?', 0, 1)) }}</x-trmnl::label>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <x-trmnl::label>{{ $departure['line'] ?? '?' }}</x-trmnl::label>
                            </td>
                            <td>
                                <x-trmnl::label>{{ $departure['arr'] ?? '-' }}</x-trmnl::label>
                            </td>
                            <td>
                                <x-trmnl::label>{{ $departure['duration'] ?? '?' }}m</x-trmnl::label>
                            </td>
                            <td>
                                <x-trmnl::label>{{ $departure['platform'] ?? '-' }}</x-trmnl::label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-trmnl::table>
        @endif
    </x-trmnl::layout>
    <x-trmnl::title-bar title="{{ $data['origin'] ?? 'NSW' }} > {{ $data['destination'] ?? 'Trip' }}" instance="updated: {{ $data['updated_at'] ?? now() }}"/>
</x-trmnl::view>
