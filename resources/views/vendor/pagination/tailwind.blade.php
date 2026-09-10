{{-- Paginación propia (reemplaza la de Laravel para no heredar sus estilos dark: automáticos) --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex items-center justify-between flex-wrap gap-3">
        <p class="text-sm text-slate-500">
            Mostrando <span class="font-medium text-slate-700">{{ $paginator->firstItem() }}</span>
            a <span class="font-medium text-slate-700">{{ $paginator->lastItem() }}</span>
            de <span class="font-medium text-slate-700">{{ $paginator->total() }}</span> resultados
        </p>

        <div class="flex items-center gap-1">
            {{-- Anterior --}}
            @if ($paginator->onFirstPage())
                <span class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-300 cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </a>
            @endif

            {{-- Números de página --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="w-9 h-9 flex items-center justify-center text-sm text-slate-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}"
                               class="w-9 h-9 flex items-center justify-center rounded-lg text-sm text-slate-600 hover:bg-slate-100 transition">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Siguiente --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
            @else
                <span class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-300 cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
