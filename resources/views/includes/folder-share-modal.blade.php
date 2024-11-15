<form action="{{ route('folder.shareable.share') }}" method="POST">
    @csrf
    @honeypot
    <input type="hidden" name="users_folder_files_id" id="folder_users_folder_files_id">
    <input type="hidden" name="users_folder_id" id="folder_users_folder_id">
    <input type="hidden" name="title" id="share_folder_title"> <!-- Hidden input for the title -->

    <div class="modal component fade" id="share_folder_modal" tabindex="-1" aria-labelledby="shareFolderModalLabel"
        aria-hidden="true">
        <div class="modal-dialog content">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareFolderModalLabel">Share Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="row gy-4">
                        <div class="col-12">
                            <div>
                                <label for="folder_category" class="form-label">Category</label>
                                <select name="category" class="form-control" id="folder_category" required>
                                    <option value="Individual">Individual</option>
                                    @if (auth()->user()->roles == 'ADMIN')
                                        <optgroup label="Department">
                                            <option value="CCS">CCS</option>
                                            <option value="CTE">CTE</option>
                                            <option value="CBE">CBE</option>
                                        </optgroup>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="col-12" id="folder_show_email">
                            <div>
                                <label for="folder_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" id="folder_email">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn freeze btn-primary">Share</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('folder_category');
        const emailInput = document.getElementById('folder_show_email');
        const emailField = document.getElementById('folder_email');

        // Function to toggle email input visibility and required attribute
        function toggleEmailInput() {
            if (categorySelect.value === 'Individual') {
                emailInput.style.display = 'block';
                emailField.setAttribute('required', 'required'); // Make required
            } else {
                emailInput.style.display = 'none';
                emailField.removeAttribute('required'); // Remove required
                emailField.value = ''; // Clear the value if hidden
            }
        }

        // Initial check
        toggleEmailInput();

        // Add event listener for category change
        categorySelect.addEventListener('change', toggleEmailInput);
    });

    document.querySelector('form').addEventListener('submit', function(event) {
        console.log('Form submitted:', {
            category: categorySelect.value,
            email: emailField.value // This will be empty if it's hidden
        });
    });
</script>
