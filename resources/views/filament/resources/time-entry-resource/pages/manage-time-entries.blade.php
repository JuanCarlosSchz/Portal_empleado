<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Lista de Trabajadores -->
        <div class="lg:col-span-1">
            <x-filament::card>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Seleccionar mes
                        </label>
                        <input 
                            type="month" 
                            wire:model.live="selectedMonth"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                            Trabajadores
                        </h3>
                        <div class="space-y-2 max-h-[600px] overflow-y-auto">
                            @foreach($this->getWorkers() as $worker)
                                <button
                                    wire:click="selectWorker({{ $worker['id'] }})"
                                    class="w-full text-left p-3 rounded-lg transition-colors {{ $selectedUserId === $worker['id'] ? 'bg-primary-500 text-white' : 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                                >
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-medium">{{ $worker['name'] }}</div>
                                            <div class="text-sm {{ $selectedUserId === $worker['id'] ? 'text-primary-100' : 'text-gray-500 dark:text-gray-400' }}">
                                                {{ $worker['email'] }}
                                            </div>
                                            @if($worker['dni'])
                                                <div class="text-xs {{ $selectedUserId === $worker['id'] ? 'text-primary-200' : 'text-gray-400' }}">
                                                    DNI: {{ $worker['dni'] }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-xs {{ $selectedUserId === $worker['id'] ? 'text-primary-100' : 'text-gray-500' }}">
                                                {{ $worker['entries_count'] }}
                                            </span>
                                            <svg class="w-4 h-4 {{ $selectedUserId === $worker['id'] ? 'text-primary-100' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-filament::card>
        </div>

        <!-- Registros del Trabajador Seleccionado -->
        <div class="lg:col-span-2">
            @if($selectedUserId)
                <x-filament::card>
                    <div class="space-y-4">
                        <!-- Resumen -->
                        <div class="flex justify-between items-center pb-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Registros de Jornada
                            </h3>
                            <div class="text-right">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Total del mes</div>
                                <div class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                    {{ $this->getMonthlyTotal() }}
                                </div>
                            </div>
                        </div>

                        <!-- Lista de días -->
                        <div class="space-y-4 max-h-[700px] overflow-y-auto">
                            @forelse($this->getTimeEntries() as $day)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                                    <!-- Cabecera del día -->
                                    <div class="flex justify-between items-center mb-3">
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white">
                                                {{ \Carbon\Carbon::parse($day['date'])->locale('es')->isoFormat('dddd, D [de] MMMM') }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ $day['total_hours'] }}
                                            </div>
                                            @if($day['pausas']->count() > 0)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    Pausas: {{ $day['pause_time'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Timeline de registros -->
                                    <div class="space-y-2">
                                        @foreach($day['entries']->sortBy('datetime') as $entry)
                                            <div class="flex items-center space-x-3 pl-2">
                                                <!-- Icono según tipo -->
                                                <div class="flex-shrink-0">
                                                    @if($entry->type === 'entrada')
                                                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                                    @elseif($entry->type === 'salida')
                                                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                                    @elseif($entry->type === 'pausa_inicio')
                                                        <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                                                    @elseif($entry->type === 'pausa_fin')
                                                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                                    @endif
                                                </div>

                                                <!-- Información del registro -->
                                                <div class="flex-1 flex items-center justify-between bg-white dark:bg-gray-900 rounded px-3 py-2">
                                                    <div class="flex items-center space-x-3">
                                                        <span class="font-mono text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $entry->datetime->format('H:i') }}
                                                        </span>
                                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                                            @if($entry->type === 'entrada')
                                                                Entrada
                                                            @elseif($entry->type === 'salida')
                                                                Salida
                                                            @elseif($entry->type === 'pausa_inicio')
                                                                Inicio pausa
                                                            @elseif($entry->type === 'pausa_fin')
                                                                Fin pausa
                                                            @endif
                                                        </span>
                                                        @if($entry->location)
                                                            <span class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400">
                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                </svg>
                                                                {{ Str::limit($entry->location, 30) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if($entry->notes)
                                                        <div class="text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate" title="{{ $entry->notes }}">
                                                            {{ $entry->notes }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-lg font-medium">No hay registros para este mes</p>
                                    <p class="text-sm mt-1">Selecciona otro mes para ver los registros</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </x-filament::card>
            @else
                <x-filament::card>
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <p class="text-lg font-medium">Selecciona un trabajador</p>
                        <p class="text-sm mt-1">Elige un trabajador de la lista para ver sus registros de jornada</p>
                    </div>
                </x-filament::card>
            @endif
        </div>
    </div>
</x-filament-panels::page>
