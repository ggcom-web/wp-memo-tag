@php
    $id = $post->ID;
    $title = get_the_title($id);
    $url = get_permalink($id);
    // Préparation du placeholder dynamique (on supprime le html)
    $clean_title = strip_tags($title);
    $placeholder_text = urlencode($clean_title);
    $placeholder_url = "https://placehold.co/600x400/0793d7/white?text={$placeholder_text}&font=raleway";
@endphp

<article {{ $attributes->merge(['class' => post_class('bg-white dark:bg-gray-700 rounded-2xl shadow-sm hover:shadow-xl transition-shadow duration-300 overflow-hidden border border-gray-100 dark:border-gray-600', $id)]) }}>
    <div class="aspect-video overflow-hidden">
        @if(has_post_thumbnail($id))
            {!! get_the_post_thumbnail($id, 'large', [
                'class' => 'w-full h-full object-cover transform hover:scale-105 transition-transform duration-500'
            ]) !!}
        @else 
            <img src="{!! $placeholder_url !!}" 
                 width="1024" height="800" alt="Placehold" 
                 class="w-full h-full object-cover transform hover:scale-105 transition-transform duration-500">
        @endif
    </div>
  
    <div class="p-6">
        <div class="flex items-center gap-2 mb-3">
            {{-- Badge de Catégorie --}}
            @if($category)
                <span class="px-3 py-1 bg-accent-100 text-accent-700 text-xs font-semibold rounded-full">
                    {!! $category->name !!}
                </span>
            @endif

            @if($is_significantly_modified && $showBadge)
                <span class="px-3 py-1 bg-green-100 text-green-700 text-[10px] uppercase tracking-wider font-bold rounded-full border border-green-200" title="{{ __('This post has been recently updated', 'sage') }}">
                    {{ __('Updated', 'sage') }}
                </span>
            @endif
            
            <span class="ml-auto text-sm text-primary-500">{{ __('Reading time:', 'sage') }} {{ \App\reading_time() }}</span>
        </div>
        <header>
            <h2 class="text-xl font-bold mb-3">
                <a href="{{ $url }}" class="hover:text-[var(--accent-500)] transition-colors">
                    {!! $title !!}
                </a>
            </h2>
            {{-- On vérifie le type de post pour inclure les meta si nécessaire --}}
            @if(get_post_type($id) === 'post')
                @include('partials.entry-meta')
            @endif
        </header>

        <div class="mt-4 text-gray-600 dark:text-gray-300">
            {{-- Utilisation de get_the_excerpt pour éviter les conflits de boucle --}}
            {!! get_the_excerpt($id) !!}
        </div>
        <x-read-more class="text-primary-500" />
    </div>
</article>