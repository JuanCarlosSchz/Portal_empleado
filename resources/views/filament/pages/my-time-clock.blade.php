<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $dailyData = $this->getViewData()['dailyData'];
        @endphp
        
        @forelse($dailyData as $date => $data)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                {{-- Fecha --}}
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-primary-600 dark:text-primary-400">
                        {{ $data['date']->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </h3>
                </div>
                
                {{-- Timeline de registros --}}
                <div class="space-y-3 mb-4">
                    @foreach($data['entries'] as $entry)
                        <div class="flex items-center gap-4">
                            {{-- Icono según tipo --}}
                            <div class="flex-shrink-0">
                                @if($entry->type === 'entrada')
                                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                                        <x-heroicon-o-play class="w-6 h-6 text-green-600 dark:text-green-400" />
                                    </div>
                                @elseif($entry->type === 'salida')
                                    <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
                                        <x-heroicon-o-stop class="w-6 h-6 text-red-600 dark:text-red-400" />
                                    </div>
                                @elseif($entry->type === 'pausa_inicio')
                                    <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900 flex items-center justify-center">
                                        <x-heroicon-o-pause class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                                    </div>
                                @else
                                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                        <x-heroicon-o-play class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Info del registro --}}
                            <div class="flex-1 bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $entry->datetime->format('H:i') }} (GMT +0)
                                        </span>
                                        <span class="text-gray-600 dark:text-gray-400 ml-2">
                                            @if($entry->type === 'entrada')
                                                Iniciar jornada
                                            @elseif($entry->type === 'salida')
                                                Fin de jornada
                                            @elseif($entry->type === 'pausa_inicio')
                                                Almuerzo (Pausa)
                                            @else
                                                Reanudar jornada
                                            @endif
                                        </span>
                                    </div>
                                    @if($entry->location)
                                        <x-heroicon-o-map-pin class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                {{-- Resumen de horas --}}
                <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center flex-shrink-0">
                        <x-heroicon-o-clock class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                            Total horas: <span class="text-blue-600 dark:text-blue-400">{{ $data['total_hours'] }}</span>
                            - Desajuste: <span class="text-gray-600 dark:text-gray-400">{{ $data['pause_hours'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <x-heroicon-o-clock class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    No hay registros
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Aún no has registrado ninguna jornada laboral.
                </p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
