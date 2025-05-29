function showForm(formId) {
    document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"));
    document.getElementById(formId).classList.add("active");
}

const modal = document.getElementById("productModal");
const btn = document.getElementById("openModalBtn");
const span = document.getElementById("closeModal");

btn.onclick = () => { modal.style.display = "block"; }
span.onclick = () => { modal.style.display = "none"; }

const editModal = document.getElementById("editModal");
const closeEditModal = document.getElementById("closeEditModal");
const cancelEdit = document.getElementById("cancelEdit");

closeEditModal.onclick = () => { editModal.style.display = "none"; }
cancelEdit.onclick = () => { window.location.href = "vendor.php"; }

const categoryModal = document.getElementById("categoryModal");
const openCategoryModalBtn = document.getElementById("openCategoryModalBtn");
const closeCategoryModal = document.getElementById("closeCategoryModal");

openCategoryModalBtn.onclick = () => { categoryModal.style.display = "block"; }
closeCategoryModal.onclick = () => { categoryModal.style.display = "none"; }

window.onclick = (e) => {
  if (e.target == modal) modal.style.display = "none";
  if (e.target == editModal) editModal.style.display = "none";
  if (e.target == categoryModal) categoryModal.style.display = "none";
};

// Show modal based on PHP variable
if (typeof showModal !== "undefined" && showModal) {
  modal.style.display = "block";
}

if (typeof showEditModal !== "undefined" && showEditModal) {
  document.getElementById("edit_id").value = editData.id;
  document.getElementById("edit_name").value = editData.itemName;
  document.getElementById("edit_price").value = editData.sellingPrice;
  document.getElementById("edit_stock").value = editData.availableStock;
  document.getElementById("edit_image").value = editData.image;
  document.getElementById("edit_category_id").value = editData.category_id;
  editModal.style.display = "block";
}

