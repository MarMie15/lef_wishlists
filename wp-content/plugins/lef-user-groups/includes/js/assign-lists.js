jQuery(document).ready(function($) {
    const assignListButton = document.getElementById("lef-assign-lists-button");
    if (assignListButton) {
        assignListButton.addEventListener('click', function() {
            console.log('Assign list button clicked');
        });
    }
});