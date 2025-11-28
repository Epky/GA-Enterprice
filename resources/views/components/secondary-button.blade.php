<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-gradient-to-r from-pink-100 to-purple-100 border border-transparent rounded-lg font-semibold text-xs text-purple-700 uppercase tracking-widest shadow-sm hover:from-pink-200 hover:to-purple-200 hover:shadow-lg hover:scale-105 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 disabled:opacity-25 transition-all duration-200']) }}>
    {{ $slot }}
</button>
