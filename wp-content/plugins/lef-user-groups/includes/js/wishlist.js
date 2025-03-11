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
                const item = document.createElement("div");
                item.textContent = product.name;
                item.classList.add("wishlist-item"); // Add a class for styling
                item.dataset.productId = product.id;

                //calls another function which should add this item to the users wishlist
                item.addEventListener("click", function() {
                    addProductToWishlist(product.id, product.name);
                });

                //creates a div and puts it below the result box to show the items
                resultsContainer.appendChild(item);
            });
        } catch (error) {
            console.error("Error fetching autocomplete data:", error);
        }
    });

    function addProductToWishlist(productId, productName) {
        alert(`The following has been added to your wishlist: ${productName}`);

        // Ensure wishlist ID is available
        const wishlistId = lefWishlistData.wishlist_id;;
        if (!wishlistId) {
            console.error("Wishlist ID not found");
            return;
        }
    
        // Prepare the AJAX request
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
                // console.log("Product successfully added to wishlist!", data);
            } else {
                console.error("Failed to add product:", data);
            }
        })
        .catch(error => console.error("Error in AJAX request:", error));
    }
});