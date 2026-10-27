@if ($paginator->hasPages())
<nav class="pagination" aria-label="Páginas de resultados">
 <span>Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}</span>
 <div class="page-links">
 @if($paginator->onFirstPage())<span aria-disabled="true">Anterior</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>@endif
 @foreach($elements as $element)
  @if(is_string($element))<span>{{ $element }}</span>@else
   @foreach($element as $page => $url)
    @if($page == $paginator->currentPage())<span class="current" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>@endif
   @endforeach
  @endif
 @endforeach
 @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>@else<span aria-disabled="true">Siguiente</span>@endif
 </div>
</nav>
@endif
