<x-filament-panels::page>
    <div class="space-y-4">
        <div class="space-y-2">
            @foreach ($conversation as $message)
                <div class="p-2 rounded-lg {{ $message['type'] === 'user' ? 'bg-primary-500/10 text-primary-700' : 'bg-gray-100 dark:bg-gray-700' }}">
                    {{ $message['message'] }}
                </div>
            @endforeach
        </div>

        @if($currentIntent)
            <div class="space-y-2">
                <div class="flex flex-wrap gap-2">
                    @foreach($this->getResponseOptions($currentIntent) as $option)
                        <x-filament::button
                            wire:click="selectResponse('{{ $option }}')"
                        >
                            {{ $option }}
                        </x-filament::button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>