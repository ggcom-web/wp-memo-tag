<footer class="bg-dark text-white py-12">
  <div class="container mx-auto px-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      @php(dynamic_sidebar('sidebar-footer'))
    </div>
    <div class="mt-8 pt-8 border-t border-white/10 text-center text-sm text-white/60">
      &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
    </div>
  </div>
</footer>
