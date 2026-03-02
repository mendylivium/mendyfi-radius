<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Create Voucher Template</h2>
            <p class="text-sm text-base-content/60">Design a custom printable voucher layout</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2">
            <x-mary-card shadow>
                <form wire:submit.prevent="create($('#templateName').val(), cssTemplate.getValue(), htmlTemplate.getValue())">
                    <div class="space-y-4">
                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Name</span>
                            </label>
                            <input type="text" id="templateName" class="input input-bordered w-full"
                                placeholder="Enter Template Name" autocomplete="off">
                            @error('templateName')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div wire:ignore>
                            <label class="label">
                                <span class="label-text font-medium">Style (CSS)</span>
                            </label>
                            <textarea id="cssEditor" class="textarea textarea-bordered w-full" cols="30" rows="10">{!! $templateCss !!}</textarea>
                            @error('templateCss')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div wire:ignore>
                            <label class="label">
                                <span class="label-text font-medium">Template (HTML)</span>
                            </label>
                            <textarea class="textarea textarea-bordered w-full" id="htmlEditor" cols="30" rows="10">{{ $templateHtml }}</textarea>
                            @error('templateHtml')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        @error('otherError')
                            <p class="text-error text-xs">{{ $message }}</p>
                        @enderror

                        <div class="flex gap-2 mt-6">
                            <a href="{{ route('client.voucher.template') }}" class="btn btn-ghost">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create</button>
                        </div>
                    </div>
                </form>
            </x-mary-card>
        </div>

        <div>
            <x-mary-card title="Parameters" shadow>
                <ul class="space-y-2 text-sm">
                    <li>
                        <span class="text-info font-mono">"voucher.id"</span>
                        <p class="text-xs text-base-content/60 ml-2">Unique ID of Voucher</p>
                    </li>
                    <li>
                        <span class="text-info font-mono">"voucher.code"</span>
                        <p class="text-xs text-base-content/60 ml-2">The CODE / Username of Voucher</p>
                    </li>
                    <li>
                        <span class="text-info font-mono">"voucher.password"</span>
                        <p class="text-xs text-base-content/60 ml-2">The Password of Voucher</p>
                    </li>
                    <li>
                        <span class="text-info font-mono">"voucher.description"</span>
                        <p class="text-xs text-base-content/60 ml-2">Description of Hotspot Profile</p>
                    </li>
                    <li>
                        <span class="text-info font-mono">"voucher.profile"</span>
                        <p class="text-xs text-base-content/60 ml-2">The name of Hotspot Profile</p>
                    </li>
                    <li>
                        <span class="text-info font-mono">"voucher.price"</span>
                        <p class="text-xs text-base-content/60 ml-2">The Price of Hotspot Profile</p>
                    </li>
                    <li>
                        <span class="text-info font-mono">"voucher.time_limit"</span>
                        <p class="text-xs text-base-content/60 ml-2">The Time Limit of Voucher</p>
                    </li>
                    <li>
                        <span class="text-info font-mono">"voucher.data_limit"</span>
                        <p class="text-xs text-base-content/60 ml-2">The Data Limit of Voucher</p>
                    </li>
                    <li>
                        <span class="text-info font-mono">"voucher.speed_max_download"</span>
                        <p class="text-xs text-base-content/60 ml-2">The download speed of Voucher</p>
                    </li>
                    <li>
                        <span class="text-info font-mono">"voucher.speed_max_upload"</span>
                        <p class="text-xs text-base-content/60 ml-2">The upload speed of Voucher</p>
                    </li>
                    <li>
                        <span class="text-info font-mono">"voucher.validity"</span>
                        <p class="text-xs text-base-content/60 ml-2">The expiration / validity of Voucher</p>
                    </li>
                    <li>
                        <span class="text-info font-mono">"voucher.reseller_name"</span>
                        <p class="text-xs text-base-content/60 ml-2">Name of the Reseller of this Voucher</p>
                    </li>
                    <li>
                        <span class="text-info font-mono">"voucher.reseller_id"</span>
                        <p class="text-xs text-base-content/60 ml-2">ID of the Reseller of this Voucher</p>
                    </li>
                </ul>

                <div class="mt-4 p-3 bg-base-200 rounded-lg text-xs text-base-content/60">
                    <strong>Note:</strong> Use AlpineJS's <span class="text-error font-mono">x-text</span> to display the value of a parameter.
                </div>
            </x-mary-card>
        </div>
    </div>
</div>


@push('scripts-bottom')
    <script src="{{ asset('js/codemirror/codemirror.js') }}"></script>
    <script src="{{ asset('js/codemirror/closetag.js') }}"></script>
    <script src="{{ asset('js/codemirror/xml-fold.js') }}"></script>
    <script src="{{ asset('js/codemirror/xml.js') }}"></script>
    <script src="{{ asset('js/codemirror/javascript.js') }}"></script>
    <script src="{{ asset('js/codemirror/css.js') }}"></script>
    <script src="{{ asset('js/codemirror/htmlmixed.js') }}"></script>
    <script src="{{ asset('js/codemirror/closebrackets.js') }}"></script>
    <script src="{{ asset('js/codemirror/matchbrackets.js') }}"></script>
    <script src="{{ asset('js/codemirror/matchtags.js') }}"></script>
    <script src="{{ asset('js/codemirror/sublime.js') }}"></script>
    <script src="{{ asset('js/codemirror/comment.js') }}"></script>
    <script src="{{ asset('js/codemirror/clike.js') }}"></script>
    <script src="{{ asset('js/codemirror/php.js') }}"></script>
    <script src="{{ asset('js/codemirror/searchcursor.js') }}"></script>
    <script src="{{ asset('js/codemirror/search.js') }}"></script>
    <script src="{{ asset('js/codemirror/dialog.js') }}"></script>
    <script src="{{ asset('js/codemirror/annotatescrollbar.js') }}"></script>
    <script src="{{ asset('js/codemirror/matchesonscrollbar.js') }}"></script>
    <script src="{{ asset('js/codemirror/jump-to-line.js') }}"></script>
    <script>
        let cssTemplate = CodeMirror.fromTextArea(cssEditor, {
            lineNumbers: true,
            mode: 'css',
            theme: 'dracula',
            fontSize: '0.7rem'
        });

        let htmlTemplate = CodeMirror.fromTextArea(htmlEditor, {
            lineNumbers: true,
            mode: 'htmlmixed',
            theme: 'dracula',
        });
    </script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/editor.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dracula.css') }}">
    <style>
        .CodeMirror {
            font-size: 0.7rem;
        }
    </style>
@endpush
