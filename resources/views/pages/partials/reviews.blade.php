{{-- Ulasan pengunjung (dicek tim sebelum tampil). Daftar disaring/diurutkan di browser (reviewList). --}}
@php($s = $rv['summary'])
@php($write = '/ulasan/'.$slug.'?jenis='.$type)
<section class="blk reviews" id="ulasan" aria-labelledby="ulasan-h" x-data="reviewList(@js($rv['items']))">
  <h2 class="display" id="ulasan-h">Ulasan pengunjung</h2>
  @if (session('review_sent'))<p class="acct-ok" role="status">Terima kasih! Ulasanmu sedang dicek tim dan tampil setelah disetujui, biasanya 1–2 hari kerja.</p>@endif
  @if (session('review_deleted'))<p class="acct-ok" role="status">Ulasanmu sudah dihapus.</p>@endif
  <div class="rv-grid">
    <div class="rv-sum">
      @if ($s && $s['showAvg'])
        <div class="rv-avg"><b>{{ $s['avg'] }}</b><span><span class="rv-stars" aria-label="{{ $s['avg'] }} dari 5">@for ($i = 1; $i <= 5; $i++)<x-star :fill="$i <= $s['full']" />@endfor</span><span class="muted">dari {{ $s['count'] }} ulasan</span></span></div>
        <div class="rv-bars">
          @foreach ($s['bars'] as $b)
            <div class="rv-bar"><span>{{ $b['star'] }}</span><span class="track"><span style="width:{{ $b['pct'] }}%"></span></span><span>{{ $b['n'] }}</span></div>
          @endforeach
        </div>
      @elseif ($s)
        <p class="muted" style="margin:0">{{ $s['count'] }} ulasan. Rata-rata tampil setelah ada {{ \App\Catalog\Present::RATING_MIN }} ulasan.</p>
      @else
        <p class="muted" style="margin:0">Belum ada ulasan. Pernah memakai vendor ini? Ceritakan pengalamanmu.</p>
      @endif
      @if (\App\Support\Account::enabled())
        <a class="btn btn-line btn-block" href="{{ auth()->check() ? $write : '/masuk?next='.rawurlencode($write) }}">{{ $rv['mine'] ? 'Ubah ulasanmu' : 'Tulis ulasan' }}</a>
      @endif
      <p class="acct-fine">Dari pengunjung yang masuk dengan akun. Setiap ulasan dicek tim sebelum tampil; vendor tidak bisa menghapus ulasan.</p>
    </div>

    <div class="rv-list">
      @if ($m = $rv['mine'])
        @if ($m['status'] !== 'approved')
          <article class="rv-item rv-mine">
            <p class="rv-state">{{ $m['status'] === 'pending' ? 'Ulasanmu · sedang dicek tim, hanya kamu yang melihatnya' : 'Ulasanmu · tidak ditampilkan' }}</p>
            @if ($m['status'] === 'rejected' && $m['reason'])<p class="rv-state">Alasan: {{ $m['reason'] }}</p>@endif
            <div class="rv-head"><span class="av" aria-hidden="true">{{ $m['initial'] }}</span><span class="who"><b>{{ $m['name'] }}</b><small>{{ $m['meta'] }}</small></span><span class="rv-score"><x-star />{{ $m['rating'] }}</span></div>
            <p class="rv-body">{{ $m['body'] }}</p>
            <div class="rv-acts"><a href="{{ $write }}">Ubah</a><form method="post" action="/ulasan/{{ $slug }}/hapus">@csrf<input type="hidden" name="jenis" value="{{ $type }}"><button type="submit" class="linkish">Hapus</button></form></div>
          </article>
        @endif
      @endif
      <template x-if="items.length > 3">
        <div class="rv-tools">
          <button type="button" class="chip" :class="occ === '' && 'on'" @click="occ = ''; limit = 3">Semua</button>
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
          @if ($m && $m['status'] === 'approved')<div class="rv-acts" x-show="r.id === {{ $m['id'] }}"><a href="{{ $write }}">Ubah</a><form method="post" action="/ulasan/{{ $slug }}/hapus">@csrf<input type="hidden" name="jenis" value="{{ $type }}"><button type="submit" class="linkish">Hapus</button></form></div>@endif
        </article>
      </template>
      <button type="button" class="btn btn-line btn-block" x-show="filtered.length > limit" x-cloak @click="limit += 10" x-text="'Tampilkan ' + Math.min(10, filtered.length - limit) + ' ulasan lagi'"></button>
    </div>
  </div>
</section>
