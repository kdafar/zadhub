@props(['data'])

<div class="bg-gray-800 rounded-lg p-4 sticky top-0">
    <div class="flex justify-between items-center mb-4">
        <span class="text-sm font-semibold text-gray-300">Preview</span>
    </div>

    <div class="bg-[#0f1519] rounded-2xl p-2 shadow-xl">
        <div class="bg-[#121b20] h-[600px] overflow-y-auto rounded-lg p-4 space-y-4">
            
            <div class="text-center mb-4">
                <h1 class="font-bold text-white text-lg">
                    {{-- Find the title of the first screen to display --}}
                    {{ $data['screens'][0]['title'] ?? 'Flow Preview' }}
                </h1>
            </div>
            
            @if(!empty($data['screens'][0]['children']))
                @foreach($data['screens'][0]['children'] as $component)
                    <div class="space-y-2">
                        @switch($component['type'])
                            @case('text_body')
                                <p class="text-gray-300 text-sm">{{ $component['text'] ?? '' }}</p>
                                @break

                            @case('dropdown')
                                <label class="text-white font-medium text-sm">{{ $component['label'] ?? '' }}</label>
                                <div class="bg-gray-700 text-white text-sm rounded-md p-2 w-full">
                                    Select an option...
                                </div>
                                @break
                                
                            @case('text_input')
                                <label class="text-white font-medium text-sm">{{ $component['label'] ?? '' }}</label>
                                <div class="bg-gray-700 h-8 rounded-md"></div>
                                @break
                        @endswitch
                    </div>
                @endforeach
            @endif

            <div class="!mt-auto pt-4">
                <div class="bg-green-600 text-white text-center font-bold py-2 rounded-full w-full">
                    {{ $data['screens'][0]['footer_label'] ?? 'Next' }}
                </div>
            </div>
        </div>
    </div>
</div>