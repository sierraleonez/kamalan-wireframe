<div x-data x-effect="document.documentElement.classList.toggle('sheet-open', $wire.sheet)" @keydown.escape.window="$wire.sheet && $wire.closeSheet()">
  <x-crumb :items="$p['crumb']" />
  <h1 class="h1">{{ $p['h1'] }}</h1>
  @if ($p['intro'])
    @foreach ($p['intro'] as $para)<p class="lead">{{ $para }}</p>@endforeach
    <p class="muted trust">Terakhir dicek tim: September 2026</p>
  @else
    <p class="muted">Intro halaman tetap ada di atas; disingkat saat hasil kosong.</p>
  @endif

  {{-- bilah filter mobile --}}
  <div class="mbar">
    <button type="button" class="mbar-btn on" wire:click="openSheet">Filter{{ $p['activeCount'] ? ' ('.$p['activeCount'].')' : '' }}</button>
    <label class="mbar-sort"><span class="sr">Urutkan</span>
      <select wire:model.live="urut">
        @foreach ($p['sorts'] as $s)<option value="{{ $s['value'] === 'editor' ? '' : $s['value'] }}">{{ $s['label'] }}</option>@endforeach
      </select></label>
    <span class="mbar-count">{{ $p['total'] }} hasil</span>
  </div>

  {{-- bilah filter desktop --}}
  <form class="filterbar" onsubmit="return false">
    <label class="filter{{ $p['area'] ? ' on' : '' }}"><span>Area</span>
      <select x-data @change="Livewire.navigate($event.target.selectedOptions[0].dataset.href)">
        <option value="" data-href="{{ $p['allAreasHref'] }}">Semua Jabodetabek</option>
        @foreach ($p['areaOptions'] as $a)<option value="{{ $a['value'] }}" data-href="{{ $a['href'] }}" @selected($p['area'] === $a['value'])>{{ $a['label'] }}</option>@endforeach
      </select></label>
    @foreach ($p['filters'] as $f)
      @if ($f['kind'] === 'band')
        <label class="filter{{ $f['value'] ? ' on' : '' }}"><span>{{ $f['label'] }}</span>
          <select wire:model.live="{{ $props[$f['key']] }}">
            <option value="">Semua</option>
            @foreach ($f['options'] as $o)<option value="{{ $o['id'] }}">{{ $o['label'] }}</option>@endforeach
          </select></label>
      @else
        <label class="filter check{{ $f['value'] ? ' on' : '' }}"><input type="checkbox" wire:click="toggle('{{ $f['key'] }}')" @checked($f['value'])> {{ $f['label'] }}</label>
      @endif
    @endforeach
    <a class="filter reset" href="{{ $p['resetHref'] }}" wire:navigate>Reset</a>
  </form>

  @if ($p['chips'])
    <div class="chips-active">
      @foreach ($p['chips'] as $c)
        @if ($c['key'] === 'area')<a class="chip-x" href="{{ $c['href'] }}" wire:navigate>{{ $c['label'] }} ✕</a>
        @else<button type="button" class="chip-x" wire:click="remove('{{ $c['key'] }}')">{{ $c['label'] }} ✕</button>@endif
      @endforeach
    </div>
  @endif

  <div class="resultline">
    <span><b>{{ $p['total'] }}</b> {{ $p['catNoun'] }}</span>
    <label>Urutan: <select wire:model.live="urut">
      @foreach ($p['sorts'] as $s)<option value="{{ $s['value'] === 'editor' ? '' : $s['value'] }}">{{ $s['label'] }}</option>@endforeach
    </select></label>
  </div>

  @if ($p['zero'])
    <div class="msg">
      <h2 class="msg-title">Belum ada {{ $p['zeroMessage']['noun'] }}@if ($p['zeroMessage']['summary']) untuk <b>{{ $p['zeroMessage']['summary'] }}</b>@endif di {{ $p['zeroMessage']['where'] }}.</h2>
      <p>Gabungan ini belum ada di katalog kami. Coba longgarkan satu filter:</p>
    </div>
  @else
    <x-cards :items="$p['results']" />
    @if ($p['total'] > $p['shown'])
      <button type="button" class="btn ghost more" wire:click="more">Muat lebih banyak · {{ $p['shown'] }} dari {{ $p['total'] }}</button>
    @endif
    @if ($p['thin'])
      <div class="msg thin"><h2 class="msg-title">Hasilnya tipis.</h2><p>Coba longgarkan satu filter:</p></div>
    @endif
  @endif

  @if ($p['zero'] || $p['thin'])
    @if ($p['relaxations'])
      <div class="suggs">
        @foreach ($p['relaxations'] as $r)
          <a class="sugg" href="{{ $r['href'] }}" wire:navigate><span>{{ $r['pre'] }}<b>{{ $r['strong'] }}</b></span><span class="n">→ {{ $r['n'] }} hasil</span></a>
        @endforeach
      </div>
    @else
      <p class="muted">Tidak ada pelonggaran satu langkah yang memberi hasil.</p>
    @endif
    <section class="band open">
      <h2 class="band-title">Atau biar kami yang carikan.</h2>
      <p>Isian di bawah sudah terisi dari filtermu. Tambahkan tanggal dan nomor WhatsApp, kami kabari dalam 2 hari kerja.</p>
      <x-demand-form :pre="$p['prefill']" id="zf" :asal="\App\Catalog\Present::listingPath($p['type'], $p['cat'], $p['area'], $p['query'])" />
    </section>
  @endif

  @if ($p['sideLinks'])
    <nav class="side-links" aria-label="Halaman terkait">
      @foreach ($p['sideLinks'] as $col)
        <div><h3>{{ $col['title'] }}</h3>
          @foreach ($col['links'] as $l)<a href="{{ $l['href'] }}" wire:navigate>{{ $l['label'] }}@if ($l['count'] !== null) <span>· {{ $l['count'] }}</span>@endif</a>@endforeach
        </div>
      @endforeach
    </nav>
  @endif

  @if (! $p['zero'] && ! $p['thin'])
    <x-band :href="$p['bandHref']" />
  @endif

  @if ($sheet)
    <div class="sheet-wrap">
      <div class="scrim" wire:click="closeSheet"></div>
      <div class="sheet" role="dialog" aria-modal="true" aria-label="Filter">
        <div class="grab"></div>
        <div class="sheet-head"><b>Filter</b><button type="button" class="linkish" wire:click="sheetReset">Reset</button></div>
        <div class="sheet-body">
          <div class="grp"><b>Area</b><div class="chips">
            @foreach ($p['areaOptions'] as $a)
              <button type="button" class="chip{{ $draftArea === $a['value'] ? ' on' : '' }}" wire:click="sheetArea('{{ $a['value'] }}')">{{ $a['short'] }}</button>
            @endforeach
          </div></div>
          @php($groups = [])
          @foreach ($p['filters'] as $f)
            @if ($f['kind'] === 'band')
              <div class="grp"><b>{{ $f['label'] }}</b><div class="chips">
                @foreach ($f['options'] as $o)
                  <button type="button" class="chip{{ ($draft[$f['key']] ?? null) === $o['id'] ? ' on' : '' }}" wire:click="sheetBand('{{ $f['key'] }}', '{{ $o['id'] }}')">{{ $o['label'] }}</button>
                @endforeach
              </div></div>
            @else
              @php($groups[$f['group']][] = $f)
            @endif
          @endforeach
          @foreach ($groups as $g => $flags)
            <div class="grp"><b>{{ $g }}</b><div class="chips">
              @foreach ($flags as $f)
                <button type="button" class="chip{{ ($draft[$f['key']] ?? null) === '1' ? ' on' : '' }}" wire:click="sheetFlag('{{ $f['key'] }}')">{{ $f['label'] }}</button>
              @endforeach
            </div></div>
          @endforeach
        </div>
        <button type="button" class="btn big sheet-apply" wire:click="applySheet">{{ $sheetCount ? 'Tampilkan '.$sheetCount.' hasil' : '0 hasil — tetap tampilkan' }}</button>
      </div>
    </div>
  @endif
</div>
