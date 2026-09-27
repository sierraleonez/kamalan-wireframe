@extends('layouts.site')
@section('content')
<div class="shell">
  <livewire:listing :type="$p['type']" :cat="$p['cat']" :area="$p['area']" />
</div>
@endsection
