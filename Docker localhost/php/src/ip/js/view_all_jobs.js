function applyFilter() {
    let sort = document.getElementById('sort').value;
    let status = document.getElementById('status').value;
    window.location.href = `?category_id=<?php echo $category_id; ?>&sort=${sort}&status=${status}`;
}