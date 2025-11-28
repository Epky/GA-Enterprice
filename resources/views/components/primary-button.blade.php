<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:from-pink-600 hover:via-purple-600 hover:to-indigo-600 hover:shadow-xl hover:scale-105 focus:outline-none focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 active:from-pink-700 active:via-purple-700 active:to-indigo-700 transition-all duration-200']) }}>
    {{ $slot }}
</button>
