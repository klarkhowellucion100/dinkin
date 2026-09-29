<form id="{{ $id }}" action="{{ $action }}" method="POST" style="{{ $style }}">
    @csrf
    @method('DELETE')
    <!-- Trigger Modal -->
    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#{{ $target }}">
        <i class="fas fa-trash fa-solid" style="color: white"></i>
    </button>
</form>

<!-- Start Delete Modal -->
<div class="modal fade" id="{{ $target }}" tabindex="-1" aria-labelledby="{{ $idLabel }}"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $idLabel }}">
                    Confirm
                    Deletion</h5>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this {{ $labelBody }}?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                <button type="button" class="btn btn-danger"
                    onclick="document.getElementById('{{ $id }}').submit()">Yes</button>
            </div>
        </div>
    </div>
</div>
<!-- End Delete Modal -->
