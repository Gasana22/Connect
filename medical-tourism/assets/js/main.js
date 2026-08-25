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

  // Hero background slider: simple crossfade rotation between uploaded
  // slide images. No-op if zero or one slide is present.
  var heroSlides = document.querySelectorAll('.hero-slide');
  if (heroSlides.length > 1) {
    var currentSlide = 0;
    setInterval(function () {
      heroSlides[currentSlide].classList.remove('active');
      currentSlide = (currentSlide + 1) % heroSlides.length;
      heroSlides[currentSlide].classList.add('active');
    }, 5000);
  }

  // Rich text editor: turns any textarea[data-rich-editor] into a
  // contenteditable box with a formatting toolbar (bold/italic/underline,
  // headings, lists, link). No external library — uses the browser's
  // built-in execCommand, which still works for this basic formatting set
  // in every major browser. The textarea stays in the DOM (hidden) and is
  // kept in sync so the form still submits its HTML value normally.
  document.querySelectorAll('textarea[data-rich-editor]').forEach(function (textarea) {
    var toolbarButtons = [
      { cmd: 'bold', icon: 'bi-type-bold', title: 'Bold' },
      { cmd: 'italic', icon: 'bi-type-italic', title: 'Italic' },
      { cmd: 'underline', icon: 'bi-type-underline', title: 'Underline' },
      { cmd: 'formatBlock', value: 'h4', icon: 'bi-type-h1', title: 'Heading' },
      { cmd: 'formatBlock', value: 'p', icon: 'bi-paragraph', title: 'Paragraph' },
      { cmd: 'insertUnorderedList', icon: 'bi-list-ul', title: 'Bullet List' },
      { cmd: 'insertOrderedList', icon: 'bi-list-ol', title: 'Numbered List' },
      { cmd: 'removeFormat', icon: 'bi-eraser', title: 'Clear Formatting' },
    ];

    var wrapper = document.createElement('div');
    wrapper.className = 'rich-editor';

    var toolbar = document.createElement('div');
    toolbar.className = 'rich-editor-toolbar';
    toolbarButtons.forEach(function (btn) {
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn btn-sm btn-outline-secondary';
      button.title = btn.title;
      button.innerHTML = '<i class="bi ' + btn.icon + '"></i>';
      button.addEventListener('click', function () {
        document.execCommand(btn.cmd, false, btn.value || null);
        editable.focus();
      });
      toolbar.appendChild(button);
    });

    var editable = document.createElement('div');
    editable.className = 'rich-editor-body form-control';
    editable.contentEditable = 'true';
    editable.innerHTML = textarea.value;
    editable.addEventListener('input', function () {
      textarea.value = editable.innerHTML;
    });

    textarea.classList.add('d-none');
    textarea.parentNode.insertBefore(wrapper, textarea);
    wrapper.appendChild(toolbar);
    wrapper.appendChild(editable);

    var form = textarea.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        textarea.value = editable.innerHTML;
      });
    }
  });
});
