{{--
    Layout: components.layouts.guest
    The guest layout wraps this in a centered Bootstrap card (.card-body.p-0).
    We provide our own full-height centering using daisyUI / Tailwind inside that shell.
--}}
<div class="min-h-[420px] flex items-center justify-center p-6 bg-base-200" data-theme="kt-light">
    <div class="w-full max-w-sm">

        {{-- Brand block --}}
        <div class="text-center mb-6">
            <div class="w-14 h-14 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="w-8 h-8 text-primary-content">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-base-content">KareTech Solutions</h1>
            <p class="text-sm text-base-content/60 mt-1 font-medium">Hotspot Manager</p>
            <p class="text-xs text-base-content/50 mt-0.5">Sign in to your account</p>
        </div>

        {{-- Error display --}}
        @if($errors->any())
        <div role="alert" class="alert alert-error shadow-sm mb-4 py-2.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm">{{ $errors->first() }}</span>
        </div>
        @endif

        {{-- Form card --}}
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body gap-4 p-6">
                <div class="form-control">
                    <label class="label pb-1"><span class="label-text font-medium">Username</span></label>
                    <label class="input input-bordered flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                             class="w-4 h-4 opacity-50">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM12.735 14c.618 0 1.093-.561.872-1.139a6.002 6.002 0 0 0-11.215 0c-.22.578.254 1.139.872 1.139h9.47Z" />
                        </svg>
                        <input type="text" wire:model="username" class="grow" placeholder="Enter username"
                               autocomplete="username" autofocus />
                    </label>
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text font-medium">Password</span></label>
                    <label class="input input-bordered flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                             class="w-4 h-4 opacity-50">
                            <path fill-rule="evenodd"
                                  d="M14 6a4 4 0 0 1-4.899 3.899l-1.955 1.955a.5.5 0 0 1-.353.146H5v1.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-2.293a.5.5 0 0 1 .146-.353l3.955-3.955A4 4 0 1 1 14 6Zm-4-2a.75.75 0 0 0 0 1.5.5.5 0 0 1 .5.5.75.75 0 0 0 1.5 0 2 2 0 0 0-2-2Z"
                                  clip-rule="evenodd" />
                        </svg>
                        <input type="password" wire:model="password" class="grow" placeholder="Enter password"
                               autocomplete="current-password" />
                    </label>
                </div>

                <button class="btn btn-primary w-full mt-2" wire:click="login" wire:loading.attr="disabled">
                    <span wire:loading.remove>Sign In</span>
                    <span wire:loading class="loading loading-spinner loading-sm"></span>
                </button>
            </div>
        </div>

        <p class="text-center text-xs text-base-content/40 mt-5">
            Powered by <span class="font-medium">KareTech Solutions</span>
        </p>
    </div>
</div>
