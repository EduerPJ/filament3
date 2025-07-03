<div class="max-w-4xl mx-auto p-6 space-y-8">
    <!-- Generar Texto -->
    <div class="bg-white dark:bg-stone-950 rounded-xl border dark:border-stone-800 shadow-sm p-6">
        <div class="mb-4">
            <flux:heading size="lg">{{ __('Generar Texto') }}</flux:heading>
            <flux:subheading>{{ __('Usa IA para generar contenido basado en tu prompt') }}</flux:subheading>
        </div>
        
        <form wire:submit="sendMessage" class="space-y-4">
            <flux:textarea 
                wire:model="message" 
                :label="__('Mensaje')" 
                placeholder="Escribe tu mensaje aquí..."
                rows="3"
                required
            />
            
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                    {{ __('Enviar') }}
                </flux:button>
            </div>
        </form>
        
        @if($response)
            <div class="mt-6 p-4 bg-gray-50 dark:bg-stone-900 rounded-lg border">
                <flux:heading size="sm" class="mb-2">{{ __('Respuesta:') }}</flux:heading>
                <div class="prose dark:prose-invert max-w-none">
                    <p class="whitespace-pre-wrap">{{ $response }}</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Analizar Contenido -->
    <div class="bg-white dark:bg-stone-950 rounded-xl border dark:border-stone-800 shadow-sm p-6">
        <div class="mb-4">
            <flux:heading size="lg">{{ __('Analizar Contenido') }}</flux:heading>
            <flux:subheading>{{ __('Analiza y obtén insights sobre tu contenido') }}</flux:subheading>
        </div>
        
        <form wire:submit="analyzeContent" class="space-y-4">
            <flux:textarea 
                wire:model="analyzeMessage" 
                :label="__('Contenido a analizar')" 
                placeholder="Pega aquí el contenido que quieres analizar..."
                rows="4"
                required
            />
            
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                    {{ __('Analizar') }}
                </flux:button>
            </div>
        </form>
        
        @if($analyzeResponse)
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-950/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <flux:heading size="sm" class="mb-2 text-blue-800 dark:text-blue-200">{{ __('Análisis:') }}</flux:heading>
                <div class="prose dark:prose-invert max-w-none">
                    <p class="whitespace-pre-wrap text-blue-700 dark:text-blue-300">{{ $analyzeResponse }}</p>
                </div>
            </div>
        @endif
    </div>
</div>