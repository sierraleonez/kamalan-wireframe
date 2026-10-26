{{-- Pratinjau satu foto vendor di repeater admin. --}}
@php($photo = $getRecord())
@if ($photo)
  <div style="display:flex;flex-direction:column;gap:6px">
    <img src="{{ $photo->url('card') }}" alt="" loading="lazy" style="width:100%;aspect-ratio:3/2;object-fit:cover;border-radius:8px;background:#e4e4e7">
    <span style="font-size:12px;color:#71717a">{{ $photo->width }}×{{ $photo->height }}</span>
  </div>
@endif
