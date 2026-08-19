<?php $layout = $layout ?? 'app'; ?>
<?php if ($layout === 'app'): ?>
</div> <!-- .main-content -->
<?php elseif ($layout === 'admin'): ?>
</div> <!-- .admin-shell -->
<?php else: ?>
</div> <!-- .auth-shell -->
<?php endif; ?>
<script src="assets/js/app.js"></script>
</body>
</html>
