<x-action-section>
    <x-slot name="title">
        {{ __('socialite-unify::socialite.social_authenticated_accounts') }}
    </x-slot>

    <x-slot name="description">
        {{ __('socialite-unify::socialite.bind.description') }}
    </x-slot>

    <x-slot name="content">
        <div class="space-y-6">
            <!-- Flash 訊息顯示區域 -->
            @if (session('success'))
                <div class="text-sm text-green-600 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="text-sm text-yellow-600 dark:text-yellow-400">
                    {{ session('warning') }}
                </div>
            @endif

            @if (session('error'))
                <div class="text-sm text-red-600 dark:text-red-400">
                    {{ session('error') }}
                </div>
            @endif

            <!-- 社交帳號列表 -->
            @foreach ($providers as $provider)
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-gray-900 dark:text-gray-100">
                            {{ __("socialite-unify::socialite.providers.{$provider}") }}
                        </div>

                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            @if($this->isBound($provider))
                                {{ __('socialite-unify::socialite.bind.connected') }}
                            @else
                                {{ __('socialite-unify::socialite.bind.not_connected') }}
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center">
                        @if ($this->isBound($provider))
                            <x-danger-button wire:click="confirmUnbindSocialAccount('{{ $provider }}')"
                                           wire:loading.attr="disabled">
                                {{ __('socialite-unify::socialite.bind.unbind') }}
                            </x-danger-button>
                        @else
                            <a href="{{ route('socialite.redirect', $provider) }}">
                                <x-button>
                                    {{ __('socialite-unify::socialite.bind.connect') }}
                                </x-button>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- 解除綁定確認對話框 -->
        <x-confirmation-modal wire:model.live="confirmingUnbind">
            <x-slot name="title">
                {{ __('socialite-unify::socialite.bind.confirm_unbind_title') }}
            </x-slot>

            <x-slot name="content">
                {{ __('socialite-unify::socialite.bind.confirm_unbind_message', ['provider' => __("socialite-unify::socialite.providers.{$selectedProvider}")]) }}
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$set('confirmingUnbind', false)" wire:loading.attr="disabled">
                    {{ __('socialite-unify::socialite.no') }}
                </x-secondary-button>

                <x-danger-button class="ms-3" wire:click="unbindSocialAccount" wire:loading.attr="disabled">
                    {{ __('socialite-unify::socialite.yes') }}
                </x-danger-button>
            </x-slot>
        </x-confirmation-modal>
    </x-slot>
</x-action-section>