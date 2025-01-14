<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="{{ asset('storage/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('storage/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('storage/libs/node-waves/waves.min.js') }}"></script>
<script src="{{ asset('storage/libs/feather-icons/feather.min.js') }}"></script>
<script src="{{ asset('storage/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('storage/FreezeUi/freeze-ui.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.7.2/min/dropzone.min.js"></script>

<!-- App js -->
<script src="{{ asset('storage/js/app.js') }}"></script>

<script>
    function createFolderModal() {
        $('#create_folder').modal('show');
    }

    function createShareableFolderModal() {
        $('#create_folder_shareable').modal('show');
    }


    function createSubFolder(parentId) {
        $('#parent_id').val(parentId);
        $('#title').val('');
        $('#create_folder').modal('show');
    }

    function update_folder(id, old) {
        $('#old,#new').val(old);
        $('#id').val(id);
        $('#update_folder').modal('show');
    }

    function update_subfolder(id, old) {
        $('#old2, #new2').val(old);
        $('#id2').val(id);
        $('#update_subfolder').modal('show');
    }


    function update_shareablefolder(id, old) {
        $('#old3, #new3').val(old);
        $('#id3').val(id);
        $('#update_shareablefolder').modal('show');
    }

    function create_files(id, title) {
        $('#caption').html(title);
        $('#folder_id').val(id);
        $('#folder').val(title);
        $('#create_files').modal('show');
        dropzone.removeAllFiles();
    }

    function create_subfiles(id, title) {
        console.log(title);
        $('#caption_subfolder').html(title);
        $('#parent_id').val(id);
        $('#subfolder_name').val(title);
        $('#create_subfiles').modal('show');
        dropzone.removeAllFiles();
    }

    function share_file(users_folder_files_id) {

        $('#shared_modal').modal('show');

        $('#users_folder_files_id').val(users_folder_files_id);

        $('#users_folder_id').val(''); // Clear folder ID if it was previously set

    }

    function share_folder(users_folder_id, title) {
        $('#share_folder_modal').modal('show');
        $('#share_folder_title').val(title);
        $('#folder_users_folder_id').val(users_folder_id);
        $('#folder_users_folder_files_id').val('');
    }

    function create_account() {
        $('#create_account').modal('show');
    }

    $("#category").change(e => {
        var category = $('#category').val();
        if (category != 'Individual') {
            $('#show_email').attr('hidden', true);
        } else {
            $('#show_email').attr('hidden', false);
        }
    });

    function account_update(id, name, department, email, address, age) {
        $('#update_account').modal('show');
        $('#account_id').val(id);
        $('#name').val(name);
        $('#department').val(department);
        $('#email').val(email);
        $('#address').val(address);
        $('#age').val(age);
    }
</script>

@if (Session::has('message'))
    <script>
        Swal.fire({
            title: "{{ Session::get('title') }}",
            text: "{{ Session::get('message') }}",
            icon: "{{ Session::get('type') }}"
        });
    </script>
@endif

<script>
    $('.freeze').click(e => {
        FreezeUI({
            selector: '.component',
            text: 'Processing'
        });
    });
    UnFreezeUI();
</script>
