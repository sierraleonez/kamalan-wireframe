{{-- Kartu CTA: harga + pengecualian, satu tombol oranye WhatsApp, simpan, bagikan, pratinjau pesan dengan kode referral. --}}
@props(['cta', 'saveKey', 'name', 'share'])
<div class="cta-card">
  <div class="price-k">{{ $cta['label'] }}</div>
  <div class="price">{{ $cta['price'] }}</div>
  <div class="price-n">{{ $cta['note'] }}</div>

  <a class="btn btn-orange btn-block" :href="wa" href="{{ $cta['wa'] }}" target="_blank" rel="noopener">{{ $cta['waLabel'] }}</a>
  <button type="button" class="btn btn-line btn-block save" x-data="{ k: @js($saveKey) }" @click="$store.saved.toggle(k)"
    :aria-pressed="$store.saved.has(k)" aria-pressed="false" aria-label="Simpan {{ $name }}"><span class="heart" x-text="$store.saved.has(k) ? '♥' : '♡'">♡</span> <span class="save-label" x-text="$store.saved.has(k) ? 'Tersimpan' : 'Simpan'">Simpan</span></button>
  <div style="margin-top:10px"><x-share :share="$share" /></div>

  <div class="ref">
    Pesanmu akan terisi otomatis:<br>
    <span class="msg">“Halo, saya dari Kamalan — ref <code>{{ $cta['refCode'] }}</code>. Saya lihat {{ $cta['messageName'] }} untuk <span class="ref-occ" x-text="phrase">{{ $cta['phrase'] }}</span>.”</span>
    @if ($cta['foot'])<br><br>{{ $cta['foot'] }}@endif
  </div>

  @if ($cta['socials'])
    <div class="socials">
      @foreach ($cta['socials'] as $s)<a class="soc" href="{{ $s['href'] }}" target="_blank" rel="noopener nofollow">{{ $s['label'] }}</a>@endforeach
    </div>
  @endif
</div>
