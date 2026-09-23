    </div><!-- /.admin-content -->
</div><!-- /.admin-main -->

<script>
const BASE_URL   = '<?= BASE_URL ?>';
const CSRF_TOKEN = '<?= e(csrfToken()) ?>';
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
