@extends('layouts.site')
@section('content')
<livewire:listing :type="$p['type']" :cat="$p['cat']" :area="$p['area']" />
@endsection
