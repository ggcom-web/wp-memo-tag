<header class="gradient-bg sticky top-0 z-50 shadow-lg px-4 py-3">
  <div class="container mx-auto flex items-center justify-between">
    <a class="brand text-2xl font-bold text-white tracking-tight" href="{{ home_url('/') }}">
      {!! $siteName !!}
    </a>

    @if (has_nav_menu('primary_navigation'))
      <nav class="nav-primary" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
        {!! wp_nav_menu([
          'theme_location' => 'primary_navigation',
          'menu_class' => 'flex gap-6 text-white font-medium hover:text-opacity-80 transition-all',
          'echo' => false
        ]) !!}
      </nav>
    @endif
  </div>
</header>
