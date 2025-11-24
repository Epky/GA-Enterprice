@props(['content', 'position' => 'top'])

<div class="inline-block relative group">
    <button type="button" class="inline-flex items-center justify-center w-5 h-5 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-full transition-colors">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
        </svg>
    </button>
    
    <div class="absolute z-50 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-opacity duration-200 
                {{ $position === 'top' ? 'bottom-full mb-2' : '' }}
                {{ $position === 'bottom' ? 'top-full mt-2' : '' }}
                {{ $position === 'left' ? 'right-full mr-2' : '' }}
                {{ $position === 'right' ? 'left-full ml-2' : '' }}
                left-1/2 transform -translate-x-1/2 w-64">
        <div class="bg-gray-900 text-white text-sm rounded-lg py-2 px-3 shadow-lg">
            <div class="relative">
                {{ $content }}
                <div class="absolute 
                            {{ $position === 'top' ? 'top-full left-1/2 transform -translate-x-1/2 border-t-gray-900 border-t-8 border-x-transparent border-x-8 border-b-0' : '' }}
                            {{ $position === 'bottom' ? 'bottom-full left-1/2 transform -translate-x-1/2 border-b-gray-900 border-b-8 border-x-transparent border-x-8 border-t-0' : '' }}
                            {{ $position === 'left' ? 'left-full top-1/2 transform -translate-y-1/2 border-l-gray-900 border-l-8 border-y-transparent border-y-8 border-r-0' : '' }}
                            {{ $position === 'right' ? 'right-full top-1/2 transform -translate-y-1/2 border-r-gray-900 border-r-8 border-y-transparent border-y-8 border-l-0' : '' }}">
                </div>
            </div>
        </div>
    </div>
</div>
