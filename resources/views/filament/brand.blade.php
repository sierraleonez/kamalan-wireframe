{{-- Logo panel admin: tanda pita + nama. Versi terang untuk mode gelap Filament (html.dark). --}}
<style>.km-mark-light{display:none}.dark .km-mark{display:none}.dark .km-mark-light{display:block}</style>
<span style="display:flex;align-items:center;gap:.6rem;font-weight:600;font-size:1.15rem;letter-spacing:-.01em">
    <img src="{{ asset('brand/kamalan-mark.png') }}" alt="" class="km-mark" style="height:2.25rem;width:2.25rem">
    <img src="{{ asset('brand/kamalan-mark-light.png') }}" alt="" class="km-mark-light" style="height:2.25rem;width:2.25rem">
    Kamalan Admin
</span>
