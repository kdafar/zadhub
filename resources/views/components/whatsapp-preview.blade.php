@props(['data'])

@php
    // The form data is nested under a 'data' key when it comes from the builder
    $flowData = $data['data'] ?? $data;
    $screen = ($flowData['screens'] ?? [])[0] ?? null;
@endphp

<div class="bg-gray-800 rounded-lg p-4 sticky top-4">
    <div class="flex justify-between items-center mb-4">
        <span class="text-sm font-semibold text-gray-300">Preview</span>
    </div>

    <div class="bg-[#0f1519] rounded-2xl p-2 shadow-xl">
        <div class="bg-[#121b20] h-[600px] overflow-y-auto rounded-lg p-4 flex flex-col">
            <div class="text-center mb-4 flex-shrink-0">
                <h1 class="font-bold text-white text-lg">
                    {{ $screen['title'] ?? 'Flow Preview' }}
                </h1>
            </div>

            <div class="space-y-3 flex-grow">
                @if(!empty($screen['children']))
                    @foreach($screen['children'] as $component)
                        @php
                            $cData = $component['data'] ?? [];
                        @endphp
                        <div class="space-y-2">
                            @switch($component['type'])
                                @case('text_body')
                                    <div class="bg-gray-700/50 rounded-lg p-3">
                                        <p class="text-gray-200 text-sm">{{ $cData['text'] ?? '' }}</p>
                                    </div>
                                    @break

                                @case('image')
                                    @if(!empty($cData['src']))
                                        <img src="{{ $cData['src'] }}" alt="Preview" class="rounded-lg w-full object-cover">
                                    @endif
                                    @break

                                @case('dropdown')
                                    <div class="bg-gray-700/50 rounded-lg p-3">
                                        <p class="text-white font-medium text-sm mb-2">{{ $cData['label'] ?? '' }}</p>
                                        <div class="border-t border-gray-600/50">
                                            @foreach($cData['options'] ?? [] as $key => $value)
                                                <div class="text-blue-400 text-sm p-2 border-b border-gray-600/50">
                                                    {{ $value ?? $key }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @break

                                @case('text_input')
                                    <div class="bg-gray-900/70 border border-gray-700 rounded-lg p-3">
                                        <label class="text-gray-400 text-xs">{{ $cData['label'] ?? '' }}</label>
                                        <div class="text-gray-200 text-sm h-4"></div>
                                    </div>
                                    @break

                                @case('date_picker')
                                    <div class="bg-gray-900/70 border border-gray-700 rounded-lg p-3 flex items-center justify-between">
                                        <span class="text-gray-400 text-sm">{{ $cData['label'] ?? 'Select a date' }}</span>
                                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                                    </div>
                                    @break

                            @endswitch
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="mt-auto pt-4 flex-shrink-0">
                <div class="bg-green-600 text-white text-center font-bold py-2 rounded-full w-full">
                    {{ $screen['footer_label'] ?? 'Next' }}
                </div>
            </div>
        </div>
    </div>
</div>