<div class="row">

    <div class="col-md-4 order-md-2">
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between py-3">
                <h6 class="m-0 text-primary">Convertions</h6>
            </div>
            <div class="card-body">
                <span class="text text-xs">
                    <b>1 MB</b> = 1,048,576 Bytes <br>
                    <b>5 MB</b> = 5,242,880 Bytes <br>
                    <b>10 MB</b> = 1,048,5760 Bytes <br>
                    <b>1 Hour</b> = 3,600 Seconds
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-8 order-md-1">
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between py-3">
                <h6 class="m-0 text-primary">Create Hotspot Profile</h6>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="create">
                    <div class="form-group">
                        <label class="text text-xs mb-0">Name:</label>
                        <input type="text" wire:model="profileName"
                            class="form-control"placeholder="Enter Profile Name" autocomplete="off">
                        @error('profileName')
                            <span class="text text-xs text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="text text-xs mb-0">Description:</label>
                        <input type="text" wire:model="profileDescription"
                            class="form-control"placeholder="Enter Description" autocomplete="off">
                        @error('profileDescription')
                            <span class="text text-xs text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label class="text text-xs mb-0">Price:</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" wire:model="profilePrice" value="0"
                                        placeholder>
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <li class="fas fa-money-bill-alt"></li>
                                        </span>
                                    </div>
                                    @error('profilePrice')
                                        <span class="text text-xs text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label class="text text-xs mb-0">Uptime Limit:</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" wire:model="profileUptimeLimit"
                                        value="0" placeholder>
                                    <div class="input-group-append">
                                        <span class="input-group-text">Seconds</span>
                                    </div>
                                    @error('profileUptimeLimit')
                                        <span class="text text-xs text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label class="text text-xs mb-0">Validity:</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" wire:model="profileValidity"
                                        value="0" placeholder>
                                    <div class="input-group-append">
                                        <span class="input-group-text">Seconds</span>
                                    </div>
                                    @error('profileValidity')
                                        <span class="text text-xs text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label class="text text-xs mb-0">Max Download Speed:</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" wire:model="profileMaxDownload"
                                        placeholder="None">
                                    <div class="input-group-append">
                                        <span class="input-group-text">Bytes</span>
                                    </div>
                                    @error('profileMaxDownload')
                                        <span class="text text-xs text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label class="text text-xs mb-0">Max Upload Speed:</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" wire:model="profileMaxUpload"
                                        placeholder="None">
                                    <div class="input-group-append">
                                        <span class="input-group-text">Bytes</span>
                                    </div>
                                    @error('profileMaxUpload')
                                        <span class="text text-xs text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label class="text text-xs mb-0">Data Limit:</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" wire:model="profileDataLimit"
                                        placeholder="None">
                                    <div class="input-group-append">
                                        <span class="input-group-text">Bytes</span>
                                    </div>
                                    @error('profileDataLimit')
                                        <span class="text text-xs text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    @error('otherError')
                        <span class="text text-xs text-danger">{{ $message }}</span>
                    @enderror
                    <hr>
                    <div class="d-flex flex-column flex-md-row justify-content-start">
                        <a class="btn btn-secondary mr-md-2 mb-2 mb-md-0" href="{{ route('client.vouchers.profiles') }}">Cancel</a>
                        <button class="btn btn-primary" type="submit">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
