<div class="flex items-center gap-4 text-sm text-gray-500 italic">
  <time class="dt-published" datetime="{{ get_post_time('c', true) }}">
    {{ get_the_date() }}
  </time>

  <p class="flex items-center gap-1">
    <span>{{ __('By', 'sage') }}</span>
    <a href="{{ get_author_posts_url(get_the_author_meta('ID')) }}" class="p-author h-card font-semibold text-accent hover:text-secondary transition-colors">
      {{ get_the_author() }}
    </a>
  </p>
</div>
