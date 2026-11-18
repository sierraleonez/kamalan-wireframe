{{-- Bintang rating (oranye), ukurannya mengikuti huruf di sekitarnya. --}}
@props(['fill' => true])
<svg class="star{{ $fill ? '' : ' star-o' }}" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8L3.5 9.7l5.9-.9z"/></svg>
