        </div> <!-- End container/container-fluid -->

    <?php if (isLoggedIn()): ?>
    </div> <!-- End page-content-wrapper -->
</div> <!-- End wrapper -->
    <?php endif; ?>

<footer class="text-center py-4 mt-auto text-muted small">
    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Feito com <i class="fas fa-heart text-danger"></i> para professores.
</footer>

<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>

</body>
</html>
