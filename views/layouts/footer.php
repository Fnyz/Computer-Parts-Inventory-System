<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.3.min.js"></script>
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/select/1.5.0/js/dataTables.select.min.js"></script>
<script>
function getEditUser(id) {
 var fill = $('#editUserFormHolder');
 $.ajax({
  url: '<?= BASE_URL ?>/app/Controllers/TransactionController.php',
  type: 'post',
  data: { ids: id, action: 'viewUser' },
  success: function(data) { fill.html(data); }
 });
}

</script>
</body>
</html>
