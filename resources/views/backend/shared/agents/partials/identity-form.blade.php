@if(empty($bare))
<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Identity Verification</h3>
            </div>
            <div class="card-body">
@endif
                @if(empty($hidePollingUnit))
                <div class="form-group">
                    <label>Polling Unit</label>
                    @if(!empty($lockedPollingUnit))
                        <input type="hidden" name="polling_unit_id" value="{{ $lockedPollingUnit->id }}">
                        <input type="text" class="form-control" value="{{ $lockedPollingUnit->name }} - {{ $lockedPollingUnit->ward?->name }} / {{ $lockedPollingUnit->ward?->localGovernmentArea?->name }}" readonly>
                    @else
                        <select name="polling_unit_id" class="form-control @error('polling_unit_id') is-invalid @enderror" required>
                            <option value="">Select Polling Unit</option>
                            @foreach($pollingUnits as $pollingUnit)
                                <option value="{{ $pollingUnit->id }}" @selected((string) old('polling_unit_id', $selectedPollingUnitId ?? '') === (string) $pollingUnit->id)>
                                    {{ $pollingUnit->name }} - {{ $pollingUnit->ward?->name }} / {{ $pollingUnit->ward?->localGovernmentArea?->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                    @error('polling_unit_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                @endif
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Means of Identification Type</label>
                            <select name="identity_type" class="form-control @error('identity_type') is-invalid @enderror" required>
                                <option value="">Select ID Type</option>
                                @foreach(['National ID', 'Voter Card', 'Driver License', 'International Passport', 'Other'] as $type)
                                    <option value="{{ $type }}" @selected(old('identity_type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                            @error('identity_type')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Means of Identification Number</label>
                            <input type="text" name="identity_number" value="{{ old('identity_number') }}" class="form-control @error('identity_number') is-invalid @enderror" required>
                            @error('identity_number')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Upload Identity Document</label>
                            <input type="file" name="identity_document" class="form-control @error('identity_document') is-invalid @enderror" required>
                            @error('identity_document')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Voter Evidence / Voter's Card</label>
                            <input type="file" name="voter_evidence_document" class="form-control @error('voter_evidence_document') is-invalid @enderror" required>
                            @error('voter_evidence_document')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Passport Photo</label>
                            <input type="file" name="passport_photo" class="form-control @error('passport_photo') is-invalid @enderror">
                            @error('passport_photo')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Current Address</label>
                            <input type="text" name="current_address" value="{{ old('current_address', $profileData->address ?? '') }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Willingness Statement</label>
                            <textarea name="willingness_statement" rows="3" class="form-control">{{ old('willingness_statement') }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check">
                            <input type="checkbox" name="availability_confirmed" value="1" class="form-check-input" id="availability-confirmed" required>
                            <label for="availability-confirmed" class="form-check-label">I confirm that I am available to serve as a Polling Unit Agent for this polling unit.</label>
                        </div>
                    </div>
                </div>
@if(empty($bare))
            </div>
        </div>
    </div>
</div>
@endif
