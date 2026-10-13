document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("form[data-confirm]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (!window.confirm(form.getAttribute("data-confirm"))) {
                event.preventDefault();
            }
        });
    });

    const MAX_BYTES = 8 * 1024 * 1024;
    document.querySelectorAll('input[type="file"]').forEach((input) => {
        input.addEventListener("change", () => {
            const file = input.files && input.files[0];
            if (!file) {
                return;
            }
            if (file.size > MAX_BYTES) {
                window.alert("This file is larger than 8 MB. Please choose a smaller image.");
                input.value = "";
                return;
            }
            const targetId = input.getAttribute("data-preview");
            const target = targetId ? document.getElementById(targetId) : null;
            if (!target) {
                return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                target.src = reader.result;
                target.style.display = "block";
            };
            reader.readAsDataURL(file);
        });
    });
});
