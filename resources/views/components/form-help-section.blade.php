@props(['title' => 'Need Help?', 'items' => []])

<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium text-blue-900">{{ $title }}</h3>
            @if(!empty($items))
                <div class="mt-2 text-sm text-blue-800">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($items as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="mt-2 text-sm text-blue-800">
                    {{ $slot }}
                </div>
            @endif
            <div class="mt-3">
                <a href="{{ route('staff.help.quick-reference') }}" class="text-sm font-medium text-blue-700 hover:text-blue-600">
                    View Quick Reference Guide →
                </a>
            </div>
        </div>
    </div>
</div>
