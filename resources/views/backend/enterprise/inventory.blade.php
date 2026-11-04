@extends('backend.template.backend-master')
@section('content')
<section class="content cm-ui"><div class="container-fluid">
  <h1 class="h3 text-primary">Campaign Merchandise &amp; Inventory</h1>
  <p>Record campaign materials and distribute stock between offices identified by their polling unit. All stock locations must be inside your campaign jurisdiction.</p>
  @if(session('inventory_status'))<div class="alert alert-success" role="status">{{ session('inventory_status') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
  @if($lowStock)<div class="alert alert-warning" role="status">{{ $lowStock }} stock location(s) are at or below their low-stock threshold.</div>@endif
  <div class="card card-outline card-primary"><div class="card-body">
    <h2 class="h5 text-primary">Stock balances</h2>
    @if($can('merchandise.reports.export'))<a class="btn btn-outline-primary mb-3" href="{{ route('enterprise.inventory.export') }}">Export stock CSV</a>@endif
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Material</th><th>SKU</th><th>Office / polling unit ID</th><th>Quantity</th><th>Unit</th><th>Status</th></tr></thead><tbody>
    @forelse($stocks as $stock)<tr><td>{{ $stock->name }}</td><td>{{ $stock->sku }}</td><td>{{ $stock->polling_unit_id }}</td><td>{{ number_format($stock->quantity) }}</td><td>{{ $stock->unit }}</td><td>{{ $stock->quantity <= $stock->low_stock_level ? 'Low stock' : 'Available' }}</td></tr>
    @empty<tr><td colspan="6">No stock has been recorded in your jurisdiction. Add a material and record its first receipt.</td></tr>@endforelse
    </tbody></table></div>{{ $stocks->links() }}
  </div></div>
  <div class="row">
  @if($can('merchandise.categories.manage'))<div class="col-12 col-lg-4"><div class="card"><div class="card-body">
    <h2 class="h5 text-primary">Add category</h2><form method="POST" action="{{ route('enterprise.inventory.categories') }}">@csrf
      <label for="category-name">Category name</label><input class="form-control mb-2" id="category-name" name="name" required maxlength="120"><button class="btn btn-primary">Create category</button>
    </form>
  </div></div></div>@endif
  @if($can('merchandise.products.manage'))<div class="col-12 col-lg-4"><div class="card"><div class="card-body">
    <h2 class="h5 text-primary">Add campaign material</h2><form method="POST" action="{{ route('enterprise.inventory.materials') }}">@csrf
      <label for="material-name">Name</label><input class="form-control mb-2" id="material-name" name="name" maxlength="160" required>
      <label for="material-sku">SKU</label><input class="form-control mb-2" id="material-sku" name="sku" maxlength="60" required>
      <label for="material-category">Category</label><select class="form-control mb-2" id="material-category" name="category_id" required><option value="">Choose category</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
      <label for="material-unit">Unit, for example pieces or cartons</label><input class="form-control mb-2" id="material-unit" name="unit" maxlength="30" required>
      <label for="material-low">Low-stock threshold</label><input class="form-control mb-2" type="number" id="material-low" name="low_stock_level" value="0" min="0" max="1000000000" required><button class="btn btn-primary">Create material</button>
    </form>
  </div></div></div>@endif
  @if($can('merchandise.inventory.manage'))<div class="col-12 col-lg-4"><div class="card"><div class="card-body">
    <h2 class="h5 text-primary">Record stock receipt or adjustment</h2><form method="POST" action="{{ route('enterprise.inventory.adjustments') }}">@csrf<input type="hidden" name="reference" value="{{ $reference }}">
      <label for="adjust-material">Material</label><select class="form-control mb-2" id="adjust-material" name="material_id" required><option value="">Choose material</option>@foreach($materials as $material)<option value="{{ $material->id }}">{{ $material->sku }} — {{ $material->name }}</option>@endforeach</select>
      <label for="adjust-office">Office / polling unit ID</label><input class="form-control mb-1" type="search" aria-label="Search campaign offices" placeholder="Search name or polling unit ID" data-office-search="adjust-office"><select class="form-control mb-2" id="adjust-office" name="polling_unit_id" data-office-select required><option value="">Choose campaign office</option></select>
      <label for="adjust-quantity">Quantity change (negative for stock loss)</label><input class="form-control mb-2" type="number" id="adjust-quantity" name="quantity" min="-1000000000" max="1000000000" required>
      <label for="adjust-reason">Receipt reference or adjustment reason</label><textarea class="form-control mb-2" id="adjust-reason" name="reason" maxlength="500" required></textarea><button class="btn btn-primary">Record movement</button>
    </form>
  </div></div></div>@endif
  </div>
  @if($requests !== null)<div class="card"><div class="card-body"><h2 class="h5 text-primary">Distribution requests</h2>
    <form class="row mb-4" method="POST" action="{{ route('enterprise.inventory.requests') }}">@csrf
      <div class="col-12 col-lg-3"><label for="request-material">Material</label><select class="form-control mb-2" id="request-material" name="material_id" required><option value="">Choose material</option>@foreach($materials as $material)<option value="{{ $material->id }}">{{ $material->sku }} — {{ $material->name }}</option>@endforeach</select></div>
      <div class="col-6 col-lg-2"><label for="request-source">Source office / PU ID</label><input class="form-control mb-1" type="search" aria-label="Search campaign offices" placeholder="Search name or polling unit ID" data-office-search="request-source"><select class="form-control mb-2" id="request-source" name="source_polling_unit_id" data-office-select required><option value="">Choose campaign office</option></select></div>
      <div class="col-6 col-lg-2"><label for="request-destination">Destination office / PU ID</label><input class="form-control mb-1" type="search" aria-label="Search campaign offices" placeholder="Search name or polling unit ID" data-office-search="request-destination"><select class="form-control mb-2" id="request-destination" name="destination_polling_unit_id" data-office-select required><option value="">Choose campaign office</option></select></div>
      <div class="col-12 col-lg-2"><label for="request-quantity">Quantity</label><input class="form-control mb-2" type="number" id="request-quantity" name="quantity" min="1" max="1000000000" required></div>
      <div class="col-12"><label for="request-purpose">Purpose</label><textarea class="form-control mb-2" id="request-purpose" name="purpose" maxlength="500" required></textarea><button class="btn btn-primary">Request distribution</button></div>
    </form>
    <div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Material</th><th>Source</th><th>Destination</th><th>Quantity</th><th>Status</th><th>Action</th></tr></thead><tbody>
    @forelse($requests as $record)<tr><td>{{ $record->id }}</td><td>{{ $record->name }}</td><td>{{ $record->source_polling_unit_id }}</td><td>{{ $record->destination_polling_unit_id }}</td><td>{{ $record->quantity }}</td><td>{{ $record->status }}</td><td>@if($record->status === 'pending' && $can('merchandise.orders.fulfil'))<form method="POST" action="{{ route('enterprise.inventory.fulfil', $record->id) }}">@csrf<button class="btn btn-primary btn-sm">Fulfil distribution</button></form>@endif
      @if($record->status === 'pending' && ($can('merchandise.orders.fulfil') || (int)$record->requested_by === (int)$profileData->id))<form method="POST" action="{{ route('enterprise.inventory.cancel', $record->id) }}" class="mt-2">@csrf<label class="sr-only" for="cancel-reason-{{ $record->id }}">Cancellation reason</label><input class="form-control form-control-sm mb-1" id="cancel-reason-{{ $record->id }}" name="reason" required maxlength="500" placeholder="Cancellation reason"><button class="btn btn-outline-secondary btn-sm">Cancel request</button></form>@endif
    </td></tr>@empty<tr><td colspan="7">No distribution requests in your jurisdiction.</td></tr>@endforelse
    </tbody></table></div>{{ $requests->links() }}
  </div></div>@endif
  @if($requestEvents !== null)<div class="card"><div class="card-body"><h2 class="h5 text-primary">Request audit</h2>
    <div class="table-responsive"><table class="table"><thead><tr><th>Time</th><th>Request ID</th><th>Event</th><th>Actor ID</th><th>Reason</th></tr></thead><tbody>
    @forelse($requestEvents as $event)<tr><td>{{ $event->created_at }}</td><td>{{ $event->request_id }}</td><td>{{ $event->event }}</td><td>{{ $event->actor_id }}</td><td>{{ $event->reason }}</td></tr>@empty<tr><td colspan="5">No request events recorded.</td></tr>@endforelse
    </tbody></table></div>{{ $requestEvents->links() }}
  </div></div>@endif
  <div class="card"><div class="card-body"><h2 class="h5 text-primary">Stock movement audit</h2>
    <div class="table-responsive"><table class="table"><thead><tr><th>Time</th><th>Material</th><th>Office / PU ID</th><th>Change</th><th>Balance after</th><th>Actor ID</th><th>Reason</th></tr></thead><tbody>
    @forelse($movements as $movement)<tr><td>{{ $movement->created_at }}</td><td>{{ $movement->name }}</td><td>{{ $movement->polling_unit_id }}</td><td>{{ $movement->change }}</td><td>{{ $movement->balance_after }}</td><td>{{ $movement->actor_id }}</td><td>{{ $movement->reason }}</td></tr>@empty<tr><td colspan="7">No stock movements recorded.</td></tr>@endforelse
    </tbody></table></div>{{ $movements->links() }}
  </div></div>
</div></section>
<script>
(() => {
  const endpoint = @json(route('enterprise.inventory.locations'));
  document.querySelectorAll('[data-office-search]').forEach(input => {
    const select = document.getElementById(input.dataset.officeSearch);
    let timer, pending;
    const load = async () => {
      if (pending) pending.abort();
      pending = new AbortController();
      try {
        const response = await fetch(endpoint + '?q=' + encodeURIComponent(input.value), {signal: pending.signal, headers: {'Accept':'application/json'}});
        if (!response.ok) throw new Error('Office lookup unavailable');
        const data = await response.json();
        select.replaceChildren(new Option('Choose campaign office', ''));
        data.locations.forEach(location => select.add(new Option(location.name + ' (ID ' + location.id + ')', location.id)));
        select.setCustomValidity('');
      } catch (error) {
        if (error.name !== 'AbortError') {
          select.replaceChildren(new Option('Office lookup unavailable — search again to retry', ''));
          select.setCustomValidity('Office lookup unavailable. Search again to retry.');
        }
      }
    };
    input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 250); });
    load();
  });
})();
</script>
@endsection
