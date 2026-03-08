@extends('layouts.admin')

@section('title', 'Category Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Category Management</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
            <i class="bi bi-plus-circle me-2"></i>
            Add New Category
        </button>
    </div>

    <!-- Categories Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Icon</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Quizzes</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-categories">
                        @forelse($categories as $category)
                            <tr data-id="{{ $category->id }}" data-order="{{ $category->order }}">
                                <td class="drag-handle" style="cursor: move;">
                                    <i class="bi bi-grip-vertical"></i> {{ $category->order }}
                                </td>
                                <td>
                                    @if($category->icon)
                                        <i class="{{ $category->icon }} fs-4"></i>
                                    @else
                                        <i class="bi bi-folder fs-4 text-muted"></i>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $category->name }}</strong>
                                    @if($category->parent_id)
                                        <br>
                                        <small class="text-muted">Parent: {{ $categories->firstWhere('id', $category->parent_id)?->name }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $category->slug }}</code></td>
                                <td>{{ \Illuminate\Support\Str::limit($category->description, 50) }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $category->quizzes_count ?? 0 }}</span>
                                </td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary edit-category"
                                                data-id="{{ $category->id }}"
                                                data-name="{{ $category->name }}"
                                                data-slug="{{ $category->slug }}"
                                                data-description="{{ $category->description }}"
                                                data-icon="{{ $category->icon }}"
                                                data-parent="{{ $category->parent_id }}"
                                                data-order="{{ $category->order }}"
                                                data-is_active="{{ $category->is_active }}"
                                                title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        <form action="{{ route('admin.categories.destroy', $category->id) }}" 
                                              method="POST" 
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this category? This will affect all quizzes in this category.')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-folder fs-1 text-muted d-block mb-2"></i>
                                    <p class="text-muted">No categories found</p>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                                        <i class="bi bi-plus-circle me-2"></i>
                                        Add Your First Category
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $categories->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug">
                        <small class="text-muted">Leave empty to auto-generate from name</small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="icon" class="form-label">Icon Class</label>
                        <input type="text" class="form-control" id="icon" name="icon" placeholder="bi bi-folder">
                        <small class="text-muted">Bootstrap Icons class name (e.g., bi bi-book, bi bi-code)</small>
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Category</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">None (Root Category)</option>
                            @foreach($categories as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="order" name="order" value="0" min="0">
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST" id="editCategoryForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="edit_slug" name="slug">
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit_icon" class="form-label">Icon Class</label>
                        <input type="text" class="form-control" id="edit_icon" name="icon">
                    </div>

                    <div class="mb-3">
                        <label for="edit_parent_id" class="form-label">Parent Category</label>
                        <select class="form-select" id="edit_parent_id" name="parent_id">
                            <option value="">None (Root Category)</option>
                            @foreach($categories as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="edit_order" name="order" min="0">
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active" value="1">
                            <label class="form-check-label" for="edit_is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .drag-handle {
        cursor: move;
    }
    .drag-handle i {
        color: #6c757d;
    }
    .table tbody tr.dragging {
        opacity: 0.5;
        background: #f8f9fa;
    }
    .table tbody tr.drag-over {
        border: 2px dashed #667eea;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize sortable for category reordering
        const categoriesTable = document.getElementById('sortable-categories');
        if (categoriesTable) {
            new Sortable(categoriesTable, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'dragging',
                dragClass: 'drag-over',
                onEnd: function(evt) {
                    const order = [];
                    const rows = categoriesTable.querySelectorAll('tr');
                    rows.forEach((row, index) => {
                        const categoryId = row.dataset.id;
                        if (categoryId) {
                            order.push({
                                id: categoryId,
                                order: index + 1
                            });
                        }
                    });
                    
                    fetch('{{ route("admin.categories.reorder") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ order: order })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update order numbers in display
                            rows.forEach((row, index) => {
                                const orderCell = row.querySelector('td:first-child');
                                if (orderCell) {
                                    orderCell.innerHTML = `<i class="bi bi-grip-vertical"></i> ${index + 1}`;
                                }
                            });
                            toastr.success('Category order updated successfully');
                        }
                    })
                    .catch(error => {
                        console.error('Error updating order:', error);
                        toastr.error('Failed to update category order');
                    });
                }
            });
        }

        // Handle edit button clicks
        document.querySelectorAll('.edit-category').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const slug = this.dataset.slug;
                const description = this.dataset.description;
                const icon = this.dataset.icon;
                const parent = this.dataset.parent;
                const order = this.dataset.order;
                const isActive = this.dataset.is_active === '1';
                
                const form = document.getElementById('editCategoryForm');
                form.action = `{{ url('admin/categories') }}/${id}`;
                
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_slug').value = slug;
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_icon').value = icon;
                document.getElementById('edit_parent_id').value = parent || '';
                document.getElementById('edit_order').value = order;
                document.getElementById('edit_is_active').checked = isActive;
                
                new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
            });
        });

        // Auto-generate slug from name
        document.getElementById('name').addEventListener('keyup', function() {
            const slug = this.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            document.getElementById('slug').value = slug;
        });

        document.getElementById('edit_name').addEventListener('keyup', function() {
            const slug = this.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            document.getElementById('edit_slug').value = slug;
        });
    });
</script>

<!-- Toastr Notifications -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    toastr.options = {
        positionClass: 'toast-top-right',
        progressBar: true,
        timeOut: 3000
    };
</script>
@endpush