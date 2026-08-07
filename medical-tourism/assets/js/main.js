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

  // Scroll-reveal: fade/slide in cards, images and section intros as they
  // enter the viewport. Pure CSS transition + IntersectionObserver, no
  // animation library. If IntersectionObserver isn't supported, targets
  // simply never get the .reveal class and stay visible as normal.
  var revealTargets = document.querySelectorAll(
    '.card, .testimonial-card, .cta-banner, .stat-card, .hero-stats, .search-card, .text-center.mb-5, img.rounded-4'
  );
  if (revealTargets.length && 'IntersectionObserver' in window) {
    var groupCounts = new Map();
    revealTargets.forEach(function (el) {
      el.classList.add('reveal');
      var group = el.closest('.row') || el.parentElement;
      var count = groupCounts.get(group) || 0;
      if (count > 0 && count < 6) {
        el.style.transitionDelay = (count * 80) + 'ms';
      }
      groupCounts.set(group, count + 1);
    });

    document.body.classList.add('js-reveal-ready');

    var revealObserver = new IntersectionObserver(function (entries, observer) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    revealTargets.forEach(function (el) { revealObserver.observe(el); });
  }
});
