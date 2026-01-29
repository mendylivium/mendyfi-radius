<div class="row">
    <div class="col-12">
        <x-partials.flash />
    </div>
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header card-header-standard">
                <h6 class="card-title">
                    <i class="fas fa-palette"></i>Voucher Templates
                    <span class="badge badge-primary">{{ $this->templates->total() }}</span>
                </h6>
                <div class="card-actions">
                    <a class="btn btn-action btn-action-primary" href="{{ route('client.voucher.template.create') }}">
                        <i class="fas fa-plus"></i>Create Template
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-standard table-striped">
                        <thead>
                            <tr>
                                <th>Template Name</th>
                                <th class="hide-mobile">Created On</th>
                                <th class="hide-mobile">Updated On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->templates as $template)
                                <tr>
                                    <td>
                                        <span class="info-value font-weight-bold">{{ $template->name }}</span>
                                    </td>
                                    <td class="hide-mobile">
                                        <span class="info-value-muted">
                                            {{ $template->created_at->format('M d, Y h:i A') }}
                                        </span>
                                    </td>
                                    <td class="hide-mobile">
                                        <span class="info-value-muted">
                                            {{ $template->updated_at->format('M d, Y h:i A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-action-group">
                                            <a href="{{ route('client.voucher.template.edit', $template->id) }}"
                                                class="btn btn-action btn-action-edit">
                                                <i class="fas fa-edit"></i>Edit
                                            </a>
                                            <button class="btn btn-action btn-action-delete"
                                                wire:confirm.prompt="Are you sure?\n\nType {{ $template->id }} to confirm|{{ $template->id }}"
                                                wire:click="deleteTemplate({{ $template->id }})">
                                                <i class="fas fa-trash"></i>Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="table-empty-state">
                                        <i class="fas fa-palette"></i>
                                        <p>No templates created yet</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    {{ $this->templates->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
