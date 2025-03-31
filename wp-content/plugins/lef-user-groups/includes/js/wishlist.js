//Ensure the Script Runs After the HTML Loads
document.addEventListener("DOMContentLoaded", function() {

    // Ensure the elements exist before proceeding
    const searchInput = document.getElementById("wishlist-item-search");
    const resultsContainer = document.getElementById("wishlist-search-results");

    //ensure that the html containers exist
    if (!searchInput || !resultsContainer) {
        console.error("Search input or results container not found.");
        return;
    }

    //this event listener should trigger any time something gets typed into the input field
    searchInput.addEventListener("input", async function() {

        //takes the spaces out of the query by trimming it
        const query = searchInput.value.trim();

        //checks that the query is atleast a certain length and clear results if query is too short
        if (query.length < 2) {
            resultsContainer.innerHTML = ""; 
            return;
        }

        //tries to fetch the product from woocommerce so it can then show them
        try {
            //tries to fetch the products in the database using a ajax (located in ajax-handlers.php)
            const response = await fetch(`${lefWishlistData.ajax_url}?action=lef_search_products&query=${encodeURIComponent(query)}`);
            const result = await response.json();

            if (!result.success || !Array.isArray(result.data)) {
                console.error("Invalid response from server:", result);
                return;
            }

            resultsContainer.innerHTML = ""; // Clear previous results
            
            // loop trough this for each product that it could find and display them
            result.data.forEach(product => {
                const item = document.createElement("li");
                item.classList.add("lef-wishlist-item"); // Add class for styling
            
                const productImage = product.image ? product.image : lefWishlistData.placeholder_image;

                
                // Construct inner HTML to match your existing wishlist format
                item.innerHTML = `
                    <div class="lef-item-image">
                        <img src="${productImage}" alt="image">
                    </div>
                    <div class="lef-item-details">
                        <span class="lef-item-title">${product.name}</span>
                        <span class="lef-item-price">${product.price}</span>
                    </div>
                `;
            
                // Add event listener to add item to wishlist when clicked
                item.addEventListener("click", function(event) {
                    if (!event.target.classList.contains("lef-delete-button")) {
                        addProductToWishlist(product.id);
                    }
                });
            
                resultsContainer.appendChild(item);
            });
        } catch (error) {
            console.error("Error fetching autocomplete data:", error);
        }
    });

    function addProductToWishlist(productId) {
        const wishlistId = lefWishlistData.wishlist_id;
        if (!wishlistId) {
            console.error("Wishlist ID not found");
            return;
        }

        fetch(lefWishlistData.ajax_url, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "lef_add_product_to_wishlist",
                wishlist_id: wishlistId,
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                searchInput.value = "";  // Clear input field
                resultsContainer.innerHTML = "";  // Clear search results
                location.reload(); // reload the page
            } else {
                console.error("Failed to add product:", data);
            alert("could not add item.");
            }
        })
        .catch(error => 
            console.error("Error in AJAX request:", error));
    }
});