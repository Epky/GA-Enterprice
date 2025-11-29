<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Picture') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Upload a profile picture to personalize your account.") }}
        </p>
    </header>

    <div class="mt-6 space-y-6">
        <!-- Current Avatar Display -->
        <div class="flex items-center space-x-6">
            <div class="shrink-0">
                <img 
                    id="avatar-preview" 
                    class="h-24 w-24 object-cover rounded-full ring-4 ring-gray-100" 
                    src="{{ $user->avatarOrDefault }}" 
                    alt="{{ __('Current profile picture') }}"
                >
            </div>
            <div class="flex-1">
                <p class="text-sm text-gray-600">
                    {{ __('JPG, JPEG, PNG, GIF or WEBP. Max size 2MB.') }}
                </p>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('Recommended minimum dimensions: 100x100 pixels.') }}
                </p>
            </div>
        </div>

        <!-- Upload Form -->
        <form id="avatar-upload-form" method="POST" action="{{ route('profile.avatar.upload') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <!-- File Input -->
            <div>
                <input 
                    type="file" 
                    id="avatar-input" 
                    name="avatar" 
                    accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                    class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-indigo-50 file:text-indigo-700
                        hover:file:bg-indigo-100
                        cursor-pointer"
                >
                <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-4">
                <!-- Upload Button (hidden by default, shown when file selected) -->
                <x-primary-button 
                    id="upload-button" 
                    type="submit"
                    class="hidden"
                >
                    {{ __('Upload Picture') }}
                </x-primary-button>

                <!-- Cancel Button (hidden by default, shown when file selected) -->
                <button 
                    id="cancel-button" 
                    type="button"
                    class="hidden inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                >
                    {{ __('Cancel') }}
                </button>

                @if (session('status') === 'avatar-uploaded')
                    <p
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 3000)"
                        class="text-sm text-green-600"
                    >{{ __('Profile picture uploaded successfully.') }}</p>
                @endif
            </div>
        </form>

        <!-- Remove Avatar Form (only shown if user has an avatar) -->
        @if($user->profile?->avatar_url)
        <form method="POST" action="{{ route('profile.avatar.delete') }}" class="pt-4 border-t border-gray-200">
            @csrf
            @method('DELETE')

            <div class="flex items-center gap-4">
                <button 
                    type="submit"
                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    onclick="return confirm('{{ __('Are you sure you want to remove your profile picture?') }}')"
                >
                    {{ __('Remove Picture') }}
                </button>

                @if (session('status') === 'avatar-deleted')
                    <p
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 3000)"
                        class="text-sm text-green-600"
                    >{{ __('Profile picture removed successfully.') }}</p>
                @endif
            </div>
        </form>
        @endif
    </div>
</section>
