import Swal from 'sweetalert2';

// Wait for the DOM to fully load
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for all elements with class .fe_fs_delete__btn
    const icons = document.querySelectorAll('.fe_fs_delete__btn > a');

    icons.forEach(icon => {
        icon.addEventListener('click', function(event) {
            event.preventDefault();

            const targetUrl = this.href;

            Swal.fire({
                text: useradmin.text,
                icon: 'success',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = targetUrl;
                }
            });
        });
    });
});
