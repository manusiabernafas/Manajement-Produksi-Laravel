@if ($paginator->hasPages())
<nav class="baris" aria-label="Halaman">
  @if (! $paginator->onFirstPage())<a class="btn putih kecil" href="{{ $paginator->previousPageUrl() }}">{!! ikon('kiri', 15) !!} Sebelumnya</a>@endif
  <span class="kecil muted">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
  @if ($paginator->hasMorePages())<a class="btn putih kecil" href="{{ $paginator->nextPageUrl() }}">Berikutnya {!! ikon('kanan', 15) !!}</a>@endif
</nav>
@endif
