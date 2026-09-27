<div x-data x-effect="document.documentElement.classList.toggle('sheet-open', $wire.sheet)" @keydown.escape.window="$wire.sheet && $wire.closeSheet()">
  <x-crumb :items="$p['crumb']" />
  <div class="list-head">
    <p class="eyebrow">{{ $p['eyebrow'] }}</p>
    <h1 class="display">{{ $p['h1'] }}</h1>
    @if ($p['intro'])
      @foreach ($p['intro'] as $para)<p class="lead">{{ $para }}</p>@endforeach
    @endif
  </div>

  {{-- filter: HP --}}
  <div class="mfbar">
    <button type="button" class="btn btn-navy mfbar-btn" wire:click="openSheet">Filter{{ $p['activeCount'] ? ' ('.$p['activeCount'].')' : '' }}</button>
    <label class="sr" for="sort-m">Urutkan</label>
    <select id="sort-m" wire:model.live="urut">
      @foreach ($p['sorts'] as $s)<option value="{{ $s['value'] === 'editor' ? '' : $s['value'] }}">{{ $s['label'] }}</option>@endforeach
    </select>
  </div>

  {{-- filter: desktop --}}
  <form class="fbar" onsubmit="return false" aria-label="Filter">
    <label class="fpill{{ $p['area'] ? ' on' : '' }}"><span>Area</span>
      <select x-data @change="Livewire.navigate($event.target.selectedOptions[0].dataset.href)">
        <option value="" data-href="{{ $p['allAreasHref'] }}">Semua</option>
        @foreach ($p['areaOptions'] as $a)<option value="{{ $a['value'] }}" data-href="{{ $a['href'] }}" @selected($p['area'] === $a['value'])>{{ $a['label'] }}</option>@endforeach
      </select></label>
    @foreach ($p['filters'] as $f)
      @if ($f['kind'] === 'band')
        <label class="fpill{{ $f['value'] ? ' on' : '' }}"><span>{{ $f['label'] }}</span>
          <select wire:model.live="{{ $props[$f['key']] }}">
            <option value="">Semua</option>
            @foreach ($f['options'] as $o)<option value="{{ $o['id'] }}">{{ $o['label'] }}</option>@endforeach
          </select></label>
      @else
        <button type="button" class="fpill flag{{ $f['value'] ? ' on' : '' }}" aria-pressed="{{ $f['value'] ? 'true' : 'false' }}" wire:click="toggle('{{ $f['key'] }}')">{{ $f['label'] }}</button>
      @endif
    @endforeach
    <a class="freset" href="{{ $p['resetHref'] }}" wire:navigate>Reset</a>
  </form>

  @if ($p['chips'])
    <div class="achips" aria-label="Filter aktif">
      @foreach ($p['chips'] as $c)
        @if ($c['key'] === 'area')<a class="achip chip-x" href="{{ $c['href'] }}" wire:navigate>{{ $c['label'] }} <i aria-hidden="true">✕</i><span class="sr">hapus</span></a>
        @else<button type="button" class="achip chip-x" wire:click="remove('{{ $c['key'] }}')">{{ $c['label'] }} <i aria-hidden="true">✕</i><span class="sr">hapus</span></button>@endif
      @endforeach
    </div>
  @endif

  <div class="rline">
    <span><b>{{ $p['total'] }}</b> {{ $p['catNoun'] }}</span>
    <label class="sortwrap">Urutkan <select wire:model.live="urut">
      @foreach ($p['sorts'] as $s)<option value="{{ $s['value'] === 'editor' ? '' : $s['value'] }}">{{ $s['label'] }}</option>@endforeach
    </select></label>
  </div>

  @if ($p['zero'])
    <div class="zero">
      <p class="eyebrow">Belum ada hasil</p>
      <h2 class="display msg-title">Belum ada {{ $p['zeroMessage']['noun'] }}@if ($p['zeroMessage']['summary']) untuk <b>{{ $p['zeroMessage']['summary'] }}</b>@endif di {{ $p['zeroMessage']['where'] }}.</h2>
      <p style="margin:0">Gabungan ini belum ada di katalog kami. Coba longgarkan satu filter:</p>
      @if ($p['relaxations'])
        <div class="relax">
          @foreach ($p['relaxations'] as $r)<a class="sugg" href="{{ $r['href'] }}" wire:navigate><span>{{ $r['pre'] }}<b>{{ $r['strong'] }}</b></span><span class="n">→ {{ $r['n'] }} hasil</span></a>@endforeach
        </div>
      @else
        <p class="muted" style="margin:14px 0 0">Tidak ada pelonggaran satu langkah yang memberi hasil.</p>
      @endif
    </div>
  @else
    <x-row :items="$p['results']" save />
    @if ($p['total'] > $p['shown'])
      <button type="button" class="btn btn-line more-btn more" wire:click="more">Muat lebih banyak · {{ $p['shown'] }} dari {{ $p['total'] }}</button>
    @endif
    @if ($p['thin'])
      <div class="zero thin">
        <p class="eyebrow">Hasilnya tipis</p>
        <h2 class="display msg-title">Coba longgarkan satu filter</h2>
        @if ($p['relaxations'])
          <div class="relax">
            @foreach ($p['relaxations'] as $r)<a class="sugg" href="{{ $r['href'] }}" wire:navigate><span>{{ $r['pre'] }}<b>{{ $r['strong'] }}</b></span><span class="n">→ {{ $r['n'] }} hasil</span></a>@endforeach
          </div>
        @endif
      </div>
    @endif
  @endif

  @if ($p['zero'] || $p['thin'])
    <div class="nemu open" style="margin-top:24px">
      <div>
        <h2 class="display">Atau biar kami yang carikan.</h2>
        <p style="margin:8px 0 0;max-width:52ch">Isian di bawah sudah terisi dari filtermu. Tambahkan tanggal dan nomor WhatsApp, kami kabari dalam 2 hari kerja.</p>
      </div>
      <x-demand-form :pre="$p['prefill']" id="zf" :asal="$p['asal']" :options="$p['formOptions']" />
    </div>
  @endif

  @if ($p['sideLinks'])
    <nav class="side" aria-label="Halaman terkait">
      @foreach ($p['sideLinks'] as $col)
        <div>
          <p class="eyebrow">{{ $col['title'] }}</p>
          <div class="chips">
            @foreach ($col['links'] as $l)<a class="chip" href="{{ $l['href'] }}" wire:navigate>{{ $l['label'] }}@if ($l['count'] !== null)<small>{{ $l['count'] }}</small>@endif</a>@endforeach
          </div>
        </div>
      @endforeach
    </nav>
  @endif

  @if (! $p['zero'] && ! $p['thin'])
    <div style="margin-top:clamp(40px,6vw,72px)"><x-nemu :href="$p['bandHref']" /></div>
  @endif

  @if ($sheet)
    <div class="sheet-wrap">
      <div class="scrim" wire:click="closeSheet"></div>
      <div class="sheet" role="dialog" aria-modal="true" aria-label="Filter">
        <div class="grab"></div>
        <div class="sheet-head"><h2>Filter</h2><button type="button" class="linkish" wire:click="sheetReset">Reset</button></div>
        <div class="sheet-body">
          <div class="grp"><div class="k">Area</div><div class="chips">
            @foreach ($p['areaOptions'] as $a)
              <button type="button" class="chip{{ $draftArea === $a['value'] ? ' on' : '' }}" wire:click="sheetArea('{{ $a['value'] }}')">{{ $a['short'] }}</button>
            @endforeach
          </div></div>
          @php($groups = [])
          @foreach ($p['filters'] as $f)
            @if ($f['kind'] === 'band')
              <div class="grp"><div class="k">{{ $f['label'] }}</div><div class="chips">
                @foreach ($f['options'] as $o)
                  <button type="button" class="chip{{ ($draft[$f['key']] ?? null) === $o['id'] ? ' on' : '' }}" wire:click="sheetBand('{{ $f['key'] }}', '{{ $o['id'] }}')">{{ $o['label'] }}</button>
                @endforeach
              </div></div>
            @else
              @php($groups[$f['group']][] = $f)
            @endif
          @endforeach
          @foreach ($groups as $g => $flags)
            <div class="grp"><div class="k">{{ $g }}</div><div class="chips">
              @foreach ($flags as $f)
                <button type="button" class="chip{{ ($draft[$f['key']] ?? null) === '1' ? ' on' : '' }}" wire:click="sheetFlag('{{ $f['key'] }}')">{{ $f['label'] }}</button>
              @endforeach
            </div></div>
          @endforeach
        </div>
        <button type="button" class="btn btn-navy btn-block sheet-apply" wire:click="applySheet">{{ $sheetCount ? 'Tampilkan '.$sheetCount.' hasil' : '0 hasil — tetap tampilkan' }}</button>
      </div>
    </div>
  @endif
</div>
