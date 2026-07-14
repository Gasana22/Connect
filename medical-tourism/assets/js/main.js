document.addEventListener('DOMContentLoaded', function () {
  // Bootstrap client-side validation
  var forms = document.querySelectorAll('.needs-validation');
  forms.forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });

  // Auto-dismiss alerts after a few seconds
  document.querySelectorAll('.alert').forEach(function (alert) {
    setTimeout(function () {
      var closeBtn = alert.querySelector('.btn-close');
      if (closeBtn) closeBtn.click();
    }, 6000);
  });

  // Image preview for admin upload fields
  document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
    input.addEventListener('change', function () {
      var target = document.querySelector(input.dataset.preview);
      if (target && input.files && input.files[0]) {
        target.src = URL.createObjectURL(input.files[0]);
        target.classList.remove('d-none');
      }
    });
  });
});
