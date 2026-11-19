{{-- Ulasan pengunjung (dicek tim sebelum tampil). Daftar disaring/diurutkan di browser (reviewList); foto dibuka di penampil (gallery). --}}
@php($s = $rv['summary'])
@php($write = '/ulasan/'.$slug.'?jenis='.$type)
@php($writeHref = auth()->check() ? $write : '/masuk?next='.rawurlencode($write))
@php($m = $rv['mine'])
<div x-data="gallery(@js($rv['viewer']))">
<section class="blk reviews" id="ulasan" aria-labelledby="ulasan-h" x-data="reviewList(@js($rv['items']))">
  <h2 class="display" id="ulasan-h">Ulasan pengunjung</h2>
  @if (session('review_sent'))<p class="acct-ok" role="status">Terima kasih! Ulasanmu sedang dicek tim dan tampil setelah disetujui, biasanya 1–2 hari kerja.</p>@endif
  @if (session('review_deleted'))<p class="acct-ok" role="status">Ulasanmu sudah dihapus.</p>@endif

  @if (! $s && ! $m)
    {{-- Belum ada ulasan sama sekali. --}}
    <div class="rv-empty">
      <span class="rv-empty-ic" aria-hidden="true"><x-star :fill="false" /></span>
      <div class="rv-empty-tx">
        <h3>Belum ada ulasan</h3>
        <p>Pernah memakai {{ $p['name'] ?? 'vendor ini' }} untuk acaramu? Ceritakan pengalamanmu supaya pengunjung lain bisa memilih dengan lebih tenang.</p>
      </div>
      @if (\App\Support\Account::enabled())<a class="btn btn-navy rv-empty-btn" href="{{ $writeHref }}">Tulis ulasan pertama</a>@endif
    </div>
  @else
  @if ($rv['strip'])
    <div class="rv-strip">
      <h3>Foto dari pengunjung <small>{{ count($rv['strip']) }} foto dari {{ $rv['withPhotos'] }} ulasan</small></h3>
      <div class="rv-strip-row">
        @foreach (array_slice($rv['strip'], 0, 6) as $i => $ph)
          <button type="button" class="rv-thumb" @click="show({{ $ph['gi'] }})" aria-label="Buka foto dari ulasan {{ $ph['name'] }}">
            <img src="{{ $ph['thumb'] }}" alt="" loading="lazy" decoding="async">
            @if ($i === 5 && count($rv['strip']) > 6)<span class="rv-more">+{{ count($rv['strip']) - 6 }}</span>@endif
          </button>
        @endforeach
      </div>
    </div>
  @endif
  <div class="rv-grid">
    <div class="rv-sum">
      @if ($s && $s['showAvg'])
        <div class="rv-avg"><b>{{ $s['avg'] }}</b><span><span class="rv-stars" aria-label="{{ $s['avg'] }} dari 5">@for ($i = 1; $i <= 5; $i++)<x-star :fill="$i <= $s['full']" />@endfor</span><span class="muted">dari {{ $s['count'] }} ulasan</span></span></div>
        <div class="rv-bars">
          @foreach ($s['bars'] as $b)
            <div class="rv-bar"><span>{{ $b['star'] }}</span><span class="track"><span style="width:{{ $b['pct'] }}%"></span></span><span>{{ $b['n'] }}</span></div>
          @endforeach
        </div>
      @else
        {{-- 1–2 ulasan (atau baru ulasanmu yang menunggu): belum ada rata-rata. --}}
        @php($n = $s['count'] ?? 0)
        <div class="rv-few">
          <b>{{ $n }} ulasan</b>
          <span>Rata-rata bintang tampil setelah ada {{ \App\Catalog\Present::RATING_MIN }} ulasan, supaya satu-dua pendapat tidak menentukan nilai.</span>
          <span class="rv-few-bar" aria-label="{{ $n }} dari {{ \App\Catalog\Present::RATING_MIN }} ulasan">@for ($i = 1; $i <= \App\Catalog\Present::RATING_MIN; $i++)<i @class(['on' => $i <= $n])></i>@endfor</span>
        </div>
      @endif
      @if (\App\Support\Account::enabled())
        <a class="btn btn-line btn-block" href="{{ $writeHref }}">{{ $m ? 'Ubah ulasanmu' : 'Tulis ulasan' }}</a>
      @endif
    </div>

    <div class="rv-list">
      @if ($m && $m['status'] !== 'approved')
        <article class="rv-item rv-mine">
          <p class="rv-state">{{ $m['status'] === 'pending' ? 'Ulasanmu · sedang dicek tim, hanya kamu yang melihatnya' : 'Ulasanmu · tidak ditampilkan' }}</p>
          @if ($m['status'] === 'rejected' && $m['reason'])<p class="rv-state">Alasan: {{ $m['reason'] }}</p>@endif
          <div class="rv-head"><span class="av" aria-hidden="true">{{ $m['initial'] }}</span><span class="who"><b>{{ $m['name'] }}</b><small>{{ $m['meta'] }}</small></span><span class="rv-score"><x-star />{{ $m['rating'] }}</span></div>
          <p class="rv-body">{{ $m['body'] }}</p>
          @if ($m['photos'])<div class="rv-photos">@foreach ($m['photos'] as $ph)<span class="rv-thumb"><img src="{{ $ph['thumb'] }}" alt="" loading="lazy"></span>@endforeach</div>@endif
          <div class="rv-acts"><a href="{{ $write }}">Ubah</a><form method="post" action="/ulasan/{{ $slug }}/hapus">@csrf<input type="hidden" name="jenis" value="{{ $type }}"><button type="submit" class="linkish">Hapus</button></form></div>
        </article>
      @endif
      <template x-if="items.length > 3">
        <div class="rv-tools">
          <button type="button" class="chip" :class="occ === '' && !withPhotos && 'on'" @click="occ = ''; withPhotos = false; limit = 3">Semua</button>
          @if ($rv['withPhotos'])<button type="button" class="chip" :class="withPhotos && 'on'" @click="withPhotos = !withPhotos; limit = 3">Dengan foto ({{ $rv['withPhotos'] }})</button>@endif
          @foreach ($rv['occasions'] as $o)
            <button type="button" class="chip" :class="occ === @js($o['slug']) && 'on'" @click="occ = @js($o['slug']); limit = 3">{{ $o['name'] }} ({{ $o['n'] }})</button>
          @endforeach
          <label class="rv-sort">Urutkan <select x-model="sort"><option value="new">Terbaru</option><option value="high">Rating tertinggi</option><option value="low">Rating terendah</option></select></label>
        </div>
      </template>
      <template x-for="r in visible" :key="r.id">
        <article class="rv-item">
          <div class="rv-head"><span class="av" aria-hidden="true" x-text="r.initial"></span><span class="who"><b x-text="r.name"></b><small x-text="r.meta"></small></span><span class="rv-score" :aria-label="r.rating + ' dari 5'"><x-star /><span x-text="r.rating"></span></span></div>
          <p class="rv-body" x-text="r.body"></p>
          <div class="rv-photos" x-show="r.photos.length">
            <template x-for="(ph, k) in r.photos" :key="ph.id">
              <button type="button" class="rv-thumb" @click="show(ph.gi)" :aria-label="'Foto ' + (k + 1) + ' dari ' + r.photos.length + ', ulasan ' + r.name"><img :src="ph.thumb" alt="" loading="lazy" decoding="async"></button>
            </template>
          </div>
          @if ($m && $m['status'] === 'approved')<div class="rv-acts" x-show="r.id === {{ $m['id'] }}"><a href="{{ $write }}">Ubah</a><form method="post" action="/ulasan/{{ $slug }}/hapus">@csrf<input type="hidden" name="jenis" value="{{ $type }}"><button type="submit" class="linkish">Hapus</button></form></div>@endif
        </article>
      </template>
      <button type="button" class="btn btn-line btn-block" x-show="filtered.length > limit" x-cloak @click="limit += 10" x-text="'Tampilkan ' + Math.min(10, filtered.length - limit) + ' ulasan lagi'"></button>
    </div>
  </div>
  @endif
</section>
@if ($rv['viewer'])
<dialog class="viewer" x-ref="dlg" aria-label="Foto dari pengunjung" @close="open = false" @keydown.left.prevent="prev()" @keydown.right.prevent="next()"
  @touchstart.passive="tx = $event.touches[0].clientX" @touchend="swipe($event.changedTouches[0].clientX)" @click.self="close()">
  <div class="viewer-bar">
    <span class="viewer-count" x-text="(i + 1) + ' / ' + items.length"></span>
    <button type="button" class="viewer-x" @click="close()" aria-label="Tutup">×</button>
  </div>
  <figure class="viewer-fig" @click.self="close()">
    <template x-if="open"><img :src="photo.src" sizes="100vw" :alt="'Foto dari ulasan ' + photo.caption" :width="photo.width" :height="photo.height"></template>
    <figcaption x-show="photo.caption" x-text="photo.caption"></figcaption>
  </figure>
  <template x-if="items.length > 1">
    <div>
      <button type="button" class="viewer-nav prev" @click="prev()" aria-label="Foto sebelumnya">‹</button>
      <button type="button" class="viewer-nav next" @click="next()" aria-label="Foto berikutnya">›</button>
    </div>
  </template>
</dialog>
@endif
</div>
