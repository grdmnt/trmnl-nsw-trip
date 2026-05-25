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
            {{-- Quadrant layout: distribute rows to fill vertical space --}}
            <div style="display: flex; flex-direction: column; justify-content: space-between; height: 100%; padding: 8px 0;">
                @foreach($data['departures'] as $departure)
                    <div style="display: flex; align-items: center; gap: 6px; padding: 2px 0; white-space: nowrap; overflow: hidden;">
                        {{-- Mode icon --}}
                        <span style="flex-shrink: 0; width: 18px; display: flex; align-items: center; justify-content: center;">
                            @if(($departure['mode'] ?? '') == 'train')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l2 3v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6l2-3z"/><path d="M6 11h12"/><path d="M6 16h12"/><path d="M8 21v2"/><path d="M16 21v2"/></svg>
                            @elseif(($departure['mode'] ?? '') == 'bus')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M6 18v2"/><path d="M18 18v2"/><path d="M6 9h12"/></svg>
                            @elseif(($departure['mode'] ?? '') == 'metro')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="3" width="12" height="17" rx="2"/><path d="M12 7v10"/><path d="M9 20h6"/></svg>
                            @elseif(($departure['mode'] ?? '') == 'ferry')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 14l10 6 10-6"/><path d="M22 14L12 8 2 14"/><path d="M2 8l10-6 10 6"/><path d="M12 2v12"/></svg>
                            @else
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/></svg>
                            @endif
                        </span>

                        {{-- Line --}}
                        <span style="flex-shrink: 0; min-width: 32px;">
                            <x-trmnl::label>{{ $departure['line'] ?? '?' }}</x-trmnl::label>
                        </span>

                        {{-- Departure time --}}
                        <span style="flex-shrink: 0;">
                            <x-trmnl::label>{{ $departure['dep'] ?? '-' }}</x-trmnl::label>
                        </span>

                        {{-- Arrow --}}
                        <span style="flex-shrink: 0; opacity: 0.6;">
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
            {{-- Full / half layouts with adaptive table --}}
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
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l2 3v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6l2-3z"/><path d="M6 11h12"/><path d="M6 16h12"/></svg>
                                    @elseif(($departure['mode'] ?? '') == 'bus')
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M6 18v2"/><path d="M18 18v2"/><path d="M6 9h12"/></svg>
                                    @elseif(($departure['mode'] ?? '') == 'metro')
                                        <x-trmnl::label>M</x-trmnl::label>
                                    @elseif(($departure['mode'] ?? '') == 'ferry')
                                        <x-trmnl::label>F</x-trmnl::label>
                                    @else
                                        <x-trmnl::label>?</x-trmnl::label>
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
    {{-- Custom footer without icon --}}
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-top: 1px solid currentColor; opacity: 0.8;">
        <x-trmnl::label>{{ $data['origin'] ?? 'NSW' }} → {{ $data['destination'] ?? 'Trip' }}</x-trmnl::label>
        <x-trmnl::label>U: {{ $data['updated_at'] ?? now() }}</x-trmnl::label>
    </div>
</x-trmnl::view>
