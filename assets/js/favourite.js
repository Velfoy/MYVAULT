const modal = document.getElementById("image-modal");
const modalImage = document.getElementById("modal-image");
const images = document.querySelectorAll(".item-image");

images.forEach((image) => {
    image.addEventListener("click", () => {
        modalImage.src = image.src; 
        modal.classList.add("active"); 
    });
});

modal.addEventListener("click", () => {
    modal.classList.remove("active");
});
