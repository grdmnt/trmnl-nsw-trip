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
                <x-trmnl::title>{{ $data['origin'] ?? 'Origin' }} &rarr; {{ $data['destination'] ?? 'Dest' }}</x-trmnl::title>
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
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle;"><path d="M8 3h8l2 3v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6l2-3z"/><path d="M6 11h12"/><path d="M6 16h12"/><path d="M8 21v2"/><path d="M16 21v2"/><path d="M6 6h12"/></svg>
                                @elseif(($departure['mode'] ?? '') == 'bus')
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle;"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M6 18v2"/><path d="M18 18v2"/><path d="M6 10h12"/></svg>
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
    <x-trmnl::title-bar title="{{ $data['origin'] ?? 'NSW' }} &rarr; {{ $data['destination'] ?? 'Trip' }}" instance="updated: {{ $data['updated_at'] ?? now() }}"/>
</x-trmnl::view>
