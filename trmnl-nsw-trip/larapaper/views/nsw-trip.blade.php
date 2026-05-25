@props(['size' => 'full'])
<x-trmnl::view size="{{$size}}">
    <x-trmnl::layout>
        @if(empty($data['departures']))
            <x-trmnl::richtext gapSize="large" align="center">
                <x-trmnl::title>No Departures</x-trmnl::title>
                <x-trmnl::content>No trips found from {{ $data['origin'] ?? 'origin' }} to {{ $data['destination'] ?? 'destination' }}.</x-trmnl::content>
            </x-trmnl::richtext>
        @else
            {{-- Route header --}}
            <div style="margin-bottom: 8px;">
                <x-trmnl::title>{{ $data['origin'] ?? 'Origin' }} > {{ $data['destination'] ?? 'Dest' }}</x-trmnl::title>
            </div>

            {{-- Departures table --}}
            <x-trmnl::table>
                <thead>
                    <tr>
                        <th style="width: 18%;"><x-trmnl::title>Dep</x-trmnl::title></th>
                        <th style="width: 12%;"><x-trmnl::title>Mode</x-trmnl::title></th>
                        <th style="width: 18%;"><x-trmnl::title>Line</x-trmnl::title></th>
                        <th style="width: 18%;"><x-trmnl::title>Arr</x-trmnl::title></th>
                        <th style="width: 14%;"><x-trmnl::title>Dur</x-trmnl::title></th>
                        <th style="width: 20%;"><x-trmnl::title>Plat</x-trmnl::title></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['departures'] as $departure)
                        <tr>
                            <td>
                                <x-trmnl::label>{{ $departure['dep'] ?? '-' }}</x-trmnl::label>
                            </td>
                            <td style="text-align: center;">
                                @if(($departure['mode'] ?? '') == 'train')
                                    {{-- Filled train icon (high contrast for e-ink) --}}
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none" style="display: inline-block; vertical-align: middle;"><rect x="4" y="2" width="16" height="14" rx="3"/><rect x="6" y="5" width="4" height="3" rx="1" fill="white"/><rect x="14" y="5" width="4" height="3" rx="1" fill="white"/><rect x="10" y="12" width="4" height="2" rx="1" fill="white"/><path d="M2 16h20v2H2z"/><circle cx="7" cy="21" r="2"/><circle cx="17" cy="21" r="2"/></svg>
                                @elseif(($departure['mode'] ?? '') == 'bus')
                                    {{-- Filled bus icon (high contrast for e-ink) --}}
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none" style="display: inline-block; vertical-align: middle;"><rect x="3" y="3" width="18" height="14" rx="2"/><rect x="6" y="6" width="4" height="3" rx="1" fill="white"/><rect x="14" y="6" width="4" height="3" rx="1" fill="white"/><rect x="10" y="12" width="4" height="2" rx="1" fill="white"/><circle cx="7" cy="21" r="2"/><circle cx="17" cy="21" r="2"/></svg>
                                @elseif(($departure['mode'] ?? '') == 'metro')
                                    <x-trmnl::label>M</x-trmnl::label>
                                @elseif(($departure['mode'] ?? '') == 'light_rail')
                                    <x-trmnl::label>L</x-trmnl::label>
                                @elseif(($departure['mode'] ?? '') == 'ferry')
                                    <x-trmnl::label>F</x-trmnl::label>
                                @else
                                    <x-trmnl::label>?</x-trmnl::label>
                                @endif
                            </td>
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
