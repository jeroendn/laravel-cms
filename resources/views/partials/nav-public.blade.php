@use('App\Support\Menu')
@foreach (Menu::items() as $item)
    @include('partials.menu-item', ['item' => $item])
@endforeach
