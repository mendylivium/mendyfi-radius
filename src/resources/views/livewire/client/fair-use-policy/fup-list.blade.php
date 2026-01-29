<div class="row">
    <div class="col-12">
        <x-partials.flash />
    </div>
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header card-header-standard">
                <h6 class="card-title">
                    <i class="fas fa-shield-alt"></i>Fair Use Policies
                    <span class="badge badge-primary">{{ $this->policies->count() }}</span>
                </h6>
                <div class="card-actions">
                    <a class="btn btn-action btn-action-primary" href="{{ route('client.fairuse.add') }}">
                        <i class="fas fa-plus"></i>Add Policy
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-standard table-striped">
                        <thead>
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th>Policy Name</th>
                                <th>Reset Interval</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->policies as $policy)
                                <tr>
                                    <td><span class="info-value font-weight-bold">{{ $policy->id }}</span></td>
                                    <td>
                                        <span class="badge badge-profile">{{ $policy->name }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-clock mr-1"></i>{{ $policy->resets_every }} Minutes
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-action-group">
                                            <a href="{{ route('client.fairuse.edit', $policy->id) }}"
                                                class="btn btn-action btn-action-edit">
                                                <i class="fas fa-edit"></i>Edit
                                            </a>
                                            <button class="btn btn-action btn-action-delete" 
                                                wire:click="delete({{ $policy->id }})"
                                                wire:confirm="Are you sure you want to delete this policy?">
                                                <i class="fas fa-trash"></i>Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="table-empty-state">
                                        <i class="fas fa-shield-alt"></i>
                                        <p>No fair use policies defined</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
