
<dl class="pt-6 pb-10 xl:border-b xl:border-gray-200 xl:pt-11 xl:dark:border-gray-700">
    <dt class="sr-only">Authors</dt>
    <dd>
        <ul class="flex flex-wrap justify-center gap-4 sm:space-x-12 xl:block xl:space-y-8 xl:space-x-0">
            <li class="flex items-center space-x-2">
                {!! $author_avatar !!}
                <dl class="text-sm leading-5 font-medium whitespace-nowrap">
                    <dt class="sr-only">{{ __('Name', 'sage') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-100 p-author h-card">{{ get_the_author() }}</dd>
                    <dt class="sr-only">{{ __('Website', 'sage') }}</dt>
                    <dd class="flex items-center text-gray-500 dark:text-gray-400 mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-globe mr-2">
                            <circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>
                        </svg>
                        <a class="text-primary-500 hover:text-primary-600 dark:hover:text-primary-400" 
                        target="_blank" 
                        rel="noopener noreferrer" 
                        href="{{ $author_url }}">{!! $author_domain !!}</a>
                    </dd>
                    
                    @if(!empty($author_phone))
                    <dt class="sr-only">{{ __('Phone', 'sage') }}</dt>
                    <dd class="flex items-center text-gray-500 dark:text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" 
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" 
                        stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone mt-1 mr-2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                        <a class="hover:text-primary-500 transition-colors"
                            target="_blank"
                            rel="noopener noreferrer"
                            href="tel:{{ $author_phone['link'] }}">
                            {{ $author_phone['display'] }}</a>

                    </dd>
                    @endif
                </dl>
            </li>
        </ul>
    </dd>
</dl>