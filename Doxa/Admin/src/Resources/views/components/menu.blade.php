{{-- @php
    print_r($menu);    
@endphp --}}

<ul>
@foreach ($menu as $item)
    <li><i class="fa {{ $item['icon'] }}"></i> <a href="{{ route($item['route']) }}" title="{{ $item['name'] }}">{{ $item['name'] }}</a></li>
@endforeach
</ul>