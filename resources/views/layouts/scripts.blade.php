<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="{{asset('storage/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('storage/libs/simplebar/simplebar.min.js')}}"></script>
<script src="{{asset('storage/libs/node-waves/waves.min.js')}}"></script>
<script src="{{asset('storage/libs/feather-icons/feather.min.js')}}"></script>
<script src="{{asset('storage/js/pages/plugins/lord-icon-2.1.0.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{asset('storage/FreezeUi/freeze-ui.min.js')}}"></script>

<!-- App js -->
<script src="{{asset('storage/js/app.js')}}"></script>

<script>
function createFolderModal() {
    $('#create_folder').modal('show');
}

function update_folder(id,old) {
    $('#old,#new').val(old)
    $('#id').val(id)
    $('#update_folder').modal('show')
}

function create_files(id,title) {
    $('#caption').html(title)
    $('#folder_id').val(id)
    $('#folder').val(title)
    $('#create_files').modal('show')
    dropzone.removeAllFiles();
}

function share_file(users_folder_files_id) {
    $('#shared_modal').modal('show');
    $('#users_folder_files_id').val(users_folder_files_id)
}

function create_account() {
    $('#create_account').modal('show')
}

$("#category").change(e => {
    var category = $('#category').val();
    if(category != 'Individual') {
        $('#show_email').attr('hidden',true)
    }  else {
        $('#show_email').attr('hidden',false)
    }
})

function account_update(id,name,department,email,address,age) {
    $('#update_account').modal('show')
    $('#account_id').val(id)
    $('#name').val(name)
    $('#department').val(department)
    $('#email').val(email)
    $('#address').val(address)
    $('#age').val(age)
}
</script>
@if(Session::has('message'))
<script>
    Swal.fire({
        title: "{{Session::get('title')}}",
        text : "{{Session::get('message')}}",
        icon : "{{Session::get('type')}}"
    });
</script>
@endif
<script>
$('.freeze').click( e => {
    FreezeUI({ selector: '.component', text: 'Processing' }) 
})
UnFreezeUI();
</script>