<article @php(post_class('mb-12 pb-8 border-b border-gray-100 last:border-0 hover:translate-x-1 transition-transform'))>
  <header>
    <h2 class="entry-title text-2xl font-bold mb-2">
      <a href="{{ get_permalink() }}" class="text-primary hover:text-secondary transition-colors">
        {!! $title !!}
      </a>
    </h2>

    <div class="mb-4">
      @include('partials.entry-meta')
    </div>
  </header>

  <div class="entry-summary text-gray-600 leading-relaxed">
    @php(the_excerpt())
  </div>
</article>
