@props(['eyebrow', 'title', 'intro' => null, 'more' => null, 'moreHref' => null, 'tag' => 'h2'])
<div class="sec-head">
  <div>
    <p class="eyebrow">{{ $eyebrow }}</p>
    <{{ $tag }} class="display">{{ $title }}</{{ $tag }}>
    @if ($intro)<p class="intro">{{ $intro }}</p>@endif
  </div>
  @if ($more)<a class="more" href="{{ $moreHref }}" wire:navigate>{{ $more }}</a>@endif
</div>
