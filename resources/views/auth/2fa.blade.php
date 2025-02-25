<x-guest-layout>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">

                    <!-- Cuerpo del formulario -->
                    <div class="card-body">
                        <p class="text-white text-center mb-4">
                            {{ __('This is a secure area of the application. Please confirm your two factor authentication code before continuing.') }}
                        </p>

                        <!-- Formulario para verificar el código -->
                        <form method="POST" action="{{ route('two-factor.verify') }}">
                            @csrf
                            <!-- Campo oculto para el ID del usuario -->
                            <input type="hidden" name="user" value="{{ $userId }}">

                            <div class="mb-3">
                                <x-input-label for="code" :value="__('Two Factor Code')" />
                                <x-text-input 
                                    id="code" 
                                    class="block mt-1 w-full" 
                                    type="text" 
                                    name="code" 
                                    required 
                                    autofocus 
                                    autocomplete="one-time-code"
                                />
                                <x-input-error :messages="$errors->get('code')" class="mt-2" />
                            </div>

                            <!-- reCAPTCHA -->
                            <div class="mt-4">
                                <x-input-label for="recaptcha" :value="__('reCAPTCHA')" />
                                <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div>
                                <x-input-error :messages="$errors->get('g-recaptcha-response')" class="mt-2" />
                                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                            </div>

                            <x-primary-button class="w-100 mt-3">
                                {{ __('Verify') }}
                            </x-primary-button>
                        </form>

                        <!-- Botón para reenviar el código -->
                        <form method="POST" action="{{ route('two-factor.resend') }}" class="mt-3">
                            @csrf
                            <!-- Campo oculto para el ID del usuario -->
                            <input type="hidden" name="user" value="{{ $userId }}">

                            <x-primary-button class="w-100">
                                {{ __('Resend Code') }}
                            </x-primary-button>
                        </form>

                        @if (session('success'))
                            <div class="alert alert-success text-center">
                                {{ session('success') }}
                            </div>
                        @endif
                        
                        @if (session('error'))
                            <div class="alert alert-danger mt-4 text-center">
                                {{ session('error') }}
                            </div>
                        @endif
                        
                        <!-- Mensajes de validación -->
                        @if ($errors->any())
                            <div class="mt-4">
                                @foreach ($errors->all() as $error)
                                    <div class="alert alert-danger text-center">
                                        {{ $error }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>